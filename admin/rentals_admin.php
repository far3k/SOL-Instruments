<?php

declare(strict_types=1);

# Rental queue: approve/decline requests, optional admin_note / decided_at columns.

require_once dirname(__DIR__) . "/includes/app.php";
require_once dirname(__DIR__) . "/includes/rental_helpers.php";
sol_require_admin();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$adminId = (int)$_SESSION["adm"];
$flashOk = "";
$flashErr = "";

$hasAdminNote = sol_db_column_exists($connect, "rental_requests", "admin_note");
$hasDecidedAt = sol_db_column_exists($connect, "rental_requests", "decided_at");
$hasRentalOptions = sol_db_column_exists($connect, "rental_requests", "payment_method")
    && sol_db_column_exists($connect, "rental_requests", "delivery_method");

# Approve / decline / notes: one POST controller with rental_action discriminator.
if ($_SERVER["REQUEST_METHOD"] === "POST" && sol_csrf_verify()) {
    $action = (string)($_POST["rental_action"] ?? "");
    $rid = (int)($_POST["request_id"] ?? 0);
    if ($rid < 1) {
        $flashErr = "Invalid request.";
    } else {
        $st = $connect->prepare("SELECT id, user_id, instrument_id, start_date, end_date, status FROM rental_requests WHERE id = ? LIMIT 1");
        $st->bind_param("i", $rid);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$row) {
            $flashErr = "Rental request not found.";
        } else {
            $old = (string)($row["status"] ?? "");
            $iid = (int)$row["instrument_id"];
            $sd = (string)$row["start_date"];
            $ed = (string)$row["end_date"];

            if ($action === "approve" && $old === "pending") {
                if (sol_rental_has_approved_overlap($connect, $iid, $sd, $ed, $rid)) {
                    $flashErr = "Cannot approve: dates overlap another approved rental for this instrument.";
                } else {
                    $noteSql = $hasAdminNote ? ", admin_note = NULL" : "";
                    $decSql = $hasDecidedAt ? ", decided_at = NOW()" : "";
                    $connect->query("UPDATE rental_requests SET status = 'approved'" . $noteSql . $decSql . " WHERE id = " . $rid . " AND status = 'pending' LIMIT 1");
                    sol_rental_log_status($connect, $rid, $old, "approved", "admin approved", $adminId);
                    $flashOk = "Request #" . $rid . " approved.";
                }
            } elseif ($action === "reject" && $old === "pending") {
                $note = trim((string)($_POST["admin_note"] ?? ""));
                if ($note === "") {
                    $flashErr = "Please enter a short reason when rejecting.";
                } else {
                    $noteDb = mb_substr($note, 0, 500);
                    if ($hasAdminNote && $hasDecidedAt) {
                        $u = $connect->prepare("UPDATE rental_requests SET status = 'rejected', admin_note = ?, decided_at = NOW() WHERE id = ? AND status = 'pending' LIMIT 1");
                        $u->bind_param("si", $noteDb, $rid);
                        $u->execute();
                        $u->close();
                    } elseif ($hasAdminNote) {
                        $u = $connect->prepare("UPDATE rental_requests SET status = 'rejected', admin_note = ? WHERE id = ? AND status = 'pending' LIMIT 1");
                        $u->bind_param("si", $noteDb, $rid);
                        $u->execute();
                        $u->close();
                    } else {
                        $connect->query("UPDATE rental_requests SET status = 'rejected' WHERE id = " . $rid . " AND status = 'pending' LIMIT 1");
                    }
                    sol_rental_log_status($connect, $rid, $old, "rejected", mb_substr("admin rejected: " . $noteDb, 0, 250), $adminId);
                    $flashOk = "Request #" . $rid . " rejected.";
                }
            } elseif ($action === "complete" && $old === "approved") {
                $connect->query("UPDATE rental_requests SET status = 'completed' WHERE id = " . $rid . " AND status = 'approved' LIMIT 1");
                sol_rental_log_status($connect, $rid, $old, "completed", "admin completed", $adminId);
                $flashOk = "Request #" . $rid . " marked completed.";
            } elseif ($action === "cancel" && in_array($old, ["pending", "approved"], true)) {
                $reason = mb_substr(trim((string)($_POST["cancel_reason"] ?? "")), 0, 500);
                if ($old === "approved" && $reason === "") {
                    $flashErr = "Enter a short reason when cancelling an approved rental.";
                } else {
                    $u = $connect->prepare("UPDATE rental_requests SET status = 'cancelled' WHERE id = ? AND status IN ('pending','approved') LIMIT 1");
                    $u->bind_param("i", $rid);
                    $u->execute();
                    $aff = $u->affected_rows;
                    $u->close();
                    if ($aff < 1) {
                        $flashErr = "Could not cancel this request.";
                    } else {
                        if ($hasDecidedAt) {
                            $connect->query("UPDATE rental_requests SET decided_at = NOW() WHERE id = " . $rid . " LIMIT 1");
                        }
                        if ($hasAdminNote && $reason !== "") {
                            $u2 = $connect->prepare("UPDATE rental_requests SET admin_note = ? WHERE id = ? LIMIT 1");
                            $u2->bind_param("si", $reason, $rid);
                            $u2->execute();
                            $u2->close();
                        }
                        sol_rental_log_status($connect, $rid, $old, "cancelled", "admin cancelled: " . ($reason !== "" ? $reason : "(no note)"), $adminId);
                        $flashOk = "Request #" . $rid . " cancelled.";
                    }
                }
            } elseif ($action === "save_note" && $hasAdminNote) {
                $note = mb_substr(trim((string)($_POST["staff_note"] ?? "")), 0, 500);
                $u = $connect->prepare("UPDATE rental_requests SET admin_note = NULLIF(?, '') WHERE id = ? LIMIT 1");
                $u->bind_param("si", $note, $rid);
                $u->execute();
                $u->close();
                $flashOk = "Staff note saved for request #" . $rid . ".";
            } else {
                $flashErr = "Invalid action for current status.";
            }
        }
    }
}

