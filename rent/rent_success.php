<?php

declare(strict_types=1);

# Thank-you page: reads one-shot flash_rent then clears session payload.

require_once dirname(__DIR__) . "/includes/app.php";
require_once dirname(__DIR__) . "/includes/rental_helpers.php";
sol_require_user();

$data = $_SESSION["flash_rent"] ?? null;
unset($_SESSION["flash_rent"]);

if (!is_array($data)) {
    header("Location: " . sol_url("rent/rent_cart.php"));
    exit;
}

$items = $data["items"] ?? [];
if (!is_array($items) || $items === []) {
    header("Location: " . sol_url("rent/rent_cart.php"));
    exit;
}

$page_title = "Rental submitted";
$nav_role = isset($_SESSION["adm"]) ? "admin" : "user";
$active_nav = "rent_cart";
require_once dirname(__DIR__) . "/includes/layout_top.php";

$s = htmlspecialchars((string)($data["start"] ?? ""), ENT_QUOTES, "UTF-8");
$e = htmlspecialchars((string)($data["end"] ?? ""), ENT_QUOTES, "UTF-8");
$pmCode = (string)($data["payment_method"] ?? "");
$dmCode = (string)($data["delivery_method"] ?? "");
$dn = trim((string)($data["delivery_notes"] ?? ""));
$pmLabel = $pmCode !== "" ? sol_rental_payment_label($pmCode) : "";
$dmLabel = $dmCode !== "" ? sol_rental_delivery_label($dmCode) : "";
?>

<div class="container py-5" style="max-width: 640px;">
    <div class="card border-0 shadow-sm sol-card-shell p-4 text-center text-md-start">
        <h2 class="text-success h4 mb-3"><i class="bi bi-check-circle me-2"></i>Rental request(s) submitted</h2>
        <p class="text-muted small mb-3"><?= $s ?> → <?= $e ?></p>
        <?php if ($pmLabel !== "" || $dmLabel !== ""): ?>
            <div class="text-start small border rounded-3 p-3 mb-3 bg-light">
                <?php if ($pmLabel !== ""): ?>
                    <div class="mb-1"><span class="text-muted">Payment:</span> <?= htmlspecialchars($pmLabel, ENT_QUOTES, "UTF-8") ?></div>
                <?php endif; ?>
                <?php if ($dmLabel !== ""): ?>
                    <div class="mb-0"><span class="text-muted">Delivery:</span> <?= htmlspecialchars($dmLabel, ENT_QUOTES, "UTF-8") ?></div>
                <?php endif; ?>
                <?php if ($dn !== ""): ?>
                    <div class="mt-2 mb-0 pt-2 border-top"><span class="text-muted">Courier / delivery notes:</span><br><?= nl2br(htmlspecialchars($dn, ENT_QUOTES, "UTF-8")) ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <ul class="list-group list-group-flush border rounded-3 mb-0 text-start">
            <?php foreach ($items as $row): ?>
                <?php
                $iid = (int)($row["instrument_id"] ?? 0);
                $name = trim((string)($row["instrument_name"] ?? ""));
                if ($name === "") {
                    $name = "Instrument #" . $iid;
                }
                $rid = (int)($row["request_id"] ?? 0);
                ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?></span>
                    <?php if ($rid > 0): ?>
                        <span class="badge bg-light text-dark border">#<?= $rid ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-2 mt-4">
            <a href="<?= htmlspecialchars(sol_url("rent/rentcatalog.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-primary">Instruments catalog</a>
            <a href="<?= htmlspecialchars(sol_url("rent/my_rent_requests.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary">My rent requests</a>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
