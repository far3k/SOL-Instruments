<?php

declare(strict_types=1);

# Rent instrument catalog: filters, POST add-to-rent-cart with CSRF.

require_once dirname(__DIR__) . "/includes/app.php";
require_once dirname(__DIR__) . "/includes/rental_helpers.php";
sol_require_user();

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_to_rent_cart"])) {
    if (!sol_csrf_verify()) {
        $_SESSION["flash_error"] = "Security check failed.";
    } else {
        $instrumentId = (int)($_POST["id"] ?? 0);
        if ($instrumentId > 0) {
            $_SESSION["rent_cart"][$instrumentId] = 1;
        }
    }
    $q = $_GET["q"] ?? "";
    $sort = $_GET["sort"] ?? "";
    $cat = $_GET["category"] ?? "";
    $redir = sol_url("rent/rentcatalog.php");
    $qs = [];
    if (is_string($q) && trim($q) !== "") {
        $qs[] = "q=" . rawurlencode(trim($q));
    }
    if (is_string($sort) && $sort !== "") {
        $qs[] = "sort=" . rawurlencode($sort);
    }
    if (is_string($cat) && $cat !== "") {
        $qs[] = "category=" . rawurlencode($cat);
    }
    if ($qs !== []) {
        $redir .= "?" . implode("&", $qs);
    }
    header("Location: " . $redir);
    exit;
}

$searchQuery = trim($_GET["q"] ?? "");
$sortOption = $_GET["sort"] ?? "price_asc";
if (!in_array($sortOption, ["price_asc", "price_desc"], true)) {
    $sortOption = "price_asc";
}

$categoryFilter = isset($_GET["category"]) ? (int)$_GET["category"] : 0;
$hasCategory = sol_db_column_exists($connect, "instruments", "category_id") && sol_db_table_exists($connect, "categories");
$categories = [];
if ($hasCategory) {
    $cr = $connect->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $cr ? $cr->fetch_all(MYSQLI_ASSOC) : [];
}
$categoryNameById = [];
foreach ($categories as $cRow) {
    $categoryNameById[(int)($cRow["id"] ?? 0)] = (string)($cRow["name"] ?? "");
}

$orderSql = $sortOption === "price_desc" ? "ORDER BY daily_price DESC" : "ORDER BY daily_price ASC";