$filterStatus = trim((string)($_GET["status"] ?? ""));
$rentalStatuses = ["pending", "approved", "rejected", "completed", "cancelled"];
if ($filterStatus !== "" && !in_array($filterStatus, $rentalStatuses, true)) {
    $filterStatus = "";
}
$userQ = trim((string)($_GET["user_q"] ?? ""));

$conds = ["1=1"];
$types = "";
$params = [];
if ($filterStatus !== "") {
    $conds[] = "r.status = ?";
    $types .= "s";
    $params[] = $filterStatus;
}
if ($userQ !== "") {
    $conds[] = "(u.email LIKE ? OR CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) LIKE ? OR CAST(r.id AS CHAR) = ?)";
    $like = "%" . $userQ . "%";
    $types .= "sss";
    $params[] = $like;
    $params[] = $like;
    $params[] = $userQ;
}
$sql = "SELECT r.*, u.first_name, u.last_name, u.email, i.name AS instrument_name
         FROM rental_requests r
         JOIN users u ON u.id = r.user_id
         JOIN instruments i ON i.id = r.instrument_id
         WHERE " . implode(" AND ", $conds) . "
         ORDER BY r.id DESC";
$stList = $connect->prepare($sql);
if ($types !== "") {
    $stList->bind_param($types, ...$params);
}
$stList->execute();
$rows = $stList->get_result()->fetch_all(MYSQLI_ASSOC);
$stList->close();

$page_title = "Rental requests";
$nav_role = "admin";
$active_nav = "rentals_admin";
require_once dirname(__DIR__) . "/includes/layout_top.php";

?>

