<?php

declare(strict_types=1);

# Top navigation partial: links depend on guest / user / admin and cart badges.

/** @var string $nav_role guest|auth|user|admin */
/** @var string $active_nav */
/** @var string $userPictureNav */
/** @var string $displayNameNav */
/** @var int $shopCount */
/** @var int $rentCount */
/** @var int $wishCount */
/** @var array<string,int>|null $adminNavCounts */

$homeUrl = sol_url("index.php");
$isGuest = $nav_role === "guest";
$isAuth = $nav_role === "auth";
$isUser = $nav_role === "user";
$isAdmin = $nav_role === "admin";

$brandHref = $isAdmin ? sol_url("admin/dashboard.php") : ($isGuest ? $homeUrl : sol_url("account/home.php"));
$brandLabel = $isAdmin ? "SOL Admin" : "SOL";
$collapseId = "solNavCollapse";

if ($isAdmin) {
    if (!isset($adminNavCounts) || !is_array($adminNavCounts)) {
        $adminNavCounts = array_fill_keys(
            [
                "pending_rentals", "unread_messages", "pending_orders", "blocked_users", "users_total",
                "products", "suppliers", "instruments_active", "instruments_inactive", "categories", "faq_entries",
            ],
            0
        );
    }
    $adminManageAttention = (int)$adminNavCounts["pending_rentals"] + (int)$adminNavCounts["pending_orders"];
    $adminContentAttention = (int)$adminNavCounts["unread_messages"];
}

