<?php

declare(strict_types=1);

# Admin CRUD for rentable instruments + optional category_id linkage.

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_admin();
require_once dirname(__DIR__) . "/file_upload.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$hasCat = sol_db_column_exists($connect, "instruments", "category_id") && sol_db_table_exists($connect, "categories");
$categories = [];
if ($hasCat) {
    $cr = $connect->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $cr ? $cr->fetch_all(MYSQLI_ASSOC) : [];
}

$conditions = ["excellent", "good", "fair", "needs_service"];
$msg = "";
$err = "";

# POST: create/update instrument, toggle active, upload image (CSRF).
if ($_SERVER["REQUEST_METHOD"] === "POST" && sol_csrf_verify()) {
    if (isset($_POST["deactivate_inst"])) {
        $id = (int)($_POST["id"] ?? 0);
        if ($id > 0) {
            $u = $connect->prepare("UPDATE instruments SET is_active = 0 WHERE id = ? LIMIT 1");
            $u->bind_param("i", $id);
            $u->execute();
            $u->close();
            $msg = "Instrument #" . $id . " deactivated (hidden from catalog).";
        }
    } elseif (isset($_POST["reactivate_inst"])) {
        $id = (int)($_POST["id"] ?? 0);
        if ($id > 0) {
            $u = $connect->prepare("UPDATE instruments SET is_active = 1 WHERE id = ? LIMIT 1");
            $u->bind_param("i", $id);
            $u->execute();
            $u->close();
            $msg = "Instrument #" . $id . " reactivated.";
        }
    } elseif (isset($_POST["delete_inst"])) {
        $id = (int)($_POST["id"] ?? 0);
        if ($id < 1) {
            $err = "Invalid instrument.";
        } else {
            $chkRent = $connect->prepare("SELECT COUNT(*) AS n FROM rental_requests WHERE instrument_id = ? LIMIT 1");
            $chkRent->bind_param("i", $id);
            $chkRent->execute();
            $used = (int)($chkRent->get_result()->fetch_assoc()["n"] ?? 0);
            $chkRent->close();
            if ($used > 0) {
                $err = "Cannot delete: this instrument already has rental history.";
            } else {
                $st = $connect->prepare("SELECT image_url FROM instruments WHERE id = ? LIMIT 1");
                $st->bind_param("i", $id);
                $st->execute();
                $row = $st->get_result()->fetch_assoc();
                $st->close();
                if (!$row) {
                    $err = "Instrument not found.";
                } else {
                    $pic = (string)($row["image_url"] ?? "");
                    $d = $connect->prepare("DELETE FROM instruments WHERE id = ? LIMIT 1");
                    $d->bind_param("i", $id);
                    $d->execute();
                    $d->close();
                    if ($pic !== "" && $pic !== "instrument.jpg") {
                        $abs = SOL_ROOT . DIRECTORY_SEPARATOR . "pictures" . DIRECTORY_SEPARATOR . $pic;
                        if (is_file($abs)) {
                            @unlink($abs);
                        }
                    }
                    $msg = "Instrument deleted.";
                }
            }
        }
    } elseif (isset($_POST["create_inst"]) || isset($_POST["update_inst"])) {
        $name = trim((string)($_POST["name"] ?? ""));
        $price = trim((string)($_POST["daily_price"] ?? ""));
        $cond = (string)($_POST["condition"] ?? "good");
        $desc = trim((string)($_POST["description"] ?? ""));
        $catId = (int)($_POST["category_id"] ?? 0);
        $active = isset($_POST["is_active"]) ? 1 : 0;

        if (!in_array($cond, $conditions, true)) {
            $cond = "good";
        }
        if ($name === "" || $price === "" || !is_numeric($price) || (float)$price < 0) {
            $err = "Name and valid daily price are required.";
        } elseif (isset($_POST["create_inst"])) {
            $upload = instrumentImageUpload($_FILES["picture"] ?? ["error" => 4]);
            if ($upload[0] === null) {
                $err = $upload[1];
            } else {
                $priceF = (float)$price;
                $pic = $upload[0];
                if ($hasCat && $catId > 0) {
                    $ins = $connect->prepare("INSERT INTO instruments (category_id, name, daily_price, `condition`, description, image_url, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $ins->bind_param("isdsssi", $catId, $name, $priceF, $cond, $desc, $pic, $active);
                } elseif ($hasCat) {
                    $ins = $connect->prepare("INSERT INTO instruments (category_id, name, daily_price, `condition`, description, image_url, is_active) VALUES (NULL, ?, ?, ?, ?, ?, ?)");
                    $ins->bind_param("sdsssi", $name, $priceF, $cond, $desc, $pic, $active);
                } else {
                    $ins = $connect->prepare("INSERT INTO instruments (name, daily_price, `condition`, description, image_url, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                    $ins->bind_param("sdsssi", $name, $priceF, $cond, $desc, $pic, $active);
                }
                if ($ins->execute()) {
                    $ins->close();
                    header("Location: " . sol_url("admin/instruments_admin.php"));
                    exit;
                }
                $err = "Could not create instrument.";
                $ins->close();
            }
        } else {
            $id = (int)($_POST["id"] ?? 0);
            if ($id < 1) {
                $err = "Invalid instrument.";
            } else {
                $priceF = (float)$price;
                $picFile = $_FILES["picture"] ?? ["error" => 4];
                if (($picFile["error"] ?? 4) !== 4) {
                    $up2 = instrumentImageUpload($picFile);
                    if ($up2[0] === null) {
                        $err = $up2[1];
                    } elseif ($hasCat && $catId > 0) {
                        $u = $connect->prepare("UPDATE instruments SET category_id = ?, name = ?, daily_price = ?, `condition` = ?, description = ?, image_url = ?, is_active = ? WHERE id = ? LIMIT 1");
                        $pic = $up2[0];
                        $u->bind_param("isdsssii", $catId, $name, $priceF, $cond, $desc, $pic, $active, $id);
                        $u->execute();
                        $u->close();
                        $msg = "Instrument updated.";
                    } elseif ($hasCat) {
                        $u = $connect->prepare("UPDATE instruments SET category_id = NULL, name = ?, daily_price = ?, `condition` = ?, description = ?, image_url = ?, is_active = ? WHERE id = ? LIMIT 1");
                        $pic = $up2[0];
                        $u->bind_param("sdsssii", $name, $priceF, $cond, $desc, $pic, $active, $id);
                        $u->execute();
                        $u->close();
                        $msg = "Instrument updated.";
                    } else {
                        $u = $connect->prepare("UPDATE instruments SET name = ?, daily_price = ?, `condition` = ?, description = ?, image_url = ?, is_active = ? WHERE id = ? LIMIT 1");
                        $pic = $up2[0];
                        $u->bind_param("sdsssii", $name, $priceF, $cond, $desc, $pic, $active, $id);
                        $u->execute();
                        $u->close();
                        $msg = "Instrument updated.";
                    }
                } elseif ($hasCat) {
                    if ($catId > 0) {
                        $u = $connect->prepare("UPDATE instruments SET category_id = ?, name = ?, daily_price = ?, `condition` = ?, description = ?, is_active = ? WHERE id = ? LIMIT 1");
                        $u->bind_param("isdssii", $catId, $name, $priceF, $cond, $desc, $active, $id);
                    } else {
                        $u = $connect->prepare("UPDATE instruments SET category_id = NULL, name = ?, daily_price = ?, `condition` = ?, description = ?, is_active = ? WHERE id = ? LIMIT 1");
                        $u->bind_param("sdssii", $name, $priceF, $cond, $desc, $active, $id);
                    }
                    $u->execute();
                    $u->close();
                    $msg = "Instrument updated.";
                } else {
                    $u = $connect->prepare("UPDATE instruments SET name = ?, daily_price = ?, `condition` = ?, description = ?, is_active = ? WHERE id = ? LIMIT 1");
                    $u->bind_param("sdssii", $name, $priceF, $cond, $desc, $active, $id);
                    $u->execute();
                    $u->close();
                    $msg = "Instrument updated.";
                }
            }
        }
    }
}

$editId = isset($_GET["edit"]) ? (int)$_GET["edit"] : 0;
$editRow = null;
if ($editId > 0) {
    $e = $connect->prepare("SELECT * FROM instruments WHERE id = ? LIMIT 1");
    $e->bind_param("i", $editId);
    $e->execute();
    $editRow = $e->get_result()->fetch_assoc();
    $e->close();
}

if ($hasCat) {
    $list = $connect->query("SELECT i.*, c.name AS category_name FROM instruments i LEFT JOIN categories c ON c.id = i.category_id ORDER BY i.id DESC");
} else {
    $list = $connect->query("SELECT i.*, NULL AS category_name FROM instruments i ORDER BY i.id DESC");
}
$all = $list ? $list->fetch_all(MYSQLI_ASSOC) : [];

$page_title = "Instruments admin";
$nav_role = "admin";
$active_nav = "instruments_admin";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-4" style="max-width: 900px;">
    <h1 class="h3 mb-3">Instruments</h1>

    <?php if ($msg !== ""): ?><div class="alert alert-success border-0"><?= htmlspecialchars($msg, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
    <?php if ($err !== ""): ?><div class="alert alert-danger border-0"><?= htmlspecialchars($err, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>

    <div class="card border-0 shadow-sm mb-4 sol-card-shell">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3"><?= $editRow ? "Edit instrument" : "Add instrument" ?></h2>
            <form method="post" enctype="multipart/form-data">
                <?= sol_csrf_field() ?>
                <?php if ($editRow): ?>
                    <input type="hidden" name="id" value="<?= (int)$editRow["id"] ?>">
                <?php endif; ?>

                <?php if ($hasCat): ?>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="0">— None —</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c["id"] ?>" <?= ((int)($editRow["category_id"] ?? 0) === (int)$c["id"]) ? "selected" : "" ?>><?= htmlspecialchars((string)$c["name"], ENT_QUOTES, "UTF-8") ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required maxlength="120" value="<?= htmlspecialchars((string)($editRow["name"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Daily price (€)</label>
                    <input type="number" name="daily_price" class="form-control" step="0.01" min="0" required value="<?= htmlspecialchars((string)($editRow["daily_price"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Condition</label>
                    <select name="condition" class="form-select">
                        <?php foreach ($conditions as $c): ?>
                            <option value="<?= htmlspecialchars($c, ENT_QUOTES, "UTF-8") ?>" <?= (($editRow["condition"] ?? "") === $c) ? "selected" : "" ?>><?= htmlspecialchars($c, ENT_QUOTES, "UTF-8") ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars((string)($editRow["description"] ?? ""), ENT_QUOTES, "UTF-8") ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Picture</label>
                    <input type="file" name="picture" class="form-control" accept="image/*">
                    <div class="form-text"><?= $editRow ? "Leave empty to keep current image." : "Optional — default placeholder if omitted." ?></div>
                </div>
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= ($editRow === null || !empty($editRow["is_active"])) ? "checked" : "" ?>>
                    <label class="form-check-label" for="is_active">Active (visible in catalog)</label>
                </div>
                <div class="mt-3">
                    <?php if ($editRow): ?>
                        <button type="submit" name="update_inst" value="1" class="btn btn-primary">Save</button>
                        <a href="<?= htmlspecialchars(sol_url("admin/instruments_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary ms-1">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="create_inst" value="1" class="btn btn-primary">Create</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <h2 class="h6 text-uppercase text-muted mb-2">All instruments</h2>
    <div class="table-responsive card border-0 shadow-sm">
        <table class="table table-sm mb-0 bg-white align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <?php if ($hasCat): ?><th>Category</th><?php endif; ?>
                    <th>€/day</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all as $row): ?>
                    <tr>
                        <td><?= (int)$row["id"] ?></td>
                        <td><?= htmlspecialchars((string)$row["name"], ENT_QUOTES, "UTF-8") ?></td>
                        <?php if ($hasCat): ?>
                            <td class="small"><?= htmlspecialchars((string)($row["category_name"] ?? "—"), ENT_QUOTES, "UTF-8") ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars((string)$row["daily_price"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= !empty($row["is_active"]) ? "Yes" : "No" ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(sol_url("admin/instruments_admin.php?edit=" . (int)$row["id"]), ENT_QUOTES, "UTF-8") ?>">Edit</a>
                            <?php if (!empty($row["is_active"])): ?>
                                <form method="post" class="d-inline" data-sol-confirm="Deactivate this instrument?">
                                    <?= sol_csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$row["id"] ?>">
                                    <button type="submit" name="deactivate_inst" value="1" class="btn btn-sm btn-outline-warning">Off</button>
                                </form>
                            <?php else: ?>
                                <form method="post" class="d-inline">
                                    <?= sol_csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$row["id"] ?>">
                                    <button type="submit" name="reactivate_inst" value="1" class="btn btn-sm btn-outline-success">On</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" class="d-inline" data-sol-confirm="Delete this instrument permanently?">
                                <?= sol_csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$row["id"] ?>">
                                <button type="submit" name="delete_inst" value="1" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
