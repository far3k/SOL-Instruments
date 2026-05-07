<?php

declare(strict_types=1);

# POST-only product delete (CSRF); GET redirects back to list.

require_once __DIR__ . "/../includes/app.php";
sol_require_admin();

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["delete_product"]) || !sol_csrf_verify()) {
    header("Location: " . sol_url("products/index.php"));
    exit;
}

$id = (int)($_POST["id"] ?? 0);
if ($id < 1) {
    header("Location: " . sol_url("products/index.php"));
    exit;
}

$st = $connect->prepare("SELECT picture FROM products WHERE id = ? LIMIT 1");
$st->bind_param("i", $id);
$st->execute();
$res = $st->get_result();
if ($res->num_rows !== 1) {
    $st->close();
    header("Location: " . sol_url("products/index.php"));
    exit;
}
$row = $res->fetch_assoc();
$st->close();

$picture = $row["picture"] ?? "product.jpg";
if ($picture !== "product.jpg" && $picture !== "avatar.png") {
    $path = SOL_ROOT . DIRECTORY_SEPARATOR . "pictures" . DIRECTORY_SEPARATOR . $picture;
    if (is_file($path)) {
        @unlink($path);
    }
}

$del = $connect->prepare("DELETE FROM products WHERE id = ? LIMIT 1");
$del->bind_param("i", $id);
$del->execute();
$del->close();

header("Location: " . sol_url("products/index.php"));
exit;
