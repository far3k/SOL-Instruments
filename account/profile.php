<?php

declare(strict_types=1);

# Profile editor: name, DOB, avatar upload, password change; customer-only path.

require_once dirname(__DIR__) . "/includes/app.php";

if (!isset($_SESSION["user"]) && !isset($_SESSION["adm"])) {
    header("Location: " . sol_url("login.php"));
    exit;
}

if (isset($_SESSION["adm"])) {
    header("Location: " . sol_url("admin/dashboard.php"));
    exit;
}

require_once dirname(__DIR__) . "/file_upload.php";

$uidLoad = isset($_SESSION["uid"]) ? (int)$_SESSION["uid"] : 0;
if ($uidLoad < 1) {
    header("Location: " . sol_url("account/home.php"));
    exit;
}

# Optional users.dob column; fetch row used by all mini-forms below.
$hasDobColumn = false;
$colResult = $connect->query("SHOW COLUMNS FROM users LIKE 'dob'");
if ($colResult && $colResult->num_rows === 1) {
    $hasDobColumn = true;
}

$cols = "id, first_name, last_name, email, pass, picture" . ($hasDobColumn ? ", dob" : "");
$st = $connect->prepare("SELECT $cols FROM users WHERE id = ? LIMIT 1");
$st->bind_param("i", $uidLoad);
$st->execute();
$user = $st->get_result()->fetch_assoc();
$st->close();

if (!$user) {
    header("Location: " . sol_url("account/home.php"));
    exit;
}

$dobInput = $hasDobColumn && !empty($user["dob"]) ? $user["dob"] : "";
$dobError = $dobSuccess = $dobUpdateError = "";
$fnameError = $fnameSuccess = "";
$lnameError = $lnameSuccess = "";
$emailError = $emailSuccess = "";
$pictureError = $pictureSuccess = "";
$currentPassError = $passError = $confirmError = $passwordSuccess = $passwordUpdateError = "";

# Each button name maps to a small POST handler (CSRF checked per action).
if (isset($_POST["update_first_name"])) {
    if (!sol_csrf_verify()) {
        $fnameError = "Security check failed.";
    } else {
    $fname = trim(strip_tags(isset($_POST["fname"]) ? $_POST["fname"] : ""));
    if ($fname === "") {
        $fnameError = "Please enter your first name.";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $fname)) {
        $fnameError = "First name can contain only letters and spaces.";
    } elseif (strlen($fname) < 2) {
        $fnameError = "First name must have at least 2 characters.";
    } else {
        $safeFname = $connect->real_escape_string($fname);
        $updateSql = "UPDATE users SET first_name='$safeFname' WHERE id=" . (int)$user["id"] . " LIMIT 1";
        if ($connect->query($updateSql)) {
            $fnameSuccess = "First name updated successfully.";
            $user["first_name"] = $fname;
            $_SESSION["user"] = $fname;
        } else {
            $fnameError = "Failed to update first name.";
        }
    }
    }
}

if (isset($_POST["update_last_name"])) {
    if (!sol_csrf_verify()) {
        $lnameError = "Security check failed.";
    } else {
    $lname = trim(strip_tags(isset($_POST["lname"]) ? $_POST["lname"] : ""));
    if ($lname === "") {
        $lnameError = "Please enter your last name.";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $lname)) {
        $lnameError = "Last name can contain only letters and spaces.";
    } elseif (strlen($lname) < 3) {
        $lnameError = "Last name must have at least 3 characters.";
    } else {
        $safeLname = $connect->real_escape_string($lname);
        $updateSql = "UPDATE users SET last_name='$safeLname' WHERE id=" . (int)$user["id"] . " LIMIT 1";
        if ($connect->query($updateSql)) {
            $lnameSuccess = "Last name updated successfully.";
            $user["last_name"] = $lname;
        } else {
            $lnameError = "Failed to update last name.";
        }
    }
    }
}

