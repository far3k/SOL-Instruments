<?php

declare(strict_types=1);

# Customer/admin sign-in with CSRF; blocked accounts rejected; password hash upgrade.

require_once __DIR__ . "/includes/app.php";

$email = "";
$emailError = $passError = $loginError = "";
$flashLogin = "";
if (!empty($_SESSION["flash_login"]) && is_string($_SESSION["flash_login"])) {
    $flashLogin = $_SESSION["flash_login"];
}
unset($_SESSION["flash_login"]);

# Signed-in visitors go straight to their dashboard.
if (isset($_SESSION["user"])) {
    header("Location: " . sol_url("account/home.php"));
    exit;
}

if (isset($_SESSION["adm"])) {
    header("Location: " . sol_url("admin/dashboard.php"));
    exit;
}

# POST login: CSRF, email/password validation, session + role branching.
if (isset($_POST["login"])) {
    if (!sol_csrf_verify()) {
        $loginError = "Security check failed. Please try again.";
    } else {
        $email = trim((string)($_POST["email"] ?? ""));
        $pass = (string)($_POST["pass"] ?? "");

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailError = "Please enter a valid email address.";
        } elseif ($pass === "") {
            $passError = "Password cannot be empty.";
        } else {
            $st = $connect->prepare("SELECT id, first_name, pass, status FROM users WHERE email = ? LIMIT 1");
            if ($st) {
                $st->bind_param("s", $email);
                $st->execute();
                $res = $st->get_result();
                if ($res->num_rows === 1) {
                    $user = $res->fetch_assoc();
                    $stored = (string)($user["pass"] ?? "");
                    if (sol_verify_password($pass, $stored)) {
                        $uidTry = (int)$user["id"];
                        if (sol_user_account_blocked($connect, $uidTry)) {
                            $loginError = "This account has been suspended.";
                        } else {
                            sol_maybe_upgrade_password($connect, $uidTry, $pass, $stored);
                            session_regenerate_id(true);
                            $_SESSION["uid"] = $uidTry;
                            if (isset($user["status"]) && $user["status"] === "adm") {
                                $_SESSION["adm"] = $user["id"];
                                header("Location: " . sol_url("admin/dashboard.php"));
                            } else {
                                $_SESSION["user"] = $user["first_name"];
                                header("Location: " . sol_url("account/home.php"));
                            }
                            $st->close();
                            exit;
                        }
                    } else {
                        $loginError = "Invalid email or password.";
                    }
                } else {
                    $loginError = "Invalid email or password.";
                }
                $st->close();
            }
        }
    }
}

$page_title = "Log in";
$nav_role = "auth";
$active_nav = "";
require_once __DIR__ . "/includes/layout_top.php";
?>

<div class="container py-4 py-lg-5" style="max-width: 1140px;">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5 col-xl-4">
            <div class="mb-4 text-center text-md-start">
                <p class="text-secondary small mb-1 text-uppercase">Sign in</p>
                <h1 class="h2 fw-bold text-dark mb-0">Log in</h1>
                <p class="text-muted small mt-2 mb-0">Use your email and password.</p>
            </div>

            <div class="card sol-card-shell border-0 shadow-sm bg-white">
                <div class="card-body p-4 p-lg-4">
                    <?php if ($flashLogin !== ""): ?>
                        <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4"><?= htmlspecialchars($flashLogin, ENT_QUOTES, "UTF-8") ?></div>
                    <?php endif; ?>
                    <?php if ($loginError !== ""): ?>
                        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4"><?= htmlspecialchars($loginError, ENT_QUOTES, "UTF-8") ?></div>
                    <?php endif; ?>

                    <form method="post" autocomplete="off">
                        <?= sol_csrf_field() ?>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-secondary"></i></span>
                                <input type="email" class="form-control border-start-0 <?= $emailError !== "" ? "is-invalid" : "" ?>" id="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>" placeholder="you@example.com">
                                <div class="invalid-feedback"><?= htmlspecialchars($emailError, ENT_QUOTES, "UTF-8") ?></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="pass" class="form-label">Password</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-key text-secondary"></i></span>
                                <input type="password" class="form-control border-start-0 <?= $passError !== "" ? "is-invalid" : "" ?>" id="pass" name="pass" placeholder="••••••••">
                                <button type="button" class="btn btn-outline-secondary password-peek-btn border-start-0" data-target="pass" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <div class="invalid-feedback"><?= htmlspecialchars($passError, ENT_QUOTES, "UTF-8") ?></div>
                            </div>
                        </div>

                        <button name="login" type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-medium"><i class="bi bi-box-arrow-in-right me-1"></i> Log in</button>
                    </form>
                </div>
            </div>

            <p class="text-center text-muted small mt-4 mb-0">
                No account yet? <a href="<?= htmlspecialchars(sol_url("register.php"), ENT_QUOTES, "UTF-8") ?>" class="fw-medium">Sign up</a>
            </p>
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
});
</script>
JS;
require_once __DIR__ . "/includes/layout_bottom.php";
