<?php

declare(strict_types=1);

# Manage instrument categories table (create if missing message otherwise).

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_admin();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!sol_db_table_exists($connect, "categories")) {
    $page_title = "Categories";
    $nav_role = "admin";
    $active_nav = "categories_admin";
    require_once dirname(__DIR__) . "/includes/layout_top.php";
    echo '<div class="container py-4"><div class="alert alert-warning">Table <code>categories</code> is missing. Run <code>schema_updates.sql</code> first.</div></div>';
    require_once dirname(__DIR__) . "/includes/layout_bottom.php";
    exit;
}

$msg = "";
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && sol_csrf_verify()) {
    if (isset($_POST["create_cat"])) {
        $name = trim((string)($_POST["name"] ?? ""));
        $desc = trim((string)($_POST["description"] ?? ""));
        if ($name === "") {
            $err = "Category name is required.";
        } else {
            $st = $connect->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $st->bind_param("ss", $name, $desc);
            if ($st->execute()) {
                $msg = "Category created.";
            } else {
                $err = "Could not create (duplicate name?).";
            }
            $st->close();
        }
    } elseif (isset($_POST["update_cat"])) {
        $id = (int)($_POST["id"] ?? 0);
        $name = trim((string)($_POST["name"] ?? ""));
        $desc = trim((string)($_POST["description"] ?? ""));
        if ($id < 1 || $name === "") {
            $err = "Invalid category.";
        } else {
            $st = $connect->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ? LIMIT 1");
            $st->bind_param("ssi", $name, $desc, $id);
            if ($st->execute()) {
                $msg = "Category updated.";
            } else {
                $err = "Could not update.";
            }
            $st->close();
        }
    } elseif (isset($_POST["delete_cat"])) {
        $id = (int)($_POST["id"] ?? 0);
        if ($id < 1) {
            $err = "Invalid category.";
        } elseif (sol_db_column_exists($connect, "instruments", "category_id")) {
            $c = $connect->prepare("SELECT COUNT(*) AS n FROM instruments WHERE category_id = ? LIMIT 1");
            $c->bind_param("i", $id);
            $c->execute();
            $n = (int)($c->get_result()->fetch_assoc()["n"] ?? 0);
            $c->close();
            if ($n > 0) {
                $isNullable = false;
                $qNull = $connect->query("
                    SELECT IS_NULLABLE
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'instruments'
                      AND COLUMN_NAME = 'category_id'
                    LIMIT 1
                ");
                if ($qNull) {
                    $nulRow = $qNull->fetch_assoc();
                    $isNullable = strtoupper((string)($nulRow["IS_NULLABLE"] ?? "NO")) === "YES";
                }
                if (!$isNullable) {
                    $err = "Cannot delete: {$n} instrument(s) use this category and category_id is NOT NULL. Run schema update to allow NULL first.";
                } else {
                    $u = $connect->prepare("UPDATE instruments SET category_id = NULL WHERE category_id = ?");
                    $u->bind_param("i", $id);
                    $u->execute();
                    $u->close();
                    $d = $connect->prepare("DELETE FROM categories WHERE id = ? LIMIT 1");
                    $d->bind_param("i", $id);
                    $d->execute();
                    $d->close();
                    $msg = "Category deleted. {$n} instrument(s) were moved to no category.";
                }
            } else {
                $d = $connect->prepare("DELETE FROM categories WHERE id = ? LIMIT 1");
                $d->bind_param("i", $id);
                $d->execute();
                $d->close();
                $msg = "Category deleted.";
            }
        } else {
            $d = $connect->prepare("DELETE FROM categories WHERE id = ? LIMIT 1");
            $d->bind_param("i", $id);
            $d->execute();
            $d->close();
            $msg = "Category deleted.";
        }
    }
}

$editId = isset($_GET["edit"]) ? (int)$_GET["edit"] : 0;
$editRow = null;
if ($editId > 0) {
    $e = $connect->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
    $e->bind_param("i", $editId);
    $e->execute();
    $editRow = $e->get_result()->fetch_assoc();
    $e->close();
}

$list = $connect->query("SELECT * FROM categories ORDER BY name ASC");
$all = $list ? $list->fetch_all(MYSQLI_ASSOC) : [];

$page_title = "Categories";
$nav_role = "admin";
$active_nav = "categories_admin";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-4" style="max-width: 720px;">
    <h1 class="h3 mb-3">Instrument categories</h1>

    <?php if ($msg !== ""): ?><div class="alert alert-success border-0"><?= htmlspecialchars($msg, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
    <?php if ($err !== ""): ?><div class="alert alert-danger border-0"><?= htmlspecialchars($err, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>

    <div class="card border-0 shadow-sm mb-4 sol-card-shell">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3"><?= $editRow ? "Edit category" : "New category" ?></h2>
            <form method="post">
                <?= sol_csrf_field() ?>
                <?php if ($editRow): ?>
                    <input type="hidden" name="id" value="<?= (int)$editRow["id"] ?>">
                    <button type="submit" name="update_cat" value="1" class="btn btn-primary mb-3">Save changes</button>
                    <a href="<?= htmlspecialchars(sol_url("admin/categories_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary mb-3 ms-1">Cancel edit</a>
                <?php else: ?>
                    <button type="submit" name="create_cat" value="1" class="btn btn-primary mb-3">Add category</button>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required maxlength="80"
                           value="<?= htmlspecialchars((string)($editRow["name"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
                </div>
                <div class="mb-0">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2" maxlength="255"><?= htmlspecialchars((string)($editRow["description"] ?? ""), ENT_QUOTES, "UTF-8") ?></textarea>
                </div>
            </form>
        </div>
    </div>

    <h2 class="h6 text-uppercase text-muted mb-2">All categories</h2>
    <ul class="list-group shadow-sm border-0">
        <?php foreach ($all as $c): ?>
            <li class="list-group-item d-flex justify-content-between align-items-start gap-2 flex-wrap">
                <div>
                    <strong><?= htmlspecialchars((string)$c["name"], ENT_QUOTES, "UTF-8") ?></strong>
                    <?php if (!empty($c["description"])): ?>
                        <div class="small text-muted"><?= htmlspecialchars((string)$c["description"], ENT_QUOTES, "UTF-8") ?></div>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-1">
                    <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(sol_url("admin/categories_admin.php?edit=" . (int)$c["id"]), ENT_QUOTES, "UTF-8") ?>">Edit</a>
                    <form method="post" data-sol-confirm="Delete this category?">
                        <?= sol_csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$c["id"] ?>">
                        <button type="submit" name="delete_cat" value="1" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
