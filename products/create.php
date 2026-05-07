<?php

declare(strict_types=1);

# Admin create product: supplier picklist + image upload.

require_once __DIR__ . "/../includes/app.php";
sol_require_admin();
require_once __DIR__ . "/../file_upload.php";

$sqlSuppliers = "SELECT `supplierId`, `sup_name` FROM `suppliers` ORDER BY `sup_name` ASC";
$resSup = $connect->query($sqlSuppliers);
$suppliers = $resSup ? $resSup->fetch_all(MYSQLI_ASSOC) : [];

$alert = "";

$name = "";
$price = "";
$description = "";
$supplierSel = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create_product"])) {
    if (!sol_csrf_verify()) {
        $alert = "Security check failed.";
    } else {
        $name = trim((string)($_POST["name"] ?? ""));
        $price = trim((string)($_POST["price"] ?? ""));
        $description = trim((string)($_POST["description"] ?? ""));
        $supplierSel = (string)($_POST["supplier"] ?? "");

        if ($name === "") {
            $alert = "Product name is required.";
        } elseif ($price === "" || !is_numeric($price) || (float)$price < 0) {
            $alert = "Please enter a valid price.";
        } else {
            $upload = productImageUpload($_FILES["picture"] ?? ["error" => 4]);
            if ($upload[0] === null) {
                $alert = $upload[1];
            } else {
                $escName = $connect->real_escape_string($name);
                $picFile = $connect->real_escape_string($upload[0]);
                $priceSql = $connect->real_escape_string(number_format((float)$price, 2, ".", ""));
                $descSql = $description === "" ? "NULL" : "'" . $connect->real_escape_string($description) . "'";
                $fkSql = "NULL";
                if ($supplierSel !== "" && $supplierSel !== "0" && ctype_digit($supplierSel)) {
                    $fkSql = (int)$supplierSel;
                }

                $ins = "INSERT INTO `products` (`name`, `price`, `picture`, `description`, `fk_supplier_id`)
                        VALUES ('$escName', '$priceSql', '$picFile', $descSql, $fkSql)";
                if ($connect->query($ins)) {
                    header("Location: " . sol_url("products/index.php"));
                    exit;
                }
                $alert = "Could not save the product.";
            }
        }
    }
}

$page_title = "Add product";
$nav_role = "admin";
$active_nav = "admin_catalog";
require_once __DIR__ . "/../includes/layout_top.php";
?>

<div class="container py-4" style="max-width: 640px;">
    <a href="<?= htmlspecialchars(sol_url("products/index.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm mb-3">← Back to products</a>
    <h1 class="h3 mb-4">Add product</h1>

    <?php if ($alert !== ""): ?>
        <div class="alert alert-danger border-0"><?= htmlspecialchars($alert, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm sol-card-shell">
        <?= sol_csrf_field() ?>
        <div class="card-body">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required value="<?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>">
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Price</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required value="<?= htmlspecialchars($price, ENT_QUOTES, "UTF-8") ?>">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($description, ENT_QUOTES, "UTF-8") ?></textarea>
            </div>
            <div class="mb-3">
                <label for="picture" class="form-label">Picture</label>
                <input type="file" class="form-control" id="picture" name="picture" accept="image/*">
                <div class="form-text">Optional — defaults to product.jpg if omitted.</div>
            </div>
            <div class="mb-3">
                <label for="supplier" class="form-label">Supplier</label>
                <select class="form-select" id="supplier" name="supplier">
                    <option value="">— None —</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= (int)$s["supplierId"] ?>" <?= (string)$supplierSel === (string)$s["supplierId"] ? "selected" : "" ?>>
                            <?= htmlspecialchars($s["sup_name"] ?? "", ENT_QUOTES, "UTF-8") ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="create_product" class="btn btn-primary">Save product</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . "/../includes/layout_bottom.php";
