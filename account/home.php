<?php

declare(strict_types=1);

# Customer home: same layout as public index — slider + rent list + shop + footer.

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_user();

$uid = sol_current_uid();
$displayName = htmlspecialchars((string)($_SESSION["user"] ?? "User"), ENT_QUOTES, "UTF-8");
$sol_home_welcome_name = $displayName;
$sol_slide_plain_name = (string)($_SESSION["user"] ?? "Member");

if ($uid > 0) {
    $st = $connect->prepare("SELECT first_name, last_name, picture FROM users WHERE id = ? LIMIT 1");
    if ($st) {
        $st->bind_param("i", $uid);
        $st->execute();
        $u = $st->get_result()->fetch_assoc();
        $st->close();
        if ($u) {
            $fn = trim((string)($u["first_name"] ?? ""));
            $ln = trim((string)($u["last_name"] ?? ""));
            $fullName = trim($fn . " " . $ln);
            $sol_home_welcome_name = htmlspecialchars($fullName !== "" ? $fullName : (string)$displayName, ENT_QUOTES, "UTF-8");
            if ($fullName !== "") {
                $sol_slide_plain_name = $fullName;
            }
        }
    }
}

$sqlInstruments = "SELECT * FROM instruments WHERE is_active = 1 ORDER BY daily_price ASC LIMIT 12";
$resultInstruments = $connect->query($sqlInstruments);
$sol_home_instruments = $resultInstruments ? $resultInstruments->fetch_all(MYSQLI_ASSOC) : [];

$sqlProducts = "SELECT * FROM products ORDER BY price ASC LIMIT 12";
$resultProducts = $connect->query($sqlProducts);
$sol_home_products = $resultProducts ? $resultProducts->fetch_all(MYSQLI_ASSOC) : [];

$sol_home_slides = sol_home_slides_for_viewer($connect, "user", $sol_slide_plain_name);
$sol_home_wish_members = [];
$sol_home_wish_instruments = [];
if ($uid > 0 && $sol_home_products !== []) {
    $pids = array_values(array_filter(array_map(static fn ($p) => (int)($p["id"] ?? 0), $sol_home_products)));
    if ($pids !== []) {
        $sol_home_wish_members = sol_wishlist_membership($connect, $uid, "product", $pids);
    }
}
if ($uid > 0 && $sol_home_instruments !== []) {
    $iids = array_values(array_filter(array_map(static fn ($i) => (int)($i["id"] ?? 0), $sol_home_instruments)));
    if ($iids !== []) {
        $sol_home_wish_instruments = sol_wishlist_membership($connect, $uid, "instrument", $iids);
    }
}

$page_title = "Home";
$nav_role = "user";
$active_nav = "home";
$sol_home_mode = "user";

$layout_extra_scripts = '<script src="' . htmlspecialchars(sol_url("assets/js/home_shop_carousel.js"), ENT_QUOTES, "UTF-8") . '" defer></script>';

require_once dirname(__DIR__) . "/includes/layout_top.php";

require dirname(__DIR__) . "/includes/partials/home_slider.php";
require dirname(__DIR__) . "/includes/partials/home_rent_shop.php";
require dirname(__DIR__) . "/includes/partials/home_footer_strip.php";

require_once dirname(__DIR__) . "/includes/layout_bottom.php";
