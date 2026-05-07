<?php

declare(strict_types=1);

# Product grid for accessories; POST add_to_cart with CSRF.

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_login();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_to_cart"])) {
    if (!sol_csrf_verify()) {
        $_SESSION["flash_error"] = "Security check failed.";
    } else {
        $productId = (int)($_POST["id"] ?? 0);
        if ($productId > 0) {
            $_SESSION["shop_cart"][$productId] = ($_SESSION["shop_cart"][$productId] ?? 0) + 1;
        }
    }
    header("Location: " . sol_url("shop/catalog.php"));
    exit;
}

$searchQuery = trim($_GET["q"] ?? "");
$sortOption = $_GET["sort"] ?? "price_asc";
if (!in_array($sortOption, ["price_asc", "price_desc"], true)) {
    $sortOption = "price_asc";
}

$orderSql = $sortOption === "price_desc" ? "ORDER BY price DESC" : "ORDER BY price ASC";
if ($searchQuery !== "") {
    $st = $connect->prepare("SELECT * FROM products WHERE name LIKE ? " . $orderSql);
    $like = "%" . $searchQuery . "%";
    $st->bind_param("s", $like);
    $st->execute();
    $products = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
} else {
    $result = $connect->query("SELECT * FROM products " . $orderSql);
    $products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

require_once dirname(__DIR__) . "/includes/partials/wishlist_fab.php";

$wishMembers = [];
if (isset($_SESSION["user"]) && !isset($_SESSION["adm"]) && $products !== []) {
    $uidW = sol_current_uid();
    $pids = array_map(static fn (array $p): int => (int)($p["id"] ?? 0), $products);
    $wishMembers = sol_wishlist_membership($connect, $uidW, "product", $pids);
}

$flash = $_SESSION["flash_error"] ?? "";
unset($_SESSION["flash_error"]);

$page_title = "Accessories catalog";
$nav_role = isset($_SESSION["adm"]) ? "admin" : "user";
$active_nav = "catalog_shop";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-4" style="max-width: 1140px;">
    <?php if ($flash !== ""): ?>
        <div class="alert alert-warning border-0"><?= htmlspecialchars($flash, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <h1 class="h3 mb-4">Accessories</h1>

    <form class="row g-3 mb-4" method="get">
        <div class="col-md-6">
            <input class="form-control" name="q" placeholder="Search…" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, "UTF-8") ?>">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="sort">
                <option value="price_asc" <?= $sortOption === "price_asc" ? "selected" : "" ?>>Price low → high</option>
                <option value="price_desc" <?= $sortOption === "price_desc" ? "selected" : "" ?>>Price high → low</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100" type="submit">Apply</button>
        </div>
    </form>

    <div class="row g-4">
        <?php foreach ($products as $product): ?>
            <?php
            $pid = (int)$product["id"];
            $detailUrl = sol_url("shop/shopItems_details.php?id=" . $pid);
            ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 position-relative sol-card-shell sol-shop-product-card">
                    <div class="sol-catalog-card-inner">
                        <div class="sol-square-product-media">
                            <img src="<?= htmlspecialchars(sol_url("pictures/" . ($product["picture"] ?? "product.jpg")), ENT_QUOTES, "UTF-8") ?>" class="card-img-top sol-square-product-img" alt="">
                        </div>
                        <div class="sol-catalog-wish-fab">
                            <?php sol_render_wishlist_fab("product", $pid, isset($wishMembers[$pid])); ?>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column pt-3 pb-2">
                        <h3 class="h6 card-title mb-1"><?= htmlspecialchars($product["name"] ?? "", ENT_QUOTES, "UTF-8") ?></h3>
                        <p class="text-muted small mb-0">€<?= htmlspecialchars((string)($product["price"] ?? ""), ENT_QUOTES, "UTF-8") ?></p>
                    </div>
                    <div class="card-body border-top-0 pt-0 sol-catalog-card-actions">
                        <form method="post" class="sol-ajax-cart">
                            <?= sol_csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $pid ?>">
                            <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm w-100">Add to cart</button>
                        </form>
                    </div>
                    <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, "UTF-8") ?>" class="stretched-link"><span class="visually-hidden">View product details</span></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
