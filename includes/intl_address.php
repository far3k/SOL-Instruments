<?php

declare(strict_types=1);

# Shared international-style postal address for shop delivery + rent courier (same session).

/** @return array<string, string> ISO 3166-1 alpha-2 => English name */
function sol_intl_countries(): array
{
    static $c = null;
    if ($c !== null) {
        return $c;
    }
    $c = [
        "AD" => "Andorra", "AE" => "United Arab Emirates", "AF" => "Afghanistan", "AL" => "Albania",
        "AM" => "Armenia", "AT" => "Austria", "AU" => "Australia", "AZ" => "Azerbaijan",
        "BA" => "Bosnia and Herzegovina", "BE" => "Belgium", "BG" => "Bulgaria", "BH" => "Bahrain",
        "BR" => "Brazil", "BY" => "Belarus", "CA" => "Canada", "CH" => "Switzerland", "CL" => "Chile",
        "CN" => "China", "CO" => "Colombia", "CR" => "Costa Rica", "CY" => "Cyprus", "CZ" => "Czechia",
        "DE" => "Germany", "DK" => "Denmark", "DO" => "Dominican Republic", "DZ" => "Algeria",
        "EC" => "Ecuador", "EE" => "Estonia", "EG" => "Egypt", "ES" => "Spain", "FI" => "Finland",
        "FR" => "France", "GB" => "United Kingdom", "GE" => "Georgia", "GR" => "Greece", "GT" => "Guatemala",
        "HK" => "Hong Kong SAR", "HR" => "Croatia", "HU" => "Hungary", "ID" => "Indonesia", "IE" => "Ireland",
        "IL" => "Israel", "IN" => "India", "IQ" => "Iraq", "IR" => "Iran", "IS" => "Iceland", "IT" => "Italy",
        "JO" => "Jordan", "JP" => "Japan", "KE" => "Kenya", "KR" => "South Korea", "KW" => "Kuwait",
        "KZ" => "Kazakhstan", "LB" => "Lebanon", "LI" => "Liechtenstein", "LT" => "Lithuania", "LU" => "Luxembourg",
        "LV" => "Latvia", "LY" => "Libya", "MA" => "Morocco", "MC" => "Monaco", "MD" => "Moldova", "ME" => "Montenegro",
        "MK" => "North Macedonia", "MT" => "Malta", "MX" => "Mexico", "MY" => "Malaysia", "NG" => "Nigeria",
        "NL" => "Netherlands", "NO" => "Norway", "NZ" => "New Zealand", "OM" => "Oman", "PA" => "Panama",
        "PE" => "Peru", "PH" => "Philippines", "PK" => "Pakistan", "PL" => "Poland", "PT" => "Portugal",
        "QA" => "Qatar", "RO" => "Romania", "RS" => "Serbia", "RU" => "Russia", "SA" => "Saudi Arabia",
        "SE" => "Sweden", "SG" => "Singapore", "SI" => "Slovenia", "SK" => "Slovakia", "SY" => "Syria",
        "TH" => "Thailand", "TN" => "Tunisia", "TR" => "Türkiye", "TW" => "Taiwan", "UA" => "Ukraine",
        "US" => "United States", "UY" => "Uruguay", "UZ" => "Uzbekistan", "VE" => "Venezuela", "VN" => "Vietnam",
        "ZA" => "South Africa", "ZW" => "Zimbabwe",
    ];
    asort($c);

    return $c;
}

/** @return array<string, string> */
function sol_intl_address_template(): array
{
    return [
        "recipient_name" => "",
        "organization" => "",
        "address_line1" => "",
        "address_line2" => "",
        "locality" => "",
        "admin_area" => "",
        "postal_code" => "",
        "country_code" => "",
        "phone" => "",
        "instructions" => "",
    ];
}

function sol_intl_address_max_len(string $key): int
{
    return match ($key) {
        "recipient_name" => 80,
        "organization" => 80,
        "address_line1" => 120,
        "address_line2" => 120,
        "locality" => 80,
        "admin_area" => 80,
        "postal_code" => 24,
        "country_code" => 2,
        "phone" => 40,
        "instructions" => 200,
        default => 80,
    };
}

/**
 * @param array<string, mixed> $in
 * @return array<string, string>
 */
