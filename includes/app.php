<?php

declare(strict_types=1);

# App kernel: session + mysqli, URL/CSRF helpers, carts, auth guards, passwords, lightweight schema checks.

if (!defined("SOL_ROOT")) {
    define("SOL_ROOT", dirname(__DIR__));
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once SOL_ROOT . "/db_connect.php";

# --- URL helpers: build correct relative hrefs from any subfolder ---

/**
 * Relative URL prefix from the current script directory to project root.
 * Example: "" from /index.php, "../" from /shop/cart.php, "../../" from /products/index.php
 */
function sol_base(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $rootReal = realpath(SOL_ROOT);
    $dir = realpath(dirname($_SERVER["SCRIPT_FILENAME"]));
    $base = "";

    if ($rootReal === false || $dir === false || $dir === $rootReal) {
        return $base;
    }

    while ($dir !== $rootReal && str_starts_with($dir, $rootReal)) {
        $base .= "../";
        $parent = dirname($dir);
        if ($parent === $dir) {
            break;
        }
        $dir = $parent;
    }

    return $base;
}

function sol_url(string $path): string
{
    return sol_base() . ltrim($path, "/");
}

/**
 * URL from site root (leading slash) for fetch() and absolute links.
 * Avoids broken relative resolution when the current path is unusual (aliases, rewrites).
 * Falls back to sol_url() if the project is not under DOCUMENT_ROOT.
 */
function sol_url_abs(string $path): string
{
    $path = ltrim(str_replace("\\", "/", $path), "/");
    $doc = isset($_SERVER["DOCUMENT_ROOT"]) ? realpath($_SERVER["DOCUMENT_ROOT"]) : false;
    $root = realpath(SOL_ROOT);
    if ($doc === false || $root === false) {
        return sol_url($path);
    }
    $docFs = str_replace("\\", "/", $doc);
    $rootFs = str_replace("\\", "/", $root);
    if (!str_starts_with($rootFs, rtrim($docFs, "/"))) {
        return sol_url($path);
    }
    $rel = (string) substr($rootFs, strlen(rtrim($docFs, "/")));
    $rel = trim(str_replace("\\", "/", $rel), "/");
    if ($rel === "") {
        return "/" . $path;
    }

    return "/" . $rel . "/" . $path;
}

function sol_csrf_token(): string
{
    if (empty($_SESSION["_csrf"])) {
        $_SESSION["_csrf"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["_csrf"];
}

function sol_csrf_field(): string
{
    $t = htmlspecialchars(sol_csrf_token(), ENT_QUOTES, "UTF-8");

    return '<input type="hidden" name="_csrf" value="' . $t . '">';
}

function sol_csrf_verify(): bool
{
    $sent = $_POST["_csrf"] ?? "";

    return is_string($sent) && isset($_SESSION["_csrf"]) && hash_equals($_SESSION["_csrf"], $sent);
}

# --- Session carts: shop vs rent buckets + one-time legacy cart migration ---

/** Accessories cart: subtotal (excl. shipping) from which standard delivery becomes free. */
function sol_shop_ship_free_threshold(): float
{
    return 80.0;
}

/** Flat shipping (EUR) for standard delivery when subtotal is strictly below the free threshold. */
function sol_shop_ship_flat_below_threshold(): float
{
    return 10.0;
}

/**
 * Shipping fee for shop accessories: pickup is always 0; delivery is 0 at/above threshold else flat rate.
 *
 * @param "pickup"|"delivery" $deliveryMode
 */
function sol_shop_shipping_fee(float $cartSubtotal, string $deliveryMode): float
{
    if ($deliveryMode === "pickup") {
        return 0.0;
    }
    if ($cartSubtotal >= sol_shop_ship_free_threshold()) {
        return 0.0;
    }

    return sol_shop_ship_flat_below_threshold();
}

/** True when `orders` has columns written by shop checkout. */
function sol_shop_orders_checkout_ready(mysqli $db): bool
{
    return sol_db_table_exists($db, "orders")
        && sol_db_column_exists($db, "orders", "cart_snapshot")
        && sol_db_column_exists($db, "orders", "delivery_mode");
}

/** Human labels for shop + rent payment codes (`store`, `iban`). */
function sol_payment_method_label(string $code): string
{
    return match ($code) {
        "store" => "Pay in store (card or cash)",
        "iban" => "IBAN bank transfer to our account",
        default => $code,
    };
}

/** @var mysqli $connect */
function sol_ensure_session_carts(mysqli $db): void
{
    if (!isset($_SESSION["shop_cart"]) || !is_array($_SESSION["shop_cart"])) {
        $_SESSION["shop_cart"] = [];
    }
    if (!isset($_SESSION["shop_cart_delivery"]) || !in_array($_SESSION["shop_cart_delivery"], ["pickup", "delivery"], true)) {
        $_SESSION["shop_cart_delivery"] = "delivery";
    }
    if (!isset($_SESSION["shop_cart_payment_method"]) || !in_array($_SESSION["shop_cart_payment_method"], ["store", "iban"], true)) {
        $_SESSION["shop_cart_payment_method"] = "store";
    }
    sol_intl_address_session_bootstrap();
    if (!isset($_SESSION["rent_cart"]) || !is_array($_SESSION["rent_cart"])) {
        $_SESSION["rent_cart"] = [];
    }
    foreach (array_keys($_SESSION["rent_cart"]) as $_rk) {
        $_ik = (int)$_rk;
        if ($_ik > 0) {
            $_SESSION["rent_cart"][$_ik] = 1;
        } else {
            unset($_SESSION["rent_cart"][$_rk]);
        }
    }

    if (!isset($_SESSION["rent_cart_start"]) || !is_string($_SESSION["rent_cart_start"]) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_SESSION["rent_cart_start"])) {
        $_SESSION["rent_cart_start"] = date("Y-m-d");
    }
    if (!isset($_SESSION["rent_cart_end"]) || !is_string($_SESSION["rent_cart_end"]) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_SESSION["rent_cart_end"])) {
        $_SESSION["rent_cart_end"] = date("Y-m-d", strtotime("+7 days"));
    }
    if ($_SESSION["rent_cart_end"] <= $_SESSION["rent_cart_start"]) {
        $_SESSION["rent_cart_end"] = date("Y-m-d", strtotime($_SESSION["rent_cart_start"] . " +7 days"));
    }
    $todayYm = date("Y-m-d");
    if ($_SESSION["rent_cart_start"] < $todayYm) {
        $_SESSION["rent_cart_start"] = $todayYm;
    }
    if ($_SESSION["rent_cart_end"] <= $_SESSION["rent_cart_start"]) {
        $_SESSION["rent_cart_end"] = date("Y-m-d", strtotime($_SESSION["rent_cart_start"] . " +7 days"));
    }

    if (!isset($_SESSION["rent_cart_payment_method"]) || !in_array($_SESSION["rent_cart_payment_method"], ["store", "iban"], true)) {
        $_SESSION["rent_cart_payment_method"] = "store";
    }
    if (!isset($_SESSION["rent_cart_delivery_method"]) || !in_array($_SESSION["rent_cart_delivery_method"], ["pickup", "courier"], true)) {
        $_SESSION["rent_cart_delivery_method"] = "pickup";
    }
    if (!isset($_SESSION["rent_cart_delivery_notes"]) || !is_string($_SESSION["rent_cart_delivery_notes"])) {
        $_SESSION["rent_cart_delivery_notes"] = "";
    }
    $rentNotesMax = sol_rent_delivery_notes_max_len($db);
    $_SESSION["rent_cart_delivery_notes"] = mb_substr(trim($_SESSION["rent_cart_delivery_notes"]), 0, $rentNotesMax);

    if (!isset($_SESSION["cart"]) || isset($_SESSION["_cart_migrated"])) {
        return;
    }

    $legacy = $_SESSION["cart"];
    if (!is_array($legacy)) {
        unset($_SESSION["cart"]);
        $_SESSION["_cart_migrated"] = true;

        return;
    }

    foreach ($legacy as $id => $qty) {
        $id = (int)$id;
        $q = max(1, (int)$qty);
        $isProduct = false;
        $st = $db->prepare("SELECT 1 FROM products WHERE id = ? LIMIT 1");
        if ($st) {
            $st->bind_param("i", $id);
            $st->execute();
            $isProduct = $st->get_result()->num_rows > 0;
            $st->close();
        }
        if ($isProduct) {
            $_SESSION["shop_cart"][$id] = ($_SESSION["shop_cart"][$id] ?? 0) + $q;
            continue;
        }
        $st2 = $db->prepare("SELECT 1 FROM instruments WHERE id = ? LIMIT 1");
        if ($st2) {
            $st2->bind_param("i", $id);
            $st2->execute();
            if ($st2->get_result()->num_rows > 0) {
                $_SESSION["rent_cart"][$id] = ($_SESSION["rent_cart"][$id] ?? 0) + $q;
            }
            $st2->close();
        }
    }

    unset($_SESSION["cart"]);
    $_SESSION["_cart_migrated"] = true;
}

# --- Cart / wishlist counters for navbar badges ---

function sol_shop_cart_count(): int
{
    sol_ensure_session_carts($GLOBALS["connect"]);
    $n = 0;
    foreach ($_SESSION["shop_cart"] as $q) {
        $n += (int)$q;
    }

    return $n;
}

function sol_rent_cart_count(): int
{
    sol_ensure_session_carts($GLOBALS["connect"]);
    $n = 0;
    foreach ($_SESSION["rent_cart"] as $q) {
        $n += (int)$q;
    }

    return $n;
}

function sol_wishlist_count(mysqli $db, int $uid): int
{
    if ($uid < 1) {
        return 0;
    }
    $st = $db->prepare("SELECT COUNT(*) AS c FROM wishlist WHERE user_id = ?");
    if (!$st) {
        return 0;
    }
    $st->bind_param("i", $uid);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    return (int)($row["c"] ?? 0);
}

/**
 * Which of the given ids are already on the user's wishlist (batch for list UIs).
 *
 * @param list<int> $ids product_id or instrument id values stored in wishlist.product_id
 *
 * @return array<int, true>
 */
function sol_wishlist_membership(mysqli $db, int $uid, string $itemType, array $ids): array
{
    if ($uid < 1 || ($itemType !== "product" && $itemType !== "instrument")) {
        return [];
    }
    $ids = array_values(array_unique(array_filter(array_map(static fn ($x) => (int)$x, $ids), static fn ($x) => $x > 0)));
    if ($ids === []) {
        return [];
    }
    $ph = implode(",", array_fill(0, count($ids), "?"));
    $types = "is" . str_repeat("i", count($ids));
    $sql = "SELECT product_id FROM wishlist WHERE user_id = ? AND item_type = ? AND product_id IN ($ph)";
    $st = $db->prepare($sql);
    if (!$st) {
        return [];
    }
    $st->bind_param($types, $uid, $itemType, ...$ids);
    $st->execute();
    $res = $st->get_result();
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[(int)$row["product_id"]] = true;
    }
    $st->close();

    return $out;
}

