<?php

declare(strict_types=1);

# Small circular heart control for list/grid cards (customer accounts only).

/**
 * @param 'product'|'instrument' $itemType
 */
function sol_render_wishlist_fab(string $itemType, int $itemId, bool $inWishlist): void
{
    if (!isset($_SESSION["user"]) || isset($_SESSION["adm"]) || $itemId < 1) {
        return;
    }
    if ($itemType !== "product" && $itemType !== "instrument") {
        return;
    }
    $dataWish = $itemType === "instrument" ? "instrument" : "product";
    ?>
    <div class="sol-wishlist-add-wrap sol-wishlist-fab">
        <?php if ($inWishlist): ?>
            <form method="post" class="sol-ajax-cart" data-sol-wish-type="<?= htmlspecialchars($dataWish, ENT_QUOTES, "UTF-8") ?>">
                <?= sol_csrf_field() ?>
                <input type="hidden" name="id" value="<?= $itemId ?>">
                <button type="submit" name="remove_from_wishlist" value="1" class="btn rounded-circle sol-wishlist-fab-btn sol-wishlist-fab-btn--saved shadow-sm border-0" title="Remove from wishlist" aria-label="Remove from wishlist"><i class="bi bi-heart-fill"></i></button>
            </form>
        <?php else: ?>
            <form method="post" class="sol-ajax-cart" data-sol-wish-type="<?= htmlspecialchars($dataWish, ENT_QUOTES, "UTF-8") ?>">
                <?= sol_csrf_field() ?>
                <input type="hidden" name="id" value="<?= $itemId ?>">
                <button type="submit" name="add_to_wishlist" value="1" class="btn btn-light border rounded-circle sol-wishlist-fab-btn shadow-sm" title="Add to wishlist" aria-label="Add to wishlist"><i class="bi bi-heart text-danger"></i></button>
            </form>
        <?php endif; ?>
    </div>
    <?php
}
