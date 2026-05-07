<?php

declare(strict_types=1);

# Admin edit product by id (GET) with optional new photo.

require_once __DIR__ . "/../includes/app.php";
sol_require_admin();
require_once __DIR__ . "/../file_upload.php";

if (!isset($_GET["id"]) || !ctype_digit((string)$_GET["id"])) {
    header("Location: " . sol_url("products/index.php"));
    exit;
}

$id = (int)$_GET["id"];

$sqlSuppliers = "SELECT `supplierId`, `sup_name` FROM `suppliers` ORDER BY `sup_name` ASC";
$resSup = $connect->query($sqlSuppliers);
$suppliers = $resSup ? $resSup->fetch_all(MYSQLI_ASSOC) : [];

$rowSql = "SELECT * FROM `products` WHERE `id` = $id LIMIT 1";
$rowRes = $connect->query($rowSql);
if (!$rowRes || $rowRes->num_rows !== 1) {
    header("Location: " . sol_url("products/index.php"));
    exit;
}
$row = $rowRes->fetch_assoc();

$alert = "";
$name = $row["name"] ?? "";
$price = isset($row["price"]) ? (string)$row["price"] : "";
$description = $row["description"] ?? "";
$supplierSel = $row["fk_supplier_id"] !== null ? (string)$row["fk_supplier_id"] : "";

$picsDir = SOL_ROOT . DIRECTORY_SEPARATOR . "pictures" . DIRECTORY_SEPARATOR;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_product"])) {
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
            $escName = $connect->real_escape_string($name);
            $priceSql = $connect->real_escape_string(number_format((float)$price, 2, ".", ""));
            $descSql = $description === "" ? "NULL" : "'" . $connect->real_escape_string($description) . "'";
            $fkSql = "NULL";
            if ($supplierSel !== "" && $supplierSel !== "0" && ctype_digit($supplierSel)) {
                $fkSql = (int)$supplierSel;
            }

            $picturePart = "";
            if (isset($_FILES["picture"]) && $_FILES["picture"]["error"] !== 4) {
                $upload = productImageUpload($_FILES["picture"]);
                if ($upload[0] === null) {
                    $alert = $upload[1];
                } else {
                    $oldPic = $row["picture"] ?? "product.jpg";
                    if ($oldPic !== "product.jpg" && $oldPic !== "avatar.png") {
                        $oldPath = $picsDir . $oldPic;
                        if (is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                    $picFile = $connect->real_escape_string($upload[0]);
                    $picturePart = ", `picture` = '$picFile'";
                }
            }

            if ($alert === "") {
                $upd = "UPDATE `products` SET `name` = '$escName', `price` = '$priceSql', `description` = $descSql, `fk_supplier_id` = $fkSql $picturePart WHERE `id` = $id LIMIT 1";
                if ($connect->query($upd)) {
                    header("Location: " . sol_url("products/index.php"));
                    exit;
                }
                $alert = "Could not update the product.";
            }
        }
    }
}

$page_title = "Edit product";
$nav_role = "admin";
$active_nav = "admin_catalog";
require_once __DIR__ . "/../includes/layout_top.php";
?>

<div class="container py-4" style="max-width: 640px;">
    <a href="<?= htmlspecialchars(sol_url("products/index.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm mb-3">← Back to products</a>
    <h1 class="h3 mb-4">Edit product</h1>

    <?php if ($alert !== ""): ?>
        <div class="alert alert-danger border-0"><?= htmlspecialchars($alert, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm sol-card-shell">
        <?= sol_csrf_field() ?>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Current image</label>
                <div>
                    <img src="<?= htmlspecialchars(sol_url("pictures/" . ($row["picture"] ?? "product.jpg")), ENT_QUOTES, "UTF-8") ?>" alt="" class="img-thumbnail" style="max-height: 120px;">
                </div>
            </div>
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
                <label for="picture" class="form-label">New picture</label>
                <input type="file" class="form-control" id="picture" name="picture" accept="image/*">
                <div class="form-text">Leave empty to keep the current image.</div>
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
            <button type="submit" name="update_product" class="btn btn-primary">Update product</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . "/../includes/layout_bottom.php";
