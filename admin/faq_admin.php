<?php

declare(strict_types=1);

# CMS for public FAQ rows (categories, sort_order when column exists).

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_admin();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$allowed = ["Rental", "Store", "Account"];
$flashOk = "";
$flashErr = "";

if (!sol_db_table_exists($connect, "faq")) {
    $page_title = "FAQ admin";
    $nav_role = "admin";
    $active_nav = "faq_admin";
    require_once dirname(__DIR__) . "/includes/layout_top.php";
    echo '<div class="container py-4"><div class="alert alert-warning">Run <code>schema_updates.sql</code> to create the <code>faq</code> table.</div></div>';
    require_once dirname(__DIR__) . "/includes/layout_bottom.php";
    exit;
}

$hasFaqSortOrder = sol_db_column_exists($connect, "faq", "sort_order");

if ($_SERVER["REQUEST_METHOD"] === "POST" && sol_csrf_verify()) {
    if (isset($_POST["delete_faq"])) {
        $id = (int)($_POST["id"] ?? 0);
        if ($id > 0) {
            $d = $connect->prepare("DELETE FROM faq WHERE id = ? LIMIT 1");
            $d->bind_param("i", $id);
            $d->execute();
            $d->close();
        }
        header("Location: " . sol_url("admin/faq_admin.php"));
        exit;
    }

    if (isset($_POST["create_faq"])) {
        $question = trim((string)($_POST["question"] ?? ""));
        $answer = trim((string)($_POST["answer"] ?? ""));
        $category = (string)($_POST["category"] ?? "");
        $sort = max(0, (int)($_POST["sort_order"] ?? 0));
        if ($question === "" || mb_strlen($question) > 1000) {
            $flashErr = "Question is required (max 1000 characters).";
        } elseif ($answer === "" || mb_strlen($answer) > 2000) {
            $flashErr = "Answer is required (max 2000 characters).";
        } elseif (!in_array($category, $allowed, true)) {
            $flashErr = "Invalid category.";
        } else {
            if ($hasFaqSortOrder) {
                $ins = $connect->prepare("INSERT INTO faq (question, answer, category, sort_order) VALUES (?, ?, ?, ?)");
                $ins->bind_param("sssi", $question, $answer, $category, $sort);
            } else {
                $ins = $connect->prepare("INSERT INTO faq (question, answer, category) VALUES (?, ?, ?)");
                $ins->bind_param("sss", $question, $answer, $category);
            }
            $ins->execute();
            $ins->close();
            $flashOk = "FAQ added.";
        }
        if ($flashErr === "") {
            header("Location: " . sol_url("admin/faq_admin.php"));
            exit;
        }
    }

    if (isset($_POST["update_faq"])) {
        $id = (int)($_POST["id"] ?? 0);
        $question = trim((string)($_POST["question"] ?? ""));
        $answer = trim((string)($_POST["answer"] ?? ""));
        $category = (string)($_POST["category"] ?? "");
        $sort = max(0, (int)($_POST["sort_order"] ?? 0));
        if ($id < 1 || $question === "" || mb_strlen($question) > 1000 || $answer === "" || mb_strlen($answer) > 2000 || !in_array($category, $allowed, true)) {
            $flashErr = "Invalid data.";
        } else {
            if ($hasFaqSortOrder) {
                $u = $connect->prepare("UPDATE faq SET question = ?, answer = ?, category = ?, sort_order = ? WHERE id = ? LIMIT 1");
                $u->bind_param("sssii", $question, $answer, $category, $sort, $id);
            } else {
                $u = $connect->prepare("UPDATE faq SET question = ?, answer = ?, category = ? WHERE id = ? LIMIT 1");
                $u->bind_param("sssi", $question, $answer, $category, $id);
            }
            $u->execute();
            $u->close();
            header("Location: " . sol_url("admin/faq_admin.php"));
            exit;
        }
    }
}

$editId = isset($_GET["edit"]) ? (int)$_GET["edit"] : 0;
$editRow = null;
if ($editId > 0) {
    $e = $connect->prepare("SELECT * FROM faq WHERE id = ? LIMIT 1");
    $e->bind_param("i", $editId);
    $e->execute();
    $editRow = $e->get_result()->fetch_assoc();
    $e->close();
}

