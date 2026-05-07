<?php

/** @var array<int, array<string, mixed>> $shopLines */
/** @var array<int, array<string, mixed>> $rentLines */
/** @var float $shopSub */
/** @var float $rentEst */
/** @var int $wishCount */
/** @var string $csrfToken */

$h = static function (string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
};

?>
<input type="hidden" id="sol-mini-cart-csrf" value="<?= $h($csrfToken) ?>">
<div class="sol-mini-cart-inner px-2 py-2">
    <?php if ($shopLines !== []): ?>
        <p class="small fw-semibold text-uppercase text-white-50 mb-2 mb-0" style="font-size: 0.65rem; letter-spacing: 0.06em;">Shop</p>
        <?php foreach ($shopLines as $line): ?>
            <?php
            $lid = (int)$line["id"];
            $qty = (int)$line["qty"];
            $nm = $h((string)$line["name"]);
            $pic = $h((string)$line["picture"]);
            $unit = (float)$line["unit"];
            $ln = round($unit * $qty, 2);
            ?>
            <div class="sol-mini-line d-flex gap-2 align-items-start pb-2 mb-2 border-bottom border-secondary border-opacity-25">
                <img src="<?= $h(sol_url("pictures/" . $pic)) ?>" alt="" class="sol-mini-thumb flex-shrink-0 rounded" width="44" height="44">
                <div class="min-w-0 flex-grow-1">
                    <div class="small fw-semibold text-white text-truncate" title="<?= $nm ?>"><?= $nm ?></div>
                    <div class="d-flex align-items-center justify-content-between gap-1 mt-1">
                        <div class="btn-group btn-group-sm sol-mini-stepper" role="group" aria-label="Quantity">
                            <button type="button" class="btn btn-outline-secondary py-0 px-2 sol-mini-step" data-bucket="shop" data-id="<?= $lid ?>" data-delta="-1" title="Decrease">−</button>
                            <span class="btn btn-outline-secondary py-0 px-2 disabled sol-mini-qty"><?= $qty ?></span>
                            <button type="button" class="btn btn-outline-secondary py-0 px-2 sol-mini-step" data-bucket="shop" data-id="<?= $lid ?>" data-delta="1" title="Increase">+</button>
                        </div>
                        <span class="small fw-semibold text-white text-nowrap">€<?= $h((string)round($ln, 2)) ?></span>
                    </div>
                    <button type="button" class="btn btn-link btn-sm link-light text-decoration-underline p-0 sol-mini-remove" data-bucket="shop" data-id="<?= $lid ?>" style="font-size: 0.7rem;">Remove</button>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="d-flex justify-content-between align-items-baseline small mb-2">
            <span class="text-white-50">Subtotal</span>
            <span class="fw-bold text-white sol-mini-shop-sub">€<?= $h((string)round($shopSub, 2)) ?></span>
        </div>

        <div class="d-grid mb-3">
            <a class="btn sol-btn-checkout-cta btn-sm text-uppercase fw-semibold py-2" style="font-size: 0.7rem; letter-spacing: 0.04em;" href="<?= $h(sol_url("shop/cart.php")) ?>">Continue to checkout</a>
        </div>
    <?php else: ?>
        <p class="small text-white-50 mb-2">Shop cart is empty.</p>
        <a class="btn btn-outline-light btn-sm w-100 mb-3" href="<?= $h(sol_url("shop/catalog.php")) ?>">Browse accessories</a>
    <?php endif; ?>

    <?php if ($rentLines !== []): ?>
        <p class="small fw-semibold text-uppercase text-white-50 mb-2 mb-0" style="font-size: 0.65rem; letter-spacing: 0.06em;">Rent</p>
        <?php foreach ($rentLines as $line): ?>
            <?php
            $lid = (int)$line["id"];
            $nm = $h((string)$line["name"]);
            $pic = $h((string)$line["picture"]);
            $day = (float)($line["unit_day"] ?? 0);
            $ln = round($day, 2);
            ?>
            <div class="sol-mini-line d-flex gap-2 align-items-start pb-2 mb-2 border-bottom border-secondary border-opacity-25">
                <img src="<?= $h(sol_url("pictures/" . $pic)) ?>" alt="" class="sol-mini-thumb flex-shrink-0 rounded" width="44" height="44">
                <div class="min-w-0 flex-grow-1">
                    <div class="small fw-semibold text-white text-truncate" title="<?= $nm ?>"><?= $nm ?></div>
                    <div class="d-flex align-items-center justify-content-between gap-1 mt-1">
                        <span class="small text-white-50">€<?= $h((string)round($ln, 2)) ?>/day</span>
                        <button type="button" class="btn btn-link btn-sm link-light text-decoration-underline p-0 sol-mini-remove" data-bucket="rent" data-id="<?= $lid ?>" style="font-size: 0.65rem;">Remove</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="d-flex justify-content-between align-items-baseline small mb-2">
            <span class="text-white-50">Rent (est.)</span>
            <span class="fw-bold text-white">€<?= $h((string)round($rentEst, 2)) ?> <span class="fw-normal text-white-50">/ day total</span></span>
        </div>
        <div class="d-grid gap-1 mb-2">
            <a class="btn sol-btn-checkout-cta btn-sm fw-semibold py-2" style="font-size: 0.65rem; letter-spacing: 0.02em;" href="<?= $h(sol_url("rent/rent_cart.php")) ?>">Set rental dates &amp; checkout</a>
        </div>
    <?php endif; ?>

    <div class="border-top pt-2 mt-1">
        <a class="dropdown-item rounded px-2 py-2 small d-flex align-items-center gap-2 sol-mini-cart-footer-link" href="<?= $h(sol_url("account/wishlist.php")) ?>">
            <span class="sol-mini-nav-icon sol-mini-nav-icon--wishlist" aria-hidden="true"><i class="bi bi-heart-fill"></i></span>
            <span class="flex-grow-1">Wishlist</span>
            <span class="badge bg-danger rounded-pill <?= $wishCount < 1 ? "d-none" : "" ?>"><?= (int)$wishCount ?></span>
        </a>
        <a class="dropdown-item rounded px-2 py-2 small d-flex align-items-center gap-2 sol-mini-cart-footer-link" href="<?= $h(sol_url("shop/my_orders.php")) ?>">
            <span class="sol-mini-nav-icon sol-mini-nav-icon--orders" aria-hidden="true"><i class="bi bi-bag-check"></i></span>
            <span>My orders</span>
        </a>
        <a class="dropdown-item rounded px-2 py-2 small d-flex align-items-center gap-2 sol-mini-cart-footer-link" href="<?= $h(sol_url("rent/my_rent_requests.php")) ?>">
            <span class="sol-mini-nav-icon sol-mini-nav-icon--rent" aria-hidden="true"><i class="bi bi-calendar2-event"></i></span>
            <span>My rent requests</span>
        </a>
    </div>
</div>
