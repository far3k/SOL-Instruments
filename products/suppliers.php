<?php

declare(strict_types=1);

# Admin CRUD for suppliers linked to products (add, edit, delete with FK safety).

require_once __DIR__ . "/../includes/app.php";
sol_require_admin();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function sol_suppliers_redirect(string $msg, string $type = "success", int $edit = 0): never
{
    $url = "products/suppliers.php?flash=" . rawurlencode($msg) . "&type=" . rawurlencode($type);
    if ($edit > 0) {
        $url .= "&edit=" . $edit;
    }
    header("Location: " . sol_url($url));
    exit;
}

$editId = max(0, (int)($_GET["edit"] ?? 0));
$editRow = null;
if ($editId > 0) {
    $st = $connect->prepare("SELECT supplierId, sup_name, sup_email, sup_website FROM suppliers WHERE supplierId = ? LIMIT 1");
    $st->bind_param("i", $editId);
    $st->execute();
    $editRow = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$editRow) {
        $editId = 0;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!sol_csrf_verify()) {
        sol_suppliers_redirect("Security check failed.", "danger", $editId);
    }

    if (isset($_POST["delete_supplier"])) {
        $sid = (int)($_POST["supplier_id"] ?? 0);
        if ($sid < 1) {
            sol_suppliers_redirect("Invalid supplier.", "danger");
        }

        $chk = $connect->prepare("SELECT COUNT(*) AS n FROM products WHERE fk_supplier_id = ? LIMIT 1");
        $chk->bind_param("i", $sid);
        $chk->execute();
        $used = (int)($chk->get_result()->fetch_assoc()["n"] ?? 0);
        $chk->close();
        if ($used > 0) {
            $u = $connect->prepare("UPDATE products SET fk_supplier_id = NULL WHERE fk_supplier_id = ?");
            $u->bind_param("i", $sid);
            $u->execute();
            $u->close();
        }

        $del = $connect->prepare("DELETE FROM suppliers WHERE supplierId = ? LIMIT 1");
        $del->bind_param("i", $sid);
        $del->execute();
        $del->close();
        if ($used > 0) {
            sol_suppliers_redirect("Supplier deleted. {$used} product(s) were detached.", "warning");
        }
        sol_suppliers_redirect("Supplier deleted.");
    }

    $supplierName = trim((string)($_POST["supplier_name"] ?? ""));
    $supplierEmail = trim((string)($_POST["email"] ?? ""));
    $supplierWebsite = trim((string)($_POST["website"] ?? ""));
    $supplierWebsiteDb = $supplierWebsite === "" ? null : $supplierWebsite;

    if ($supplierName === "") {
        sol_suppliers_redirect("Supplier name is required.", "danger", (int)($_POST["supplier_id"] ?? 0));
    }
    if ($supplierEmail === "" || !filter_var($supplierEmail, FILTER_VALIDATE_EMAIL)) {
        sol_suppliers_redirect("Please enter a valid email address.", "danger", (int)($_POST["supplier_id"] ?? 0));
    }
    if ($supplierWebsite !== "" && !filter_var($supplierWebsite, FILTER_VALIDATE_URL)) {
        sol_suppliers_redirect("Please enter a valid website URL (or leave it empty).", "danger", (int)($_POST["supplier_id"] ?? 0));
    }

    if (isset($_POST["add_supplier"])) {
        $ins = $connect->prepare("INSERT INTO suppliers (sup_name, sup_email, sup_website) VALUES (?, ?, ?)");
        $ins->bind_param("sss", $supplierName, $supplierEmail, $supplierWebsiteDb);
        $ins->execute();
        $ins->close();
        sol_suppliers_redirect("Supplier added successfully.");
    }

    if (isset($_POST["update_supplier"])) {
        $sid = (int)($_POST["supplier_id"] ?? 0);
        if ($sid < 1) {
            sol_suppliers_redirect("Invalid supplier.", "danger");
        }
        $up = $connect->prepare("UPDATE suppliers SET sup_name = ?, sup_email = ?, sup_website = ? WHERE supplierId = ? LIMIT 1");
        $up->bind_param("sssi", $supplierName, $supplierEmail, $supplierWebsiteDb, $sid);
        $up->execute();
        $up->close();
        sol_suppliers_redirect("Supplier updated successfully.");
    }
}

