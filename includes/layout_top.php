<?php

declare(strict_types=1);

# Opens HTML shell, navbar, counts; blocks suspended customers before content.

if (!function_exists("sol_url")) {
    require_once __DIR__ . "/app.php";
}

$page_title = $page_title ?? "SOL";
$nav_role = $nav_role ?? sol_nav_role();
$active_nav = $active_nav ?? "";
$body_class = $body_class ?? "sol-body";
$extra_head = $extra_head ?? "";

$uidNav = sol_current_uid();
$userPictureNav = "avatar.png";
$displayNameNav = "User";

if (isset($connect) && $connect instanceof mysqli && isset($_SESSION["user"]) && !isset($_SESSION["adm"]) && $uidNav > 0) {
    if (sol_user_account_blocked($connect, $uidNav)) {
        unset($_SESSION["user"], $_SESSION["uid"], $_SESSION["shop_cart"], $_SESSION["rent_cart"]);
        $_SESSION["flash_login"] = "Your account has been suspended. If you think this is a mistake, contact support.";
        header("Location: " . sol_url("login.php"));
        exit;
    }
}

if ($uidNav > 0 && isset($connect) && $connect instanceof mysqli) {
    $st = $connect->prepare("SELECT first_name, last_name, picture FROM users WHERE id = ? LIMIT 1");
    if ($st) {
        $st->bind_param("i", $uidNav);
        $st->execute();
        $ur = $st->get_result()->fetch_assoc();
        $st->close();
        if ($ur) {
            $userPictureNav = $ur["picture"] ?? "avatar.png";
            $fn = trim((string)($ur["first_name"] ?? ""));
            $ln = trim((string)($ur["last_name"] ?? ""));
            $displayNameNav = trim($fn . " " . $ln) ?: ($_SESSION["user"] ?? "User");
        }
    }
}

$shopCount = sol_shop_cart_count();
$rentCount = sol_rent_cart_count();
# Wishlist badge only for customers — admins do not use wishlist in the UI.
$wishCount = ($uidNav > 0 && isset($connect) && $nav_role !== "admin")
    ? sol_wishlist_count($connect, $uidNav)
    : 0;

/** @var array<string,int>|null $adminNavCounts — set for admin layout (navbar + dashboard). */
$adminNavCounts = null;
if ($nav_role === "admin" && isset($connect) && $connect instanceof mysqli) {
    $adminNavCounts = sol_admin_nav_counts($connect);
}

/** @var array<string, mixed>|null $solMiniCart — shop/rent lines for header mini-cart (customers only). */
$solMiniCart = null;
if ($nav_role === "user" && isset($connect) && $connect instanceof mysqli) {
    $solMiniCart = sol_mini_cart_payload($connect);
}

$sol_body_attrs = "";
if ($nav_role === "user") {
    $sol_body_attrs =
        ' data-sol-nav-live="1"'
        . ' data-sol-nav-counts-url="' . htmlspecialchars(sol_url_abs("api/nav_counts.php"), ENT_QUOTES, "UTF-8") . '"'
        . ' data-sol-cart-add-url="' . htmlspecialchars(sol_url_abs("api/cart_add.php"), ENT_QUOTES, "UTF-8") . '"'
        . ' data-sol-cart-delta-url="' . htmlspecialchars(sol_url_abs("api/cart_qty_delta.php"), ENT_QUOTES, "UTF-8") . '"'
        . ' data-sol-wishlist-url="' . htmlspecialchars(sol_url_abs("account/wishlist.php"), ENT_QUOTES, "UTF-8") . '"';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, "UTF-8") ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars(sol_url("assets/css/main.css"), ENT_QUOTES, "UTF-8") ?>" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <?= $extra_head ?>
</head>
<body class="<?= htmlspecialchars($body_class, ENT_QUOTES, "UTF-8") ?>"<?= $sol_body_attrs ?>>
<?php require __DIR__ . "/partials/navbar.php"; ?>
