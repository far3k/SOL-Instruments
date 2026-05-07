<?php

declare(strict_types=1);

# New account registration with validation, CSRF, and duplicate-email checks.

require_once __DIR__ . "/includes/app.php";

# Already signed in → no need to register again.
if (isset($_SESSION["user"])) {
    header("Location: " . sol_url("account/home.php"));
    exit;
}

if (isset($_SESSION["adm"])) {
    header("Location: " . sol_url("admin/dashboard.php"));
    exit;
}

$error = false;
$redirectToLogin = false;
$successMessage = "";
$signupDbError = "";

$fname = $lname = $email = $pass = "";
$fnameError = $lnameError = $emailError = $passError = $confirmPassError = "";

# POST signup: field rules, duplicate email check, bcrypt insert.
if (isset($_POST["signup"])) {
    if (!sol_csrf_verify()) {
        $error = true;
        $signupDbError = "Security check failed. Please refresh and try again.";
    } else {
        $fname = trim(strip_tags((string)($_POST["fname"] ?? "")));
        $lname = trim(strip_tags((string)($_POST["lname"] ?? "")));
        $email = trim((string)($_POST["email"] ?? ""));
        $pass = (string)($_POST["pass"] ?? "");
        $confirmPass = (string)($_POST["confirm_pass"] ?? "");

        if ($fname === "") {
            $error = true;
            $fnameError = "Please enter your first name.";
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $fname)) {
            $error = true;
            $fnameError = "First name can contain only letters and spaces.";
        } elseif (strlen($fname) < 2) {
            $error = true;
            $fnameError = "First name must have at least 2 characters.";
        }

        if ($lname === "") {
            $error = true;
            $lnameError = "Please enter your last name.";
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $lname)) {
            $error = true;
            $lnameError = "Last name can contain only letters and spaces.";
        } elseif (strlen($lname) < 3) {
            $error = true;
            $lnameError = "Last name must have at least 3 characters.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = true;
            $emailError = "Please enter a valid email address.";
        } else {
            $st = $connect->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
            if ($st) {
                $st->bind_param("s", $email);
                $st->execute();
                if ($st->get_result()->num_rows > 0) {
                    $error = true;
                    $emailError = "This email address is already in use.";
                }
                $st->close();
            }
        }

        if ($pass === "") {
            $error = true;
            $passError = "Password cannot be empty.";
        } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{6,}$/', $pass)) {
            $error = true;
            $passError = "Password must be at least 6 characters and include letters and numbers.";
        }

        if ($confirmPass === "") {
            $error = true;
            $confirmPassError = "Please confirm your password.";
        } elseif ($confirmPass !== $pass) {
            $error = true;
            $confirmPassError = "Passwords do not match.";
        }

        if (!$error) {
            $hashedPass = sol_hash_password($pass);
            $picFile = "avatar.png";
            $ins = $connect->prepare("INSERT INTO users (first_name, last_name, pass, email, picture) VALUES (?, ?, ?, ?, ?)");
            if ($ins) {
                $ins->bind_param("sssss", $fname, $lname, $hashedPass, $email, $picFile);
                if ($ins->execute()) {
                    $successMessage = "Account created successfully!";
                    $redirectToLogin = true;
                } else {
                    $signupDbError = "Something went wrong, please try again later.";
                }
                $ins->close();
            } else {
                $signupDbError = "Something went wrong, please try again later.";
            }
        }
    }
}

# View: card form + optional client-side password hints; meta refresh after success.
$page_title = "Sign up";
$nav_role = "auth";
$active_nav = "";
$extra_head = $redirectToLogin ? '<meta http-equiv="refresh" content="3;url=' . htmlspecialchars(sol_url("login.php"), ENT_QUOTES, "UTF-8") . '">' : "";
require_once __DIR__ . "/includes/layout_top.php";
?>

