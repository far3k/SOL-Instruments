<?php

declare(strict_types=1);

# Shop cart: card layout, qty AJAX, delivery mode (pickup vs threshold shipping), payment method, totals.

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_login();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["ajax_shop_cart_save"])) {
    header("Content-Type: application/json; charset=UTF-8");
    if (!sol_csrf_verify()) {
        echo json_encode(["ok" => false, "error" => "csrf"], JSON_THROW_ON_ERROR);
        exit;
    }
    sol_ensure_session_carts($connect);
    $dm = (string)($_POST["shop_cart_delivery"] ?? "");
    if (!in_array($dm, ["pickup", "delivery"], true)) {
        echo json_encode(["ok" => false, "error" => "delivery"], JSON_THROW_ON_ERROR);
        exit;
    }
    $pm = (string)($_POST["payment_method"] ?? "");
    if (!in_array($pm, ["store", "iban"], true)) {
        echo json_encode(["ok" => false, "error" => "payment"], JSON_THROW_ON_ERROR);
        exit;
    }
    $_SESSION["shop_cart_delivery"] = $dm;
    $_SESSION["shop_cart_payment_method"] = $pm;
    $_SESSION["sol_intl_address"] = sol_intl_address_from_post($_POST);

    $cart = $_SESSION["shop_cart"] ?? [];
    $subtotal = 0.0;
    if ($cart !== []) {
        $ids = implode(",", array_map("intval", array_keys($cart)));
        if ($ids !== "") {
            $res = $connect->query("SELECT id, price FROM products WHERE id IN ($ids)");
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $pid = (int)$r["id"];
                    $q = (int)($cart[$pid] ?? 0);
                    if ($q > 0) {
                        $subtotal += (float)$r["price"] * $q;
                    }
                }
            }
        }
    }
    $subtotal = round($subtotal, 2);
    $shipping = round(sol_shop_shipping_fee($subtotal, $dm), 2);
    $grand = round($subtotal + $shipping, 2);

    echo json_encode(
        [
            "ok" => true,
            "subtotal" => $subtotal,
            "shipping" => $shipping,
            "grand_total" => $grand,
            "delivery" => $dm,
            "free_at" => sol_shop_ship_free_threshold(),
            "flat_below" => sol_shop_ship_flat_below_threshold(),
        ],
        JSON_THROW_ON_ERROR
    );
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["qty"])) {
    foreach ($_POST["qty"] as $id => $qty) {
        $id = (int)$id;
        $qty = (int)$qty;
        if ($qty <= 0) {
            unset($_SESSION["shop_cart"][$id]);
        } else {
            $_SESSION["shop_cart"][$id] = $qty;
        }
    }
    exit;
}

if (isset($_GET["remove"])) {
    $id = (int)$_GET["remove"];
    unset($_SESSION["shop_cart"][$id]);
    header("Location: " . sol_url("shop/cart.php"));
    exit;
}

$nav_role = isset($_SESSION["adm"]) ? "admin" : "user";
$cart = $_SESSION["shop_cart"] ?? [];
$shopDelivery = (string)($_SESSION["shop_cart_delivery"] ?? "delivery");
if (!in_array($shopDelivery, ["pickup", "delivery"], true)) {
    $shopDelivery = "delivery";
}
$shopPay = (string)($_SESSION["shop_cart_payment_method"] ?? "store");
if (!in_array($shopPay, ["store", "iban"], true)) {
    $shopPay = "store";
}
$intlAddr = $_SESSION["sol_intl_address"] ?? sol_intl_address_template();

$rows = [];
$subtotal = 0.0;
$lineCount = 0;
if (!empty($cart)) {
    $ids = implode(",", array_map("intval", array_keys($cart)));
    if ($ids !== "") {
        $res = $connect->query("SELECT id, name, price, picture, description FROM products WHERE id IN ($ids)");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[(int)$r["id"]] = $r;
            }
        }
    }
    foreach ($cart as $cid => $cqty) {
        $id = (int)$cid;
        $qty = (int)$cqty;
        $row = $rows[$id] ?? null;
        if (!$row || $qty < 1) {
            continue;
        }
        $subtotal += (float)$row["price"] * $qty;
        $lineCount++;
    }
}
$subtotal = round($subtotal, 2);
$shipping = round(sol_shop_shipping_fee($subtotal, $shopDelivery), 2);
$grandTotal = round($subtotal + $shipping, 2);
$shipFreeAt = sol_shop_ship_free_threshold();
$shipFlat = sol_shop_ship_flat_below_threshold();

