<?php

declare(strict_types=1);

/** @var string $sol_home_mode guest|user */
/** @var string $sol_home_welcome_name */
/** @var list<array<string,mixed>> $sol_home_slides from DB; empty = use built-in fallbacks */

$sol_home_mode = $sol_home_mode ?? "guest";
$sol_home_welcome_name = $sol_home_welcome_name ?? "";
$sol_home_slides = $sol_home_slides ?? [];

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, "UTF-8");

function sol_home_slide_btn_href(string $u): string
{
    $u = trim($u);
    if ($u === "") {
        return "#";
    }
    if (preg_match('#^https?://#i', $u)) {
        return $u;
    }

    return sol_url(ltrim($u, "/"));
}

$useDb = $sol_home_slides !== [];
$carouselId = "solHomeCarousel";

?>
<div class="container-fluid sol-home-slider-wrap px-0 mb-0">
    <div id="<?= $h($carouselId) ?>" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="6500">
        <?php if ($useDb): ?>
            <div class="carousel-indicators mb-0">
                <?php foreach ($sol_home_slides as $idx => $_s): ?>
                    <button type="button" data-bs-target="#<?= $h($carouselId) ?>" data-bs-slide-to="<?= (int)$idx ?>" <?= $idx === 0 ? 'class="active" aria-current="true"' : "" ?> aria-label="Slide <?= (int)($idx + 1) ?>"></button>
                <?php endforeach; ?>
            </div>
            <div class="carousel-inner">
                <?php foreach ($sol_home_slides as $idx => $slide): ?>
                    <?php
                    $bg = trim((string)($slide["background_image"] ?? ""));
                    $bgUrl = $bg !== "" ? sol_url("pictures/" . str_replace("\\", "/", $bg)) : "";
                    $ov = max(0, min(90, (int)($slide["overlay_pct"] ?? 45))) / 100;
                    $fadeClass = "sol-home-slide-fallback-" . (((int)$idx % 3) + 1);
                    ?>
                    <div class="carousel-item <?= $idx === 0 ? "active" : "" ?> sol-home-slide <?= $bgUrl !== "" ? "sol-home-slide--photo" : $fadeClass ?>" <?= $bgUrl !== "" ? 'style="background-image: url(\'' . $h($bgUrl) . '\');"' : "" ?>>
                        <div class="sol-home-slide-overlay" style="opacity: <?= $h((string)$ov) ?>;"></div>
                        <div class="sol-home-slide-inner">
                        <div class="sol-home-slide-content text-center text-white">
                            <?php if (($slide["heading"] ?? "") !== ""): ?>
                                <h2 class="h1 fw-bold mb-3"><?= nl2br($h((string)$slide["heading"])) ?></h2>
                            <?php endif; ?>
                            <?php if (($slide["subheading"] ?? "") !== ""): ?>
                                <p class="text-white-50 mb-4 mx-auto sol-home-slide-sub" style="max-width: 38rem;"><?= nl2br($h((string)$slide["subheading"])) ?></p>
                            <?php endif; ?>
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <?php
                                $l1 = trim((string)($slide["button1_label"] ?? ""));
                                $u1 = trim((string)($slide["button1_url"] ?? ""));
                                $l2 = trim((string)($slide["button2_label"] ?? ""));
                                $u2 = trim((string)($slide["button2_url"] ?? ""));
                                ?>
                                <?php if ($l1 !== "" && $u1 !== ""): ?>
                                    <a class="btn btn-light btn-sm rounded-pill px-4" href="<?= $h(sol_home_slide_btn_href($u1)) ?>"><?= $h($l1) ?></a>
                                <?php endif; ?>
                                <?php if ($l2 !== "" && $u2 !== ""): ?>
                                    <a class="btn btn-outline-light btn-sm rounded-pill px-4" href="<?= $h(sol_home_slide_btn_href($u2)) ?>"><?= $h($l2) ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="carousel-indicators mb-0">
                <button type="button" data-bs-target="#<?= $h($carouselId) ?>" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#<?= $h($carouselId) ?>" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#<?= $h($carouselId) ?>" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active sol-home-slide sol-home-slide-fallback-1">
                    <div class="sol-home-slide-overlay" style="opacity: 0.35;"></div>
                    <div class="sol-home-slide-inner">
                    <div class="sol-home-slide-content text-center text-white">
                        <?php if ($sol_home_mode === "user"): ?>
                            <p class="small text-white-50 text-uppercase mb-2 mb-md-3" style="letter-spacing: 0.12em;">Welcome back</p>
                            <h2 class="h1 fw-bold mb-3"><?= $h($sol_home_welcome_name !== "" ? $sol_home_welcome_name : "Member") ?></h2>
                            <p class="text-white-50 mb-4 mx-auto" style="max-width: 36rem;">Rent instruments, shop accessories, and manage carts from one place.</p>
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <a class="btn btn-light btn-sm rounded-pill px-4" href="<?= $h(sol_url("rent/rentcatalog.php")) ?>"><i class="bi bi-music-note-beamed me-1"></i> Instruments</a>
                                <a class="btn btn-outline-light btn-sm rounded-pill px-4" href="<?= $h(sol_url("shop/catalog.php")) ?>"><i class="bi bi-grid-3x3-gap me-1"></i> Accessories</a>
                                <a class="btn btn-outline-light btn-sm rounded-pill px-4" href="<?= $h(sol_url("account/wishlist.php")) ?>"><i class="bi bi-heart me-1"></i> Wishlist</a>
                                <a class="btn btn-outline-light btn-sm rounded-pill px-4" href="<?= $h(sol_url("account/profile.php")) ?>"><i class="bi bi-person me-1"></i> Profile</a>
                            </div>
                        <?php else: ?>
                            <h2 class="h1 fw-bold mb-3">Rent instruments before buying</h2>
                            <p class="text-white-50 mb-4 mx-auto" style="max-width: 36rem;">Explore the catalog, try gear for a short period, and shop accessories.</p>
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <a class="btn btn-light btn-sm rounded-pill px-4" href="<?= $h(sol_url("login.php")) ?>"><i class="bi bi-box-arrow-in-right me-1"></i> Log in</a>
                                <a class="btn btn-outline-light btn-sm rounded-pill px-4" href="<?= $h(sol_url("register.php")) ?>"><i class="bi bi-person-plus me-1"></i> Create account</a>
                                <a class="btn btn-outline-light btn-sm rounded-pill px-4" href="<?= $h(sol_url("faq.php")) ?>"><i class="bi bi-question-circle me-1"></i> FAQ</a>
                                <a class="btn btn-outline-light btn-sm rounded-pill px-4" href="<?= $h(sol_url("contact.php")) ?>"><i class="bi bi-envelope me-1"></i> Contact</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    </div>
                </div>
                <div class="carousel-item sol-home-slide sol-home-slide-fallback-2">
                    <div class="sol-home-slide-overlay" style="opacity: 0.35;"></div>
                    <div class="sol-home-slide-inner">
                    <div class="sol-home-slide-content text-center text-white">
                        <h2 class="h1 fw-bold mb-3">Quality instruments for rent</h2>
                        <p class="text-white-50 mb-4 mx-auto" style="max-width: 34rem;">Browse guitars, keys, strings, and more — pick dates and confirm in your rent cart.</p>
                        <a class="btn btn-light btn-sm rounded-pill px-4" href="<?= $h(sol_url($sol_home_mode === "user" ? "rent/rentcatalog.php" : "login.php")) ?>">
                            <?= $sol_home_mode === "user" ? "Open rent catalog" : "Sign in to browse rent" ?>
                        </a>
                    </div>
                    </div>
                </div>
                <div class="carousel-item sol-home-slide sol-home-slide-fallback-3">
                    <div class="sol-home-slide-overlay" style="opacity: 0.35;"></div>
                    <div class="sol-home-slide-inner">
                    <div class="sol-home-slide-content text-center text-white">
                        <h2 class="h1 fw-bold mb-3">Accessories &amp; essentials</h2>
                        <p class="text-white-50 mb-4 mx-auto" style="max-width: 34rem;">Cases, strings, stands, and more — shipped or pickup.</p>
                        <a class="btn btn-light btn-sm rounded-pill px-4" href="<?= $h(sol_url($sol_home_mode === "user" ? "shop/catalog.php" : "login.php")) ?>">
                            <?= $sol_home_mode === "user" ? "Shop accessories" : "Sign in to shop" ?>
                        </a>
                    </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#<?= $h($carouselId) ?>" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#<?= $h($carouselId) ?>" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</div>
