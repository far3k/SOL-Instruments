<?php

declare(strict_types=1);

# Session rent cart: one unit per instrument, dates/payment/delivery (AJAX save), confirm checkout.

require_once dirname(__DIR__) . "/includes/app.php";
require_once dirname(__DIR__) . "/includes/rental_helpers.php";
sol_require_user();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["ajax_rent_cart_save"])) {
    header("Content-Type: application/json; charset=UTF-8");
    if (!sol_csrf_verify()) {
        echo json_encode(["ok" => false, "error" => "csrf"], JSON_THROW_ON_ERROR);
        exit;
    }
    sol_ensure_session_carts($connect);
    foreach (array_keys($_SESSION["rent_cart"] ?? []) as $rk) {
        $ik = (int)$rk;
        if ($ik > 0) {
            $_SESSION["rent_cart"][$ik] = 1;
        } else {
            unset($_SESSION["rent_cart"][$rk]);
        }
    }

    $start = trim((string)($_POST["start_date"] ?? ""));
    $end = trim((string)($_POST["end_date"] ?? ""));
    $dateErr = sol_rental_validate_booking_dates($start, $end);
    if ($dateErr !== null) {
        echo json_encode(["ok" => false, "error" => "invalid_dates", "message" => $dateErr], JSON_THROW_ON_ERROR);
        exit;
    }

    $pm = (string)($_POST["payment_method"] ?? "");
    $dm = (string)($_POST["delivery_method"] ?? "");
    if (!in_array($pm, ["store", "iban"], true)) {
        echo json_encode(["ok" => false, "error" => "payment"], JSON_THROW_ON_ERROR);
        exit;
    }
    if (!in_array($dm, ["pickup", "courier"], true)) {
        echo json_encode(["ok" => false, "error" => "delivery"], JSON_THROW_ON_ERROR);
        exit;
    }

    $_SESSION["rent_cart_start"] = $start;
    $_SESSION["rent_cart_end"] = $end;
    $_SESSION["rent_cart_payment_method"] = $pm;
    $_SESSION["rent_cart_delivery_method"] = $dm;
    $_SESSION["sol_intl_address"] = sol_intl_address_from_post($_POST);
    if ($dm === "courier") {
        $_SESSION["rent_cart_delivery_notes"] = sol_intl_address_for_rent_notes($connect, $_SESSION["sol_intl_address"]);
    } else {
        $_SESSION["rent_cart_delivery_notes"] = "";
    }

    $overlapAjax = [];
    foreach ($_SESSION["rent_cart"] ?? [] as $iid => $_q) {
        $iid = (int)$iid;
        if ($iid < 1) {
            continue;
        }
        if (sol_rental_has_customer_block_overlap($connect, $iid, $start, $end, null)) {
            $overlapAjax[] = $iid;
        }
    }
    $overlapAjax = array_values(array_unique($overlapAjax));
    $courierInvalidAjax = $dm === "courier" && !sol_intl_address_is_complete($_SESSION["sol_intl_address"] ?? []);
    $overlapNamesAjax = [];
    foreach ($overlapAjax as $oid) {
        $stn = $connect->prepare("SELECT name FROM instruments WHERE id = ? LIMIT 1");
        if ($stn) {
            $stn->bind_param("i", $oid);
            $stn->execute();
            $rn = $stn->get_result()->fetch_assoc();
            $stn->close();
            $overlapNamesAjax[] = $rn ? (string)($rn["name"] ?? ("#" . $oid)) : ("#" . $oid);
        }
    }

    $dayCountAjax = max(1, (int) round((strtotime($end) - strtotime($start)) / 86400));
    $lineCountAjax = 0;
    $cartKeysAjax = [];
    foreach (array_keys($_SESSION["rent_cart"] ?? []) as $cartKey) {
        $ik = (int)$cartKey;
        if ($ik > 0) {
            $cartKeysAjax[] = $ik;
            $lineCountAjax++;
        }
    }
    $cartKeysAjax = array_values(array_unique($cartKeysAjax));
    $estAjax = 0.0;
    if ($cartKeysAjax !== []) {
        $idsSqlAjax = implode(",", $cartKeysAjax);
        $er = $connect->query("SELECT id, daily_price FROM instruments WHERE is_active = 1 AND id IN ($idsSqlAjax)");
        if ($er) {
            while ($pr = $er->fetch_assoc()) {
                $estAjax += (float)($pr["daily_price"] ?? 0) * $dayCountAjax;
            }
        }
    }
    $estAjax = round($estAjax, 2);

    echo json_encode(
        [
            "ok" => true,
            "review_ready" => $overlapAjax === [] && !$courierInvalidAjax,
            "overlap" => $overlapAjax !== [],
            "courier_invalid" => $courierInvalidAjax,
            "overlap_names" => $overlapNamesAjax,
            "summary" => [
                "start" => $start,
                "end" => $end,
                "days" => $dayCountAjax,
                "lines" => $lineCountAjax,
                "est" => $estAjax,
            ],
        ],
        JSON_THROW_ON_ERROR
    );
    exit;
}

