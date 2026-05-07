<?php

declare(strict_types=1);

# Dedicated admin page for user account management.

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_admin();

$adminId = (int)$_SESSION["adm"];
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["toggle_block"]) && sol_csrf_verify()) {
    $tid = (int)($_POST["user_id"] ?? 0);
    if ($tid > 0 && $tid !== $adminId && sol_db_column_exists($connect, "users", "account_blocked")) {
        $u = $connect->prepare("UPDATE users SET account_blocked = 1 - COALESCE(account_blocked, 0) WHERE id = ? LIMIT 1");
        $u->bind_param("i", $tid);
        $u->execute();
        $u->close();
    }
    header("Location: " . sol_url("admin/users_admin.php"));
    exit;
}

$hasBlockedCol = sol_db_column_exists($connect, "users", "account_blocked");
$selectUsers = $hasBlockedCol
    ? "SELECT id, first_name, last_name, email, picture, status, account_blocked FROM users ORDER BY id DESC"
    : "SELECT id, first_name, last_name, email, picture, status FROM users ORDER BY id DESC";
$result = $connect->query($selectUsers);

$page_title = "User accounts";
$nav_role = "admin";
$active_nav = "users_admin";
$extra_head = <<<'HTML'
<style>
.user-card{border:1px solid var(--sol-card-border);border-radius:1rem;transition:box-shadow .2s}
.user-card:hover{box-shadow:0 8px 28px rgba(15,23,42,.08)}
</style>
HTML;
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-4 py-lg-5" style="max-width: 1140px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <p class="text-secondary small mb-1 text-uppercase">Admin</p>
            <h1 class="h3 fw-bold text-dark mb-0">User accounts</h1>
        </div>
    </div>

    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
        <span class="text-muted small">Manage role, block status, edit, and delete.</span>
        <span class="badge rounded-pill bg-dark bg-opacity-10 text-dark"><?= ($result instanceof mysqli_result) ? $result->num_rows : 0 ?> total</span>
    </div>

    <div class="row g-3">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($user = $result->fetch_assoc()): ?>
                <?php
                $st = strtolower((string)($user["status"] ?? ""));
                $blk = $hasBlockedCol && $st !== "adm" && (int)($user["account_blocked"] ?? 0) === 1;
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card user-card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <img src="<?= htmlspecialchars(sol_url("pictures/" . ($user["picture"] ?? "avatar.png")), ENT_QUOTES, "UTF-8") ?>" alt="" width="52" height="52" class="rounded-circle object-fit-cover flex-shrink-0 border">
                                <div class="min-w-0 flex-grow-1">
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars(($user["first_name"] ?? "") . " " . ($user["last_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                                    <div class="text-muted small text-break"><?= htmlspecialchars($user["email"] ?? "", ENT_QUOTES, "UTF-8") ?></div>
                                    <span class="badge <?= $st === "adm" ? "bg-danger" : "bg-secondary" ?> mt-2"><?= htmlspecialchars((string)($user["status"] ?? ""), ENT_QUOTES, "UTF-8") ?></span>
                                    <?php if ($hasBlockedCol && $st !== "adm"): ?>
                                        <span class="badge <?= $blk ? "bg-dark" : "bg-success" ?> mt-2"><?= $blk ? "Blocked" : "Active" ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex gap-2 pt-1 flex-wrap">
                                <a href="<?= htmlspecialchars(sol_url("admin/user_edit.php?id=" . (int)$user["id"]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-primary btn-sm flex-grow-1 rounded-pill"><i class="bi bi-pencil-square me-1"></i> Edit</a>
                                <?php if ((int)$user["id"] !== $adminId && $hasBlockedCol && $st !== "adm"): ?>
                                    <form method="post" class="flex-grow-1">
                                        <?= sol_csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int)$user["id"] ?>">
                                        <button type="submit" name="toggle_block" value="1" class="btn btn-outline-warning btn-sm w-100 rounded-pill"><?= $blk ? "Unblock" : "Block" ?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if ((int)$user["id"] !== $adminId): ?>
                                    <form method="post" action="<?= htmlspecialchars(sol_url("admin/user_delete.php"), ENT_QUOTES, "UTF-8") ?>" class="flex-grow-1" data-sol-confirm="Delete this user?">
                                        <?= sol_csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$user["id"] ?>">
                                        <button type="submit" name="delete_user" value="1" class="btn btn-outline-danger btn-sm w-100 rounded-pill"><i class="bi bi-trash me-1"></i> Delete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-light border shadow-sm mb-0 rounded-3">No users found.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
