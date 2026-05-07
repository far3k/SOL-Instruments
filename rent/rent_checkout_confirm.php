<?php

declare(strict_types=1);

# Review rent cart dates, payment & delivery, then insert rental_requests rows.

require_once dirname(__DIR__) . "/includes/app.php";
require_once dirname(__DIR__) . "/includes/rental_helpers.php";
sol_require_user();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$uid = sol_current_uid();
if (sol_user_account_blocked($connect, $uid)) {
    $_SESSION["flash_error"] = "Your account cannot submit rental requests.";
    header("Location: " . sol_url("rent/rent_cart.php"));
    exit;
}

$flashErr = $_SESSION["flash_checkout"] ?? "";
unset($_SESSION["flash_checkout"]);

$hasPm = sol_db_column_exists($connect, "rental_requests", "payment_method");
$hasDm = sol_db_column_exists($connect, "rental_requests", "delivery_method");
$hasDn = sol_db_column_exists($connect, "rental_requests", "delivery_notes");
$hasRentalOptions = $hasPm && $hasDm && $hasDn;

$rentCart = $_SESSION["rent_cart"] ?? [];
$lines = [];
foreach ($rentCart as $id => $qty) {
    $id = (int)$id;
    $qty = max(1, (int)$qty);
    if ($id > 0) {
        $lines[$id] = ($lines[$id] ?? 0) + $qty;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["confirm_rental"])) {
    if (!sol_csrf_verify()) {
        $_SESSION["flash_checkout"] = "Security check failed. Please try again.";
        header("Location: " . sol_url("rent/rent_checkout_confirm.php"));
        exit;
    }

    if ($lines === []) {
        header("Location: " . sol_url("rent/rent_cart.php"));
        exit;
    }

    $startDate = trim((string)($_SESSION["rent_cart_start"] ?? ""));
    $endDate = trim((string)($_SESSION["rent_cart_end"] ?? ""));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        $_SESSION["flash_error"] = "Please set valid rental dates in your rent cart.";
        header("Location: " . sol_url("rent/rent_cart.php"));
        exit;
    }
    if ($endDate <= $startDate) {
        $_SESSION["flash_error"] = "End date must be after start date. Update your rent cart.";
        header("Location: " . sol_url("rent/rent_cart.php"));
        exit;
    }

    $paymentMethod = (string)($_SESSION["rent_cart_payment_method"] ?? "");
    $deliveryMethod = (string)($_SESSION["rent_cart_delivery_method"] ?? "");

    if (!$hasRentalOptions) {
        $_SESSION["flash_checkout"] = "Database is missing payment/delivery columns. Run schema_updates_rental_options.sql then try again.";
        header("Location: " . sol_url("rent/rent_checkout_confirm.php"));
        exit;
    }

    if (!in_array($paymentMethod, ["store", "iban"], true)) {
        $_SESSION["flash_error"] = "Please choose a payment method in your rent cart and save.";
        header("Location: " . sol_url("rent/rent_cart.php"));
        exit;
    }
    if (!in_array($deliveryMethod, ["pickup", "courier"], true)) {
        $_SESSION["flash_error"] = "Please choose a delivery method in your rent cart and save.";
        header("Location: " . sol_url("rent/rent_cart.php"));
        exit;
    }
    $intlForRent = $_SESSION["sol_intl_address"] ?? sol_intl_address_template();
    if ($deliveryMethod === "courier" && !sol_intl_address_is_complete($intlForRent)) {
        $_SESSION["flash_error"] = "For courier delivery, complete the international address in your rent cart and save.";
        header("Location: " . sol_url("rent/rent_cart.php"));
        exit;
    }

    $notesBind = $deliveryMethod === "courier" ? sol_intl_address_for_rent_notes($connect, $intlForRent) : "";

    foreach ($lines as $instrumentId => $_qty) {
        $chk = $connect->prepare("SELECT id, name FROM instruments WHERE id = ? AND is_active = 1 LIMIT 1");
        $chk->bind_param("i", $instrumentId);
        $chk->execute();
        $instRow = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$instRow) {
            $_SESSION["flash_checkout"] = "One or more instruments are no longer available. Update your cart.";
            header("Location: " . sol_url("rent/rent_cart.php"));
            exit;
        }
        if (sol_rental_has_customer_block_overlap($connect, $instrumentId, $startDate, $endDate, null)) {
            $_SESSION["flash_checkout"] = "Dates are not available for: " . (string)($instRow["name"] ?? ("#" . $instrumentId)) . " (overlaps another booking).";
            header("Location: " . sol_url("rent/rent_checkout_confirm.php"));
            exit;
        }
    }

    $purpose = "Cart checkout (confirm)";
    $inserted = [];

    $connect->begin_transaction();
    try {
        foreach ($lines as $instrumentId => $_qty) {
            $chk = $connect->prepare("SELECT id, name FROM instruments WHERE id = ? AND is_active = 1 LIMIT 1");
            $chk->bind_param("i", $instrumentId);
            $chk->execute();
            $instRow = $chk->get_result()->fetch_assoc();
            $chk->close();
            if (!$instRow) {
                throw new RuntimeException("Instrument not available.");
            }
            if (sol_rental_has_customer_block_overlap($connect, $instrumentId, $startDate, $endDate, null)) {
                throw new RuntimeException("Dates became unavailable for: " . (string)($instRow["name"] ?? ""));
            }

            $ins = $connect->prepare("
                INSERT INTO rental_requests
                (user_id, instrument_id, start_date, end_date, purpose, status, payment_method, delivery_method, delivery_notes, created_at)
                VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, NULLIF(?, ''), NOW())
            ");
            $ins->bind_param(
                "iissssss",
                $uid,
                $instrumentId,
                $startDate,
                $endDate,
                $purpose,
                $paymentMethod,
                $deliveryMethod,
                $notesBind
            );
            $ins->execute();
            $requestId = (int)$connect->insert_id;
            $ins->close();

            $log = $connect->prepare("
                INSERT INTO rental_status_logs
                (rental_request_id, old_status, new_status, change_reason, changed_by_user_id, changed_at)
                VALUES (?, NULL, 'pending', 'checkout confirm', ?, NOW())
            ");
            $log->bind_param("ii", $requestId, $uid);
            $log->execute();
            $log->close();

            $inserted[] = [
                "instrument_id" => $instrumentId,
                "instrument_name" => (string)($instRow["name"] ?? ""),
                "request_id" => $requestId,
            ];
        }
        $connect->commit();
    } catch (Throwable $e) {
        $connect->rollback();
        $_SESSION["flash_checkout"] = $e->getMessage();
        header("Location: " . sol_url("rent/rent_checkout_confirm.php"));
        exit;
    }

    unset($_SESSION["rent_cart"]);
    $_SESSION["rent_cart_payment_method"] = "store";
    $_SESSION["rent_cart_delivery_method"] = "pickup";
    $_SESSION["rent_cart_delivery_notes"] = "";
    $_SESSION["sol_intl_address"] = sol_intl_address_template();

    $_SESSION["flash_rent"] = [
        "items" => $inserted,
        "start" => $startDate,
        "end" => $endDate,
        "payment_method" => $paymentMethod,
        "delivery_method" => $deliveryMethod,
        "delivery_notes" => $notesBind,
    ];

    header("Location: " . sol_url("rent/rent_success.php"));
    exit;
}

