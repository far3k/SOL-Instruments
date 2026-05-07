<?php
# Alternate admin products listing (mysqli OOP + standalone HTML shell).

session_start();

# mysqli OOP connection (standalone listing; align credentials with includes/db_config.php in production).
$hostname = "localhost";
$username = "root";
$password = "";
$dbname   = "login";

$connect = new mysqli($hostname, $username, $password, $dbname);

if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

# Require login: admin tools only; regular users go to customer home.
if (!isset($_SESSION["user"]) && !isset($_SESSION["adm"])) {
    $connect->close();
    header("Location: ../login.php");
    exit;
}

if (isset($_SESSION["user"])) {
    $connect->close();
    header("Location: ../account/home.php");
    exit;
}

# List products with supplier label via LEFT JOIN.
$sql = "SELECT p.*, s.`sup_name` AS sup_name
        FROM `products` p
        LEFT JOIN `suppliers` s ON p.`fk_supplier_id` = s.`supplierId`
        ORDER BY p.`id` DESC";

$result = $connect->query($sql);

$rows = [];
if ($result instanceof mysqli_result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
}

# Release DB before sending HTML (this script does not use layout_top).
$connect->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>
<div class="container" style="max-width: 1100px;">
    <div class="d-flex flex-wrap gap-2 align-items-center my-4">
        <a href="../admin/dashboard.php" class="btn btn-secondary">Back to the dashboard</a>
        <a href="create.php" class="btn btn-success">Add product</a>
        <a href="suppliers.php" class="btn btn-outline-primary">Suppliers</a>
    </div>

    <h1 class="mb-4">Manage products</h1>

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
                    <div class="card h-100 shadow-sm">
                        <img src="../pictures/<?= $pic ?>" class="card-img-top object-fit-cover" alt="<?= $name ?>" style="height: 200px;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= $name ?></h5>
                            <p class="card-text mb-1">Price: <?= $price ?></p>
                            <p class="card-text text-muted small mb-3">Supplier: <?= $supplierLabel ?></p>
                            <div class="d-grid gap-2 mt-auto">
                                <a href="details.php?id=<?= $pid ?>" class="btn btn-primary btn-sm">View Details</a>
                                <div class="btn-group" role="group">
                                    <a href="update.php?id=<?= $pid ?>" class="btn btn-outline-warning btn-sm">Edit</a>
                                    <a href="delete.php?id=<?= $pid ?>" class="btn btn-outline-danger btn-sm sol-confirm-link" data-sol-confirm="Delete this product?">Delete</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No products found.</div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="../assets/js/sol-swal.js"></script>
</body>
</html>