$suppliers = [];
$resultList = $connect->query("SELECT supplierId, sup_name, sup_email, sup_website FROM suppliers ORDER BY supplierId DESC");
if ($resultList) {
    $suppliers = $resultList->fetch_all(MYSQLI_ASSOC);
}

$flashMsg = trim((string)($_GET["flash"] ?? ""));
$flashType = (string)($_GET["type"] ?? "success");
if (!in_array($flashType, ["success", "danger", "warning", "info"], true)) {
    $flashType = "info";
}

$page_title = "Suppliers";
$nav_role = "admin";
$active_nav = "admin_catalog";
require_once __DIR__ . "/../includes/layout_top.php";
?>

<div class="container py-4" style="max-width: 1000px;">
    <div class="mb-4 d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <h1 class="h3 mb-0">Suppliers</h1>
        <a href="<?= htmlspecialchars(sol_url("products/index.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm">Products</a>
    </div>

    <?php if ($flashMsg !== ""): ?>
        <div class="alert alert-<?= htmlspecialchars($flashType, ENT_QUOTES, "UTF-8") ?> border-0" role="alert">
            <?= htmlspecialchars($flashMsg, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm sol-card-shell mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3"><?= $editRow ? "Edit supplier" : "Add supplier" ?></h2>
            <form method="post" novalidate>
                <?= sol_csrf_field() ?>
                <?php if ($editRow): ?>
                    <input type="hidden" name="supplier_id" value="<?= (int)$editRow["supplierId"] ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label for="supplier_name" class="form-label">Supplier name</label>
                    <input type="text" class="form-control" id="supplier_name" name="supplier_name" required value="<?= htmlspecialchars((string)($editRow["sup_name"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars((string)($editRow["sup_email"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                </div>
                <div class="mb-3">
                    <label for="website" class="form-label">Website</label>
                    <input type="text" class="form-control" id="website" name="website" value="<?= htmlspecialchars((string)($editRow["sup_website"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                    <div class="form-text">Optional. Must be a valid URL if provided.</div>
                </div>
                <?php if ($editRow): ?>
                    <button type="submit" name="update_supplier" value="1" class="btn btn-primary">Save changes</button>
                    <a href="<?= htmlspecialchars(sol_url("products/suppliers.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary ms-1">Cancel</a>
                <?php else: ?>
                    <button type="submit" name="add_supplier" value="1" class="btn btn-primary">Add supplier</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <h2 class="h5 mb-3">Supplier list</h2>

    <?php if (!empty($suppliers)): ?>
        <div class="table-responsive card border-0 shadow-sm">
            <table class="table table-bordered mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:70px">ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Website</th>
                        <th style="width:180px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td><?= (int)($supplier["supplierId"] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string)($supplier["sup_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars((string)($supplier["sup_email"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                            <td><?php $w = (string)($supplier["sup_website"] ?? ""); echo $w !== "" ? htmlspecialchars($w, ENT_QUOTES, "UTF-8") : "—"; ?></td>
                            <td class="text-nowrap">
                                <a href="<?= htmlspecialchars(sol_url("products/suppliers.php?edit=" . (int)$supplier["supplierId"]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="post" class="d-inline" data-sol-confirm="Delete this supplier?">
                                    <?= sol_csrf_field() ?>
                                    <input type="hidden" name="supplier_id" value="<?= (int)($supplier["supplierId"] ?? 0) ?>">
                                    <button type="submit" name="delete_supplier" value="1" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info border-0 mb-0">No suppliers found.</div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . "/../includes/layout_bottom.php";
