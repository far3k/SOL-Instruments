<?php

declare(strict_types=1);

# AJAX: adjust shop/rent cart qty (+/-) or remove line; returns counts + mini_cart HTML.

require_once dirname(__DIR__) . "/includes/app.php";

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["ok" => false], JSON_THROW_ON_ERROR);
    exit;
}

if (!isset($_SESSION["user"]) || isset($_SESSION["adm"])) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "auth"], JSON_THROW_ON_ERROR);
    exit;
}

if (!sol_csrf_verify()) {
    http_response_code(403);
    echo json_encode(["ok" => false, "error" => "csrf"], JSON_THROW_ON_ERROR);
    exit;
}

/** @var mysqli $connect */
sol_ensure_session_carts($connect);

$bucket = (string)($_POST["bucket"] ?? "");
$id = (int)($_POST["id"] ?? 0);
$remove = isset($_POST["remove"]);
$delta = (int)($_POST["delta"] ?? 0);

if ($id < 1 || !in_array($bucket, ["shop", "rent"], true)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "input"], JSON_THROW_ON_ERROR);
    exit;
}

$key = $bucket === "shop" ? "shop_cart" : "rent_cart";
if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
    $_SESSION[$key] = [];
}

if ($bucket === "rent") {
    if ($remove || $delta === -1) {
        unset($_SESSION[$key][$id]);
    } elseif ($delta === 1) {
        $_SESSION[$key][$id] = 1;
    } else {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "delta"], JSON_THROW_ON_ERROR);
        exit;
    }
} elseif ($remove) {
    unset($_SESSION[$key][$id]);
} else {
    if ($delta !== -1 && $delta !== 1) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "delta"], JSON_THROW_ON_ERROR);
        exit;
    }
    $cur = (int)($_SESSION[$key][$id] ?? 0);
    $cur += $delta;
    if ($cur < 1) {
        unset($_SESSION[$key][$id]);
    } else {
        $_SESSION[$key][$id] = $cur;
    }
}

$uid = sol_current_uid();
$payload = sol_mini_cart_payload($connect);

echo json_encode(
    [
        "ok" => true,
        "shop" => sol_shop_cart_count(),
        "rent" => sol_rent_cart_count(),
        "wish" => $uid > 0 ? sol_wishlist_count($connect, $uid) : 0,
        "mini_html" => sol_mini_cart_render_html($payload, sol_csrf_token()),
    ],
    JSON_THROW_ON_ERROR
);