<div class="container py-4 py-lg-5" style="max-width: 1140px;">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="mb-4 text-center text-md-start">
                <p class="text-secondary small mb-1 text-uppercase">New account</p>
                <h1 class="h2 fw-bold text-dark mb-0">Sign up</h1>
                <p class="text-muted small mt-2 mb-0">Fill in your details to register.</p>
            </div>

            <div class="card sol-card-shell border-0 shadow-sm bg-white">
                <div class="card-body p-4 p-lg-5">
                    <?php if ($successMessage !== ""): ?>
                        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-0"><?= $successMessage ?> Redirecting to login…</div>
                    <?php elseif ($signupDbError !== ""): ?>
                        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4"><?= htmlspecialchars($signupDbError, ENT_QUOTES, "UTF-8") ?></div>
                    <?php endif; ?>

                    <?php if (!$redirectToLogin): ?>
                    <form id="registerForm" method="post" autocomplete="off" novalidate>
                        <?= sol_csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="fname" class="form-label">First name</label>
                                <input type="text" class="form-control <?= $fnameError !== "" ? "is-invalid" : "" ?>" id="fname" name="fname" value="<?= htmlspecialchars($fname, ENT_QUOTES, "UTF-8") ?>">
                                <span class="invalid-feedback"><?= htmlspecialchars($fnameError, ENT_QUOTES, "UTF-8") ?></span>
                            </div>
                            <div class="col-md-6">
                                <label for="lname" class="form-label">Last name</label>
                                <input type="text" class="form-control <?= $lnameError !== "" ? "is-invalid" : "" ?>" id="lname" name="lname" value="<?= htmlspecialchars($lname, ENT_QUOTES, "UTF-8") ?>">
                                <span class="invalid-feedback"><?= htmlspecialchars($lnameError, ENT_QUOTES, "UTF-8") ?></span>
                            </div>
                        </div>

                        <div class="mb-3 mt-1">
                            <label for="email" class="form-label">Email address</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-secondary"></i></span>
                                <input type="email" class="form-control border-start-0 <?= $emailError !== "" ? "is-invalid" : "" ?>" id="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>" placeholder="you@example.com">
                                <div class="invalid-feedback"><?= htmlspecialchars($emailError, ENT_QUOTES, "UTF-8") ?></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="pass" class="form-label">Password</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-key text-secondary"></i></span>
                                <input type="password" class="form-control border-start-0 <?= $passError !== "" ? "is-invalid" : "" ?>" id="pass" name="pass" placeholder="••••••••" minlength="6" autocomplete="new-password" aria-describedby="passHelp">
                                <button type="button" class="btn btn-outline-secondary password-peek-btn border-start-0" data-target="pass" aria-label="Show password"><i class="bi bi-eye"></i></button>
                                <div class="invalid-feedback"><?= htmlspecialchars($passError, ENT_QUOTES, "UTF-8") ?></div>
                            </div>
                            <small id="passHelp" class="form-text text-muted">At least 6 characters, with at least one letter and one number.</small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_pass" class="form-label">Confirm password</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-key-fill text-secondary"></i></span>
                                <input type="password" class="form-control border-start-0 <?= $confirmPassError !== "" ? "is-invalid" : "" ?>" id="confirm_pass" name="confirm_pass" placeholder="Repeat password" autocomplete="new-password" aria-describedby="confirmPassFeedback">
                                <button type="button" class="btn btn-outline-secondary password-peek-btn border-start-0" data-target="confirm_pass" aria-label="Show confirm password"><i class="bi bi-eye"></i></button>
                                <div class="invalid-feedback"><?= htmlspecialchars($confirmPassError, ENT_QUOTES, "UTF-8") ?></div>
                            </div>
                            <div id="confirmPassFeedback" class="form-text small"></div>
                        </div>

                        <button name="signup" type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-medium"><i class="bi bi-person-plus me-1"></i> Create account</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$redirectToLogin): ?>
            <p class="text-center text-muted small mt-4 mb-0">
                Already have an account? <a href="<?= htmlspecialchars(sol_url("login.php"), ENT_QUOTES, "UTF-8") ?>" class="fw-medium">Log in</a>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$layout_extra_scripts = <<<'JS'
<script>
(function () {
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
    var form = document.getElementById("registerForm");
    if (!form) return;
    var passEl = document.getElementById("pass");
    var confirmEl = document.getElementById("confirm_pass");
    var passHelp = document.getElementById("passHelp");
    var confirmFb = document.getElementById("confirmPassFeedback");
    if (!passEl || !confirmEl || !passHelp || !confirmFb) return;
    var PASS_RE = /^(?=.*[A-Za-z])(?=.*\d).{6,}$/;
    function isPasswordValid(value) { return PASS_RE.test(value); }
    function setPassVisual(valid, empty) { passEl.classList.toggle("is-invalid", !empty && !valid); }
    function updatePassHelp() {
        var v = passEl.value;
        if (v.length === 0) {
            passHelp.textContent = "At least 6 characters, with at least one letter and one number.";
            passHelp.className = "form-text text-muted";
            setPassVisual(true, true);
            return false;
        }
        if (!isPasswordValid(v)) {
            passHelp.textContent = "Password must be at least 6 characters and include letters and numbers.";
            passHelp.className = "form-text text-danger";
            setPassVisual(false, false);
            return false;
        }
        passHelp.textContent = "Password meets the requirements.";
        passHelp.className = "form-text text-success";
        setPassVisual(true, false);
        return true;
    }
    function updateConfirmHelp() {
        var p = passEl.value, c = confirmEl.value;
        if (c.length === 0) {
            confirmFb.textContent = "";
            confirmFb.className = "form-text small";
            confirmEl.classList.remove("is-invalid");
            return false;
        }
        if (c !== p) {
            confirmFb.textContent = "Passwords do not match.";
            confirmFb.className = "form-text small text-danger";
            confirmEl.classList.add("is-invalid");
            return false;
        }
        confirmFb.textContent = "Passwords match.";
        confirmFb.className = "form-text small text-success";
        confirmEl.classList.remove("is-invalid");
        return true;
    }
    passEl.addEventListener("input", function () {
        updatePassHelp();
        if (confirmEl.value.length > 0) updateConfirmHelp();
    });
    confirmEl.addEventListener("input", updateConfirmHelp);
    if (passEl.value) updatePassHelp();
    if (confirmEl.value) updateConfirmHelp();
    form.addEventListener("submit", function (e) {
        var pv = passEl.value, cv = confirmEl.value;
        if (pv.length === 0) {
            passHelp.textContent = "Please enter a password.";
            passHelp.className = "form-text text-danger";
            passEl.classList.add("is-invalid");
            e.preventDefault();
            passEl.focus();
            return;
        }
        updatePassHelp();
        updateConfirmHelp();
        if (!isPasswordValid(pv)) { e.preventDefault(); passEl.focus(); return; }
        if (cv.length === 0) {
            confirmFb.textContent = "Please confirm your password.";
            confirmFb.className = "form-text small text-danger";
            confirmEl.classList.add("is-invalid");
            e.preventDefault();
            confirmEl.focus();
            return;
        }
        if (cv !== pv) { e.preventDefault(); confirmEl.focus(); }
    });
})();
</script>
JS;
require_once __DIR__ . "/includes/layout_bottom.php";
