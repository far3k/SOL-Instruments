<?php

declare(strict_types=1);

# Legacy rent/checkout.php — rental checkout now uses rent_checkout_confirm.php.

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_user();

$_SESSION["flash_error"] = "Please open your rent cart and use «Review & confirm» to choose dates, payment, and delivery.";

header("Location: " . sol_url("rent/rent_cart.php"));
exit;
