<?php

declare(strict_types=1);

# Legacy URL: rentals go through rent catalog → rent cart → checkout confirm.

require_once dirname(__DIR__) . "/includes/app.php";

$_SESSION["flash_error"] = "Rentals are booked from the instruments catalog and your rent cart.";

header("Location: " . sol_url("rent/rentcatalog.php"));
exit;