if ($lines === []) {
    header("Location: " . sol_url("rent/rent_cart.php"));
    exit;
}

$rows = [];
$ids = implode(",", array_map("intval", array_keys($lines)));
if ($ids !== "") {
    $result = $connect->query("SELECT id, name, daily_price, image_url, description FROM instruments WHERE id IN ($ids)");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $rows[(int)$r["id"]] = $r;
        }
    }
}

$startDate = (string)$_SESSION["rent_cart_start"];
$endDate = (string)$_SESSION["rent_cart_end"];
$dateErrGet = sol_rental_validate_booking_dates($startDate, $endDate);
if ($dateErrGet !== null) {
    $_SESSION["flash_error"] = $dateErrGet;
    header("Location: " . sol_url("rent/rent_cart.php"));
    exit;
}
$selPay = (string)($_SESSION["rent_cart_payment_method"] ?? "store");
$selDel = (string)($_SESSION["rent_cart_delivery_method"] ?? "pickup");
$selNotes = trim((string)($_SESSION["rent_cart_delivery_notes"] ?? ""));

if (!in_array($selPay, ["store", "iban"], true)) {
    $selPay = "store";
}
if (!in_array($selDel, ["pickup", "courier"], true)) {
    $selDel = "pickup";
}
if ($selDel === "courier" && !sol_intl_address_is_complete($_SESSION["sol_intl_address"] ?? [])) {
    $_SESSION["flash_error"] = "For courier delivery, complete the international address in your rent cart, then save.";
    header("Location: " . sol_url("rent/rent_cart.php"));
    exit;
}

$overlapNames = [];
foreach ($lines as $iid => $_q) {
    if (sol_rental_has_customer_block_overlap($connect, $iid, $startDate, $endDate, null)) {
        $r = $rows[$iid] ?? null;
        $overlapNames[] = $r ? (string)($r["name"] ?? ("#" . $iid)) : ("#" . $iid);
    }
}