$extra_head = "";
if (!empty($cart)) {
    $extra_head = <<<'HTML'
<style>
.sol-shop-cart-wrap { max-width: 720px; margin-left: auto; margin-right: auto; }
.sol-shop-cart-shell {
  border-radius: 1rem;
  box-shadow: 0 0.35rem 1.25rem rgba(15, 23, 42, 0.08);
  border: 1px solid rgba(15, 23, 42, 0.06);
  overflow: hidden;
  background: #fff;
}
.sol-shop-cart-shell .card-header {
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
  font-weight: 600;
  letter-spacing: 0.02em;
}
.sol-shop-cart-item {
  display: flex;
  gap: 1rem;
  align-items: stretch;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid rgba(15, 23, 42, 0.06);
  transition: background 0.15s ease;
}
.sol-shop-cart-item:last-child { border-bottom: 0; }
.sol-shop-cart-item:hover { background: #fafbfc; }
.sol-shop-cart-thumb {
  width: 4.5rem;
  height: 4.5rem;
  object-fit: cover;
  border-radius: 0.65rem;
  border: 1px solid rgba(15, 23, 42, 0.08);
  flex-shrink: 0;
}
.sol-shop-cart-item-body { min-width: 0; flex: 1; }
.sol-shop-cart-item-title { font-weight: 600; font-size: 0.95rem; color: #0f172a; }
.sol-shop-cart-item-meta { font-size: 0.8rem; color: #64748b; margin-top: 0.25rem; }
.sol-shop-cart-item-price { font-weight: 700; color: #0d6efd; white-space: nowrap; font-size: 0.95rem; }
.sol-shop-summary-panel {
  font-size: 0.875rem;
  color: #475569;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  border: 1px solid rgba(15, 23, 42, 0.06);
}
.sol-shop-options-panel .form-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; font-weight: 600; }
.sol-shop-options-panel .form-check-label { font-size: 0.875rem; }
</style>
HTML;
}

$flash = $_SESSION["flash_error"] ?? "";
unset($_SESSION["flash_error"]);

$page_title = "Your cart";
$active_nav = "shop_cart";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-4 py-lg-5">
    <?php if ($flash !== ""): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-3 sol-shop-cart-wrap"><?= htmlspecialchars($flash, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 sol-shop-cart-wrap">
        <h1 class="h4 mb-0 fw-semibold text-dark">Shop cart</h1>
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <?php if (empty($cart)): ?>
        <div class="alert alert-info border-0 shadow-sm rounded-3 sol-shop-cart-wrap">Your cart is empty. Browse <a href="<?= htmlspecialchars(sol_url("shop/catalog.php"), ENT_QUOTES, "UTF-8") ?>">accessories</a>.</div>
    <?php else: ?>
        <div class="sol-shop-cart-wrap">
            <div id="shop-cart-lines" class="card sol-shop-cart-shell mb-4" style="scroll-margin-top: 5.5rem;">
                <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="text-uppercase small text-secondary" style="letter-spacing: 0.05em;">Products</span>
                    <span class="badge rounded-pill bg-primary text-white border border-primary"><?= (int)$lineCount ?></span>
                </div>
                <?php foreach ($cart as $cid => $cqty): ?>
        <?php
                    $id = (int)$cid;
                    $qty = (int)$cqty;
                    $row = $rows[$id] ?? null;
                    if (!$row || $qty < 1) {
                            continue;
                        }
                        $price = (float)$row["price"];
                    $lineTotal = round($price * $qty, 2);
                    $pic = htmlspecialchars((string)($row["picture"] ?? "product.jpg"), ENT_QUOTES, "UTF-8");
                    $name = htmlspecialchars((string)($row["name"] ?? ""), ENT_QUOTES, "UTF-8");
                    $ex = sol_line_excerpt($row["description"] ?? null, 72);
                    $exHtml = $ex !== "" ? htmlspecialchars($ex, ENT_QUOTES, "UTF-8") : "";
                    ?>
                    <div class="sol-shop-cart-item">
                        <img src="<?= htmlspecialchars(sol_url("pictures/" . $pic), ENT_QUOTES, "UTF-8") ?>" alt="" class="sol-shop-cart-thumb">
                        <div class="sol-shop-cart-item-body d-flex flex-column flex-md-row align-items-md-start justify-content-md-between gap-3">
                            <div class="min-w-0 flex-grow-1">
                                <div class="sol-shop-cart-item-title"><?= $name ?></div>
                                <?php if ($exHtml !== ""): ?>
                                    <div class="sol-shop-cart-item-meta text-truncate"><?= $exHtml ?></div>
                                <?php endif; ?>
                                <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                                    <label class="visually-hidden" for="qty-shop-<?= $id ?>">Quantity</label>
                                    <input id="qty-shop-<?= $id ?>" type="number" class="form-control form-control-sm qty-input-shop rounded-3" style="max-width: 5rem;" data-id="<?= $id ?>" data-unit-price="<?= htmlspecialchars((string)$price, ENT_QUOTES, "UTF-8") ?>" value="<?= $qty ?>" min="1" title="Quantity">
                                    <span class="small text-muted">× €<?= htmlspecialchars((string)$price, ENT_QUOTES, "UTF-8") ?> each</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 flex-shrink-0">
                                <span class="sol-shop-cart-item-price sol-shop-line-subtotal" data-line-for="<?= $id ?>">€<?= htmlspecialchars((string)$lineTotal, ENT_QUOTES, "UTF-8") ?></span>
                                <a class="btn btn-sm btn-outline-danger rounded-pill px-3" href="<?= htmlspecialchars(sol_url("shop/cart.php?remove=" . $id), ENT_QUOTES, "UTF-8") ?>">Remove</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="shop-cart-checkout" style="scroll-margin-top: 5.5rem;">
            <div class="card sol-shop-cart-shell mb-4 sol-shop-options-panel">
                <div class="card-header py-3 px-4">
                    <span class="text-uppercase small text-secondary" style="letter-spacing: 0.05em;">Delivery &amp; payment</span>
                </div>
                <div class="card-body px-4 py-4">
                    <form id="sol-shop-cart-form" action="<?= htmlspecialchars(sol_url("shop/cart.php"), ENT_QUOTES, "UTF-8") ?>" onsubmit="return false;">
                        <?= sol_csrf_field() ?>
                        <p class="small text-muted mb-3 mb-md-4">
                            Standard delivery: <strong>€<?= htmlspecialchars((string)$shipFlat, ENT_QUOTES, "UTF-8") ?></strong> if your basket is under <strong>€<?= htmlspecialchars((string)$shipFreeAt, ENT_QUOTES, "UTF-8") ?></strong>; <strong class="text-success">free</strong> from €<?= htmlspecialchars((string)$shipFreeAt, ENT_QUOTES, "UTF-8") ?> upward. Pickup is always free.
                        </p>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label mb-2">Delivery</label>
                                <div class="border rounded-3 p-3 bg-light bg-opacity-50">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="shop_cart_delivery" id="shop_del_pickup" value="pickup" <?= $shopDelivery === "pickup" ? "checked" : "" ?>>
                                        <label class="form-check-label" for="shop_del_pickup">Pickup at our store <span class="text-muted">(no shipping)</span></label>
                                    </div>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="shop_cart_delivery" id="shop_del_standard" value="delivery" <?= $shopDelivery === "delivery" ? "checked" : "" ?>>
                                        <label class="form-check-label" for="shop_del_standard">Standard delivery <span class="text-muted">(threshold rule above)</span></label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label mb-2">Payment</label>
                                <div class="border rounded-3 p-3 bg-light bg-opacity-50">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="shop_pay_store" value="store" <?= $shopPay === "store" ? "checked" : "" ?>>
                                        <label class="form-check-label" for="shop_pay_store"><?= htmlspecialchars(sol_payment_method_label("store"), ENT_QUOTES, "UTF-8") ?></label>
                                    </div>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="radio" name="payment_method" id="shop_pay_iban" value="iban" <?= $shopPay === "iban" ? "checked" : "" ?>>
                                        <label class="form-check-label" for="shop_pay_iban"><?= htmlspecialchars(sol_payment_method_label("iban"), ENT_QUOTES, "UTF-8") ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="shop-shipping-address-wrap" class="<?= $shopDelivery === "delivery" ? "" : "d-none" ?> mt-3">
                            <?php
                            $intl_id_prefix = "shop";
                            $intl_values = $intlAddr;
                            require dirname(__DIR__) . "/includes/partials/intl_address_fields.php";
                            ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card sol-shop-cart-shell mb-4 overflow-hidden" id="sol-shop-summary-card"
                data-ship-free="<?= htmlspecialchars((string)$shipFreeAt, ENT_QUOTES, "UTF-8") ?>"
                data-ship-flat="<?= htmlspecialchars((string)$shipFlat, ENT_QUOTES, "UTF-8") ?>">
                <div class="sol-shop-summary-panel py-3 px-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Subtotal</span>
                        <span class="fw-semibold text-dark" id="sol-shop-subtotal">€<?= htmlspecialchars((string)$subtotal, ENT_QUOTES, "UTF-8") ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Shipping</span>
                        <span class="fw-semibold text-dark" id="sol-shop-shipping-label">
                            <?php if ($shipping <= 0): ?>
                                <span class="text-success">Free</span>
                            <?php else: ?>
                                €<?= htmlspecialchars((string)$shipping, ENT_QUOTES, "UTF-8") ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <hr class="my-2 opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Total</span>
                        <span class="h5 mb-0 text-primary" id="sol-shop-grand-total">€<?= htmlspecialchars((string)$grandTotal, ENT_QUOTES, "UTF-8") ?></span>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-stretch align-items-sm-center gap-3">
                <a href="<?= htmlspecialchars(sol_url("shop/catalog.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary rounded-3">Continue shopping</a>
                <a href="<?= htmlspecialchars(sol_url("shop/checkout_confirm.php"), ENT_QUOTES, "UTF-8") ?>" id="sol-shop-checkout-link" class="btn btn-primary btn-lg rounded-3 px-4 shadow-sm text-center">Proceed to checkout</a>
            </div>
            </div>
            <p class="small text-muted mt-3 mb-0 text-center text-sm-start">Rentals: <a href="<?= htmlspecialchars(sol_url("rent/rent_cart.php"), ENT_QUOTES, "UTF-8") ?>">rent cart</a>.</p>
        </div>
    <?php endif; ?>
</div>

<?php
$u = json_encode(sol_url("shop/cart.php"), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$remove_js = json_encode(sol_url("shop/cart.php?remove="), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$checkout_u = json_encode(sol_url("shop/checkout_confirm.php"), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

if (!empty($cart)) {
    $layout_extra_scripts = <<<'JSEOF'
<script>
(function () {
  var cartUrl = CART_URL_PLACEHOLDER;
  var removeBase = REMOVE_BASE_PLACEHOLDER;
  var checkoutUrl = CHECKOUT_URL_PLACEHOLDER;
  function intlField(name) {
    var el = document.querySelector("#sol-shop-cart-form [name=\"intl_addr[" + name + "]\"]");
    return el ? String(el.value).trim() : "";
  }
  function intlShopComplete() {
    return intlField("recipient_name").length >= 2
      && intlField("address_line1").length >= 4
      && intlField("locality").length >= 2
      && intlField("postal_code").length >= 2
      && intlField("country_code").length === 2;
  }
  function moneyStr(n) {
    return "€" + (Math.round(n * 100) / 100).toFixed(2);
  }
  function getDeliveryMode() {
    var r = document.querySelector("#sol-shop-cart-form input[name=shop_cart_delivery]:checked");
    return r && r.value === "pickup" ? "pickup" : "delivery";
  }
  function syncAddressVisibility() {
    var wrap = document.getElementById("shop-shipping-address-wrap");
    if (!wrap) return;
    wrap.classList.toggle("d-none", getDeliveryMode() === "pickup");
  }
  function shippingFor(sub, mode) {
    var card = document.getElementById("sol-shop-summary-card");
    var freeAt = card ? parseFloat(card.getAttribute("data-ship-free") || "80") : 80;
    var flat = card ? parseFloat(card.getAttribute("data-ship-flat") || "10") : 10;
    if (mode === "pickup") return 0;
    if (sub >= freeAt) return 0;
    return flat;
  }
  function updateTotalsFromDom() {
    var sum = 0;
    document.querySelectorAll(".qty-input-shop").forEach(function (el) {
      var unit = parseFloat(String(el.getAttribute("data-unit-price") || "0"), 10);
      var n = parseInt(el.value, 10);
      if (isNaN(n) || n < 1) return;
      if (isNaN(unit)) unit = 0;
      var line = unit * n;
      sum += line;
      var lid = el.getAttribute("data-id");
      document.querySelectorAll(".sol-shop-line-subtotal[data-line-for=\"" + lid + "\"]").forEach(function (sub) {
        sub.textContent = moneyStr(line);
      });
    });
    sum = Math.round(sum * 100) / 100;
    var mode = getDeliveryMode();
    var ship = Math.round(shippingFor(sum, mode) * 100) / 100;
    var grand = Math.round((sum + ship) * 100) / 100;
    var st = document.getElementById("sol-shop-subtotal");
    var sl = document.getElementById("sol-shop-shipping-label");
    var gt = document.getElementById("sol-shop-grand-total");
    if (st) st.textContent = moneyStr(sum);
    if (sl) {
      sl.innerHTML = ship <= 0 ? "<span class=\"text-success\">Free</span>" : moneyStr(ship);
    }
    if (gt) gt.textContent = moneyStr(grand);
  }
  function saveDeliveryPrefs() {
    var form = document.getElementById("sol-shop-cart-form");
    if (!form) return;
    var fd = new FormData(form);
    fd.append("ajax_shop_cart_save", "1");
    fetch(cartUrl, { method: "POST", body: fd, credentials: "same-origin" })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.ok) return;
        var st = document.getElementById("sol-shop-subtotal");
        var sl = document.getElementById("sol-shop-shipping-label");
        var gt = document.getElementById("sol-shop-grand-total");
        if (st) st.textContent = moneyStr(data.subtotal);
        if (sl) {
          sl.innerHTML = data.shipping <= 0 ? "<span class=\"text-success\">Free</span>" : moneyStr(data.shipping);
        }
        if (gt) gt.textContent = moneyStr(data.grand_total);
        syncAddressVisibility();
        if (window.SOL_NAV && typeof window.SOL_NAV.refresh === "function") {
          window.SOL_NAV.refresh();
        }
      });
  }
  document.querySelectorAll("#sol-shop-cart-form input[name=shop_cart_delivery]").forEach(function (r) {
    r.addEventListener("change", function () {
      updateTotalsFromDom();
      saveDeliveryPrefs();
    });
  });
  document.querySelectorAll("#sol-shop-cart-form input[name=payment_method]").forEach(function (r) {
    r.addEventListener("change", function () {
      saveDeliveryPrefs();
    });
  });
  syncAddressVisibility();
  var addrTimer = null;
  var shopForm = document.getElementById("sol-shop-cart-form");
  if (shopForm) {
    shopForm.addEventListener("input", function (e) {
      var t = e.target;
      if (!t || !t.name || t.name.indexOf("intl_addr[") !== 0) return;
      if (addrTimer) clearTimeout(addrTimer);
      addrTimer = setTimeout(function () {
        addrTimer = null;
        saveDeliveryPrefs();
      }, 400);
    });
    shopForm.addEventListener("change", function (e) {
      var t = e.target;
      if (t && t.name && t.name.indexOf("intl_addr[") === 0) {
        saveDeliveryPrefs();
      }
    });
  }
  var chkLink = document.getElementById("sol-shop-checkout-link");
  if (chkLink) {
    chkLink.addEventListener("click", function (e) {
      if (getDeliveryMode() !== "delivery") return;
      if (!intlShopComplete()) {
        e.preventDefault();
        alert("Please complete the international address (name, street, city, postal code, country).");
        var el = document.querySelector("#sol-shop-cart-form [name=\"intl_addr[recipient_name]\"]");
        if (el) el.focus();
        return;
      }
      e.preventDefault();
      var form = document.getElementById("sol-shop-cart-form");
      if (!form) return;
      var fd = new FormData(form);
      fd.append("ajax_shop_cart_save", "1");
      fetch(cartUrl, { method: "POST", body: fd, credentials: "same-origin" })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && data.ok) window.location.href = checkoutUrl;
        });
    });
  }
document.querySelectorAll(".qty-input-shop").forEach(function (inp) {
  inp.addEventListener("change", function () {
      var q = parseInt(inp.value, 10);
      if (isNaN(q) || q < 1) {
        window.location.href = removeBase + encodeURIComponent(inp.dataset.id);
        return;
      }
    var fd = new FormData();
      fd.append("qty[" + inp.dataset.id + "]", String(q));
      fetch(cartUrl, { method: "POST", body: fd }).then(function () {
        updateTotalsFromDom();
        saveDeliveryPrefs();
        if (window.SOL_NAV && typeof window.SOL_NAV.refresh === "function") {
          window.SOL_NAV.refresh();
        }
      }).catch(function () {
        updateTotalsFromDom();
      });
  });
});
})();
</script>
JSEOF;
    $layout_extra_scripts = str_replace(
        ["CART_URL_PLACEHOLDER", "REMOVE_BASE_PLACEHOLDER", "CHECKOUT_URL_PLACEHOLDER"],
        [$u, $remove_js, $checkout_u],
        $layout_extra_scripts
    );
} else {
    $layout_extra_scripts = "";
}

require_once dirname(__DIR__) . "/includes/layout_bottom.php";
