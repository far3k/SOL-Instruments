<?php

declare(strict_types=1);

# JSON counts for shop cart, rent cart, wishlist (navbar live refresh).

require_once dirname(__DIR__) . "/includes/app.php";

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["user"]) && !isset($_SESSION["adm"])) {
    http_response_code(401);
    echo json_encode(["ok" => false, "shop" => 0, "rent" => 0, "wish" => 0], JSON_THROW_ON_ERROR);
    exit;
}

/** @var mysqli $connect */
sol_ensure_session_carts($connect);
$uid = sol_current_uid();
$shop = sol_shop_cart_count();
$rent = sol_rent_cart_count();
$wish = ($uid > 0 && !isset($_SESSION["adm"])) ? sol_wishlist_count($connect, $uid) : 0;

$out = [
    "ok" => true,
    "shop" => $shop,
    "rent" => $rent,
    "wish" => $wish,
];

if (isset($_GET["mini"]) && $_GET["mini"] === "1" && isset($_SESSION["user"]) && !isset($_SESSION["adm"])) {
    $payload = sol_mini_cart_payload($connect);
    $out["mini_html"] = sol_mini_cart_render_html($payload, sol_csrf_token());
}

echo json_encode($out, JSON_THROW_ON_ERROR);