$faqListOrder = $hasFaqSortOrder ? "category ASC, sort_order ASC, id ASC" : "category ASC, id ASC";
$list = $connect->query("SELECT * FROM faq ORDER BY " . $faqListOrder);
$rows = $list ? $list->fetch_all(MYSQLI_ASSOC) : [];

$page_title = "FAQ admin";
$nav_role = "admin";
$active_nav = "faq_admin";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-4" style="max-width: 960px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0">FAQ management</h1>
            <p class="text-muted small mb-0">Add, edit, or remove public FAQ entries.</p>
        </div>
    </div>

    <?php if ($flashOk !== ""): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-3"><?= htmlspecialchars($flashOk, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>
    <?php if ($flashErr !== ""): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-3"><?= htmlspecialchars($flashErr, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4 sol-card-shell">
        <div class="card-body p-4">
            <h2 class="h6 text-uppercase text-muted mb-3"><?= $editRow ? "Edit FAQ #" . (int)$editRow["id"] : "Add FAQ" ?></h2>
            <form method="post">
                <?= sol_csrf_field() ?>
                <?php if ($editRow): ?>
                    <input type="hidden" name="id" value="<?= (int)$editRow["id"] ?>">
                    <button type="submit" name="update_faq" value="1" class="btn btn-primary mb-3">Save changes</button>
                    <a href="<?= htmlspecialchars(sol_url("admin/faq_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary mb-3 ms-1">Cancel</a>
                <?php else: ?>
                    <button type="submit" name="create_faq" value="1" class="btn btn-primary mb-3">Add FAQ</button>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" required>
                        <?php foreach ($allowed as $c): ?>
                            <option value="<?= htmlspecialchars($c, ENT_QUOTES, "UTF-8") ?>" <?= (($editRow["category"] ?? "") === $c) ? "selected" : "" ?>><?= htmlspecialchars($c, ENT_QUOTES, "UTF-8") ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($hasFaqSortOrder): ?>
                <div class="mb-3">
                    <label class="form-label">Sort order</label>
                    <input type="number" name="sort_order" class="form-control" min="0" value="<?= (int)($editRow["sort_order"] ?? 0) ?>" style="max-width:120px">
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Question</label>
                    <textarea name="question" class="form-control" rows="2" maxlength="1000" required><?= htmlspecialchars((string)($editRow["question"] ?? ""), ENT_QUOTES, "UTF-8") ?></textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label">Answer</label>
                    <textarea name="answer" class="form-control" rows="4" maxlength="2000" required><?= htmlspecialchars((string)($editRow["answer"] ?? ""), ENT_QUOTES, "UTF-8") ?></textarea>
                </div>
            </form>
        </div>
    </div>

    <h2 class="h6 text-uppercase text-muted mb-2">All entries</h2>
    <div class="table-responsive card border-0 shadow-sm">
        <table class="table table-hover align-middle mb-0 bg-white">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Category</th>
                    <th>Question</th>
                    <?php if ($hasFaqSortOrder): ?><th>Order</th><?php endif; ?>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="<?= $hasFaqSortOrder ? 5 : 4 ?>" class="text-muted text-center py-4">No FAQs yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int)$r["id"] ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars((string)$r["category"], ENT_QUOTES, "UTF-8") ?></span></td>
                        <td class="small"><?php
                            $qq = (string)$r["question"];
                            $short = mb_strlen($qq) > 80 ? mb_substr($qq, 0, 80) . "…" : $qq;
                            echo htmlspecialchars($short, ENT_QUOTES, "UTF-8");
                        ?></td>
                        <?php if ($hasFaqSortOrder): ?><td><?= (int)($r["sort_order"] ?? 0) ?></td><?php endif; ?>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(sol_url("admin/faq_admin.php?edit=" . (int)$r["id"]), ENT_QUOTES, "UTF-8") ?>">Edit</a>
                            <form method="post" class="d-inline" data-sol-confirm="Delete this FAQ?">
                                <?= sol_csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
                                <button type="submit" name="delete_faq" value="1" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="small text-muted mt-3 mb-0">
        <a href="<?= htmlspecialchars(sol_url("faq.php"), ENT_QUOTES, "UTF-8") ?>" target="_blank" rel="noopener">Preview public FAQ</a>
    </p>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
