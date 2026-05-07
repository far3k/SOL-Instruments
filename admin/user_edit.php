<?php

declare(strict_types=1);

# Edit another user (admin): profile fields, status, optional avatar.

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_admin();
require_once dirname(__DIR__) . "/file_upload.php";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: " . sol_url("admin/users_admin.php"));
    exit;
}

$id = (int)$_GET["id"];
$hasDobColumn = false;
$colResult = $connect->query("SHOW COLUMNS FROM users LIKE 'dob'");
if ($colResult && $colResult->num_rows === 1) {
    $hasDobColumn = true;
}

$sql = "SELECT id, first_name, last_name, email, picture, status" . ($hasDobColumn ? ", dob" : "") . " FROM users WHERE id = $id LIMIT 1";
$result = $connect->query($sql);

if (!$result || $result->num_rows !== 1) {
    header("Location: " . sol_url("admin/users_admin.php"));
    exit;
}

$row = $result->fetch_assoc();
$fname = $row["first_name"];
$lname = $row["last_name"];
$email = $row["email"];
$role = $row["status"];
$dob = $hasDobColumn && isset($row["dob"]) ? $row["dob"] : "";
$formError = "";
$successMsg = "";

$picsDir = SOL_ROOT . DIRECTORY_SEPARATOR . "pictures" . DIRECTORY_SEPARATOR;

/**
 * Normalize date string to Y-m-d or return null when invalid.
 */
function sol_admin_normalize_dob(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === "") {
        return "";
    }

    $formats = ["Y-m-d", "m/d/Y", "d/m/Y"];
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $raw);
        if ($dt instanceof DateTime && $dt->format($fmt) === $raw) {
            return $dt->format("Y-m-d");
        }
    }

    return null;
}

if (isset($_POST["update"])) {
    if (!sol_csrf_verify()) {
        $formError = "Security check failed.";
    } else {
        $fname = trim(strip_tags((string)($_POST["fname"] ?? "")));
        $lname = trim(strip_tags((string)($_POST["lname"] ?? "")));
        $email = trim((string)($_POST["email"] ?? ""));
        $role = (string)($_POST["role"] ?? "user");
        $dob = trim((string)($_POST["dob"] ?? ""));

        if ($fname === "" || $lname === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ["user", "adm"], true)) {
            $formError = "Please provide valid first name, last name, email and role.";
        } else {
            if ($hasDobColumn) {
                $dobNorm = sol_admin_normalize_dob($dob);
                if ($dobNorm === null) {
                    $formError = "Please provide a valid date of birth.";
                } else {
                    $dob = $dobNorm;
                }
            }

            if ($formError === "") {
            $safeFname = $connect->real_escape_string($fname);
            $safeLname = $connect->real_escape_string($lname);
            $safeEmail = $connect->real_escape_string($email);
            $safeRole = $connect->real_escape_string($role);
            $updateSql = "UPDATE users SET first_name='$safeFname', last_name='$safeLname', email='$safeEmail', status='$safeRole'";
            if ($hasDobColumn) {
                if ($dob === "") {
                    $updateSql .= ", dob=NULL";
                } else {
                    $safeDob = $connect->real_escape_string($dob);
                    $updateSql .= ", dob='$safeDob'";
                }
            }
            $updateSql .= " WHERE id=$id";

            if (isset($_FILES["picture"]) && ($_FILES["picture"]["error"] ?? 4) !== 4) {
                $picture = fileUpload($_FILES["picture"]);
                if ($picture[0] === null) {
                    $formError = $picture[1];
                } else {
                    if ($row["picture"] !== "avatar.png" && is_file($picsDir . $row["picture"])) {
                        unlink($picsDir . $row["picture"]);
                    }
                    $safePicture = $connect->real_escape_string($picture[0]);
                    $updateSql = "UPDATE users SET first_name='$safeFname', last_name='$safeLname', email='$safeEmail', status='$safeRole', picture='$safePicture'";
                    if ($hasDobColumn) {
                        if ($dob === "") {
                            $updateSql .= ", dob=NULL";
                        } else {
                            $safeDob = $connect->real_escape_string($dob);
                            $updateSql .= ", dob='$safeDob'";
                        }
                    }
                    $updateSql .= " WHERE id=$id";
                }
            }

                if ($formError === "") {
                    if ($connect->query($updateSql)) {
                        $successMsg = "User updated successfully.";
                        $refreshResult = $connect->query($sql);
                        if ($refreshResult && $refreshResult->num_rows === 1) {
                            $row = $refreshResult->fetch_assoc();
                            $role = $row["status"];
                        }
                    } else {
                        $formError = "Update failed. Please try again.";
                    }
                }
            }
        }
    }
}

$page_title = "Update user";
$nav_role = "admin";
$active_nav = "users_admin";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-4" style="max-width: 620px;">
    <h1 class="h3 mb-4">Update user</h1>

    <?php if ($formError !== ""): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($formError, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <?php if ($successMsg !== ""): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMsg, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" autocomplete="off" class="card border-0 shadow-sm sol-card-shell p-4">
        <?= sol_csrf_field() ?>
        <div class="mb-3">
            <label for="fname" class="form-label">First name</label>
            <input type="text" class="form-control" id="fname" name="fname" value="<?= htmlspecialchars((string)$row["first_name"], ENT_QUOTES, "UTF-8") ?>">
        </div>
        <div class="mb-3">
            <label for="lname" class="form-label">Last name</label>
            <input type="text" class="form-control" id="lname" name="lname" value="<?= htmlspecialchars((string)$row["last_name"], ENT_QUOTES, "UTF-8") ?>">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars((string)$row["email"], ENT_QUOTES, "UTF-8") ?>">
        </div>
        <?php if ($hasDobColumn): ?>
            <div class="mb-3">
                <label for="dob" class="form-label">Date of birth</label>
                <input type="date" class="form-control" id="dob" name="dob" value="<?= htmlspecialchars((string)($row["dob"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
            </div>
        <?php endif; ?>
        <div class="mb-3">
            <label for="role" class="form-label">Role</label>
            <select class="form-select" id="role" name="role">
                <option value="user" <?= ($role === "user") ? "selected" : "" ?>>user</option>
                <option value="adm" <?= ($role === "adm") ? "selected" : "" ?>>adm</option>
            </select>
        </div>
        <div class="mb-4">
            <label for="picture" class="form-label">Profile picture</label>
            <input type="file" class="form-control" id="picture" name="picture">
        </div>
        <div class="d-flex flex-wrap gap-2 mt-2">
            <button type="submit" name="update" class="btn btn-primary px-4">Update</button>
            <a href="<?= htmlspecialchars(sol_url("admin/users_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary px-4">Back</a>
        </div>
    </form>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
