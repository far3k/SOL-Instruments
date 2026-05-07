<?php

declare(strict_types=1);

/** @var string $sol_home_mode guest|user */

$sol_home_mode = $sol_home_mode ?? "guest";

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, "UTF-8");

?>
<footer class="sol-home-footer border-top mt-2">
    <div class="container py-4 py-lg-5" style="max-width: 1140px;">
        <p class="small text-uppercase text-secondary mb-3 text-center" style="letter-spacing: 0.08em;">Quick links</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a class="btn btn-outline-secondary btn-sm rounded-pill" href="<?= $h(sol_url("faq.php")) ?>"><i class="bi bi-question-circle me-1"></i> FAQ</a>
            <a class="btn btn-outline-secondary btn-sm rounded-pill" href="<?= $h(sol_url("contact.php")) ?>"><i class="bi bi-envelope me-1"></i> Contact</a>
            <a class="btn btn-outline-secondary btn-sm rounded-pill" href="<?= $h(sol_url($sol_home_mode === "user" ? "rent/rentcatalog.php" : "login.php")) ?>"><i class="bi bi-music-note-beamed me-1"></i> Rent catalog</a>
            <a class="btn btn-outline-secondary btn-sm rounded-pill" href="<?= $h(sol_url($sol_home_mode === "user" ? "shop/catalog.php" : "login.php")) ?>"><i class="bi bi-grid-3x3-gap me-1"></i> Shop</a>
            <?php if ($sol_home_mode === "user"): ?>
                <a class="btn btn-outline-secondary btn-sm rounded-pill" href="<?= $h(sol_url("account/wishlist.php")) ?>"><i class="bi bi-heart me-1"></i> Wishlist</a>
                <a class="btn btn-outline-secondary btn-sm rounded-pill" href="<?= $h(sol_url("rent/rent_cart.php")) ?>"><i class="bi bi-calendar-check me-1"></i> Rent cart</a>
                <a class="btn btn-outline-secondary btn-sm rounded-pill" href="<?= $h(sol_url("shop/cart.php")) ?>"><i class="bi bi-basket3 me-1"></i> Shop cart</a>
                <a class="btn btn-outline-secondary btn-sm rounded-pill" href="<?= $h(sol_url("account/profile.php")) ?>"><i class="bi bi-person me-1"></i> Profile</a>
            <?php else: ?>
                <a class="btn btn-outline-dark btn-sm rounded-pill" href="<?= $h(sol_url("login.php")) ?>"><i class="bi bi-box-arrow-in-right me-1"></i> Log in</a>
                <a class="btn btn-outline-dark btn-sm rounded-pill" href="<?= $h(sol_url("register.php")) ?>"><i class="bi bi-person-plus me-1"></i> Register</a>
            <?php endif; ?>
        </div>
        <p class="small text-muted text-center mb-0 mt-4">&copy; <?= (int) date("Y") ?> SOL</p>
    </div>
</footer>