?>
<nav class="navbar navbar-expand-lg sol-navbar py-3" data-bs-theme="dark">
    <div class="container" style="max-width: 1140px;">
        <?php if ($isGuest || $isAuth): ?>
        <a class="navbar-brand d-flex align-items-center gap-2 gap-md-3 text-white text-decoration-none mb-0" href="<?= htmlspecialchars($brandHref, ENT_QUOTES, "UTF-8") ?>">
            <img src="<?= htmlspecialchars(sol_url("info/sol-logo.png"), ENT_QUOTES, "UTF-8") ?>" alt="SOL" class="sol-navbar-logo flex-shrink-0">
            <span class="d-flex flex-column lh-1">
                <span class="fw-semibold"><?= htmlspecialchars($brandLabel, ENT_QUOTES, "UTF-8") ?></span>
            </span>
        </a>
        <?php else: ?>
        <div class="d-flex align-items-center gap-2 gap-md-3 me-2 me-lg-3 flex-shrink-1 min-w-0 sol-navbar-brand-cluster">
            <a class="navbar-brand d-flex align-items-center gap-2 text-white text-decoration-none mb-0 py-0" href="<?= htmlspecialchars($brandHref, ENT_QUOTES, "UTF-8") ?>">
                <img src="<?= htmlspecialchars(sol_url("info/sol-logo.png"), ENT_QUOTES, "UTF-8") ?>" alt="SOL" class="sol-navbar-logo flex-shrink-0">
                <span class="fw-semibold d-none d-sm-inline"><?= htmlspecialchars($brandLabel, ENT_QUOTES, "UTF-8") ?></span>
            </a>
            <div class="dropdown">
                <a class="sol-navbar-user-toggle d-flex align-items-center gap-2 text-white text-decoration-none dropdown-toggle <?= ($isUser && $active_nav === "profile") ? "active" : "" ?>"
                   href="#" id="solNavbarUserMenu" role="button" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" aria-label="Account menu">
                    <img src="<?= htmlspecialchars(sol_url("pictures/" . $userPictureNav), ENT_QUOTES, "UTF-8") ?>"
                         width="36" height="36" alt=""
                         class="rounded-circle border border-light border-opacity-25 object-fit-cover flex-shrink-0">
                    <span class="d-flex flex-column lh-1 text-start min-w-0 d-none d-sm-flex">
                        <span class="sol-brand-sub text-truncate" style="max-width: 9.5rem"><?= htmlspecialchars($displayNameNav, ENT_QUOTES, "UTF-8") ?></span>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-start" aria-labelledby="solNavbarUserMenu">
                    <?php if ($isUser): ?>
                        <li><a class="dropdown-item" href="<?= htmlspecialchars(sol_url("account/profile.php"), ENT_QUOTES, "UTF-8") ?>">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                    <?php elseif ($isAdmin): ?>
                        <li><a class="dropdown-item" href="<?= htmlspecialchars(sol_url("admin/dashboard.php"), ENT_QUOTES, "UTF-8") ?>">Dashboard</a></li>
                        <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="<?= htmlspecialchars(sol_url("logout.php?logout"), ENT_QUOTES, "UTF-8") ?>">Log out</a></li>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <button class="navbar-toggler border-light text-white" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-controls="<?= $collapseId ?>" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="<?= $collapseId ?>">
            <ul class="navbar-nav ms-auto align-items-lg-center sol-nav-toolbar flex-wrap mt-3 mt-lg-0">

                <?php if ($isGuest): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-custom dropdown-toggle <?= ($active_nav === "faq" || $active_nav === "contact") ? "active" : "" ?>" href="#" role="button" data-bs-toggle="dropdown"><i class="bi bi-life-preserver me-1"></i> Help</a>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(sol_url("faq.php"), ENT_QUOTES, "UTF-8") ?>">FAQ</a></li>
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(sol_url("contact.php"), ENT_QUOTES, "UTF-8") ?>">Contact</a></li>
                        </ul>
                    </li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-light btn-sm rounded-pill px-3" href="<?= htmlspecialchars(sol_url("register.php"), ENT_QUOTES, "UTF-8") ?>"><i class="bi bi-person-plus me-1"></i> Register</a></li>
                    <li class="nav-item ms-lg-1"><a class="btn btn-outline-light btn-sm rounded-pill px-3" href="<?= htmlspecialchars(sol_url("login.php"), ENT_QUOTES, "UTF-8") ?>"><i class="bi bi-box-arrow-in-right me-1"></i> Log in</a></li>

                <?php elseif ($isAuth): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-custom dropdown-toggle <?= ($active_nav === "faq" || $active_nav === "contact") ? "active" : "" ?>" href="#" role="button" data-bs-toggle="dropdown"><i class="bi bi-life-preserver me-1"></i> Help</a>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(sol_url("faq.php"), ENT_QUOTES, "UTF-8") ?>">FAQ</a></li>
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(sol_url("contact.php"), ENT_QUOTES, "UTF-8") ?>">Contact</a></li>
                        </ul>
                    </li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-light btn-sm rounded-pill px-3" href="<?= htmlspecialchars(sol_url("register.php"), ENT_QUOTES, "UTF-8") ?>">Register</a></li>
                    <li class="nav-item ms-lg-1"><a class="btn btn-outline-light btn-sm rounded-pill px-3" href="<?= htmlspecialchars(sol_url("login.php"), ENT_QUOTES, "UTF-8") ?>">Log in</a></li>

                <?php elseif ($isUser): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-custom dropdown-toggle <?= ($active_nav === "faq" || $active_nav === "contact") ? "active" : "" ?>" href="#" role="button" data-bs-toggle="dropdown"><i class="bi bi-life-preserver me-1"></i> Help</a>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(sol_url("faq.php"), ENT_QUOTES, "UTF-8") ?>">FAQ</a></li>
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(sol_url("contact.php"), ENT_QUOTES, "UTF-8") ?>">Contact</a></li>
                        </ul>
                    </li>

                    <li class="nav-item ms-lg-1">
                        <a class="nav-link nav-link-custom d-inline-flex align-items-center rounded-pill border border-light border-opacity-25 px-3 py-1 <?= $active_nav === "catalog_rent" ? "active" : "" ?>" href="<?= htmlspecialchars(sol_url("rent/rentcatalog.php"), ENT_QUOTES, "UTF-8") ?>"><i class="bi bi-music-note-beamed me-1"></i> Instruments (RENT)</a>
                    </li>
                    <li class="nav-item ms-lg-1">
                        <a class="nav-link nav-link-custom d-inline-flex align-items-center rounded-pill border border-light border-opacity-25 px-3 py-1 <?= $active_nav === "catalog_shop" ? "active" : "" ?>" href="<?= htmlspecialchars(sol_url("shop/catalog.php"), ENT_QUOTES, "UTF-8") ?>"><i class="bi bi-grid-3x3-gap me-1"></i> Accessories (SHOP)</a>
                    </li>

                    <?php
                    $myCartsActive = ($active_nav === "wishlist" || str_starts_with($active_nav, "shop") || str_starts_with($active_nav, "rent"));
                    $solMiniCartForNav = (isset($solMiniCart) && is_array($solMiniCart)) ? $solMiniCart : sol_mini_cart_payload($connect);
                    ?>
                    <li class="nav-item dropdown">
                        <a class="btn btn-outline-light btn-sm rounded-pill px-3 dropdown-toggle sol-nav-carts-toggle d-inline-flex align-items-center flex-wrap gap-1 <?= $myCartsActive ? "active" : "" ?>" href="#" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" title="Carts &amp; wishlist"><i class="bi bi-basket3"></i><span class="d-none d-xl-inline">My carts</span><span id="sol-nav-shop-count" class="badge sol-badge-cart-shop rounded-pill <?= $shopCount < 1 ? "d-none" : "" ?>"><?= (int)$shopCount ?></span><span id="sol-nav-rent-count" class="badge bg-success rounded-pill <?= $rentCount < 1 ? "d-none" : "" ?>"><?= (int)$rentCount ?></span><span id="sol-nav-wish-count" class="badge bg-danger rounded-pill <?= $wishCount < 1 ? "d-none" : "" ?>"><?= (int)$wishCount ?></span></a>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end sol-mini-cart-menu p-0 overflow-hidden" style="min-width: 288px; max-width: 320px;">
                            <li class="px-0 py-0">
                                <div id="sol-mini-cart-root"><?= sol_mini_cart_render_html($solMiniCartForNav, sol_csrf_token()) ?></div>
                            </li>
                        </ul>
                    </li>

                <?php elseif ($isAdmin): ?>

                    <li class="nav-item">
                        <a class="nav-link nav-link-custom d-flex align-items-center flex-wrap gap-1 <?= $active_nav === "users_admin" ? "active" : "" ?>" href="<?= htmlspecialchars(sol_url("admin/users_admin.php"), ENT_QUOTES, "UTF-8") ?>"><i class="bi bi-people me-1"></i><span>User accounts</span><span class="badge bg-secondary rounded-pill"><?= (int)$adminNavCounts["users_total"] ?></span><?php if ((int)$adminNavCounts["blocked_users"] > 0): ?><span class="badge bg-dark rounded-pill" title="Suspended accounts"><?= (int)$adminNavCounts["blocked_users"] ?> blocked</span><?php endif; ?></a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-custom dropdown-toggle d-inline-flex align-items-center flex-wrap gap-1 <?= $active_nav === "rentals_admin" || $active_nav === "shop_orders_admin" || $active_nav === "instruments_admin" || $active_nav === "categories_admin" || str_starts_with($active_nav, "admin_catalog") ? "active" : "" ?>" href="#" role="button" data-bs-toggle="dropdown"><i class="bi bi-sliders me-1"></i> Manage<?php if ($adminManageAttention > 0): ?><span class="badge bg-warning text-dark rounded-pill"><?= (int)$adminManageAttention ?></span><?php endif; ?></a>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" style="min-width: 280px;">
                            <li><h6 class="dropdown-header text-uppercase small text-muted mb-0">Rent</h6></li>
                            <li><a class="dropdown-item d-flex justify-content-between align-items-center gap-2" href="<?= htmlspecialchars(sol_url("admin/rentals_admin.php"), ENT_QUOTES, "UTF-8") ?>"><span>Rental queue</span><span class="badge <?= (int)$adminNavCounts["pending_rentals"] > 0 ? "bg-warning text-dark" : "bg-secondary" ?> rounded-pill"><?= (int)$adminNavCounts["pending_rentals"] ?></span></a></li>
                            <li><a class="dropdown-item d-flex justify-content-between align-items-center gap-2" href="<?= htmlspecialchars(sol_url("admin/instruments_admin.php"), ENT_QUOTES, "UTF-8") ?>"><span>Instruments</span><span class="badge bg-secondary rounded-pill small"><?= (int)$adminNavCounts["instruments_active"] ?> / <?= (int)$adminNavCounts["instruments_inactive"] ?></span></a></li>
                            <li><a class="dropdown-item d-flex justify-content-between align-items-center gap-2" href="<?= htmlspecialchars(sol_url("admin/categories_admin.php"), ENT_QUOTES, "UTF-8") ?>"><span>Categories</span><span class="badge bg-secondary rounded-pill"><?= (int)$adminNavCounts["categories"] ?></span></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header text-uppercase small text-muted mb-0">Shop</h6></li>
                            <li><a class="dropdown-item d-flex justify-content-between align-items-center gap-2" href="<?= htmlspecialchars(sol_url("admin/shop_orders_admin.php"), ENT_QUOTES, "UTF-8") ?>"><span>Shop orders</span><span class="badge <?= (int)$adminNavCounts["pending_orders"] > 0 ? "bg-warning text-dark" : "bg-secondary" ?> rounded-pill"><?= (int)$adminNavCounts["pending_orders"] ?> pending</span></a></li>
                            <li><a class="dropdown-item d-flex justify-content-between align-items-center gap-2" href="<?= htmlspecialchars(sol_url("products/index.php"), ENT_QUOTES, "UTF-8") ?>"><span>Products</span><span class="badge bg-secondary rounded-pill"><?= (int)$adminNavCounts["products"] ?></span></a></li>
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(sol_url("products/create.php"), ENT_QUOTES, "UTF-8") ?>">Add product</a></li>
                            <li><a class="dropdown-item d-flex justify-content-between align-items-center gap-2" href="<?= htmlspecialchars(sol_url("products/suppliers.php"), ENT_QUOTES, "UTF-8") ?>"><span>Suppliers</span><span class="badge bg-secondary rounded-pill"><?= (int)$adminNavCounts["suppliers"] ?></span></a></li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-custom dropdown-toggle d-inline-flex align-items-center flex-wrap gap-1 <?= $active_nav === "faq_admin" || $active_nav === "contact_messages" ? "active" : "" ?>" href="#" role="button" data-bs-toggle="dropdown"><i class="bi bi-chat-left-text me-1"></i> Content<?php if ($adminContentAttention > 0): ?><span class="badge bg-danger rounded-pill"><?= (int)$adminContentAttention ?></span><?php endif; ?></a>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" style="min-width: 260px;">
                            <li><a class="dropdown-item d-flex justify-content-between align-items-center gap-2" href="<?= htmlspecialchars(sol_url("admin/contact_messages.php"), ENT_QUOTES, "UTF-8") ?>"><span>Messages</span><span class="badge <?= (int)$adminNavCounts["unread_messages"] > 0 ? "bg-danger" : "bg-secondary" ?> rounded-pill"><?= (int)$adminNavCounts["unread_messages"] ?> unread</span></a></li>
                            <li><a class="dropdown-item" href="<?= htmlspecialchars(sol_url("admin/home_slides_admin.php"), ENT_QUOTES, "UTF-8") ?>">Home slides</a></li>
                            <li><a class="dropdown-item d-flex justify-content-between align-items-center gap-2" href="<?= htmlspecialchars(sol_url("admin/faq_admin.php"), ENT_QUOTES, "UTF-8") ?>"><span>FAQ admin</span><span class="badge bg-secondary rounded-pill"><?= (int)$adminNavCounts["faq_entries"] ?></span></a></li>
                        </ul>
                    </li>

                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>
