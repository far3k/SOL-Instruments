<?php

declare(strict_types=1);

# Compact header mini-cart payload + HTML fragment (shop + rent).

/**
 * @return array{
 *   shop_lines: list<array{id:int,name:string,picture:string,qty:int,unit:float,line:float}>,
 *   shop_subtotal: float,
 *   rent_lines: list<array{id:int,name:string,picture:string,qty:int,unit_day:float,line_est:float}>,
 *   rent_subtotal_est: float,
 *   wish_count: int
 * }
 */
function sol_mini_cart_payload(mysqli $db): array
{
    sol_ensure_session_carts($db);
    $shopCart = $_SESSION["shop_cart"] ?? [];
    $rentCart = $_SESSION["rent_cart"] ?? [];
    $uid = sol_current_uid();

    $shopLines = [];
    $shopSub = 0.0;
    if ($shopCart !== []) {
        $ids = implode(",", array_map("intval", array_keys($shopCart)));
        if ($ids !== "") {
            $res = $db->query("SELECT id, name, price, picture FROM products WHERE id IN ($ids)");
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $pid = (int)$r["id"];
                    $q = (int)($shopCart[$pid] ?? 0);
                    if ($q < 1) {
                        continue;
                    }
                    $unit = (float)$r["price"];
                    $line = $unit * $q;
                    $shopSub += $line;
                    $shopLines[] = [
                        "id" => $pid,
                        "name" => (string)($r["name"] ?? ""),
                        "picture" => (string)($r["picture"] ?? "product.jpg"),
                        "qty" => $q,
                        "unit" => $unit,
                        "line" => $line,
                    ];
                }
            }
        }
    }

    $rentLines = [];
    $rentEst = 0.0;
    if ($rentCart !== []) {
        $ids = implode(",", array_map("intval", array_keys($rentCart)));
        if ($ids !== "") {
            $res = $db->query("SELECT id, name, daily_price, image_url FROM instruments WHERE id IN ($ids)");
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $iid = (int)$r["id"];
                    $q = (int)($rentCart[$iid] ?? 0);
                    if ($q < 1) {
                        continue;
                    }
                    $day = (float)$r["daily_price"];
                    $line = $day * $q;
                    $rentEst += $line;
                    $rentLines[] = [
                        "id" => $iid,
                        "name" => (string)($r["name"] ?? ""),
                        "picture" => (string)($r["image_url"] ?? "instrument.jpg"),
                        "qty" => $q,
                        "unit_day" => $day,
                        "line_est" => $line,
                    ];
                }
            }
        }
    }

    return [
        "shop_lines" => $shopLines,
        "shop_subtotal" => round($shopSub, 2),
        "rent_lines" => $rentLines,
        "rent_subtotal_est" => round($rentEst, 2),
        "wish_count" => $uid > 0 ? sol_wishlist_count($db, $uid) : 0,
    ];
}

function sol_mini_cart_render_html(array $p, string $csrfToken): string
{
    $shopLines = $p["shop_lines"];
    $rentLines = $p["rent_lines"];
    $shopSub = 0.0;
    foreach ($shopLines as $sl) {
        $shopSub += (float)($sl["unit"] ?? 0) * (int)($sl["qty"] ?? 0);
    }
    $shopSub = round($shopSub, 2);
    $rentEst = 0.0;
    foreach ($rentLines as $rl) {
        $rentEst += (float)($rl["unit_day"] ?? 0) * (int)($rl["qty"] ?? 0);
    }
    $rentEst = round($rentEst, 2);
    $wishCount = (int)$p["wish_count"];
    ob_start();
    require __DIR__ . "/partials/mini_cart_body.php";

    return (string) ob_get_clean();
}