if (isset($_GET["remove"])) {
    $id = (int)$_GET["remove"];
    unset($_SESSION["rent_cart"][$id]);
    header("Location: " . sol_url("rent/rent_cart.php"));
    exit;
}

$nav_role = isset($_SESSION["adm"]) ? "admin" : "user";
foreach (array_keys($_SESSION["rent_cart"] ?? []) as $rk) {
    $ik = (int)$rk;
    if ($ik > 0) {
        $_SESSION["rent_cart"][$ik] = 1;
    } else {
        unset($_SESSION["rent_cart"][$rk]);
    }
}
$rentCart = $_SESSION["rent_cart"] ?? [];
$rentStart = (string)($_SESSION["rent_cart_start"] ?? date("Y-m-d"));
$rentEnd = (string)($_SESSION["rent_cart_end"] ?? date("Y-m-d", strtotime("+7 days")));
$rentPay = (string)($_SESSION["rent_cart_payment_method"] ?? "store");
$rentDel = (string)($_SESSION["rent_cart_delivery_method"] ?? "pickup");
$courierOptsInvalid = $rentDel === "courier" && !sol_intl_address_is_complete($_SESSION["sol_intl_address"] ?? []);

$flashErr = $_SESSION["flash_error"] ?? "";
unset($_SESSION["flash_error"]);

$rows = [];
$cartInstrumentIds = [];
foreach ($rentCart as $k => $q) {
    $ii = (int)$k;
    if ($ii > 0 && (int)$q >= 1) {
        $cartInstrumentIds[] = $ii;
    }
}
$cartInstrumentIds = array_values(array_unique($cartInstrumentIds));

$bookingsByInstrument = [];
if ($cartInstrumentIds !== []) {
    $idsSql = implode(",", $cartInstrumentIds);
    $result = $connect->query("SELECT id, name, daily_price, image_url, description FROM instruments WHERE id IN ($idsSql)");
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $rows[(int)$r["id"]] = $r;
        }
    }

    $ph = implode(",", array_fill(0, count($cartInstrumentIds), "?"));
    $types = str_repeat("i", count($cartInstrumentIds));
    $st = $connect->prepare("
        SELECT instrument_id, start_date, end_date
        FROM rental_requests
        WHERE status IN ('pending','approved')
          AND instrument_id IN ($ph)
        ORDER BY instrument_id, start_date
    ");
    if ($st) {
        $st->bind_param($types, ...$cartInstrumentIds);
        $st->execute();
        $br = $st->get_result();
        while ($b = $br->fetch_assoc()) {
            $iid = (int)$b["instrument_id"];
            $sd = (string)$b["start_date"];
            $ed = (string)$b["end_date"];
            if ($sd !== "" && $ed !== "" && $ed >= $sd) {
                $bookingsByInstrument[$iid][] = [$sd, $ed];
            }
        }
        $st->close();
    }
    foreach ($cartInstrumentIds as $iid) {
        if (!isset($bookingsByInstrument[$iid])) {
            $bookingsByInstrument[$iid] = [];
        }
    }
}

