<?php

declare(strict_types=1);

# POST-only admin delete user (CSRF); redirects if method invalid.

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_admin();

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["delete_user"]) || !sol_csrf_verify()) {
    header("Location: " . sol_url("admin/users_admin.php"));
    exit;
}

$id = (int)($_POST["id"] ?? 0);
if ($id < 1) {
    header("Location: " . sol_url("admin/users_admin.php"));
    exit;
}

$adminId = (int)$_SESSION["adm"];
if ($id === $adminId) {
    header("Location: " . sol_url("admin/users_admin.php"));
    exit;
}

$st = $connect->prepare("SELECT picture FROM users WHERE id = ? LIMIT 1");
$st->bind_param("i", $id);
$st->execute();
$res = $st->get_result();
if ($res->num_rows === 1) {
    $user = $res->fetch_assoc();
    $picture = $user["picture"] ?? "avatar.png";
    if ($picture !== "avatar.png") {
        $path = SOL_ROOT . DIRECTORY_SEPARATOR . "pictures" . DIRECTORY_SEPARATOR . $picture;
        if (is_file($path)) {
            unlink($path);
        }
    }
    $del = $connect->prepare("DELETE FROM users WHERE id = ? LIMIT 1");
    $del->bind_param("i", $id);
    $del->execute();
    $del->close();
}
$st->close();

header("Location: " . sol_url("admin/users_admin.php"));
exit;
