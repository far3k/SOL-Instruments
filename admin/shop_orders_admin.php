<?php

declare(strict_types=1);

# Admin: shop orders — filter, view lines & address, change status, internal notes.

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_admin();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$adminId = (int)$_SESSION["adm"];
$flashOk = "";
$flashErr = "";

$ordersTable = sol_db_table_exists($connect, "orders");
$hasRich = sol_shop_orders_checkout_ready($connect);
$hasPlacedAt = $ordersTable && sol_db_column_exists($connect, "orders", "placed_at");

$orderStatuses = ["pending", "paid", "shipped", "completed", "cancelled"];

if ($ordersTable && $_SERVER["REQUEST_METHOD"] === "POST" && sol_csrf_verify()) {
    $action = (string)($_POST["order_action"] ?? "");
    $oid = (int)($_POST["order_id"] ?? 0);
    if ($oid < 1) {
        $flashErr = "Invalid order.";
    } else {
        $chk = $connect->prepare("SELECT id, status FROM orders WHERE id = ? LIMIT 1");
        $chk->bind_param("i", $oid);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$row) {
            $flashErr = "Order not found.";
        } elseif ($action === "update_status") {
            $new = (string)($_POST["new_status"] ?? "");
            if (!in_array($new, $orderStatuses, true)) {
                $flashErr = "Invalid status.";
            } else {
                $u = $connect->prepare("UPDATE orders SET status = ? WHERE id = ? LIMIT 1");
                $u->bind_param("si", $new, $oid);
                $u->execute();
                $u->close();
                $flashOk = "Order #" . $oid . " status set to " . $new . ".";
            }
        } elseif ($action === "save_note") {
            $note = mb_substr(trim((string)($_POST["admin_note"] ?? "")), 0, 4000);
            $u = $connect->prepare("UPDATE orders SET admin_note = NULLIF(?, '') WHERE id = ? LIMIT 1");
            $u->bind_param("si", $note, $oid);
            $u->execute();
            $u->close();
            $flashOk = "Note saved for order #" . $oid . ".";
        } else {
            $flashErr = "Unknown action.";
        }
    }
}

$filterStatus = trim((string)($_GET["status"] ?? ""));
if ($filterStatus !== "" && !in_array($filterStatus, $orderStatuses, true)) {
    $filterStatus = "";
}
$userQ = trim((string)($_GET["user_q"] ?? ""));

$rows = [];
if ($ordersTable) {
    $conds = ["1=1"];
    $types = "";
    $params = [];
    if ($filterStatus !== "") {
        $conds[] = "o.status = ?";
        $types .= "s";
        $params[] = $filterStatus;
    }
    if ($userQ !== "") {
        $conds[] = "(u.email LIKE ? OR CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) LIKE ? OR CAST(o.id AS CHAR) = ?)";
        $like = "%" . $userQ . "%";
        $types .= "sss";
        $params[] = $like;
        $params[] = $like;
        $params[] = $userQ;
    }
    $orderBy = $hasPlacedAt ? "o.placed_at DESC, o.id DESC" : "o.id DESC";
    $sql = "SELECT o.*, u.first_name, u.last_name, u.email
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            WHERE " . implode(" AND ", $conds) . "
            ORDER BY " . $orderBy;
    $st = $connect->prepare($sql);
    if ($types !== "") {
        $st->bind_param($types, ...$params);
    }
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
}

$page_title = "Shop orders";
$nav_role = "admin";
$active_nav = "shop_orders_admin";
require_once dirname(__DIR__) . "/includes/layout_top.php";

function sol_admin_order_status_badge(string $s): string
{
    return match ($s) {
        "pending" => "bg-warning text-dark",
        "paid" => "bg-info text-dark",
        "shipped" => "bg-primary",
        "completed" => "bg-success",
        "cancelled" => "bg-secondary",
        default => "bg-secondary",
    };
}
?>