/** Plain one-line excerpt for cart / wishlist rows (no HTML). */
function sol_line_excerpt(?string $raw, int $max = 96): string
{
    $t = trim(preg_replace('/\s+/u', " ", strip_tags((string)$raw)));
    if ($t === "") {
        return "";
    }
    if (function_exists("mb_strlen") && function_exists("mb_substr") && mb_strlen($t) > $max) {
        return rtrim(mb_substr($t, 0, $max - 1)) . "…";
    }
    if (strlen($t) > $max) {
        return rtrim(substr($t, 0, $max - 1)) . "…";
    }

    return $t;
}

/**
 * Admin navbar + dashboard: counts for rent, shop, users, content (missing tables → 0).
 *
 * @return array{
 *   pending_rentals: int,
 *   unread_messages: int,
 *   pending_orders: int,
 *   blocked_users: int,
 *   users_total: int,
 *   products: int,
 *   suppliers: int,
 *   instruments_active: int,
 *   instruments_inactive: int,
 *   categories: int,
 *   faq_entries: int
 * }
 */
function sol_admin_nav_counts(mysqli $db): array
{
    $out = [
        "pending_rentals" => 0,
        "unread_messages" => 0,
        "pending_orders" => 0,
        "blocked_users" => 0,
        "users_total" => 0,
        "products" => 0,
        "suppliers" => 0,
        "instruments_active" => 0,
        "instruments_inactive" => 0,
        "categories" => 0,
        "faq_entries" => 0,
    ];

    $scalar = static function (mysqli $mysqli, string $sql): int {
        $r = $mysqli->query($sql);
        if ($r && ($row = $r->fetch_assoc())) {
            return (int)($row["c"] ?? 0);
        }

        return 0;
    };

    $out["users_total"] = $scalar($db, "SELECT COUNT(*) AS c FROM users");

    if (sol_db_column_exists($db, "users", "account_blocked")) {
        $out["blocked_users"] = $scalar(
            $db,
            "SELECT COUNT(*) AS c FROM users WHERE COALESCE(account_blocked,0) = 1 AND LOWER(TRIM(COALESCE(status,''))) <> 'adm'"
        );
    }

    if (sol_db_table_exists($db, "rental_requests")) {
        $out["pending_rentals"] = $scalar($db, "SELECT COUNT(*) AS c FROM rental_requests WHERE status = 'pending'");
    }

    if (sol_db_table_exists($db, "contact_messages")) {
        $out["unread_messages"] = $scalar($db, "SELECT COUNT(*) AS c FROM contact_messages WHERE is_read = 0");
    }

    if (sol_db_table_exists($db, "orders")) {
        $out["pending_orders"] = $scalar($db, "SELECT COUNT(*) AS c FROM orders WHERE status = 'pending'");
    }

    if (sol_db_table_exists($db, "products")) {
        $out["products"] = $scalar($db, "SELECT COUNT(*) AS c FROM products");
    }

    if (sol_db_table_exists($db, "suppliers")) {
        $out["suppliers"] = $scalar($db, "SELECT COUNT(*) AS c FROM suppliers");
    }

    if (sol_db_table_exists($db, "instruments")) {
        $out["instruments_active"] = $scalar($db, "SELECT COUNT(*) AS c FROM instruments WHERE is_active = 1");
        $out["instruments_inactive"] = $scalar($db, "SELECT COUNT(*) AS c FROM instruments WHERE is_active = 0");
    }

    if (sol_db_table_exists($db, "categories")) {
        $out["categories"] = $scalar($db, "SELECT COUNT(*) AS c FROM categories");
    }

    if (sol_db_table_exists($db, "faq")) {
        $out["faq_entries"] = $scalar($db, "SELECT COUNT(*) AS c FROM faq");
    }

    return $out;
}

