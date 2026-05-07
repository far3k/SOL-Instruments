<?php

declare(strict_types=1);

# Clears session cookie and redirects; ?logout=1 performs full sign-out.

require_once __DIR__ . "/includes/app.php";

if (isset($_GET["logout"])) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), "", time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
    header("Location: " . sol_url("login.php"));
    exit;
}

if (!isset($_SESSION["user"]) && !isset($_SESSION["adm"])) {
    header("Location: " . sol_url("login.php"));
    exit;
}

header("Location: " . sol_url("account/home.php"));
exit;