$overlapIds = [];
if (!empty($rentCart)) {
    foreach ($rentCart as $iid => $qty) {
        $iid = (int)$iid;
        $qty = (int)$qty;
        if ($iid < 1 || $qty < 1) {
            continue;
        }
        if (sol_rental_has_customer_block_overlap($connect, $iid, $rentStart, $rentEnd, null)) {
            $overlapIds[] = $iid;
        }
    }
}
$overlapIds = array_values(array_unique($overlapIds));

$rentDayCount = max(1, (int) round((strtotime($rentEnd) - strtotime($rentStart)) / 86400));
$rentSummaryEst = 0.0;
foreach ($cartInstrumentIds as $sumIid) {
    $rentSummaryEst += (float)($rows[$sumIid]["daily_price"] ?? 0) * $rentDayCount;
}
$rentSummaryEst = round($rentSummaryEst, 2);
$rentLineCount = count($cartInstrumentIds);
$rentDailyRateSum = 0.0;
foreach ($cartInstrumentIds as $dsid) {
    $rentDailyRateSum += (float)($rows[$dsid]["daily_price"] ?? 0);
}
$rentDailyRateSum = round($rentDailyRateSum, 2);

$extra_head = "";
$rent_calendar_json = "";
if (!empty($rentCart)) {
    $rent_calendar_json = json_encode(
        [
            "instruments" => $cartInstrumentIds,
            "bookings" => $bookingsByInstrument,
        ],
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_THROW_ON_ERROR
    );
    $extra_head = <<<'HTML'
<link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
<style>
.sol-rent-cart-wrap { max-width: 720px; margin-left: auto; margin-right: auto; }
.sol-rent-cart-shell {
  border-radius: 1rem;
  box-shadow: 0 0.35rem 1.25rem rgba(15, 23, 42, 0.08);
  border: 1px solid rgba(15, 23, 42, 0.06);
  overflow: hidden;
  background: #fff;
}
.sol-rent-cart-shell .card-header {
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
  font-weight: 600;
  letter-spacing: 0.02em;
}
.sol-rent-cart-item {
  display: flex;
  gap: 1rem;
  align-items: stretch;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid rgba(15, 23, 42, 0.06);
  transition: background 0.15s ease;
}
.sol-rent-cart-item:last-child { border-bottom: 0; }
.sol-rent-cart-item:hover { background: #fafbfc; }
.sol-rent-cart-thumb {
  width: 4.5rem;
  height: 4.5rem;
  object-fit: cover;
  border-radius: 0.65rem;
  border: 1px solid rgba(15, 23, 42, 0.08);
  flex-shrink: 0;
}
.sol-rent-cart-item-body { min-width: 0; flex: 1; }
.sol-rent-cart-item-title { font-weight: 600; font-size: 0.95rem; color: #0f172a; }
.sol-rent-cart-item-meta { font-size: 0.8rem; color: #64748b; margin-top: 0.25rem; }
.sol-rent-cart-item-price { font-weight: 700; color: #0d6efd; white-space: nowrap; font-size: 0.95rem; }
.sol-rent-summary-panel {
  font-size: 0.875rem;
  color: #475569;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  border: 1px solid rgba(15, 23, 42, 0.06);
}
.sol-rent-summary-panel #sol-rent-strip-est { font-variant-numeric: tabular-nums; }
.sol-rent-options-panel .form-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; font-weight: 600; }
.sol-rent-options-panel .form-check-label { font-size: 0.875rem; }
#sol-rent-prefs-status { min-height: 1.25rem; }
.flatpickr-day.sol-rent-booked,
.flatpickr-day.flatpickr-disabled.sol-rent-booked {
  background: #fecaca !important;
  border-color: #f87171 !important;
  color: #991b1b !important;
  font-weight: 600;
}
.flatpickr-day.sol-rent-booked:hover { background: #fca5a5 !important; }
</style>
HTML;
}

$page_title = "Rent cart";
$active_nav = "rent_cart";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 sol-rent-cart-wrap">
        <h1 class="h4 mb-0 fw-semibold text-dark">Rent cart</h1>
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <?php if ($flashErr !== ""): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-3 sol-rent-cart-wrap"><?= htmlspecialchars($flashErr, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <?php if (empty($rentCart)): ?>
        <div class="alert alert-info border-0 shadow-sm rounded-3 sol-rent-cart-wrap">Your rent cart is empty. Browse the <a href="<?= htmlspecialchars(sol_url("rent/rentcatalog.php"), ENT_QUOTES, "UTF-8") ?>">instruments catalog</a>.</div>
    <?php else: ?>
        <div class="sol-rent-cart-wrap">
            <div class="card sol-rent-cart-shell mb-4">
                <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="text-uppercase small text-secondary" style="letter-spacing: 0.05em;">Instruments</span>
                    <span class="badge rounded-pill bg-primary text-white border border-primary"><?= (int)$rentLineCount ?></span>
                </div>
                <?php foreach ($rentCart as $iid => $qty): ?>
                    <?php
                    $iid = (int)$iid;
                    $qty = (int)$qty;
                    $row = $rows[$iid] ?? null;
                    if (!$row || $qty < 1) {
                        continue;
                    }
                    $pic = htmlspecialchars((string)($row["image_url"] ?? "instrument.jpg"), ENT_QUOTES, "UTF-8");
                    $name = htmlspecialchars((string)($row["name"] ?? ""), ENT_QUOTES, "UTF-8");
                    $day = htmlspecialchars((string)($row["daily_price"] ?? ""), ENT_QUOTES, "UTF-8");
                    $ex = sol_line_excerpt($row["description"] ?? null, 72);
                    $exHtml = $ex !== "" ? htmlspecialchars($ex, ENT_QUOTES, "UTF-8") : "";
                    ?>
                    <div class="sol-rent-cart-item">
                        <img src="<?= htmlspecialchars(sol_url("pictures/" . $pic), ENT_QUOTES, "UTF-8") ?>" alt="" class="sol-rent-cart-thumb">
                        <div class="sol-rent-cart-item-body d-flex flex-column flex-md-row align-items-md-start justify-content-md-between gap-2">
                            <div class="min-w-0">
                                <div class="sol-rent-cart-item-title"><?= $name ?></div>
                                <?php if ($exHtml !== ""): ?>
                                    <div class="sol-rent-cart-item-meta text-truncate"><?= $exHtml ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center gap-3 flex-shrink-0">
                                <span class="sol-rent-cart-item-price">€<?= $day ?><span class="fw-normal text-secondary small">/day</span></span>
                                <a class="btn btn-sm btn-outline-danger rounded-pill px-3" href="<?= htmlspecialchars(sol_url("rent/rent_cart.php?remove=" . $iid), ENT_QUOTES, "UTF-8") ?>" title="Remove">Remove</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card sol-rent-cart-shell mb-4 sol-rent-options-panel">
                <div class="card-header py-3 px-4">
                    <span class="text-uppercase small text-secondary" style="letter-spacing: 0.05em;">Schedule &amp; options</span>
                </div>
                <div class="card-body px-4 py-4">
                    <form action="<?= htmlspecialchars(sol_url("rent/rent_cart.php"), ENT_QUOTES, "UTF-8") ?>" id="sol-rent-dates-form" class="row g-4" onsubmit="return false;">
                        <?= sol_csrf_field() ?>
                        <input type="hidden" name="start_date" id="sol-rent-start-hidden" value="<?= htmlspecialchars($rentStart, ENT_QUOTES, "UTF-8") ?>">
                        <input type="hidden" name="end_date" id="sol-rent-end-hidden" value="<?= htmlspecialchars($rentEnd, ENT_QUOTES, "UTF-8") ?>">
                        <div class="col-12 sol-rent-cal-wrap">
                            <label class="form-label mb-2" for="sol-rent-range-input">Rental dates</label>
                            <input type="text" class="form-control form-control-lg rounded-3 border-secondary-subtle shadow-sm" id="sol-rent-range-input" readonly autocomplete="off" placeholder="Tap to choose dates…">
                            <p class="form-text small mb-0 mt-2" id="sol-rent-cal-hint"><span class="text-danger fw-semibold">Red</span> days are booked. Changes save automatically.</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-2">Delivery</label>
                            <div class="border rounded-3 p-3 bg-light bg-opacity-50">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="delivery_method" id="cart_del_pickup" value="pickup" <?= $rentDel === "pickup" ? "checked" : "" ?>>
                                    <label class="form-check-label" for="cart_del_pickup"><?= htmlspecialchars(sol_rental_delivery_label("pickup"), ENT_QUOTES, "UTF-8") ?></label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="delivery_method" id="cart_del_courier" value="courier" <?= $rentDel === "courier" ? "checked" : "" ?>>
                                    <label class="form-check-label" for="cart_del_courier"><?= htmlspecialchars(sol_rental_delivery_label("courier"), ENT_QUOTES, "UTF-8") ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-2">Payment</label>
                            <div class="border rounded-3 p-3 bg-light bg-opacity-50">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cart_pay_store" value="store" <?= $rentPay === "store" ? "checked" : "" ?>>
                                    <label class="form-check-label" for="cart_pay_store"><?= htmlspecialchars(sol_rental_payment_label("store"), ENT_QUOTES, "UTF-8") ?></label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cart_pay_iban" value="iban" <?= $rentPay === "iban" ? "checked" : "" ?>>
                                    <label class="form-check-label" for="cart_pay_iban"><?= htmlspecialchars(sol_rental_payment_label("iban"), ENT_QUOTES, "UTF-8") ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 <?= $rentDel === "courier" ? "" : "d-none" ?>" id="sol-cart-courier-wrap">
                            <?php
                            $intl_id_prefix = "rent";
                            $intl_values = $_SESSION["sol_intl_address"] ?? sol_intl_address_template();
                            require dirname(__DIR__) . "/includes/partials/intl_address_fields.php";
                            ?>
                        </div>
                        <div class="col-12">
                            <p class="small mb-0" id="sol-rent-prefs-status"><span class="text-success d-none" id="sol-rent-saved-pill"><i class="bi bi-check-circle-fill me-1"></i>Saved</span></p>
                        </div>
                    </form>
                    <div id="sol-rent-live-msg" class="mt-2">
                        <?php if ($overlapIds !== []): ?>
                            <?php
                            $names = [];
                            foreach ($overlapIds as $oid) {
                                $r = $rows[(int)$oid] ?? null;
                                $names[] = $r ? (string)($r["name"] ?? ("#" . $oid)) : ("#" . $oid);
                            }
                            ?>
                            <div class="alert alert-danger border-0 mb-2 small rounded-3 sol-rent-live-alert">
                                Unavailable for these dates: <strong><?= htmlspecialchars(implode(", ", $names), ENT_QUOTES, "UTF-8") ?></strong>.
                            </div>
                        <?php endif; ?>
                        <?php if ($courierOptsInvalid): ?>
                            <div class="alert alert-warning border-0 mb-0 small rounded-3 sol-rent-live-alert" id="sol-rent-courier-hint">
                                Courier delivery needs the full international address (name, street, city, postal code, country).
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card sol-rent-cart-shell mb-4 overflow-hidden">
                <div class="sol-rent-summary-panel py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <span id="sol-rent-strip-meta"><?= (int)$rentLineCount ?> item<?= $rentLineCount === 1 ? "" : "s" ?> · <?= (int)$rentDayCount ?> day<?= $rentDayCount === 1 ? "" : "s" ?> · <?= htmlspecialchars($rentStart, ENT_QUOTES, "UTF-8") ?> → <?= htmlspecialchars($rentEnd, ENT_QUOTES, "UTF-8") ?></span>
                    <span class="fw-semibold text-dark">Est. <span class="text-primary" id="sol-rent-strip-est">€<?= htmlspecialchars((string)$rentSummaryEst, ENT_QUOTES, "UTF-8") ?></span></span>
                </div>
            </div>

            <div class="d-grid d-sm-flex justify-content-sm-end gap-2">
                <?php $reviewReady = $overlapIds === [] && !$courierOptsInvalid; ?>
                <a href="<?= htmlspecialchars(sol_url("rent/rent_checkout_confirm.php"), ENT_QUOTES, "UTF-8") ?>" id="sol-rent-review-btn" class="btn btn-primary btn-lg rounded-3 px-4 shadow-sm <?= $reviewReady ? "" : "disabled" ?>" <?= $reviewReady ? "" : "aria-disabled=\"true\" tabindex=\"-1\"" ?> style="min-width: 12rem;">Review &amp; confirm</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$rent_cart_fetch_url = json_encode(sol_url("rent/rent_cart.php"), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$fpDefaultStart = $rentStart;
$fpDefaultEnd = $rentEnd;
$todayYmd = date("Y-m-d");
if ($fpDefaultStart < $todayYmd) {
    $fpDefaultStart = $todayYmd;
}
if ($fpDefaultEnd <= $fpDefaultStart) {
    $fpDefaultEnd = date("Y-m-d", strtotime($fpDefaultStart . " +7 days"));
}
$rent_start_js = json_encode($fpDefaultStart, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$rent_end_js = json_encode($fpDefaultEnd, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$rent_daily_sum_js = json_encode($rentDailyRateSum, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$rent_line_count_js = json_encode((int)$rentLineCount, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

if (!empty($rentCart)) {
    $layout_extra_scripts = '<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>'
        . "<script>\n"
        . "window.SOL_RENT_CAL = " . $rent_calendar_json . ";\n"
        . "window.SOL_RENT_CART_POST_URL = " . $rent_cart_fetch_url . ";\n"
        . "window.SOL_RENT_DAILY_SUM = " . $rent_daily_sum_js . ";\n"
        . "window.SOL_RENT_LINE_COUNT = " . $rent_line_count_js . ";\n"
        . <<<'JS'
(function () {
  var saveTimer = null;
  function updateRentStripLocal() {
    var h0 = document.getElementById("sol-rent-start-hidden");
    var h1 = document.getElementById("sol-rent-end-hidden");
    var sm = document.getElementById("sol-rent-strip-meta");
    var se = document.getElementById("sol-rent-strip-est");
    if (!h0 || !h1 || !sm || !se) {
      return;
    }
    var s0 = h0.value;
    var s1 = h1.value;
    if (!s0 || !s1 || s1 <= s0) {
      return;
    }
    var p0 = s0.split("-");
    var p1 = s1.split("-");
    var d0 = new Date(parseInt(p0[0], 10), parseInt(p0[1], 10) - 1, parseInt(p0[2], 10));
    var d1 = new Date(parseInt(p1[0], 10), parseInt(p1[1], 10) - 1, parseInt(p1[2], 10));
    var days = Math.max(1, Math.round((d1.getTime() - d0.getTime()) / 86400000));
    var lines = parseInt(String(window.SOL_RENT_LINE_COUNT), 10) || 1;
    var daily = Number(window.SOL_RENT_DAILY_SUM) || 0;
    var est = Math.round(daily * days * 100) / 100;
    var itemWord = lines === 1 ? "item" : "items";
    var dayWord = days === 1 ? "day" : "days";
    sm.textContent = lines + " " + itemWord + " · " + days + " " + dayWord + " · " + s0 + " → " + s1;
    se.textContent = "€" + est;
  }
  function ymd(d) {
    var y = d.getFullYear();
    var m = String(d.getMonth() + 1).padStart(2, "0");
    var day = String(d.getDate()).padStart(2, "0");
    return y + "-" + m + "-" + day;
  }
  function rangesFor(iid) {
    var b = window.SOL_RENT_CAL.bookings || {};
    return b[iid] || b[String(iid)] || [];
  }
  function isBookedDay(dateObj) {
    var s = ymd(dateObj);
    var ids = window.SOL_RENT_CAL.instruments || [];
    for (var i = 0; i < ids.length; i++) {
      var ranges = rangesFor(ids[i]);
      for (var r = 0; r < ranges.length; r++) {
        var a = ranges[r][0];
        var b = ranges[r][1];
        if (s >= a && s <= b) {
          return true;
        }
      }
    }
    return false;
  }
  function isPastDay(dateObj) {
    var t = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate()).getTime();
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    return t < today.getTime();
  }
  function rangeHasBookedDay(a, b) {
    var x = new Date(a.getFullYear(), a.getMonth(), a.getDate());
    var end = new Date(b.getFullYear(), b.getMonth(), b.getDate());
    if (x > end) {
      var sw = x;
      x = end;
      end = sw;
    }
    while (x.getTime() <= end.getTime()) {
      if (isBookedDay(x)) {
        return true;
      }
      x.setDate(x.getDate() + 1);
    }
    return false;
  }
  function syncHidden(selectedDates) {
    var h0 = document.getElementById("sol-rent-start-hidden");
    var h1 = document.getElementById("sol-rent-end-hidden");
    if (!h0 || !h1) {
      return;
    }
    if (!selectedDates || selectedDates.length === 0) {
      h0.value = "";
      h1.value = "";
      return;
    }
    if (selectedDates.length === 1) {
      h0.value = ymd(selectedDates[0]);
      h1.value = "";
      return;
    }
    var a = selectedDates[0];
    var b = selectedDates[1];
    if (a > b) {
      var t = a;
      a = b;
      b = t;
    }
    h0.value = ymd(a);
    h1.value = ymd(b);
  }
  function buildPrefsFormData() {
    var form = document.getElementById("sol-rent-dates-form");
    if (!form) return null;
    var fd = new FormData(form);
    fd.append("ajax_rent_cart_save", "1");
    return fd;
  }
  function setReviewButton(ready) {
    var btn = document.getElementById("sol-rent-review-btn");
    if (!btn) return;
    if (ready) {
      btn.classList.remove("disabled");
      btn.removeAttribute("aria-disabled");
      btn.removeAttribute("tabindex");
    } else {
      btn.classList.add("disabled");
      btn.setAttribute("aria-disabled", "true");
      btn.setAttribute("tabindex", "-1");
    }
  }
  function flashSaved() {
    var pill = document.getElementById("sol-rent-saved-pill");
    if (!pill) return;
    pill.classList.remove("d-none");
    window.clearTimeout(flashSaved._t);
    flashSaved._t = window.setTimeout(function () {
      pill.classList.add("d-none");
    }, 1600);
  }
  function saveRentPrefs() {
    var fd = buildPrefsFormData();
    if (!fd) return;
    var h0 = document.getElementById("sol-rent-start-hidden");
    var h1 = document.getElementById("sol-rent-end-hidden");
    if (!h0 || !h1 || !h0.value || !h1.value || h1.value <= h0.value) {
      return;
    }
    fetch(window.SOL_RENT_CART_POST_URL, { method: "POST", body: fd, credentials: "same-origin" })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.ok) {
          if (typeof Swal !== "undefined" && data && data.error) {
            var msg = data.message || "Could not save preferences. Please refresh the page.";
            Swal.fire({ icon: "error", text: msg });
          }
          return;
        }
        setReviewButton(!!data.review_ready);
        if (data.summary) {
          var sm = document.getElementById("sol-rent-strip-meta");
          var se = document.getElementById("sol-rent-strip-est");
          var s = data.summary;
          if (sm && s.lines != null && s.days != null && s.start && s.end) {
            var itemWord = s.lines === 1 ? "item" : "items";
            var dayWord = s.days === 1 ? "day" : "days";
            sm.textContent = s.lines + " " + itemWord + " · " + s.days + " " + dayWord + " · " + s.start + " → " + s.end;
          }
          if (se && s.est != null) {
            se.textContent = "€" + s.est;
          }
        }
        var live = document.getElementById("sol-rent-live-msg");
        if (live) {
          var h = "";
          if (data.overlap && data.overlap_names && data.overlap_names.length) {
            var safeNames = data.overlap_names.map(function (n) {
              return String(n).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
            }).join(", ");
            h += "<div class=\"alert alert-danger border-0 mb-2 small rounded-3 sol-rent-live-alert\">Unavailable for these dates: <strong>" + safeNames + "</strong>.</div>";
          }
          if (data.courier_invalid) {
            h += "<div class=\"alert alert-warning border-0 mb-0 small rounded-3 sol-rent-live-alert\">Complete the international address (name, street, city, postal code, country).</div>";
          }
          live.innerHTML = h;
        }
        flashSaved();
        if (window.SOL_NAV && typeof window.SOL_NAV.refresh === "function") {
          window.SOL_NAV.refresh();
        }
      })
      .catch(function () {
        if (typeof Swal !== "undefined") {
          Swal.fire({ icon: "error", text: "Network error while saving." });
        }
      });
  }
  function scheduleSave() {
    window.clearTimeout(saveTimer);
    saveTimer = window.setTimeout(saveRentPrefs, 320);
  }
  var inp = document.getElementById("sol-rent-range-input");
  if (!inp || typeof flatpickr === "undefined") {
    return;
  }
  var wrapCourier = document.getElementById("sol-cart-courier-wrap");
  function syncCourierWrap() {
    if (!wrapCourier) return;
    var c = document.querySelector('#sol-rent-dates-form input[name="delivery_method"]:checked');
    var courier = c && c.value === "courier";
    wrapCourier.classList.toggle("d-none", !courier);
  }
  document.querySelectorAll('#sol-rent-dates-form input[name="delivery_method"]').forEach(function (r) {
    r.addEventListener("change", function () {
      syncCourierWrap();
      saveRentPrefs();
    });
  });
  syncCourierWrap();
  document.querySelectorAll('#sol-rent-dates-form input[name="payment_method"]').forEach(function (r) {
    r.addEventListener("change", saveRentPrefs);
  });
  if (wrapCourier) {
    wrapCourier.addEventListener("input", scheduleSave);
    wrapCourier.addEventListener("change", scheduleSave);
  }
  var maxD = new Date();
  maxD.setFullYear(maxD.getFullYear() + 1);
  var showM = typeof window.matchMedia !== "undefined" && window.matchMedia("(min-width: 768px)").matches ? 2 : 1;
  flatpickr(inp, {
    mode: "range",
    dateFormat: "Y-m-d",
    showMonths: showM,
    minDate: "today",
    maxDate: maxD,
    defaultDate: [RENT_START_PLACEHOLDER, RENT_END_PLACEHOLDER],
    disable: [function (date) {
      return isPastDay(date) || isBookedDay(date);
    }],
    onDayCreate: function (_sel, _inputVal, _inst, dayElem) {
      var d = dayElem && dayElem.dateObj;
      if (!(d instanceof Date)) {
        return;
      }
      if (isBookedDay(d)) {
        dayElem.classList.add("sol-rent-booked");
      }
    },
    onChange: function (selectedDates, _str, inst) {
      if (selectedDates.length === 2) {
        var a = selectedDates[0];
        var b = selectedDates[1];
        if (rangeHasBookedDay(a, b)) {
          inst.clear();
          syncHidden([]);
          if (typeof Swal !== "undefined") {
            Swal.fire({ icon: "error", text: "That range includes unavailable dates. Choose a range without red days." });
          }
          return;
        }
      }
      syncHidden(selectedDates);
      if (selectedDates.length === 2) {
        updateRentStripLocal();
        saveRentPrefs();
      }
    },
    onReady: function (_d, _s, inst) {
      syncHidden(inst.selectedDates);
      updateRentStripLocal();
    }
  });
})();
JS
        . "\n</script>\n";
    $layout_extra_scripts = str_replace(
        ["RENT_START_PLACEHOLDER", "RENT_END_PLACEHOLDER"],
        [$rent_start_js, $rent_end_js],
        $layout_extra_scripts
    );
} else {
    $layout_extra_scripts = "";
}

require_once dirname(__DIR__) . "/includes/layout_bottom.php";