function sol_intl_address_normalize(array $in): array
{
    $t = sol_intl_address_template();
    $out = [];
    foreach ($t as $k => $_) {
        $v = isset($in[$k]) && is_string($in[$k]) ? trim($in[$k]) : "";
        if ($k === "country_code") {
            $v = strtoupper(mb_substr(preg_replace("/[^A-Za-z]/", "", $v), 0, 2));
            if ($v !== "" && !array_key_exists($v, sol_intl_countries())) {
                $v = "";
            }
        } else {
            $v = mb_substr($v, 0, sol_intl_address_max_len($k));
        }
        $out[$k] = $v;
    }

    return $out;
}

function sol_intl_country_is_valid(string $code): bool
{
    return $code !== "" && array_key_exists($code, sol_intl_countries());
}

/**
 * @param array<string, mixed> $a
 */
function sol_intl_address_is_complete(array $a): bool
{
    $a = sol_intl_address_normalize($a);
    if (mb_strlen($a["recipient_name"]) < 2) {
        return false;
    }
    if (mb_strlen($a["address_line1"]) < 4) {
        return false;
    }
    if (mb_strlen($a["locality"]) < 2) {
        return false;
    }
    if (mb_strlen($a["postal_code"]) < 2) {
        return false;
    }
    if (!sol_intl_country_is_valid($a["country_code"])) {
        return false;
    }

    return true;
}

/**
 * @param array<string, mixed> $a
 */
function sol_intl_address_format_multiline(array $a): string
{
    $a = sol_intl_address_normalize($a);
    $lines = [];
    if ($a["recipient_name"] !== "") {
        $lines[] = $a["recipient_name"];
    }
    if ($a["organization"] !== "") {
        $lines[] = $a["organization"];
    }
    $street = $a["address_line1"];
    if ($a["address_line2"] !== "") {
        $street .= ($street !== "" ? ", " : "") . $a["address_line2"];
    }
    if ($street !== "") {
        $lines[] = $street;
    }
    $cityLine = trim($a["postal_code"] . " " . $a["locality"]);
    if ($a["admin_area"] !== "") {
        $cityLine .= ($cityLine !== "" ? ", " : "") . $a["admin_area"];
    }
    if ($cityLine !== "") {
        $lines[] = $cityLine;
    }
    if ($a["country_code"] !== "") {
        $cc = $a["country_code"];
        $lines[] = sol_intl_countries()[$cc] ?? $cc;
    }
    if ($a["phone"] !== "") {
        $lines[] = "Tel: " . $a["phone"];
    }
    if ($a["instructions"] !== "") {
        $lines[] = "Delivery notes: " . $a["instructions"];
    }

    return implode("\n", $lines);
}

/** Max length stored on rental_requests.delivery_notes (extend DB with schema_updates_rental_delivery_notes_text.sql). */
function sol_rent_delivery_notes_max_len(mysqli $db): int
{
    if (!sol_db_column_exists($db, "rental_requests", "delivery_notes")) {
        return 500;
    }
    $st = $db->prepare(
        "SELECT CHARACTER_MAXIMUM_LENGTH AS m, DATA_TYPE AS t FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rental_requests' AND COLUMN_NAME = 'delivery_notes' LIMIT 1"
    );
    if (!$st) {
        return 500;
    }
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    $m = isset($row["m"]) ? (int)$row["m"] : 0;
    if ($m > 0) {
        return min($m, 8000);
    }
    $t = strtolower((string)($row["t"] ?? ""));
    if (in_array($t, ["text", "mediumtext", "longtext"], true)) {
        return 4000;
    }

    return 500;
}

/**
 * @param array<string, mixed> $a
 */
function sol_intl_address_for_rent_notes(mysqli $db, array $a): string
{
    $formatted = sol_intl_address_format_multiline($a);
    $max = sol_rent_delivery_notes_max_len($db);

    return mb_substr($formatted, 0, $max);
}

/**
 * @param array<string, mixed> $post Usually $_POST
 * @return array<string, string>
 */
function sol_intl_address_from_post(array $post): array
{
    $raw = $post["intl_addr"] ?? [];
    if (!is_array($raw)) {
        $raw = [];
    }

    return sol_intl_address_normalize($raw);
}

function sol_intl_address_session_bootstrap(): void
{
    $t = sol_intl_address_template();
    if (!isset($_SESSION["sol_intl_address"]) || !is_array($_SESSION["sol_intl_address"])) {
        $_SESSION["sol_intl_address"] = $t;

        return;
    }
    $_SESSION["sol_intl_address"] = sol_intl_address_normalize(array_merge($t, $_SESSION["sol_intl_address"]));
}