$instruments = [];
if ($searchQuery !== "") {
    $like = "%" . sol_rental_like_fragment($searchQuery) . "%";
    if ($hasCategory && $categoryFilter > 0) {
        $st = $connect->prepare("SELECT * FROM instruments WHERE is_active = 1 AND category_id = ? AND name LIKE ? " . $orderSql);
        $st->bind_param("is", $categoryFilter, $like);
    } else {
        $st = $connect->prepare("SELECT * FROM instruments WHERE is_active = 1 AND name LIKE ? " . $orderSql);
        $st->bind_param("s", $like);
    }
    $st->execute();
    $instruments = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
} else {
    if ($hasCategory && $categoryFilter > 0) {
        $st = $connect->prepare("SELECT * FROM instruments WHERE is_active = 1 AND category_id = ? " . $orderSql);
        $st->bind_param("i", $categoryFilter);
        $st->execute();
        $instruments = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
    } else {
        $result = $connect->query("SELECT * FROM instruments WHERE is_active = 1 " . $orderSql);
        $instruments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

require_once dirname(__DIR__) . "/includes/partials/wishlist_fab.php";

$wishMembers = [];
if (isset($_SESSION["user"]) && !isset($_SESSION["adm"]) && $instruments !== []) {
    $uidW = sol_current_uid();
    $iids = array_map(static fn (array $r): int => (int)($r["id"] ?? 0), $instruments);
    $wishMembers = sol_wishlist_membership($connect, $uidW, "instrument", $iids);
}

$flash = $_SESSION["flash_error"] ?? "";
unset($_SESSION["flash_error"]);

$page_title = "Instruments catalog";
$nav_role = isset($_SESSION["adm"]) ? "admin" : "user";
$active_nav = "catalog_rent";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-4" style="max-width: 1140px;">
    <?php if ($flash !== ""): ?>
        <div class="alert alert-warning border-0"><?= htmlspecialchars($flash, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <h1 class="h3 mb-3">Instruments for rent</h1>
    <p class="text-muted small mb-4">Add items to your <strong>rent cart</strong>, set dates once, then review &amp; confirm.</p>

    <form class="row g-2 g-md-3 mb-4" method="get">
        <div class="col-12 col-md-4 col-lg-3">
            <input class="form-control" name="q" placeholder="Search by name…" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, "UTF-8") ?>" autocomplete="off">
        </div>
        <?php if ($hasCategory && $categories !== []): ?>
            <div class="col-12 col-md-4 col-lg-3">
                <select class="form-select" name="category">
                    <option value="0" <?= $categoryFilter === 0 ? "selected" : "" ?>>All categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c["id"] ?>" <?= $categoryFilter === (int)$c["id"] ? "selected" : "" ?>><?= htmlspecialchars((string)$c["name"], ENT_QUOTES, "UTF-8") ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="col-md-4 col-lg-3">
            <select class="form-select" name="sort">
                <option value="price_asc" <?= $sortOption === "price_asc" ? "selected" : "" ?>>Daily price ↑</option>
                <option value="price_desc" <?= $sortOption === "price_desc" ? "selected" : "" ?>>Daily price ↓</option>
            </select>
        </div>
        <div class="col-12 col-md-4 col-lg-3 d-grid d-md-block">
            <button class="btn btn-primary w-100" type="submit">Apply filters</button>
        </div>
    </form>

    <div class="row g-4">
        <?php foreach ($instruments as $row): ?>
            <?php
            $iid = (int)$row["id"];
            $instUrl = sol_url("rent/product_details.php?id=" . $iid);
            $catLabel = "";
            if ($hasCategory) {
                $catLabel = trim((string)($categoryNameById[(int)($row["category_id"] ?? 0)] ?? ""));
            }
            ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100 position-relative sol-card-shell sol-shop-product-card">
                    <div class="sol-catalog-card-inner">
                        <div class="sol-square-product-media">
                            <img src="<?= htmlspecialchars(sol_url("pictures/" . ($row["image_url"] ?? "instrument.jpg")), ENT_QUOTES, "UTF-8") ?>" class="card-img-top sol-square-product-img" alt="">
                        </div>
                        <div class="sol-catalog-wish-fab">
                            <?php sol_render_wishlist_fab("instrument", $iid, isset($wishMembers[$iid])); ?>
                        </div>
                        <div class="sol-shop-card-hover position-absolute bottom-0 start-0 end-0">
                            <a href="<?= htmlspecialchars($instUrl, ENT_QUOTES, "UTF-8") ?>" class="btn btn-dark w-100 rounded-0 py-2 small text-uppercase fw-semibold">Quick view</a>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column pt-3 pb-2">
                        <h3 class="h6 card-title mb-1"><?= htmlspecialchars($row["name"] ?? "", ENT_QUOTES, "UTF-8") ?></h3>
                        <?php if ($catLabel !== ""): ?>
                            <p class="text-muted small mb-1">Category: <?= htmlspecialchars($catLabel, ENT_QUOTES, "UTF-8") ?></p>
                        <?php endif; ?>
                        <p class="text-muted small mb-1">Condition: <?= htmlspecialchars($row["condition"] ?? "", ENT_QUOTES, "UTF-8") ?></p>
                        <p class="fw-semibold text-secondary mb-0">€<?= htmlspecialchars((string)($row["daily_price"] ?? ""), ENT_QUOTES, "UTF-8") ?> <span class="fw-normal small">/ day</span></p>
                    </div>
                    <div class="card-body border-top-0 pt-0 sol-catalog-card-actions">
                        <form method="post" class="sol-ajax-cart">
                            <?= sol_csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $iid ?>">
                            <button type="submit" name="add_to_rent_cart" class="btn btn-primary btn-sm w-100">Add to rent cart</button>
                        </form>
                    </div>
                    <a href="<?= htmlspecialchars($instUrl, ENT_QUOTES, "UTF-8") ?>" class="stretched-link"><span class="visually-hidden">View instrument details</span></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