$days = max(1, (int) round((strtotime($endDate) - strtotime($startDate)) / 86400));
$estTotal = 0.0;
foreach ($lines as $iid => $q) {
    $day = (float)($rows[$iid]["daily_price"] ?? 0);
    $estTotal += $day * $q * $days;
}
$estTotal = round($estTotal, 2);

$page_title = "Confirm rental";
$nav_role = isset($_SESSION["adm"]) ? "admin" : "user";
$active_nav = "rent_cart";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-4" style="max-width: 640px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Confirm rental</h1>
        <a href="<?= htmlspecialchars(sol_url("rent/rent_cart.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm">Back to rent cart</a>
    </div>

    <?php if ($flashErr !== ""): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-3"><?= htmlspecialchars($flashErr, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <?php if (!$hasRentalOptions): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-3">
            Run <code>schema_updates_rental_options.sql</code> on your database so payment and delivery choices can be saved.
        </div>
    <?php endif; ?>

    <?php if ($overlapNames !== []): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-3">
            These instruments are not available for the selected dates (another pending or approved booking overlaps):
            <strong><?= htmlspecialchars(implode(", ", $overlapNames), ENT_QUOTES, "UTF-8") ?></strong>.
            Change dates in your <a href="<?= htmlspecialchars(sol_url("rent/rent_cart.php"), ENT_QUOTES, "UTF-8") ?>">rent cart</a> or remove the item.
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">Items</h2>
            <ul class="list-group list-group-flush">
                <?php foreach ($lines as $iid => $qty): ?>
                    <?php
                    $row = $rows[$iid] ?? null;
                    if (!$row) {
                        continue;
                    }
                    $nm = htmlspecialchars((string)($row["name"] ?? ""), ENT_QUOTES, "UTF-8");
                    $day = (float)($row["daily_price"] ?? 0);
                    $lineEst = round($day * $qty * $days, 2);
                    ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                        <div>
                            <div class="fw-semibold"><?= $nm ?></div>
                            <div class="small text-muted">Qty <?= (int)$qty ?> · €<?= htmlspecialchars((string)$day, ENT_QUOTES, "UTF-8") ?>/day × <?= (int)$days ?> days</div>
                        </div>
                        <div class="fw-semibold text-nowrap">€<?= htmlspecialchars((string)$lineEst, ENT_QUOTES, "UTF-8") ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex justify-content-between align-items-baseline border-top pt-3 mt-2">
                <span class="text-muted">Estimated total</span>
                <span class="h5 mb-0">€<?= htmlspecialchars((string)$estTotal, ENT_QUOTES, "UTF-8") ?></span>
            </div>
            <p class="small text-muted mb-0 mt-2">Final pricing may be confirmed by staff.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">Your choices (change in rent cart if needed)</h2>
            <dl class="row mb-0 small">
                <dt class="col-sm-4 text-muted">Rental period</dt>
                <dd class="col-sm-8 mb-2"><?= htmlspecialchars($startDate, ENT_QUOTES, "UTF-8") ?> → <?= htmlspecialchars($endDate, ENT_QUOTES, "UTF-8") ?></dd>
                <dt class="col-sm-4 text-muted">Payment</dt>
                <dd class="col-sm-8 mb-2"><?= htmlspecialchars(sol_rental_payment_label($selPay), ENT_QUOTES, "UTF-8") ?></dd>
                <dt class="col-sm-4 text-muted">Delivery</dt>
                <dd class="col-sm-8 mb-2"><?= htmlspecialchars(sol_rental_delivery_label($selDel), ENT_QUOTES, "UTF-8") ?></dd>
                <?php if ($selNotes !== ""): ?>
                    <dt class="col-sm-4 text-muted">Delivery notes</dt>
                    <dd class="col-sm-8 mb-0"><?= nl2br(htmlspecialchars($selNotes, ENT_QUOTES, "UTF-8")) ?></dd>
                <?php endif; ?>
            </dl>
        </div>
    </div>

    <?php if ($selPay === "iban"): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light py-3"><span class="text-uppercase small text-secondary fw-semibold">Bank transfer (IBAN)</span></div>
            <div class="card-body">
                <p class="mb-0 fw-medium text-dark"><?= htmlspecialchars(sol_rental_payment_label("iban"), ENT_QUOTES, "UTF-8") ?></p>
                <?php require dirname(__DIR__) . "/includes/partials/checkout_iban_transfer_box.php"; ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars(sol_url("rent/rent_checkout_confirm.php"), ENT_QUOTES, "UTF-8") ?>" class="d-grid gap-2">
        <?= sol_csrf_field() ?>
        <button type="submit" name="confirm_rental" value="1" class="btn btn-primary" <?= ($overlapNames !== [] || !$hasRentalOptions) ? "disabled" : "" ?>>Submit rental request</button>
    </form>
</div>

<?php
require_once dirname(__DIR__) . "/includes/layout_bottom.php";
