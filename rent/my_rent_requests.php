<?php

declare(strict_types=1);

# Customer list of rental_requests + optional cancel POST.

require_once dirname(__DIR__) . "/includes/app.php";
require_once dirname(__DIR__) . "/includes/rental_helpers.php";
sol_require_user();

$uid = sol_current_uid();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancel_id"])) {
    if (!sol_csrf_verify()) {
        $_SESSION["flash_error"] = "Security check failed. Please try again.";
    } else {
        $cancelId = (int)$_POST["cancel_id"];
        if ($cancelId < 1) {
            $_SESSION["flash_error"] = "Invalid request.";
        } else {
            $st = $connect->prepare("UPDATE rental_requests SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending' LIMIT 1");
            if ($st) {
                $st->bind_param("ii", $cancelId, $uid);
                $st->execute();
                if ($st->affected_rows > 0) {
                    sol_rental_log_status($connect, $cancelId, "pending", "cancelled", "customer cancelled", $uid);
                    $_SESSION["flash_success"] = "Your rental request was cancelled.";
                } else {
                    $_SESSION["flash_error"] = "This request could not be cancelled. It may already be processed or is not yours.";
                }
                $st->close();
            } else {
                $_SESSION["flash_error"] = "Something went wrong. Please try again later.";
            }
        }
    }
    header("Location: " . sol_url("rent/my_rent_requests.php"));
    exit;
}

$st = $connect->prepare("SELECT * FROM rental_requests WHERE user_id = ? ORDER BY id DESC");
$st->bind_param("i", $uid);
$st->execute();
$result = $st->get_result();
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$st->close();

$instrumentNames = [];
$instIds = [];
foreach ($rows as $r) {
    $iid = (int)($r["instrument_id"] ?? 0);
    if ($iid > 0) {
        $instIds[$iid] = true;
    }
}
$instIdList = array_keys($instIds);
if ($instIdList !== []) {
    $placeholders = implode(",", array_fill(0, count($instIdList), "?"));
    $types = str_repeat("i", count($instIdList));
    $qst = $connect->prepare("SELECT id, name FROM instruments WHERE id IN ($placeholders)");
    if ($qst) {
        $qst->bind_param($types, ...$instIdList);
        $qst->execute();
        $ires = $qst->get_result();
        while ($ir = $ires->fetch_assoc()) {
            $instrumentNames[(int)$ir["id"]] = (string)($ir["name"] ?? "");
        }
        $qst->close();
    }
}

$flashErr = (string)($_SESSION["flash_error"] ?? "");
$flashOk = (string)($_SESSION["flash_success"] ?? "");
unset($_SESSION["flash_error"], $_SESSION["flash_success"]);

$page_title = "My rent requests";
$nav_role = isset($_SESSION["adm"]) ? "admin" : "user";
$active_nav = "rent_requests";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-3 py-md-4" style="max-width: 800px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 mb-md-4">
        <h1 class="h3 mb-0">My rental requests</h1>
        <a href="<?= htmlspecialchars(sol_url("rent/rentcatalog.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-primary btn-sm">Instruments</a>
    </div>

    <?php if ($flashOk !== ""): ?>
        <div class="alert alert-success border-0 small"><?= htmlspecialchars($flashOk, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>
    <?php if ($flashErr !== ""): ?>
        <div class="alert alert-danger border-0 small"><?= htmlspecialchars($flashErr, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <?php if ($rows === []): ?>
        <div class="alert alert-info border-0">No requests yet.</div>
    <?php else: ?>
        <?php foreach ($rows as $request): ?>
            <?php
            $oid = (int)$request["id"];
            $iid = (int)$request["instrument_id"];
            $itemName = $instrumentNames[$iid] ?? "";
            $stt = (string)($request["status"] ?? "");
            $badge = sol_rental_status_badge_class($stt);
            $adminNote = trim((string)($request["admin_note"] ?? ""));
            $decidedAt = trim((string)($request["decided_at"] ?? ""));
            $purpose = trim((string)($request["purpose"] ?? ""));
            ?>
            <div class="card border-0 shadow-sm mb-3 mb-md-4 sol-card-shell">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                        <h2 class="h5 card-title mb-0">Request #<?= $oid ?></h2>
                        <span class="badge <?= $badge ?>"><?= htmlspecialchars($stt, ENT_QUOTES, "UTF-8") ?></span>
                    </div>
                    <p class="small text-muted mb-2"><?= htmlspecialchars((string)$request["start_date"], ENT_QUOTES, "UTF-8") ?> → <?= htmlspecialchars((string)$request["end_date"], ENT_QUOTES, "UTF-8") ?></p>
                    <?php if ($decidedAt !== ""): ?>
                        <p class="small text-muted mb-2">Updated: <?= htmlspecialchars($decidedAt, ENT_QUOTES, "UTF-8") ?></p>
                    <?php endif; ?>
                    <hr>
                    <h3 class="h6 small text-uppercase text-muted">Instrument</h3>
                    <?php if ($itemName !== ""): ?>
                        <p class="mb-2"><?= htmlspecialchars($itemName, ENT_QUOTES, "UTF-8") ?></p>
                    <?php else: ?>
                        <p class="text-muted small mb-2">Instrument #<?= $iid ?> (details unavailable)</p>
                    <?php endif; ?>
                    <?php if ($purpose !== ""): ?>
                        <h3 class="h6 small text-uppercase text-muted mt-3">Purpose</h3>
                        <p class="small mb-0"><?= nl2br(htmlspecialchars($purpose, ENT_QUOTES, "UTF-8")) ?></p>
                    <?php endif; ?>
                    <?php
                    $pmC = trim((string)($request["payment_method"] ?? ""));
                    $dmC = trim((string)($request["delivery_method"] ?? ""));
                    $dnR = trim((string)($request["delivery_notes"] ?? ""));
                    if ($pmC !== "" || $dmC !== "" || $dnR !== ""):
                        ?>
                        <h3 class="h6 small text-uppercase text-muted mt-3">Payment &amp; delivery</h3>
                        <?php if ($pmC !== ""): ?>
                            <p class="small mb-1"><span class="text-muted">Payment:</span> <?= htmlspecialchars(sol_rental_payment_label($pmC), ENT_QUOTES, "UTF-8") ?></p>
                        <?php endif; ?>
                        <?php if ($dmC !== ""): ?>
                            <p class="small mb-1"><span class="text-muted">Delivery:</span> <?= htmlspecialchars(sol_rental_delivery_label($dmC), ENT_QUOTES, "UTF-8") ?></p>
                        <?php endif; ?>
                        <?php if ($dnR !== ""): ?>
                            <p class="small mb-0"><span class="text-muted">Notes:</span> <?= nl2br(htmlspecialchars($dnR, ENT_QUOTES, "UTF-8")) ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($adminNote !== ""): ?>
                        <div class="alert alert-light border mt-3 mb-0 small">
                            <strong class="d-block text-muted text-uppercase small">Message from admin</strong>
                            <?= nl2br(htmlspecialchars($adminNote, ENT_QUOTES, "UTF-8")) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($stt === "pending"): ?>
                        <form method="post" class="mt-3" data-sol-confirm="Cancel this request?">
                            <?= sol_csrf_field() ?>
                            <input type="hidden" name="cancel_id" value="<?= $oid ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Cancel request</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