<div class="container py-4" style="max-width: 1200px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0">Rental requests</h1>
            <p class="text-muted small mb-0">Filter the queue, approve or reject (overlap-checked), mark completed, cancel, and keep staff notes.</p>
        </div>
    </div>

    <?php if ($flashOk !== ""): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-3"><?= htmlspecialchars($flashOk, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>
    <?php if ($flashErr !== ""): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-3"><?= htmlspecialchars($flashErr, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <?php if (!$hasAdminNote || !$hasDecidedAt): ?>
        <div class="alert alert-warning border-0 small">Run <code>schema_updates.sql</code> so rejected requests can store <code>admin_note</code> and <code>decided_at</code>.</div>
    <?php endif; ?>
    <?php if (!$hasRentalOptions): ?>
        <div class="alert alert-warning border-0 small">Run <code>schema_updates_rental_options.sql</code> to record customer <strong>payment</strong> and <strong>delivery</strong> choices on each request.</div>
    <?php endif; ?>

    <form method="get" action="<?= htmlspecialchars(sol_url("admin/rentals_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="card border-0 shadow-sm mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach ($rentalStatuses as $rs): ?>
                        <option value="<?= htmlspecialchars($rs, ENT_QUOTES, "UTF-8") ?>" <?= $filterStatus === $rs ? "selected" : "" ?>><?= htmlspecialchars(ucfirst($rs), ENT_QUOTES, "UTF-8") ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">Search (request #, email, name)</label>
                <input type="search" name="user_q" class="form-control form-control-sm" value="<?= htmlspecialchars($userQ, ENT_QUOTES, "UTF-8") ?>" placeholder="e.g. 31 or @domain">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="<?= htmlspecialchars(sol_url("admin/rentals_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </div>
    </form>

    <?php if ($rows === []): ?>
        <div class="alert alert-light border shadow-sm">No rental requests match your filters.</div>
    <?php else: ?>
        <div class="table-responsive card border-0 shadow-sm">
            <table class="table table-hover align-middle mb-0 bg-white small">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Instrument</th>
                        <th>Dates</th>
                        <th>Payment &amp; delivery</th>
                        <th>Status</th>
                        <th style="min-width: 280px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $rid = (int)$r["id"];
                        $stt = (string)($r["status"] ?? "");
                        $badge = sol_rental_status_badge_class($stt);
                        $existingNote = trim((string)($r["admin_note"] ?? ""));
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= $rid ?></td>
                            <td>
                                <div class="fw-medium"><?= htmlspecialchars(trim(($r["first_name"] ?? "") . " " . ($r["last_name"] ?? "")), ENT_QUOTES, "UTF-8") ?></div>
                                <div class="small text-muted text-break"><?= htmlspecialchars((string)($r["email"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                            </td>
                            <td><?= htmlspecialchars((string)($r["instrument_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                            <td class="text-nowrap"><?= htmlspecialchars((string)($r["start_date"] ?? ""), ENT_QUOTES, "UTF-8") ?> → <?= htmlspecialchars((string)($r["end_date"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                            <td class="small" style="max-width: 220px;">
                                <?php if ($hasRentalOptions): ?>
                                    <?php
                                    $pmC = (string)($r["payment_method"] ?? "");
                                    $dmC = (string)($r["delivery_method"] ?? "");
                                    $dnC = trim((string)($r["delivery_notes"] ?? ""));
                                    ?>
                                    <div><?= htmlspecialchars(sol_rental_payment_label($pmC !== "" ? $pmC : "store"), ENT_QUOTES, "UTF-8") ?></div>
                                    <div class="text-muted"><?= htmlspecialchars(sol_rental_delivery_label($dmC !== "" ? $dmC : "pickup"), ENT_QUOTES, "UTF-8") ?></div>
                                    <?php if ($dnC !== ""): ?>
                                        <div class="mt-1 text-break small"><?= nl2br(htmlspecialchars(mb_substr($dnC, 0, 400), ENT_QUOTES, "UTF-8")) ?><?= mb_strlen($dnC) > 400 ? "…" : "" ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($stt, ENT_QUOTES, "UTF-8") ?></span></td>
                            <td>
                                <?php if ($stt === "pending"): ?>
                                    <form method="post" class="d-inline">
                                        <?= sol_csrf_field() ?>
                                        <input type="hidden" name="rental_action" value="approve">
                                        <input type="hidden" name="request_id" value="<?= $rid ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                    </form>
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#rej<?= $rid ?>">Reject</button>
                                    <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="collapse" data-bs-target="#can<?= $rid ?>">Cancel</button>
                                    <div class="collapse mt-2" id="rej<?= $rid ?>">
                                        <form method="post" class="border rounded p-2 bg-light">
                                            <?= sol_csrf_field() ?>
                                            <input type="hidden" name="rental_action" value="reject">
                                            <input type="hidden" name="request_id" value="<?= $rid ?>">
                                            <label class="form-label small">Reason (shown to customer)</label>
                                            <textarea name="admin_note" class="form-control form-control-sm mb-2" rows="2" required maxlength="500" placeholder="e.g. Dates not available"></textarea>
                                            <button type="submit" class="btn btn-danger btn-sm">Confirm reject</button>
                                        </form>
                                    </div>
                                    <div class="collapse mt-2" id="can<?= $rid ?>">
                                        <form method="post" class="border rounded p-2 bg-light" data-sol-confirm="Cancel this pending request?">
                                            <?= sol_csrf_field() ?>
                                            <input type="hidden" name="rental_action" value="cancel">
                                            <input type="hidden" name="request_id" value="<?= $rid ?>">
                                            <label class="form-label small">Reason (optional)</label>
                                            <input type="text" name="cancel_reason" class="form-control form-control-sm mb-2" maxlength="500" placeholder="Internal / customer message">
                                            <button type="submit" class="btn btn-warning btn-sm">Confirm cancel</button>
                                        </form>
                                    </div>
                                <?php elseif ($stt === "approved"): ?>
                                    <form method="post" class="d-inline mb-1" data-sol-confirm="Mark this rental as completed?">
                                        <?= sol_csrf_field() ?>
                                        <input type="hidden" name="rental_action" value="complete">
                                        <input type="hidden" name="request_id" value="<?= $rid ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">Mark completed</button>
                                    </form>
                                    <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="collapse" data-bs-target="#can<?= $rid ?>">Cancel booking</button>
                                    <div class="collapse mt-2" id="can<?= $rid ?>">
                                        <form method="post" class="border rounded p-2 bg-light" data-sol-confirm="Cancel this approved rental? Calendar slot will free up.">
                                            <?= sol_csrf_field() ?>
                                            <input type="hidden" name="rental_action" value="cancel">
                                            <input type="hidden" name="request_id" value="<?= $rid ?>">
                                            <label class="form-label small">Reason <span class="text-danger">*</span></label>
                                            <input type="text" name="cancel_reason" class="form-control form-control-sm mb-2" required maxlength="500" placeholder="Required for audit trail">
                                            <button type="submit" class="btn btn-warning btn-sm">Confirm cancel</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                                <?php if ($hasAdminNote): ?>
                                    <form method="post" class="mt-2 pt-2 border-top">
                                        <?= sol_csrf_field() ?>
                                        <input type="hidden" name="rental_action" value="save_note">
                                        <input type="hidden" name="request_id" value="<?= $rid ?>">
                                        <label class="form-label visually-hidden" for="staff-<?= $rid ?>">Staff note</label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" name="staff_note" id="staff-<?= $rid ?>" value="<?= htmlspecialchars($existingNote, ENT_QUOTES, "UTF-8") ?>" maxlength="500" placeholder="Staff / internal note">
                                            <button type="submit" class="btn btn-outline-secondary">Save</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
