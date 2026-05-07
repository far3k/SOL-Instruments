<?php

declare(strict_types=1);

# Admin product list with supplier join (procedural mysqli).

require_once __DIR__ . "/../includes/app.php";
sol_require_admin();

$sql = "SELECT p.*, s.`sup_name` AS sup_name
        FROM `products` p
        LEFT JOIN `suppliers` s ON p.`fk_supplier_id` = s.`supplierId`
        ORDER BY p.`id` DESC";
$result = $connect->query($sql);
$rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$page_title = "Products";
$nav_role = "admin";
$active_nav = "admin_catalog";
require_once __DIR__ . "/../includes/layout_top.php";
?>

<div class="container py-4" style="max-width: 1100px;">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
        <a href="<?= htmlspecialchars(sol_url("products/create.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-primary">Add product</a>
        <a href="<?= htmlspecialchars(sol_url("products/suppliers.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary">Suppliers</a>
    </div>

    <h1 class="h3 mb-4">Manage products</h1>

    <?php if (!empty($rows)): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($rows as $row): ?>
                <?php
                $supplierLabel = (isset($row["sup_name"]) && trim((string)$row["sup_name"]) !== "")
                    ? htmlspecialchars($row["sup_name"], ENT_QUOTES, "UTF-8")
                    : "No supplier assigned";
                $pic = htmlspecialchars($row["picture"] ?? "product.jpg", ENT_QUOTES, "UTF-8");
                $name = htmlspecialchars($row["name"] ?? "", ENT_QUOTES, "UTF-8");
                $price = htmlspecialchars((string)($row["price"] ?? ""), ENT_QUOTES, "UTF-8");
                $pid = (int)($row["id"] ?? 0);
                ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0 sol-card-shell">
                        <div class="sol-square-product-media">
                            <img src="<?= htmlspecialchars(sol_url("pictures/" . $pic), ENT_QUOTES, "UTF-8") ?>" class="card-img-top sol-square-product-img" alt="<?= $name ?>">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= $name ?></h5>
                            <p class="card-text mb-1">Price: €<?= $price ?></p>
                            <p class="card-text text-muted small mb-3">Supplier: <?= $supplierLabel ?></p>
                            <div class="d-grid gap-2 mt-auto">
                                <a href="<?= htmlspecialchars(sol_url("products/update.php?id=" . $pid), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-primary btn-sm">Edit</a>
                                <form method="post" action="<?= htmlspecialchars(sol_url("products/delete.php"), ENT_QUOTES, "UTF-8") ?>" data-sol-confirm="Delete this product?">
                                    <?= sol_csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $pid ?>">
                                    <button type="submit" name="delete_product" value="1" class="btn btn-outline-danger btn-sm w-100">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info border-0">No products found.</div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . "/../includes/layout_bottom.php";