if (isset($_POST["update_email"])) {
    if (!sol_csrf_verify()) {
        $emailError = "Security check failed.";
    } else {
    $email = trim(isset($_POST["email"]) ? $_POST["email"] : "");
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailError = "Please enter a valid email address.";
    } else {
        $safeEmail = $connect->real_escape_string($email);
        $uid = (int)$user["id"];
        $dupQuery = "SELECT id FROM users WHERE email='$safeEmail' AND id <> $uid LIMIT 1";
        $dupResult = $connect->query($dupQuery);
        if ($dupResult && $dupResult->num_rows > 0) {
            $emailError = "This email address is already in use.";
        } else {
            $updateSql = "UPDATE users SET email='$safeEmail' WHERE id=$uid LIMIT 1";
            if ($connect->query($updateSql)) {
                $emailSuccess = "Email updated successfully.";
                $user["email"] = $email;
            } else {
                $emailError = "Failed to update email.";
            }
        }
    }
    }
}

if (isset($_POST["update_picture"])) {
    if (!sol_csrf_verify()) {
        $pictureError = "Security check failed.";
    } else {
    if (!isset($_FILES["picture"]) || (isset($_FILES["picture"]["error"]) && $_FILES["picture"]["error"] === UPLOAD_ERR_NO_FILE)) {
        $pictureError = "Please select an image file.";
    } else {
    $upload = fileUpload($_FILES["picture"]);
    if ($upload[0] === null) {
        $pictureError = $upload[1];
    } else {
        $safePicture = $connect->real_escape_string($upload[0]);
        $updateSql = "UPDATE users SET picture='$safePicture' WHERE id=" . (int)$user["id"] . " LIMIT 1";
        if ($connect->query($updateSql)) {
            $pictureSuccess = "Profile picture updated. " . $upload[1];
            $user["picture"] = $upload[0];
        } else {
            $pictureError = "Failed to save profile picture.";
        }
    }
    }
    }
}

if (isset($_POST["update_dob"])) {
    if (!sol_csrf_verify()) {
        $dobUpdateError = "Security check failed.";
    } else {
    if (!$hasDobColumn) {
        $dobUpdateError = "Date of birth column is not available in database.";
    } else {
        $newDob = isset($_POST["dob"]) ? trim($_POST["dob"]) : "";
        if ($newDob !== "") {
            $dobDate = date_create($newDob);
            if ($dobDate === false || date_format($dobDate, "Y-m-d") !== $newDob) {
                $dobError = "Please enter a valid date of birth.";
            } else {
                $safeDob = $connect->real_escape_string($newDob);
                $updateSql = "UPDATE users SET dob='$safeDob' WHERE id=" . (int)$user["id"] . " LIMIT 1";
                if ($connect->query($updateSql)) {
                    $dobSuccess = "Date of birth updated successfully.";
                    $user["dob"] = $newDob;
                    $dobInput = $newDob;
                } else {
                    $dobUpdateError = "Failed to update date of birth.";
                }
            }
        } else {
            $updateSql = "UPDATE users SET dob=NULL WHERE id=" . (int)$user["id"] . " LIMIT 1";
            if ($connect->query($updateSql)) {
                $dobSuccess = "Date of birth cleared successfully.";
                $user["dob"] = null;
                $dobInput = "";
            } else {
                $dobUpdateError = "Failed to update date of birth.";
            }
        }
    }
    }
}

if (isset($_POST["update_password"])) {
    if (!sol_csrf_verify()) {
        $passwordUpdateError = "Security check failed.";
    } else {
    $currentPass = isset($_POST["current_pass"]) ? $_POST["current_pass"] : "";
    $newPass = isset($_POST["new_pass"]) ? $_POST["new_pass"] : "";
    $confirmPass = isset($_POST["confirm_pass"]) ? $_POST["confirm_pass"] : "";

    if ($currentPass === "") {
        $currentPassError = "Please enter your current password.";
    } elseif (!sol_verify_password($currentPass, (string)$user["pass"])) {
        $currentPassError = "Current password is incorrect.";
    }

    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{6,}$/', $newPass)) {
        $passError = "Password must be at least 6 characters and include letters and numbers.";
    }

    if ($newPass !== $confirmPass) {
        $confirmError = "Password confirmation does not match.";
    }

    if ($currentPassError === "" && empty($passError) && empty($confirmError)) {
        $hashedPass = sol_hash_password($newPass);
        $uidPw = (int)$user["id"];
        $stPw = $connect->prepare("UPDATE users SET pass = ? WHERE id = ? LIMIT 1");
        $stPw->bind_param("si", $hashedPass, $uidPw);
        if ($stPw->execute()) {
            $passwordSuccess = "Password changed successfully.";
            $user["pass"] = $hashedPass;
        } else {
            $passwordUpdateError = "Failed to change password.";
        }
        $stPw->close();
    }
    }
}

