<?php

declare(strict_types=1);

# Customer wishlist only (admins are redirected by sol_require_user).

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_user();

$uid = sol_current_uid();
if ($uid < 1) {
    header("Location: " . sol_url("login.php"));
    exit;
}

$notice = "";
$noticeOk = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remove_wishlist"])) {
    if (!sol_csrf_verify()) {
        $notice = "Security check failed.";
        $noticeOk = false;
    } else {
        $wid = (int)($_POST["wishlist_id"] ?? 0);
        if ($wid > 0) {
            $del = $connect->prepare("DELETE FROM wishlist WHERE wishlist_id = ? AND user_id = ? LIMIT 1");
            $del->bind_param("ii", $wid, $uid);
            $del->execute();
            if ($del->affected_rows > 0) {
                $notice = "Removed from wishlist.";
                $noticeOk = true;
            } else {
                $notice = "Could not remove item.";
            }
            $del->close();
        }
    }
}

$sql = "
SELECT 
    w.wishlist_id,
    w.created_at,
    w.item_type,
    CASE WHEN w.item_type = 'instrument' THEN i.name WHEN w.item_type = 'product' THEN p.name END AS name,
    CASE WHEN w.item_type = 'instrument' THEN i.daily_price WHEN w.item_type = 'product' THEN p.price END AS price,
    CASE WHEN w.item_type = 'instrument' THEN i.image_url WHEN w.item_type = 'product' THEN p.picture END AS picture,
    CASE WHEN w.item_type = 'instrument' THEN i.description WHEN w.item_type = 'product' THEN p.description END AS description,
    CASE WHEN w.item_type = 'instrument' THEN i.id WHEN w.item_type = 'product' THEN p.id END AS item_id
FROM wishlist w
LEFT JOIN instruments i ON w.item_type = 'instrument' AND i.id = w.product_id
LEFT JOIN products p ON w.item_type = 'product' AND p.id = w.product_id
WHERE w.user_id = ?
ORDER BY w.created_at DESC
";

$st = $connect->prepare($sql);
$st->bind_param("i", $uid);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

$page_title = "Wishlist";
$nav_role = isset($_SESSION["adm"]) ? "admin" : "user";
$active_nav = "wishlist";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-4" style="max-width: 1140px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0">My wishlist</h1>
            <p class="text-muted small mb-0"><?= count($rows) ?> item<?= count($rows) === 1 ? "" : "s" ?></p>
        </div>
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <?php if ($notice !== ""): ?>
        <div class="alert alert-<?= $noticeOk ? "success" : "danger" ?>"><?= htmlspecialchars($notice, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info border-0">Wishlist is empty.</div>
    <?php else: ?>
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table sol-line-items-table align-middle mb-0 bg-white">
                    <caption class="visually-hidden">Saved wishlist items with image and remove</caption>
                    <thead class="visually-hidden">
                        <tr>
                            <th scope="col">Item</th>
                            <th scope="col">Price</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $wid = (int)$row["wishlist_id"];
                            $pid = (int)$row["item_id"];
                            $name = htmlspecialchars((string)$row["name"], ENT_QUOTES, "UTF-8");
                            $price = htmlspecialchars((string)$row["price"], ENT_QUOTES, "UTF-8");
                            $pic = htmlspecialchars((string)$row["picture"], ENT_QUOTES, "UTF-8");
                            $type = $row["item_type"] ?? "instrument";
                            $typeLabel = $type === "product" ? "Shop" : "Rent";
                            $viewUrl = $type === "product"
                                ? sol_url("shop/shopItems_details.php?id=" . $pid)
                                : sol_url("rent/product_details.php?id=" . $pid);
                            $ex = sol_line_excerpt($row["description"] ?? null, 100);
                            $exHtml = $ex !== "" ? htmlspecialchars($ex, ENT_QUOTES, "UTF-8") : "";
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex gap-3 py-1">
                                        <img src="<?= htmlspecialchars(sol_url("pictures/" . $pic), ENT_QUOTES, "UTF-8") ?>" alt="" class="sol-line-thumb" width="72" height="72">
                                        <div class="min-w-0">
                                            <span class="badge bg-secondary bg-opacity-25 text-dark border mb-1"><?= htmlspecialchars($typeLabel, ENT_QUOTES, "UTF-8") ?></span>
                                            <div class="fw-semibold text-dark"><?= $name ?></div>
                                            <?php if ($exHtml !== ""): ?>
                                                <p class="sol-line-meta mb-1"><?= $exHtml ?></p>
                                            <?php endif; ?>
                                            <a href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, "UTF-8") ?>" class="small">View details</a>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-nowrap">
                                    <span class="fw-semibold">€<?= $price ?></span><?php if ($type === "instrument"): ?> <span class="small text-muted">/ day</span><?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap" style="width: 4rem;">
                                    <form method="post" class="d-inline">
                                        <?= sol_csrf_field() ?>
                                        <input type="hidden" name="wishlist_id" value="<?= $wid ?>">
                                        <button class="btn btn-link text-danger p-1" type="submit" name="remove_wishlist" value="1" title="Remove from list" aria-label="Remove from list"><i class="bi bi-trash3 fs-5"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
