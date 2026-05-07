<?php

declare(strict_types=1);

# Lists customer orders when `orders` table exists (graceful if missing).

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_user();

$uid = sol_current_uid();

$ordersTableOk = false;
$tcheck = $connect->query("SHOW TABLES LIKE 'orders'");
if ($tcheck && $tcheck->num_rows > 0) {
    $ordersTableOk = true;
}

$hasSnapshot = $ordersTableOk && sol_db_column_exists($connect, "orders", "cart_snapshot");
$hasLegacyProductId = $ordersTableOk && sol_db_column_exists($connect, "orders", "product_id");

$orders = [];
if ($ordersTableOk) {
    $st = $connect->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
    $st->bind_param("i", $uid);
    $st->execute();
    $orders = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
}

$page_title = "My orders";
$nav_role = "user";
$active_nav = "shop_orders";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-4" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">My orders</h1>
        <a href="<?= htmlspecialchars(sol_url("shop/catalog.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-primary btn-sm">Shop catalog</a>
    </div>

    <?php if (!$ordersTableOk): ?>
        <div class="alert alert-warning border-0">The <code>orders</code> table is not installed. Import <code>login.sql</code> to enable order history.</div>
    <?php elseif (empty($orders)): ?>
        <div class="alert alert-info border-0">No orders yet.</div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php
            $orderId = (int)$order["id"];
            $snapRaw = trim((string)($order["cart_snapshot"] ?? ""));
            $linesFromSnap = [];
            $payFromSnap = "";
            if ($hasSnapshot && $snapRaw !== "") {
                try {
                    $decoded = json_decode($snapRaw, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($decoded) && isset($decoded["lines"]) && is_array($decoded["lines"])) {
                        $linesFromSnap = $decoded["lines"];
                    }
                    if (is_array($decoded) && isset($decoded["payment_method"])) {
                        $payFromSnap = trim((string)$decoded["payment_method"]);
                    }
                } catch (Throwable $e) {
                    $linesFromSnap = [];
                }
            }
            $payOrder = trim((string)($order["payment_method"] ?? ""));
            if ($payOrder === "") {
                $payOrder = $payFromSnap;
            }
            if (!in_array($payOrder, ["store", "iban"], true)) {
                $payOrder = "";
            }
            ?>
            <div class="card border-0 shadow-sm mb-4 sol-card-shell">
                <div class="card-body">
                    <h5 class="card-title">Order #<?= $orderId ?></h5>
                    <p class="mb-2">Status: <span class="badge bg-secondary"><?= htmlspecialchars((string)($order["status"] ?? ""), ENT_QUOTES, "UTF-8") ?></span></p>
                    <?php if (!empty($order["delivery_mode"])): ?>
                        <?php $dmO = (string)($order["delivery_mode"] ?? ""); ?>
                        <p class="small text-muted mb-1">
                            <?= $dmO === "pickup" ? "Pickup" : ($dmO === "delivery" ? "Standard delivery" : htmlspecialchars($dmO, ENT_QUOTES, "UTF-8")) ?>
                            <?php if ($dmO === "delivery" && trim((string)($order["shipping_address"] ?? "")) !== ""): ?>
                                <br><span class="text-dark"><?= nl2br(htmlspecialchars(trim((string)$order["shipping_address"]), ENT_QUOTES, "UTF-8")) ?></span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($payOrder !== ""): ?>
                        <p class="small text-muted mb-1"><span class="text-muted">Payment:</span> <?= htmlspecialchars(sol_payment_method_label($payOrder), ENT_QUOTES, "UTF-8") ?></p>
                    <?php endif; ?>
                    <?php if (isset($order["order_total"]) && $order["order_total"] !== null && $order["order_total"] !== ""): ?>
                        <p class="small mb-2"><strong>Total:</strong> €<?= htmlspecialchars((string)$order["order_total"], ENT_QUOTES, "UTF-8") ?></p>
                    <?php endif; ?>
                    <?php if (!empty($order["admin_note"])): ?>
                        <p><strong>Note:</strong> <?= htmlspecialchars((string)$order["admin_note"], ENT_QUOTES, "UTF-8") ?></p>
                    <?php endif; ?>
                    <hr>
                    <h6 class="small text-uppercase text-muted">Items</h6>
                    <?php if ($linesFromSnap !== []): ?>
                        <?php foreach ($linesFromSnap as $ln): ?>
                            <?php
                            $pid = (int)($ln["product_id"] ?? 0);
                            $pic = htmlspecialchars((string)($ln["picture"] ?? "product.jpg"), ENT_QUOTES, "UTF-8");
                            $nm = htmlspecialchars((string)($ln["name"] ?? ("#" . $pid)), ENT_QUOTES, "UTF-8");
                            $q = (int)($ln["qty"] ?? 1);
                            $lt = (string)($ln["line_total"] ?? "");
                            ?>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img src="<?= htmlspecialchars(sol_url("pictures/" . $pic), ENT_QUOTES, "UTF-8") ?>" width="60" height="60" class="rounded object-fit-cover" alt="">
                                <div class="flex-grow-1">
                                    <strong><?= $nm ?></strong><br>
                                    <span class="text-muted small">Qty <?= $q ?><?= $lt !== "" ? " · €" . htmlspecialchars($lt, ENT_QUOTES, "UTF-8") : "" ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($hasLegacyProductId && (int)($order["product_id"] ?? 0) > 0): ?>
                        <?php
                        $pst = $connect->prepare("SELECT name, price, picture FROM products WHERE id = ? LIMIT 1");
                        $pid = (int)$order["product_id"];
                        $pst->bind_param("i", $pid);
                        $pst->execute();
                        $product = $pst->get_result()->fetch_assoc();
                        $pst->close();
                        ?>
                        <?php if ($product): ?>
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?= htmlspecialchars(sol_url("pictures/" . ($product["picture"] ?? "")), ENT_QUOTES, "UTF-8") ?>" width="60" height="60" class="rounded object-fit-cover" alt="">
                                <div>
                                    <strong><?= htmlspecialchars($product["name"] ?? "", ENT_QUOTES, "UTF-8") ?></strong><br>
                                    <span class="text-muted">€<?= htmlspecialchars((string)($product["price"] ?? ""), ENT_QUOTES, "UTF-8") ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">Product not found</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No line details stored for this order. New checkouts save a full cart snapshot.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
