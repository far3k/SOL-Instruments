<?php

declare(strict_types=1);

# Thank-you after shop checkout; reads one-shot flash_shop_order.

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_login();

$data = $_SESSION["flash_shop_order"] ?? null;
unset($_SESSION["flash_shop_order"]);

if (!is_array($data) || (int)($data["order_id"] ?? 0) < 1) {
    header("Location: " . sol_url("shop/cart.php"));
    exit;
}

$orderId = (int)$data["order_id"];
$lines = $data["lines"] ?? [];
if (!is_array($lines)) {
    $lines = [];
}
$dm = (string)($data["delivery_mode"] ?? "");
$pm = (string)($data["payment_method"] ?? "");
if (!in_array($pm, ["store", "iban"], true)) {
    $pm = "";
}
$addr = trim((string)($data["shipping_address"] ?? ""));
$grand = (float)($data["grand_total"] ?? 0);

$page_title = "Order placed";
$nav_role = isset($_SESSION["adm"]) ? "admin" : "user";
$active_nav = "shop_orders";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-5" style="max-width: 640px;">
    <div class="card border-0 shadow-sm sol-card-shell p-4 text-center text-md-start">
        <h2 class="text-success h4 mb-3"><i class="bi bi-check-circle me-2"></i>Order received</h2>
        <p class="text-muted small mb-3">Order #<?= (int)$orderId ?> — status <strong>pending</strong> until staff confirms payment / pickup.</p>
        <?php if ($dm !== "" || $pm !== ""): ?>
            <div class="text-start small border rounded-3 p-3 mb-3 bg-light">
                <?php if ($dm !== ""): ?>
                    <div class="mb-0"><span class="text-muted">Delivery:</span> <?= $dm === "pickup" ? "Pickup at store" : "Standard delivery" ?></div>
                    <?php if ($dm === "delivery" && $addr !== ""): ?>
                        <div class="mt-2 mb-0 pt-2 border-top"><span class="text-muted">Ship to:</span><br><?= nl2br(htmlspecialchars($addr, ENT_QUOTES, "UTF-8")) ?></div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($pm !== ""): ?>
                    <div class="<?= $dm !== "" ? "mt-2 pt-2 border-top" : "mb-0" ?>"><span class="text-muted">Payment:</span> <?= htmlspecialchars(sol_payment_method_label($pm), ENT_QUOTES, "UTF-8") ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <ul class="list-group list-group-flush border rounded-3 mb-3 text-start">
            <?php foreach ($lines as $row): ?>
                <?php
                $nm = htmlspecialchars((string)($row["name"] ?? ""), ENT_QUOTES, "UTF-8");
                $q = (int)($row["qty"] ?? 0);
                $lt = (float)($row["line_total"] ?? 0);
                ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= $nm ?><?php if ($q > 1): ?> <span class="text-muted">×<?= $q ?></span><?php endif; ?></span>
                    <span class="fw-semibold">€<?= htmlspecialchars((string)round($lt, 2), ENT_QUOTES, "UTF-8") ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="h5 text-primary mb-4">Total €<?= htmlspecialchars((string)round($grand, 2), ENT_QUOTES, "UTF-8") ?></p>
        <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
            <a href="<?= htmlspecialchars(sol_url("shop/my_orders.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-primary rounded-3">My orders</a>
            <a href="<?= htmlspecialchars(sol_url("shop/catalog.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary rounded-3">Continue shopping</a>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
