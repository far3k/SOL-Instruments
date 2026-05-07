<?php

declare(strict_types=1);

# Public landing: guests — slider + rent list + shop teaser + footer strip.

require_once __DIR__ . "/includes/app.php";

if (isset($_SESSION["adm"])) {
    header("Location: " . sol_url("admin/dashboard.php"));
    exit;
}

if (isset($_SESSION["user"])) {
    header("Location: " . sol_url("account/home.php"));
    exit;
}

$sqlInstruments = "SELECT * FROM instruments WHERE is_active = 1 ORDER BY daily_price ASC LIMIT 12";
$resultInstruments = $connect->query($sqlInstruments);
$sol_home_instruments = $resultInstruments ? $resultInstruments->fetch_all(MYSQLI_ASSOC) : [];

$sqlProducts = "SELECT * FROM products ORDER BY price ASC LIMIT 12";
$resultProducts = $connect->query($sqlProducts);
$sol_home_products = $resultProducts ? $resultProducts->fetch_all(MYSQLI_ASSOC) : [];

$sol_home_slides = sol_home_slides_for_viewer($connect, "guest", "");
$sol_home_wish_members = [];
$sol_home_wish_instruments = [];

$page_title = "SOL — Welcome";
$nav_role = "guest";
$active_nav = "home";
$sol_home_mode = "guest";

$layout_extra_scripts = '<script src="' . htmlspecialchars(sol_url("assets/js/home_shop_carousel.js"), ENT_QUOTES, "UTF-8") . '" defer></script>';

require_once __DIR__ . "/includes/layout_top.php";

require __DIR__ . "/includes/partials/home_slider.php";
require __DIR__ . "/includes/partials/home_rent_shop.php";
require __DIR__ . "/includes/partials/home_footer_strip.php";

require_once __DIR__ . "/includes/layout_bottom.php";
