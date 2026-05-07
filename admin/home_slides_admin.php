<?php

declare(strict_types=1);

# Manage home page hero carousel (background image + copy + CTAs).

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_admin();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$flashOk = "";
$flashErr = "";

if (!sol_db_table_exists($connect, "home_slides")) {
    $page_title = "Home slides";
    $nav_role = "admin";
    $active_nav = "home_slides_admin";
    require_once dirname(__DIR__) . "/includes/layout_top.php";
    echo '<div class="container py-4"><div class="alert alert-warning">Run <code>schema_updates_home_slides.sql</code> on your database first.</div></div>';
    require_once dirname(__DIR__) . "/includes/layout_bottom.php";
    exit;
}

$uploadDir = sol_home_slides_upload_dir();
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

function sol_admin_slide_validate_upload(array $f): ?string
{
    if (($f["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($f["error"] ?? 0) !== UPLOAD_ERR_OK || empty($f["tmp_name"])) {
        return "Upload failed.";
    }
    if (($f["size"] ?? 0) > 2_500_000) {
        return "Image must be under 2.5 MB.";
    }
    $info = @getimagesize($f["tmp_name"]);
    if ($info === false) {
        return "File is not a valid image.";
    }
    $allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF];
    if (!in_array($info[2], $allowed, true)) {
        return "Use JPG, PNG, WebP, or GIF.";
    }

    return null;
}

function sol_admin_slide_store_upload(array $f, string $uploadDir): string
{
    $ext = match ($f["type"] ?? "") {
        "image/jpeg", "image/jpg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp",
        "image/gif" => "gif",
        default => "jpg",
    };
    $name = "slide_" . bin2hex(random_bytes(8)) . "." . $ext;
    $dest = $uploadDir . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($f["tmp_name"], $dest)) {
        throw new RuntimeException("Could not save file.");
    }

    return sol_home_slides_upload_web_prefix() . $name;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && sol_csrf_verify()) {
    if (isset($_POST["delete_slide"])) {
        $id = (int)($_POST["id"] ?? 0);
        if ($id > 0) {
            $st = $connect->prepare("SELECT background_image FROM home_slides WHERE id = ? LIMIT 1");
            $st->bind_param("i", $id);
            $st->execute();
            $row = $st->get_result()->fetch_assoc();
            $st->close();
            if ($row && !empty($row["background_image"])) {
                $abs = SOL_ROOT . "/pictures/" . str_replace("\\", "/", (string)$row["background_image"]);
                if (is_file($abs)) {
                    @unlink($abs);
                }
            }
            $d = $connect->prepare("DELETE FROM home_slides WHERE id = ? LIMIT 1");
            $d->bind_param("i", $id);
            $d->execute();
            $d->close();
        }
        header("Location: " . sol_url("admin/home_slides_admin.php"));
        exit;
    }

    $heading = trim((string)($_POST["heading"] ?? ""));
    $subheading = trim((string)($_POST["subheading"] ?? ""));
    $sortOrder = (int)($_POST["sort_order"] ?? 0);
    $isActive = isset($_POST["is_active"]) ? 1 : 0;
    $audienceRaw = trim((string)($_POST["audience"] ?? "all"));
    $audienceKey = strtolower($audienceRaw);
    $audMap = [
        "all" => "all",
        "everyone" => "all",
        "guest" => "guest",
        "guests only" => "guest",
        "user" => "user",
        "users" => "user",
        "logged-in customers" => "user",
    ];
    $audience = $audMap[$audienceKey] ?? "all";
    $overlayPct = max(0, min(90, (int)($_POST["overlay_pct"] ?? 45)));
    $b1l = trim((string)($_POST["button1_label"] ?? ""));
    $b1u = trim((string)($_POST["button1_url"] ?? ""));
    $b2l = trim((string)($_POST["button2_label"] ?? ""));
    $b2u = trim((string)($_POST["button2_url"] ?? ""));
    if ($b1l === "") {
        $b1u = "";
    }
    if ($b2l === "") {
        $b2u = "";
    }

    if (isset($_POST["create_slide"])) {
        if ($heading === "") {
            $flashErr = "Heading is required.";
        } else {
            $bg = null;
            if (!empty($_FILES["background"]) && is_array($_FILES["background"])) {
                $errUp = sol_admin_slide_validate_upload($_FILES["background"]);
                if ($errUp !== null) {
                    $flashErr = $errUp;
                } else {
                    try {
                        $bg = sol_admin_slide_store_upload($_FILES["background"], $uploadDir);
                    } catch (Throwable $e) {
                        $flashErr = "Could not store image.";
                    }
                }
            }
            if ($flashErr === "") {
                $ins = $connect->prepare(
                    "INSERT INTO home_slides (sort_order, is_active, audience, background_image, overlay_pct, heading, subheading, button1_label, button1_url, button2_label, button2_url)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $ins->bind_param(
                    "iississssss",
                    $sortOrder,
                    $isActive,
                    $audience,
                    $bg,
                    $overlayPct,
                    $heading,
                    $subheading,
                    $b1l,
                    $b1u,
                    $b2l,
                    $b2u
                );
                $ins->execute();
                $ins->close();
                header("Location: " . sol_url("admin/home_slides_admin.php"));
                exit;
            }
        }
    }

    if (isset($_POST["update_slide"])) {
        $id = (int)($_POST["id"] ?? 0);
        if ($id < 1 || $heading === "") {
            $flashErr = "Invalid data.";
        } else {
            $bgNew = null;
            $replaceBg = false;
            if (!empty($_FILES["background"]) && is_array($_FILES["background"]) && ($_FILES["background"]["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $errUp = sol_admin_slide_validate_upload($_FILES["background"]);
                if ($errUp !== null) {
                    $flashErr = $errUp;
                } else {
                    try {
                        $bgNew = sol_admin_slide_store_upload($_FILES["background"], $uploadDir);
                        $replaceBg = true;
                    } catch (Throwable $e) {
                        $flashErr = "Could not store image.";
                    }
                }
            }
            if ($flashErr === "") {
                $oldBg = "";
                $st = $connect->prepare("SELECT background_image FROM home_slides WHERE id = ? LIMIT 1");
                $st->bind_param("i", $id);
                $st->execute();
                $row = $st->get_result()->fetch_assoc();
                $st->close();
                if (!$row) {
                    $flashErr = "Slide not found.";
                } else {
                    $oldBg = (string)($row["background_image"] ?? "");
                    if ($replaceBg && $bgNew !== null) {
                        if ($oldBg !== "") {
                            $abs = SOL_ROOT . "/pictures/" . str_replace("\\", "/", $oldBg);
                            if (is_file($abs)) {
                                @unlink($abs);
                            }
                        }
                        $up = $connect->prepare(
                            "UPDATE home_slides SET sort_order=?, is_active=?, audience=?, background_image=?, overlay_pct=?, heading=?, subheading=?, button1_label=?, button1_url=?, button2_label=?, button2_url=? WHERE id=? LIMIT 1"
                        );
                        $up->bind_param(
                            "iississssssi",
                            $sortOrder,
                            $isActive,
                            $audience,
                            $bgNew,
                            $overlayPct,
                            $heading,
                            $subheading,
                            $b1l,
                            $b1u,
                            $b2l,
                            $b2u,
                            $id
                        );
                    } else {
                        $up = $connect->prepare(
                            "UPDATE home_slides SET sort_order=?, is_active=?, audience=?, overlay_pct=?, heading=?, subheading=?, button1_label=?, button1_url=?, button2_label=?, button2_url=? WHERE id=? LIMIT 1"
                        );
                        $up->bind_param(
                            "iisissssssi",
                            $sortOrder,
                            $isActive,
                            $audience,
                            $overlayPct,
                            $heading,
                            $subheading,
                            $b1l,
                            $b1u,
                            $b2l,
                            $b2u,
                            $id
                        );
                    }
                    $up->execute();
                    $up->close();
                    header("Location: " . sol_url("admin/home_slides_admin.php"));
                    exit;
                }
            }
        }
    }
}

$editId = isset($_GET["edit"]) ? (int)$_GET["edit"] : 0;
$editRow = null;
if ($editId > 0) {
    $st = $connect->prepare("SELECT * FROM home_slides WHERE id = ? LIMIT 1");
    $st->bind_param("i", $editId);
    $st->execute();
    $editRow = $st->get_result()->fetch_assoc();
    $st->close();
}

$list = $connect->query("SELECT * FROM home_slides ORDER BY sort_order ASC, id ASC");
$rows = $list ? $list->fetch_all(MYSQLI_ASSOC) : [];

$page_title = "Home slides";
$nav_role = "admin";
$active_nav = "home_slides_admin";
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

<div class="container py-4" style="max-width: 960px;">
    <h1 class="h3 mb-3">Home page slides</h1>
    <p class="text-muted small mb-4">Upload a wide background image (optional). Use <code>{{name}}</code> in heading or subheading for the logged-in user’s first name on user-only slides.</p>

    <?php if ($flashErr !== ""): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($flashErr, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <div class="table-responsive border rounded mb-4">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>#</th><th>Active</th><th>Audience</th><th>Heading</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int)$r["sort_order"] ?></td>
                        <td><?= (int)$r["is_active"] ? "Yes" : "No" ?></td>
                        <td><?= htmlspecialchars((string)$r["audience"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars(mb_substr((string)$r["heading"], 0, 60), ENT_QUOTES, "UTF-8") ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(sol_url("admin/home_slides_admin.php?edit=" . (int)$r["id"]), ENT_QUOTES, "UTF-8") ?>">Edit</a>
                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this slide?');">
                                <?= sol_csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
                                <button type="submit" name="delete_slide" value="1" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($rows === []): ?>
                    <tr><td colspan="5" class="text-muted small">No slides — the site uses built-in default slides until you add one.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    $f = static function (string $k, $row, string $def = ""): string {
        if ($row !== null && array_key_exists($k, $row)) {
            return htmlspecialchars((string)$row[$k], ENT_QUOTES, "UTF-8");
        }

        return htmlspecialchars($def, ENT_QUOTES, "UTF-8");
    };
    $isEdit = $editRow !== null;
    ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3"><?= $isEdit ? "Edit slide" : "Add slide" ?></h2>
            <form method="post" enctype="multipart/form-data" class="row g-3">
                <?= sol_csrf_field() ?>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int)$editRow["id"] ?>">
                <?php endif; ?>

                <div class="col-md-3">
                    <label class="form-label small">Sort order</label>
                    <input type="number" name="sort_order" class="form-control form-control-sm" value="<?= $f("sort_order", $editRow, "0") ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Audience</label>
                    <select name="audience" class="form-select form-select-sm">
                        <?php foreach (["all" => "Everyone", "guest" => "Guests only", "user" => "Logged-in customers"] as $av => $lab): ?>
                            <option value="<?= htmlspecialchars($av, ENT_QUOTES, "UTF-8") ?>" <?= ($editRow["audience"] ?? "all") === $av ? "selected" : "" ?>><?= htmlspecialchars($lab, ENT_QUOTES, "UTF-8") ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="hs_active" <?= ($editRow === null || (int)($editRow["is_active"] ?? 1)) ? "checked" : "" ?>>
                        <label class="form-check-label small" for="hs_active">Active</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Overlay darkness (0–90)</label>
                    <input type="number" name="overlay_pct" class="form-control form-control-sm" min="0" max="90" value="<?= $f("overlay_pct", $editRow, "45") ?>">
                </div>

                <div class="col-12">
                    <label class="form-label small">Background image</label>
                    <input type="file" name="background" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,image/gif">
                    <?php if ($isEdit && !empty($editRow["background_image"])): ?>
                        <p class="small text-muted mb-0 mt-1">Current: <code><?= htmlspecialchars((string)$editRow["background_image"], ENT_QUOTES, "UTF-8") ?></code> — upload a new file to replace.</p>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <label class="form-label small">Heading</label>
                    <input type="text" name="heading" class="form-control form-control-sm" required maxlength="255" value="<?= $f("heading", $editRow) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label small">Subheading</label>
                    <textarea name="subheading" class="form-control form-control-sm" rows="2"><?= $editRow ? htmlspecialchars((string)($editRow["subheading"] ?? ""), ENT_QUOTES, "UTF-8") : "" ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label small">Button 1 label</label>
                    <input type="text" name="button1_label" class="form-control form-control-sm" maxlength="120" value="<?= $f("button1_label", $editRow) ?>">
                    <label class="form-label small mt-1">Button 1 URL</label>
                    <input type="text" name="button1_url" class="form-control form-control-sm" placeholder="shop/catalog.php or full path from site root" value="<?= $f("button1_url", $editRow) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Button 2 label</label>
                    <input type="text" name="button2_label" class="form-control form-control-sm" maxlength="120" value="<?= $f("button2_label", $editRow) ?>">
                    <label class="form-label small mt-1">Button 2 URL</label>
                    <input type="text" name="button2_url" class="form-control form-control-sm" value="<?= $f("button2_url", $editRow) ?>">
                </div>

                <div class="col-12">
                    <?php if ($isEdit): ?>
                        <button type="submit" name="update_slide" value="1" class="btn btn-primary btn-sm">Save changes</button>
                        <a href="<?= htmlspecialchars(sol_url("admin/home_slides_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="create_slide" value="1" class="btn btn-primary btn-sm">Add slide</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