# --- Identity + HTTP redirects for customer-only or admin-only routes ---

function sol_current_uid(): int
{
    if (isset($_SESSION["uid"])) {
        return (int)$_SESSION["uid"];
    }
    if (isset($_SESSION["adm"])) {
        return (int)$_SESSION["adm"];
    }

    return 0;
}

function sol_nav_role(): string
{
    if (isset($_SESSION["adm"])) {
        return "admin";
    }
    if (isset($_SESSION["user"])) {
        return "user";
    }

    return "guest";
}

function sol_require_login(): void
{
    if (!isset($_SESSION["user"]) && !isset($_SESSION["adm"])) {
        header("Location: " . sol_url("login.php"));
        exit;
    }
}

function sol_require_user(): void
{
    sol_require_login();
    if (isset($_SESSION["adm"])) {
        header("Location: " . sol_url("admin/dashboard.php"));
        exit;
    }
}

function sol_require_admin(): void
{
    if (!isset($_SESSION["user"]) && !isset($_SESSION["adm"])) {
        header("Location: " . sol_url("login.php"));
        exit;
    }
    if (!isset($_SESSION["adm"])) {
        header("Location: " . sol_url("account/home.php"));
        exit;
    }
}

# --- Passwords: bcrypt/argon vs legacy SHA-256 + transparent upgrade on login ---

