<?php

declare(strict_types=1);

# Single instrument detail + add to rent cart (logged-in).

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_user();

$uid = sol_current_uid();
$productId = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($productId < 1) {
    header("Location: " . sol_url("rent/rentcatalog.php"));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_to_wishlist"])) {
    if (sol_csrf_verify() && $uid > 0) {
        $postPid = (int)($_POST["product_id"] ?? 0);
        if ($postPid === $productId) {
            $chk = $connect->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ? AND item_type = 'instrument' LIMIT 1");
            $chk->bind_param("ii", $uid, $postPid);
            $chk->execute();
            if ($chk->get_result()->num_rows === 0) {
                $chk->close();
                $ins = $connect->prepare("INSERT INTO wishlist (user_id, product_id, item_type) VALUES (?, ?, 'instrument')");
                $ins->bind_param("ii", $uid, $postPid);
                $ins->execute();
                $ins->close();
            } else {
                $chk->close();
            }
        }
    }
    header("Location: " . sol_url("rent/product_details.php?id=" . $productId));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_to_rent_cart"])) {
    if (sol_csrf_verify()) {
        $cartId = (int)($_POST["product_id"] ?? 0);
        if ($cartId === $productId && $cartId > 0) {
            $_SESSION["rent_cart"][$cartId] = 1;
        }
    }
    header("Location: " . sol_url("rent/product_details.php?id=" . $productId));
    exit;
}

$st = $connect->prepare("SELECT * FROM instruments WHERE id = ? AND is_active = 1 LIMIT 1");
$st->bind_param("i", $productId);
$st->execute();
$result = $st->get_result();
if ($result->num_rows !== 1) {
    $st->close();
    header("Location: " . sol_url("rent/rentcatalog.php"));
    exit;
}
$row = $result->fetch_assoc();
$st->close();

$name = htmlspecialchars($row["name"] ?? "", ENT_QUOTES, "UTF-8");
$price = htmlspecialchars((string)($row["daily_price"] ?? ""), ENT_QUOTES, "UTF-8");
$pic = htmlspecialchars($row["image_url"] ?? "instrument.jpg", ENT_QUOTES, "UTF-8");
$cond = htmlspecialchars($row["condition"] ?? "", ENT_QUOTES, "UTF-8");
$desc = !empty($row["description"])
    ? nl2br(htmlspecialchars((string)$row["description"], ENT_QUOTES, "UTF-8"))
    : "<span class='text-muted'>No description.</span>";

$inWishlist = false;
if ($uid > 0) {
    $w = $connect->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND product_id = ? AND item_type = 'instrument' LIMIT 1");
    $w->bind_param("ii", $uid, $productId);
    $w->execute();
    $inWishlist = $w->get_result()->num_rows > 0;
    $w->close();
}

$page_title = ($row["name"] ?? "Instrument") . " — Details";
$nav_role = isset($_SESSION["adm"]) ? "admin" : "user";
$active_nav = "catalog_rent";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-4 pb-5" style="max-width: 900px;">
    <div class="card sol-card-shell border-0 shadow-sm overflow-hidden sol-product-detail-card">
        <div class="row g-0">
            <div class="col-md-6 d-flex align-items-center justify-content-center p-3 sol-product-detail-media" style="min-height: 280px;">
                <img src="<?= htmlspecialchars(sol_url("pictures/" . $pic), ENT_QUOTES, "UTF-8") ?>" class="img-fluid rounded sol-product-detail-img" alt="<?= $name ?>" style="max-height: 400px; object-fit: contain;">
            </div>
            <div class="col-md-6 sol-product-detail-content-col">
                <div class="card-body p-4">
                    <h1 class="h3 mb-3"><?= $name ?></h1>
                    <p class="fs-4 text-primary mb-2">€<?= $price ?> <span class="fs-6 text-muted">/ day</span></p>
                    <p class="small text-muted mb-4">Condition: <strong><?= $cond ?></strong></p>

                    <h2 class="h6 text-uppercase text-muted">Description</h2>
                    <div class="mb-4"><?= $desc ?></div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php if (!$inWishlist): ?>
                            <div class="sol-wishlist-add-wrap d-inline">
                                <form method="post" class="d-inline sol-ajax-cart" data-sol-wish-type="instrument">
                                    <?= sol_csrf_field() ?>
                                    <input type="hidden" name="product_id" value="<?= (int)$productId ?>">
                                    <button type="submit" name="add_to_wishlist" value="1" class="btn btn-outline-danger btn-sm">Add to wishlist</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars(sol_url("account/wishlist.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-heart-fill me-1"></i> In wishlist</a>
                        <?php endif; ?>

                        <form method="post" class="d-inline sol-ajax-cart">
                            <?= sol_csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int)$productId ?>">
                            <button type="submit" name="add_to_rent_cart" class="btn btn-outline-primary btn-sm">Add to rent cart</button>
                        </form>
                    </div>

                    <div class="mt-4">
                        <a href="<?= htmlspecialchars(sol_url("rent/rentcatalog.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-secondary">Back to instruments</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
