<?php

declare(strict_types=1);

# Review shop cart, delivery / shipping address, totals; insert one `orders` row with JSON snapshot.

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_login();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$uid = sol_current_uid();
if (sol_user_account_blocked($connect, $uid)) {
    $_SESSION["flash_error"] = "Your account cannot place shop orders.";
    header("Location: " . sol_url("shop/cart.php"));
    exit;
}

sol_ensure_session_carts($connect);
$cart = $_SESSION["shop_cart"] ?? [];
$shopDelivery = (string)($_SESSION["shop_cart_delivery"] ?? "delivery");
if (!in_array($shopDelivery, ["pickup", "delivery"], true)) {
    $shopDelivery = "delivery";
}
$shopPayment = (string)($_SESSION["shop_cart_payment_method"] ?? "store");
if (!in_array($shopPayment, ["store", "iban"], true)) {
    $shopPayment = "store";
}
$intlAddr = $_SESSION["sol_intl_address"] ?? sol_intl_address_template();
$shipAddr = sol_intl_address_format_multiline($intlAddr);

$flashCheckout = $_SESSION["flash_shop_checkout"] ?? "";
unset($_SESSION["flash_shop_checkout"]);

$hasSchema = sol_shop_orders_checkout_ready($connect);

/** @return list<array{product_id:int,name:string,qty:int,unit_price:float,line_total:float,picture:string}> */
function sol_shop_build_lines(mysqli $db, array $cart): array
{
    $lines = [];
    if ($cart === []) {
        return $lines;
    }
    $ids = implode(",", array_map("intval", array_keys($cart)));
    if ($ids === "") {
        return $lines;
    }
    $res = $db->query("SELECT id, name, price, picture FROM products WHERE id IN ($ids)");
    if (!$res) {
        return $lines;
    }
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[(int)$r["id"]] = $r;
    }
    foreach ($cart as $cid => $cqty) {
        $pid = (int)$cid;
        $qty = max(1, (int)$cqty);
        $row = $rows[$pid] ?? null;
        if (!$row) {
            continue;
        }
        $unit = round((float)$row["price"], 2);
        $lineTotal = round($unit * $qty, 2);
        $lines[] = [
            "product_id" => $pid,
            "name" => (string)$row["name"],
            "qty" => $qty,
            "unit_price" => $unit,
            "line_total" => $lineTotal,
            "picture" => (string)($row["picture"] ?? "product.jpg"),
        ];
    }

    return $lines;
}

$lines = sol_shop_build_lines($connect, is_array($cart) ? $cart : []);
$subtotal = 0.0;
foreach ($lines as $ln) {
    $subtotal += (float)$ln["line_total"];
}
$subtotal = round($subtotal, 2);
$shipping = round(sol_shop_shipping_fee($subtotal, $shopDelivery), 2);
$grandTotal = round($subtotal + $shipping, 2);

if ($lines === []) {
    header("Location: " . sol_url("shop/cart.php"));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["confirm_shop_order"])) {
    if (!sol_csrf_verify()) {
        $_SESSION["flash_shop_checkout"] = "Security check failed. Please try again.";
        header("Location: " . sol_url("shop/checkout_confirm.php"));
        exit;
    }
    if (!$hasSchema) {
        $_SESSION["flash_shop_checkout"] = "Database is not ready for shop checkout. Run schema_updates_shop_checkout.sql then try again.";
        header("Location: " . sol_url("shop/checkout_confirm.php"));
        exit;
    }

    $lines = sol_shop_build_lines($connect, $_SESSION["shop_cart"] ?? []);
    if ($lines === []) {
        header("Location: " . sol_url("shop/cart.php"));
        exit;
    }
    $subtotal = 0.0;
    foreach ($lines as $ln) {
        $subtotal += (float)$ln["line_total"];
    }
    $subtotal = round($subtotal, 2);

    $dm = (string)($_SESSION["shop_cart_delivery"] ?? "delivery");
    if (!in_array($dm, ["pickup", "delivery"], true)) {
        $dm = "delivery";
    }
    $intlPost = $_SESSION["sol_intl_address"] ?? sol_intl_address_template();
    if ($dm === "pickup") {
        $addrBind = "";
    } elseif (!sol_intl_address_is_complete($intlPost)) {
        $_SESSION["flash_error"] = "For standard delivery, complete the international address form in your shop cart, then try again.";
        header("Location: " . sol_url("shop/cart.php"));
        exit;
    } else {
        $addrBind = mb_substr(sol_intl_address_format_multiline($intlPost), 0, 4000);
    }

    $shipping = round(sol_shop_shipping_fee($subtotal, $dm), 2);
    $grandTotal = round($subtotal + $shipping, 2);

    $paymentMethod = (string)($_SESSION["shop_cart_payment_method"] ?? "store");
    if (!in_array($paymentMethod, ["store", "iban"], true)) {
        $paymentMethod = "store";
    }

    $snapshot = [
        "currency" => "EUR",
        "lines" => $lines,
        "payment_method" => $paymentMethod,
    ];
    $json = json_encode($snapshot, JSON_THROW_ON_ERROR);

    $hasPayCol = sol_db_column_exists($connect, "orders", "payment_method");
    if ($hasPayCol) {
        $st = $connect->prepare(
            "INSERT INTO orders (user_id, delivery_mode, payment_method, shipping_address, cart_snapshot, order_subtotal, order_shipping, order_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $st->bind_param(
            "issssddd",
            $uid,
            $dm,
            $paymentMethod,
            $addrBind,
            $json,
            $subtotal,
            $shipping,
            $grandTotal
        );
    } else {
        $st = $connect->prepare(
            "INSERT INTO orders (user_id, delivery_mode, shipping_address, cart_snapshot, order_subtotal, order_shipping, order_total) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $st->bind_param(
            "isssddd",
            $uid,
            $dm,
            $addrBind,
            $json,
            $subtotal,
            $shipping,
            $grandTotal
        );
    }
    $st->execute();
    $newId = (int)$st->insert_id;
    $st->close();

    $_SESSION["shop_cart"] = [];
    $_SESSION["sol_intl_address"] = sol_intl_address_template();
    $_SESSION["shop_cart_payment_method"] = "store";

    $_SESSION["flash_shop_order"] = [
        "order_id" => $newId,
        "lines" => $lines,
        "delivery_mode" => $dm,
        "payment_method" => $paymentMethod,
        "shipping_address" => $addrBind,
        "subtotal" => $subtotal,
        "shipping" => $shipping,
        "grand_total" => $grandTotal,
    ];

    header("Location: " . sol_url("shop/order_success.php"));
    exit;
}