function sol_verify_password(string $plain, string $stored): bool
{
    if ($stored !== "" && (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$') || str_starts_with($stored, '$2b$') || str_starts_with($stored, '$argon2'))) {
        return password_verify($plain, $stored);
    }

    return hash("sha256", $plain) === $stored;
}

function sol_hash_password(string $plain): string
{
    return password_hash($plain, PASSWORD_DEFAULT);
}

/** After successful login with legacy SHA-256 hash, upgrade storage. */
function sol_maybe_upgrade_password(mysqli $db, int $userId, string $plain, string $stored): void
{
    if ($stored === "" || str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$') || str_starts_with($stored, '$2b$') || str_starts_with($stored, '$argon2')) {
        return;
    }
    if (hash("sha256", $plain) !== $stored) {
        return;
    }
    $newHash = sol_hash_password($plain);
    $st = $db->prepare("UPDATE users SET pass = ? WHERE id = ? LIMIT 1");
    if ($st) {
        $st->bind_param("si", $newHash, $userId);
        $st->execute();
        $st->close();
    }
}

# --- INFORMATION_SCHEMA lookups (cached) for optional migrations ---

function sol_db_table_exists(mysqli $db, string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    $st = $db->prepare(
        "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1"
    );
    if (!$st) {
        $cache[$table] = false;

        return false;
    }
    $st->bind_param("s", $table);
    $st->execute();
    $cache[$table] = $st->get_result()->num_rows > 0;
    $st->close();

    return $cache[$table];
}

function sol_db_column_exists(mysqli $db, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . "." . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $st = $db->prepare(
        "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1"
    );
    if (!$st) {
        $cache[$key] = false;

        return false;
    }
    $st->bind_param("ss", $table, $column);
    $st->execute();
    $cache[$key] = $st->get_result()->num_rows > 0;
    $st->close();

    return $cache[$key];
}

require_once __DIR__ . "/intl_address.php";
require_once __DIR__ . "/home_slides.php";

# --- Soft suspension when users.account_blocked exists ---

/** Customer account blocked (column optional until migration). */
function sol_user_account_blocked(mysqli $db, int $userId): bool
{
    if ($userId < 1 || !sol_db_column_exists($db, "users", "account_blocked")) {
        return false;
    }
    $st = $db->prepare("SELECT account_blocked FROM users WHERE id = ? LIMIT 1");
    if (!$st) {
        return false;
    }
    $st->bind_param("i", $userId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    return isset($row["account_blocked"]) && (int)$row["account_blocked"] === 1;
}

require_once __DIR__ . "/mini_cart.php";

# Normalize carts on every request after helpers are defined.
sol_ensure_session_carts($connect);