# View helpers: which editors stay expanded after failed validation.
$uidProfile = (int)$user["id"];

$dateOfBirth = $hasDobColumn && isset($user["dob"]) && $user["dob"] !== "" ? $user["dob"] : "Not set";
$showDobEditor = isset($_POST["update_dob"]) || $dobError || $dobUpdateError;
$showFnameEditor = isset($_POST["update_first_name"]) || $fnameError;
$showLnameEditor = isset($_POST["update_last_name"]) || $lnameError;
$showEmailEditor = isset($_POST["update_email"]) || $emailError;
$showPictureEditor = isset($_POST["update_picture"]) || $pictureError;

$editIconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2 3 10.207V11h.793L12 2.793zm1.586 1.586L13.5 2.879 12.121 1.5l-.707.707z"/></svg>';

$page_title = "Profile";
$nav_role = "user";
$active_nav = "profile";
$extra_head = <<<'HTML'
<style>
.profile-shell{border:1px solid var(--sol-card-border);border-radius:1rem}
.profile-section{border:1px solid var(--sol-card-border);border-radius:.75rem;background:#fff}
</style>
HTML;
require_once dirname(__DIR__) . "/includes/layout_top.php";
?>

    <div class="container py-4 py-lg-5" style="max-width: 1140px;">
        <div class="mb-4">
            <p class="text-secondary small mb-1 text-uppercase letter-spacing-1">Account</p>
            <h1 class="h2 fw-bold text-dark mb-0">My profile</h1>
            <p class="text-muted mb-0 mt-1">Update your details and password.</p>
        </div>

        <div class="card profile-shell border-0 shadow-sm bg-white">
            <div class="card-body p-4 p-lg-5">
                <h2 class="h5 fw-bold text-dark text-center mb-4">My Profile</h2>

                <?php if ($dobSuccess): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($dobSuccess) ?></div>
                <?php endif; ?>

                <?php if ($dobUpdateError): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($dobUpdateError) ?></div>
                <?php endif; ?>

                <?php if ($passwordSuccess): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($passwordSuccess) ?></div>
                <?php endif; ?>

                <?php if ($passwordUpdateError): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($passwordUpdateError) ?></div>
                <?php endif; ?>

                <?php if ($fnameSuccess): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($fnameSuccess) ?></div>
                <?php endif; ?>
                <?php if ($lnameSuccess): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($lnameSuccess) ?></div>
                <?php endif; ?>
                <?php if ($emailSuccess): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($emailSuccess) ?></div>
                <?php endif; ?>
                <?php if ($pictureSuccess): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($pictureSuccess) ?></div>
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-12">
                        <div class="profile-section p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= htmlspecialchars(sol_url("pictures/" . ($user["picture"] ?? "avatar.png")), ENT_QUOTES, "UTF-8") ?>" alt="Profile picture" width="80" height="80" class="rounded-circle object-fit-cover border">
                                    <div>
                                        <small class="text-muted d-block">Profile picture</small>
                                        <strong class="d-block">Photo</strong>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#pictureEditor"
                                    aria-expanded="<?= $showPictureEditor ? "true" : "false" ?>"
                                    aria-controls="pictureEditor"
                                    title="Edit profile picture">
                                    <?= $editIconSvg ?>
                                </button>
                            </div>
                            <div id="pictureEditor" class="collapse mt-3 <?= $showPictureEditor ? "show" : "" ?>">
                                <form method="post" enctype="multipart/form-data" autocomplete="off">
                                    <?= sol_csrf_field() ?>
                                    <div class="mb-2">
                                        <input type="file" class="form-control <?= $pictureError ? "is-invalid" : "" ?>" id="picture" name="picture" accept="image/*">
                                        <?php if ($pictureError): ?>
                                            <span class="invalid-feedback"><?= htmlspecialchars($pictureError) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button type="submit" name="update_picture" class="btn btn-primary btn-sm">Save picture</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="profile-section p-3 h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">First Name</small>
                                    <strong><?= htmlspecialchars($user["first_name"]) ?></strong>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#fnameEditor"
                                    aria-expanded="<?= $showFnameEditor ? "true" : "false" ?>"
                                    aria-controls="fnameEditor"
                                    title="Edit first name">
                                    <?= $editIconSvg ?>
                                </button>
                            </div>
                            <div id="fnameEditor" class="collapse mt-3 <?= $showFnameEditor ? "show" : "" ?>">
                                <form method="post" autocomplete="off">
                                    <?= sol_csrf_field() ?>
                                    <div class="mb-2">
                                        <input type="text" name="fname" id="fname" class="form-control <?= $fnameError ? "is-invalid" : "" ?>" value="<?= htmlspecialchars($user["first_name"]) ?>">
                                        <?php if ($fnameError): ?>
                                            <span class="invalid-feedback"><?= htmlspecialchars($fnameError) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button type="submit" name="update_first_name" class="btn btn-primary btn-sm">Save</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="profile-section p-3 h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">Last Name</small>
                                    <strong><?= htmlspecialchars($user["last_name"]) ?></strong>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#lnameEditor"
                                    aria-expanded="<?= $showLnameEditor ? "true" : "false" ?>"
                                    aria-controls="lnameEditor"
                                    title="Edit last name">
                                    <?= $editIconSvg ?>
                                </button>
                            </div>
                            <div id="lnameEditor" class="collapse mt-3 <?= $showLnameEditor ? "show" : "" ?>">
                                <form method="post" autocomplete="off">
                                    <?= sol_csrf_field() ?>
                                    <div class="mb-2">
                                        <input type="text" name="lname" id="lname" class="form-control <?= $lnameError ? "is-invalid" : "" ?>" value="<?= htmlspecialchars($user["last_name"]) ?>">
                                        <?php if ($lnameError): ?>
                                            <span class="invalid-feedback"><?= htmlspecialchars($lnameError) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button type="submit" name="update_last_name" class="btn btn-primary btn-sm">Save</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="profile-section p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">Email</small>
                                    <strong><?= htmlspecialchars($user["email"]) ?></strong>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#emailEditor"
                                    aria-expanded="<?= $showEmailEditor ? "true" : "false" ?>"
                                    aria-controls="emailEditor"
                                    title="Edit email">
                                    <?= $editIconSvg ?>
                                </button>
                            </div>
                            <div id="emailEditor" class="collapse mt-3 <?= $showEmailEditor ? "show" : "" ?>">
                                <form method="post" autocomplete="off">
                                    <?= sol_csrf_field() ?>
                                    <div class="mb-2">
                                        <input type="email" name="email" id="email" class="form-control <?= $emailError ? "is-invalid" : "" ?>" value="<?= htmlspecialchars($user["email"]) ?>">
                                        <?php if ($emailError): ?>
                                            <span class="invalid-feedback"><?= htmlspecialchars($emailError) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button type="submit" name="update_email" class="btn btn-primary btn-sm">Save</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="profile-section p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">Date of Birth</small>
                                    <strong><?= htmlspecialchars($dateOfBirth) ?></strong>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#dobEditor"
                                    aria-expanded="<?= $showDobEditor ? "true" : "false" ?>"
                                    aria-controls="dobEditor"
                                    title="Edit date of birth"
                                    <?= !$hasDobColumn ? "disabled" : "" ?>>
                                    <?= $editIconSvg ?>
                                </button>
                            </div>

                            <?php if (!$hasDobColumn): ?>
                                <div class="alert alert-warning py-2 mt-2 mb-0">Add <code>dob</code> column to enable editing.</div>
                            <?php endif; ?>

                            <div id="dobEditor" class="collapse mt-3 <?= $showDobEditor ? "show" : "" ?>">
                                <form method="post" autocomplete="off">
                                    <?= sol_csrf_field() ?>
                                    <div class="mb-2">
                                        <input type="date" id="dob" name="dob" class="form-control <?= $dobError ? "is-invalid" : "" ?>" value="<?= htmlspecialchars($dobInput) ?>" <?= !$hasDobColumn ? "disabled" : "" ?>>
                                        <?php if ($dobError): ?>
                                            <span class="invalid-feedback"><?= htmlspecialchars($dobError) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button type="submit" name="update_dob" class="btn btn-primary btn-sm" <?= !$hasDobColumn ? "disabled" : "" ?>>Save</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4 border-secondary border-opacity-25">

                <div class="profile-section p-4">
                <h2 class="h5 fw-bold text-dark mb-3">Change Password</h2>
                <form method="post" autocomplete="off">
                    <?= sol_csrf_field() ?>
                    <div class="mb-3">
                        <label for="current_pass" class="form-label">Current Password</label>
                        <div class="input-group">
                            <input type="password" id="current_pass" name="current_pass" class="form-control <?= $currentPassError ? "is-invalid" : "" ?>">
                            <button type="button" class="btn btn-outline-secondary password-peek-btn" data-target="current_pass" aria-label="Show password while holding">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.087.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                    <path d="M8 5.5A2.5 2.5 0 1 0 8 10.5 2.5 2.5 0 0 0 8 5.5m0 1A1.5 1.5 0 1 1 8 9.5 1.5 1.5 0 0 1 8 6.5"/>
                                </svg>
                            </button>
                            <?php if ($currentPassError): ?>
                                <span class="invalid-feedback"><?= htmlspecialchars($currentPassError) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="new_pass" class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" id="new_pass" name="new_pass" class="form-control <?= $passError ? "is-invalid" : "" ?>">
                            <button type="button" class="btn btn-outline-secondary password-peek-btn" data-target="new_pass" aria-label="Show password while holding">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.087.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                    <path d="M8 5.5A2.5 2.5 0 1 0 8 10.5 2.5 2.5 0 0 0 8 5.5m0 1A1.5 1.5 0 1 1 8 9.5 1.5 1.5 0 0 1 8 6.5"/>
                                </svg>
                            </button>
                            <?php if ($passError): ?>
                                <span class="invalid-feedback"><?= htmlspecialchars($passError) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_pass" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" id="confirm_pass" name="confirm_pass" class="form-control <?= $confirmError ? "is-invalid" : "" ?>">
                            <button type="button" class="btn btn-outline-secondary password-peek-btn" data-target="confirm_pass" aria-label="Show password while holding">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.087.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                    <path d="M8 5.5A2.5 2.5 0 1 0 8 10.5 2.5 2.5 0 0 0 8 5.5m0 1A1.5 1.5 0 1 1 8 9.5 1.5 1.5 0 0 1 8 6.5"/>
                                </svg>
                            </button>
                            <?php if ($confirmError): ?>
                                <span class="invalid-feedback"><?= htmlspecialchars($confirmError) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" name="update_password" class="btn btn-primary rounded-pill px-4">Change Password</button>
                </form>
                </div>
            </div>
        </div>
    </div>
<?php
$layout_extra_scripts = <<<'JS'
<script>
document.querySelectorAll(".password-peek-btn").forEach(function (btn) {
    var target = document.getElementById(btn.getAttribute("data-target"));
    if (!target) return;
    var show = function () { target.type = "text"; };
    var hide = function () { target.type = "password"; };
    btn.addEventListener("mousedown", show);
    btn.addEventListener("mouseup", hide);
    btn.addEventListener("mouseleave", hide);
    btn.addEventListener("touchstart", show, { passive: true });
    btn.addEventListener("touchend", hide);
    btn.addEventListener("touchcancel", hide);
});
</script>
JS;
require_once dirname(__DIR__) . "/includes/layout_bottom.php";