$page_title = "Checkout";
$nav_role = isset($_SESSION["adm"]) ? "admin" : "user";
$active_nav = "shop_cart";
require_once dirname(__DIR__) . "/includes/layout_top.php";

$addrOk = $shopDelivery === "pickup" || sol_intl_address_is_complete($intlAddr);
$reviewReady = $hasSchema && $addrOk;
?>

<div class="container py-4 py-lg-5" style="max-width: 720px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0 fw-semibold">Review order</h1>
        <a href="<?= htmlspecialchars(sol_url("shop/cart.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm rounded-pill">Edit cart</a>
    </div>

    <?php if ($flashCheckout !== ""): ?>
        <div class="alert alert-warning border-0 rounded-3"><?= htmlspecialchars($flashCheckout, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <?php if (!$hasSchema): ?>
        <div class="alert alert-warning border-0 rounded-3">
            Run <code>schema_updates_shop_checkout.sql</code> on your database so shop orders can be saved.
        </div>
    <?php endif; ?>

    <?php if ($shopDelivery === "delivery" && !$addrOk): ?>
        <div class="alert alert-danger border-0 rounded-3">
            Standard delivery is selected but the international address is incomplete. Go back to your <a href="<?= htmlspecialchars(sol_url("shop/cart.php"), ENT_QUOTES, "UTF-8") ?>">cart</a> and fill every required field (name, street, city, postal code, country).
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-header bg-light py-3"><span class="text-uppercase small text-secondary fw-semibold">Items</span></div>
        <ul class="list-group list-group-flush">
            <?php foreach ($lines as $ln): ?>
                <li class="list-group-item d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <strong><?= htmlspecialchars($ln["name"], ENT_QUOTES, "UTF-8") ?></strong>
                        <div class="small text-muted">Qty <?= (int)$ln["qty"] ?> × €<?= htmlspecialchars((string)$ln["unit_price"], ENT_QUOTES, "UTF-8") ?></div>
                    </div>
                    <span class="fw-semibold text-nowrap">€<?= htmlspecialchars((string)$ln["line_total"], ENT_QUOTES, "UTF-8") ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-header bg-light py-3"><span class="text-uppercase small text-secondary fw-semibold">Delivery</span></div>
        <div class="card-body">
            <p class="mb-1">
                <span class="text-muted">Method:</span>
                <?= $shopDelivery === "pickup" ? "Pickup at store" : "Standard delivery" ?>
            </p>
            <?php if ($shopDelivery === "delivery" && $shipAddr !== ""): ?>
                <p class="mb-0 small"><span class="text-muted">Address:</span><br><?= nl2br(htmlspecialchars($shipAddr, ENT_QUOTES, "UTF-8")) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-header bg-light py-3"><span class="text-uppercase small text-secondary fw-semibold">Payment</span></div>
        <div class="card-body">
            <p class="mb-1 fw-medium text-dark"><?= htmlspecialchars(sol_payment_method_label($shopPayment), ENT_QUOTES, "UTF-8") ?></p>
            <?php if ($shopPayment === "iban"): ?>
                <?php require dirname(__DIR__) . "/includes/partials/checkout_iban_transfer_box.php"; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span class="fw-semibold">€<?= htmlspecialchars((string)$subtotal, ENT_QUOTES, "UTF-8") ?></span></div>
            <div class="d-flex justify-content-between mb-2">
                <span>Shipping</span>
                <span class="fw-semibold">
                    <?php if ($shipping <= 0): ?>
                        <span class="text-success">Free</span>
                    <?php else: ?>
                        €<?= htmlspecialchars((string)$shipping, ENT_QUOTES, "UTF-8") ?>
                    <?php endif; ?>
                </span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Total</span>
                <span class="h5 mb-0 text-primary">€<?= htmlspecialchars((string)$grandTotal, ENT_QUOTES, "UTF-8") ?></span>
            </div>
        </div>
    </div>

    <form method="post" action="<?= htmlspecialchars(sol_url("shop/checkout_confirm.php"), ENT_QUOTES, "UTF-8") ?>" class="d-grid gap-2">
        <?= sol_csrf_field() ?>
        <button type="submit" name="confirm_shop_order" value="1" class="btn btn-primary btn-lg rounded-3 shadow-sm" <?= $reviewReady ? "" : "disabled" ?> <?= $reviewReady ? "" : "aria-disabled=\"true\"" ?>>
            Place order
        </button>
    </form>
    <p class="small text-muted mt-3 mb-0">No online card gateway — staff will confirm <?= htmlspecialchars(sol_payment_method_label($shopPayment), ENT_QUOTES, "UTF-8") ?>.</p>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
