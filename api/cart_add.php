<?php

declare(strict_types=1);

# AJAX add: shop cart, rent cart, wishlist (product / instrument). Returns updated counts.

require_once dirname(__DIR__) . "/includes/app.php";

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["ok" => false, "error" => "method"], JSON_THROW_ON_ERROR);
    exit;
}

if (!isset($_SESSION["user"]) && !isset($_SESSION["adm"])) {
    http_response_code(401);
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
$uid = sol_current_uid();

$action = (string)($_POST["sol_cart_action"] ?? "");
$itemId = (int)($_POST["item_id"] ?? 0);
$already = false;
$wishSaveFailed = false;

switch ($action) {
    case "shop":
        if ($itemId < 1) {
            break;
        }
        $st = $connect->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
        if ($st) {
            $st->bind_param("i", $itemId);
            $st->execute();
            if ($st->get_result()->num_rows === 1) {
                $_SESSION["shop_cart"][$itemId] = ($_SESSION["shop_cart"][$itemId] ?? 0) + 1;
            }
            $st->close();
        }
        break;

    case "rent":
        if ($itemId < 1) {
            break;
        }
        $st = $connect->prepare("SELECT id FROM instruments WHERE id = ? AND is_active = 1 LIMIT 1");
        if ($st) {
            $st->bind_param("i", $itemId);
            $st->execute();
            if ($st->get_result()->num_rows === 1) {
                $_SESSION["rent_cart"][$itemId] = ($_SESSION["rent_cart"][$itemId] ?? 0) + 1;
            }
            $st->close();
        }
        break;

    case "wish_product":
        if (isset($_SESSION["adm"]) || $uid < 1 || $itemId < 1) {
            http_response_code(403);
            echo json_encode(["ok" => false, "error" => "forbidden"], JSON_THROW_ON_ERROR);
            exit;
        }
        $st = $connect->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
        if ($st) {
            $st->bind_param("i", $itemId);
            $st->execute();
            if ($st->get_result()->num_rows !== 1) {
                $st->close();
                break;
            }
            $st->close();
        }
        $chk = $connect->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ? AND item_type = 'product' LIMIT 1");
        if ($chk) {
            $chk->bind_param("ii", $uid, $itemId);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $already = true;
                $chk->close();
                break;
            }
            $chk->close();
        }
        $ins = $connect->prepare("INSERT INTO wishlist (user_id, product_id, item_type) VALUES (?, ?, 'product')");
        if ($ins) {
            $ins->bind_param("ii", $uid, $itemId);
            if (!$ins->execute()) {
                $wishSaveFailed = true;
            }
            $ins->close();
        } else {
            $wishSaveFailed = true;
        }
        break;

    case "wish_instrument":
        if (isset($_SESSION["adm"]) || $uid < 1 || $itemId < 1) {
            http_response_code(403);
            echo json_encode(["ok" => false, "error" => "forbidden"], JSON_THROW_ON_ERROR);
            exit;
        }
        $st = $connect->prepare("SELECT id FROM instruments WHERE id = ? AND is_active = 1 LIMIT 1");
        if ($st) {
            $st->bind_param("i", $itemId);
            $st->execute();
            if ($st->get_result()->num_rows !== 1) {
                $st->close();
                break;
            }
            $st->close();
        }
        $chk = $connect->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ? AND item_type = 'instrument' LIMIT 1");
        if ($chk) {
            $chk->bind_param("ii", $uid, $itemId);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $already = true;
                $chk->close();
                break;
            }
            $chk->close();
        }
        $ins = $connect->prepare("INSERT INTO wishlist (user_id, product_id, item_type) VALUES (?, ?, 'instrument')");
        if ($ins) {
            $ins->bind_param("ii", $uid, $itemId);
            if (!$ins->execute()) {
                $wishSaveFailed = true;
            }
            $ins->close();
        } else {
            $wishSaveFailed = true;
        }
        break;

    case "wish_remove_product":
        if (isset($_SESSION["adm"]) || $uid < 1 || $itemId < 1) {
            http_response_code(403);
            echo json_encode(["ok" => false, "error" => "forbidden"], JSON_THROW_ON_ERROR);
            exit;
        }
        $del = $connect->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ? AND item_type = 'product' LIMIT 1");
        if ($del) {
            $del->bind_param("ii", $uid, $itemId);
            $del->execute();
            $del->close();
        }
        break;

    case "wish_remove_instrument":
        if (isset($_SESSION["adm"]) || $uid < 1 || $itemId < 1) {
            http_response_code(403);
            echo json_encode(["ok" => false, "error" => "forbidden"], JSON_THROW_ON_ERROR);
            exit;
        }
        $del = $connect->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ? AND item_type = 'instrument' LIMIT 1");
        if ($del) {
            $del->bind_param("ii", $uid, $itemId);
            $del->execute();
            $del->close();
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "action"], JSON_THROW_ON_ERROR);
        exit;
}

if ($wishSaveFailed) {
    http_response_code(500);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(["ok" => false, "error" => "db_wishlist"], JSON_THROW_ON_ERROR);
    exit;
}

$shop = sol_shop_cart_count();
$rent = sol_rent_cart_count();
$wish = ($uid > 0 && !isset($_SESSION["adm"])) ? sol_wishlist_count($connect, $uid) : 0;

try {
    echo json_encode(
        [
            "ok" => true,
            "shop" => $shop,
            "rent" => $rent,
            "wish" => $wish,
            "already" => $already,
        ],
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo '{"ok":false,"error":"encode"}';
}
