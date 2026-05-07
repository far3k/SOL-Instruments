<?php

declare(strict_types=1);

# Inbox for public contact form submissions (read flag, delete, search).

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_admin();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!sol_db_table_exists($connect, "contact_messages")) {
    $page_title = "Contact messages";
    $nav_role = "admin";
    $active_nav = "contact_messages";
    require_once dirname(__DIR__) . "/includes/layout_top.php";
    echo '<div class="container py-4"><div class="alert alert-warning">Run <code>schema_updates.sql</code> to create <code>contact_messages</code>.</div></div>';
    require_once dirname(__DIR__) . "/includes/layout_bottom.php";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && sol_csrf_verify()) {
    $id = (int)($_POST["msg_id"] ?? 0);
    if ($id > 0) {
        if (isset($_POST["toggle_read"])) {
            $u = $connect->prepare("UPDATE contact_messages SET is_read = 1 - is_read WHERE id = ? LIMIT 1");
            $u->bind_param("i", $id);
            $u->execute();
            $u->close();
        } elseif (isset($_POST["delete_msg"])) {
            $d = $connect->prepare("DELETE FROM contact_messages WHERE id = ? LIMIT 1");
            $d->bind_param("i", $id);
            $d->execute();
            $d->close();
        }
    }
    $q = trim((string)($_POST["search"] ?? $_GET["search"] ?? ""));
    $p = max(1, (int)($_POST["page"] ?? $_GET["page"] ?? 1));
    $redir = sol_url("admin/contact_messages.php?page=" . $p);
    if ($q !== "") {
        $redir .= "&search=" . rawurlencode($q);
    }
    header("Location: " . $redir);
    exit;
}

$page = max(1, (int)($_GET["page"] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $like = "%" . $search . "%";
    $st = $connect->prepare("
        SELECT * FROM contact_messages
        WHERE name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $st->bind_param("ssssii", $like, $like, $like, $like, $limit, $offset);
} else {
    $st = $connect->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $st->bind_param("ii", $limit, $offset);
}
$st->execute();
$messages = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

$page_title = "Contact messages";
$nav_role = "admin";
$active_nav = "contact_messages";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-4" style="max-width: 1100px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0">Contact messages</h1>
            <p class="text-muted small mb-0">Submissions from the public contact form.</p>
        </div>
    </div>

    <form class="row g-2 mb-3" method="get">
        <div class="col-md-8">
            <input type="search" class="form-control" name="search" placeholder="Search name, email, subject, message…" value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>">
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary w-100">Search</button>
        </div>
        <input type="hidden" name="page" value="1">
    </form>

    <div class="table-responsive card border-0 shadow-sm">
        <table class="table table-sm align-middle mb-0 bg-white">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th style="min-width:140px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($messages === []): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No messages found.</td></tr>
                <?php endif; ?>
                <?php foreach ($messages as $msg): ?>
                    <tr class="<?= empty($msg["is_read"]) ? "table-warning" : "" ?>">
                        <td><?= (int)$msg["id"] ?></td>
                        <td><?= htmlspecialchars((string)$msg["name"], ENT_QUOTES, "UTF-8") ?></td>
                        <td class="small text-break"><?= htmlspecialchars((string)$msg["email"], ENT_QUOTES, "UTF-8") ?></td>
                        <td class="small"><?= htmlspecialchars((string)$msg["subject"], ENT_QUOTES, "UTF-8") ?></td>
                        <td class="small"><?= nl2br(htmlspecialchars((string)$msg["message"], ENT_QUOTES, "UTF-8")) ?></td>
                        <td class="small text-nowrap"><?= htmlspecialchars((string)($msg["created_at"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                        <td>
                            <form method="post" class="d-inline">
                                <?= sol_csrf_field() ?>
                                <input type="hidden" name="msg_id" value="<?= (int)$msg["id"] ?>">
                                <?php if ($search !== ""): ?>
                                    <input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>">
                                <?php endif; ?>
                                <input type="hidden" name="page" value="<?= $page ?>">
                                <button type="submit" name="toggle_read" value="1" class="btn btn-sm btn-outline-secondary mb-1"><?= !empty($msg["is_read"]) ? "Unread" : "Read" ?></button>
                            </form>
                            <form method="post" class="d-inline" data-sol-confirm="Delete this message?">
                                <?= sol_csrf_field() ?>
                                <input type="hidden" name="msg_id" value="<?= (int)$msg["id"] ?>">
                                <?php if ($search !== ""): ?>
                                    <input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>">
                                <?php endif; ?>
                                <input type="hidden" name="page" value="<?= $page ?>">
                                <button type="submit" name="delete_msg" value="1" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3 small">
        <?php
        $prev = $page - 1;
        $next = $page + 1;
        $qs = $search !== "" ? "&search=" . rawurlencode($search) : "";
        ?>
        <?php if ($page > 1): ?>
            <a href="<?= htmlspecialchars(sol_url("admin/contact_messages.php?page=" . $prev . $qs), ENT_QUOTES, "UTF-8") ?>">← Previous</a>
        <?php else: ?>
            <span class="text-muted">← Previous</span>
        <?php endif; ?>
        <span class="text-muted">Page <?= $page ?></span>
        <?php if (count($messages) === $limit): ?>
            <a href="<?= htmlspecialchars(sol_url("admin/contact_messages.php?page=" . $next . $qs), ENT_QUOTES, "UTF-8") ?>">Next →</a>
        <?php else: ?>
            <span class="text-muted">Next →</span>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
