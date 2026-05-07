<?php

# Must be included by app/bootstrap; direct hits redirect to login.

if (count(get_included_files()) === 1) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . "Database.php";

/** @var mysqli $connect */
$connect = Database::getInstance()->getConnection();

