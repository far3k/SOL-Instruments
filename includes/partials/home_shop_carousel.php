<?php

declare(strict_types=1);

/** @var list<array<string,mixed>> $sol_home_products */
/** @var string $sol_home_mode */
/** @var array<int, true> $sol_home_wish_members */

$sol_home_products = $sol_home_products ?? [];
$sol_home_mode = $sol_home_mode ?? "guest";
$sol_home_wish_members = $sol_home_wish_members ?? [];

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, "UTF-8");
$shopBrowse = sol_url($sol_home_mode === "user" ? "shop/catalog.php" : "login.php");

if (!function_exists("sol_render_wishlist_fab")) {
    require_once dirname(__DIR__) . "/partials/wishlist_fab.php";
}

?>
<section class="container pb-4 pb-lg-5" style="max-width: 1140px;">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <h2 class="h4 text-dark mb-0">Accessories</h2>
        <a href="<?= $h($shopBrowse) ?>" class="btn btn-sm btn-outline-secondary rounded-pill"><?= $sol_home_mode === "user" ? "Full shop" : "Browse after login" ?></a>
    </div>

    <?php if ($sol_home_products === []): ?>
        <p class="text-muted small mb-0">No products listed yet.</p>
    <?php else: ?>
        <div class="sol-shop-carousel position-relative">
            <button type="button" class="sol-shop-carousel-nav sol-shop-carousel-prev btn rounded-0 border-0 shadow-sm" aria-label="Scroll left" data-sol-scroll="#solHomeShopTrack" data-sol-dir="-1">
                <i class="bi bi-chevron-left fs-5"></i>
            </button>
            <button type="button" class="sol-shop-carousel-nav sol-shop-carousel-next btn rounded-0 border-0 shadow-sm" aria-label="Scroll right" data-sol-scroll="#solHomeShopTrack" data-sol-dir="1">
                <i class="bi bi-chevron-right fs-5"></i>
            </button>
            <div class="sol-shop-carousel-clip overflow-hidden">
                <div class="sol-shop-carousel-track d-flex gap-3 pb-2" id="solHomeShopTrack">
                    <?php foreach ($sol_home_products as $product): ?>
                        <?php
                        $pid = (int)($product["id"] ?? 0);
                        $pUrl = $sol_home_mode === "user"
                            ? sol_url("shop/shopItems_details.php?id=" . $pid)
                            : sol_url("login.php");
                        $inWish = isset($sol_home_wish_members[$pid]);
                        ?>
                        <div class="sol-shop-carousel-card flex-shrink-0">
                            <div class="card border-0 shadow-sm h-100 sol-shop-product-card">
                                <div class="sol-catalog-card-inner position-relative">
                                    <div class="sol-square-product-media">
                                        <img src="<?= $h(sol_url("pictures/" . ($product["picture"] ?? "product.jpg"))) ?>" class="card-img-top sol-square-product-img" alt="">
                                    </div>
                                    <?php if ($sol_home_mode === "user"): ?>
                                        <div class="sol-catalog-wish-fab">
                                            <?php sol_render_wishlist_fab("product", $pid, $inWish); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="sol-shop-card-hover position-absolute bottom-0 start-0 end-0">
                                        <a href="<?= $h($pUrl) ?>" class="btn btn-dark w-100 rounded-0 py-2 small text-uppercase fw-semibold">Quick view</a>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column pt-3">
                                    <h3 class="h6 card-title mb-1"><?= $h((string)($product["name"] ?? "")) ?></h3>
                                    <p class="text-muted small mb-2">€<?= $h((string)($product["price"] ?? "")) ?></p>
                                    <?php if ($sol_home_mode === "user"): ?>
                                        <form method="post" class="mt-auto sol-ajax-cart">
                                            <?= sol_csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $pid ?>">
                                            <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm w-100">Add to cart</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="<?= $h(sol_url("login.php")) ?>" class="btn btn-outline-primary btn-sm w-100 mt-auto">Log in to shop</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>