<div class="container py-4" style="max-width: 1200px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0">Shop orders</h1>
            <p class="text-muted small mb-0">Filter, inspect line items and shipping, update fulfilment status, add staff notes.</p>
        </div>
    </div>

    <?php if ($flashOk !== ""): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-3"><?= htmlspecialchars($flashOk, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>
    <?php if ($flashErr !== ""): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-3"><?= htmlspecialchars($flashErr, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <?php if (!$ordersTable): ?>
        <div class="alert alert-warning border-0">The <code>orders</code> table is missing. Import <code>login.sql</code> (or create the table) to use this screen.</div>
    <?php elseif (!$hasRich): ?>
        <div class="alert alert-warning border-0 small">Run <code>schema_updates_shop_checkout.sql</code> for full order snapshots (lines, delivery, totals). Until then, only basic columns are shown if present.</div>
    <?php endif; ?>

    <?php if ($ordersTable): ?>
        <form method="get" action="<?= htmlspecialchars(sol_url("admin/shop_orders_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="card border-0 shadow-sm mb-4">
            <div class="card-body row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($orderStatuses as $os): ?>
                            <option value="<?= htmlspecialchars($os, ENT_QUOTES, "UTF-8") ?>" <?= $filterStatus === $os ? "selected" : "" ?>><?= htmlspecialchars(ucfirst($os), ENT_QUOTES, "UTF-8") ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small text-muted mb-1">Search (order #, email, name)</label>
                    <input type="search" name="user_q" class="form-control form-control-sm" value="<?= htmlspecialchars($userQ, ENT_QUOTES, "UTF-8") ?>" placeholder="e.g. 12 or @domain">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                    <a href="<?= htmlspecialchars(sol_url("admin/shop_orders_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>

        <?php if ($rows === []): ?>
            <div class="alert alert-light border shadow-sm">No orders match your filters.</div>
        <?php else: ?>
            <div class="table-responsive card border-0 shadow-sm">
                <table class="table table-hover align-middle mb-0 bg-white small">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>When</th>
                            <th>Customer</th>
                            <th>Delivery</th>
                            <th>Payment</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                            <th style="min-width: 280px">Control</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <?php
                            $oid = (int)$r["id"];
                            $stt = (string)($r["status"] ?? "pending");
                            $badge = sol_admin_order_status_badge($stt);
                            $placed = $hasPlacedAt ? (string)($r["placed_at"] ?? "") : "";
                            $dm = (string)($r["delivery_mode"] ?? "");
                            $sub = $r["order_subtotal"] ?? null;
                            $ship = $r["order_shipping"] ?? null;
                            $tot = $r["order_total"] ?? null;
                            $snapRaw = trim((string)($r["cart_snapshot"] ?? ""));
                            $lines = [];
                            $payFromSnap = "";
                            if ($snapRaw !== "") {
                                try {
                                    $dec = json_decode($snapRaw, true, 512, JSON_THROW_ON_ERROR);
                                    if (is_array($dec) && isset($dec["lines"]) && is_array($dec["lines"])) {
                                        $lines = $dec["lines"];
                                    }
                                    if (is_array($dec) && isset($dec["payment_method"])) {
                                        $payFromSnap = trim((string)$dec["payment_method"]);
                                    }
                                } catch (Throwable $e) {
                                    $lines = [];
                                }
                            }
                            $pmDisp = trim((string)($r["payment_method"] ?? ""));
                            if ($pmDisp === "") {
                                $pmDisp = $payFromSnap;
                            }
                            if (!in_array($pmDisp, ["store", "iban"], true)) {
                                $pmDisp = "";
                            }
                            $addr = trim((string)($r["shipping_address"] ?? ""));
                            $collapseId = "ord-det-" . $oid;
                            ?>
                            <tr>
                                <td class="fw-semibold"><?= $oid ?></td>
                                <td class="text-muted"><?= $placed !== "" ? htmlspecialchars($placed, ENT_QUOTES, "UTF-8") : "—" ?></td>
                                <td>
                                    <?php
                                    $fn = trim((string)($r["first_name"] ?? ""));
                                    $ln = trim((string)($r["last_name"] ?? ""));
                                    $em = trim((string)($r["email"] ?? ""));
                                    $dispName = trim($fn . " " . $ln);
                                    if ($dispName === "" && $em === "") {
                                        $dispName = "User #" . (int)($r["user_id"] ?? 0);
                                    }
                                    ?>
                                    <div class="fw-medium"><?= htmlspecialchars($dispName !== "" ? $dispName : "—", ENT_QUOTES, "UTF-8") ?></div>
                                    <div class="text-break text-muted"><?= htmlspecialchars($em !== "" ? $em : "—", ENT_QUOTES, "UTF-8") ?></div>
                                </td>
                                <td><?= $dm === "pickup" ? "Pickup" : ($dm === "delivery" ? "Delivery" : htmlspecialchars($dm !== "" ? $dm : "—", ENT_QUOTES, "UTF-8")) ?></td>
                                <td><?= $pmDisp !== "" ? htmlspecialchars(sol_payment_method_label($pmDisp), ENT_QUOTES, "UTF-8") : "—" ?></td>
                                <td class="text-end fw-semibold"><?= $tot !== null && $tot !== "" ? "€" . htmlspecialchars((string)$tot, ENT_QUOTES, "UTF-8") : "—" ?></td>
                                <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($stt, ENT_QUOTES, "UTF-8") ?></span></td>
                                <td>
                                    <button class="btn btn-outline-secondary btn-sm mb-1" type="button" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($collapseId, ENT_QUOTES, "UTF-8") ?>">Details</button>
                                    <form method="post" class="d-inline-flex flex-wrap align-items-center gap-1 mb-1">
                                        <?= sol_csrf_field() ?>
                                        <input type="hidden" name="order_action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?= $oid ?>">
                                        <select name="new_status" class="form-select form-select-sm" style="width: auto; min-width: 7rem;">
                                            <?php foreach ($orderStatuses as $os): ?>
                                                <option value="<?= htmlspecialchars($os, ENT_QUOTES, "UTF-8") ?>" <?= $stt === $os ? "selected" : "" ?>><?= htmlspecialchars($os, ENT_QUOTES, "UTF-8") ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm">Set status</button>
                                    </form>
                                    <form method="post" class="mt-1">
                                        <?= sol_csrf_field() ?>
                                        <input type="hidden" name="order_action" value="save_note">
                                        <input type="hidden" name="order_id" value="<?= $oid ?>">
                                        <label class="visually-hidden" for="note-<?= $oid ?>">Staff note</label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" name="admin_note" id="note-<?= $oid ?>" value="<?= htmlspecialchars((string)($r["admin_note"] ?? ""), ENT_QUOTES, "UTF-8") ?>" maxlength="4000" placeholder="Internal note…">
                                            <button type="submit" class="btn btn-outline-primary">Save note</button>
                                        </div>
                                    </form>
                                    <div class="collapse mt-2" id="<?= htmlspecialchars($collapseId, ENT_QUOTES, "UTF-8") ?>">
                                        <div class="border rounded p-2 bg-light small text-start">
                                            <?php if ($sub !== null && $sub !== ""): ?>
                                                <div><span class="text-muted">Subtotal:</span> €<?= htmlspecialchars((string)$sub, ENT_QUOTES, "UTF-8") ?></div>
                                            <?php endif; ?>
                                            <?php if ($ship !== null && $ship !== ""): ?>
                                                <div><span class="text-muted">Shipping:</span> €<?= htmlspecialchars((string)$ship, ENT_QUOTES, "UTF-8") ?></div>
                                            <?php endif; ?>
                                            <?php if ($addr !== ""): ?>
                                                <div class="mt-2"><span class="text-muted">Ship to:</span><br><?= nl2br(htmlspecialchars($addr, ENT_QUOTES, "UTF-8")) ?></div>
                                            <?php endif; ?>
                                            <?php if ($pmDisp !== ""): ?>
                                                <div class="mt-2"><span class="text-muted">Payment:</span> <?= htmlspecialchars(sol_payment_method_label($pmDisp), ENT_QUOTES, "UTF-8") ?></div>
                                            <?php endif; ?>
                                            <?php if ($lines !== []): ?>
                                                <div class="mt-2 fw-semibold">Line items</div>
                                                <ul class="mb-0 ps-3">
                                                    <?php foreach ($lines as $ln): ?>
                                                        <li><?= htmlspecialchars((string)($ln["name"] ?? ""), ENT_QUOTES, "UTF-8") ?> × <?= (int)($ln["qty"] ?? 0) ?> — €<?= htmlspecialchars((string)($ln["line_total"] ?? ""), ENT_QUOTES, "UTF-8") ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php elseif ($snapRaw === ""): ?>
                                                <p class="text-muted mb-0">No line snapshot stored for this row.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
