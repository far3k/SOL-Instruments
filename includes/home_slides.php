<?php

declare(strict_types=1);

/** Absolute filesystem dir for uploaded hero backgrounds */
function sol_home_slides_upload_dir(): string
{
    return SOL_ROOT . DIRECTORY_SEPARATOR . "pictures" . DIRECTORY_SEPARATOR . "home_slides";
}

function sol_home_slides_upload_web_prefix(): string
{
    return "home_slides/";
}

/**
 * Active slides for guest or user. Replaces {{name}} in heading/subheading when $viewerName is non-empty.
 *
 * @return list<array<string,mixed>>
 */
function sol_home_slides_for_viewer(mysqli $db, string $mode, string $viewerName): array
{
    if (!sol_db_table_exists($db, "home_slides")) {
        return [];
    }
    $aud = $mode === "user" ? "user" : "guest";
    $st = $db->prepare(
        "SELECT id, sort_order, audience, background_image, overlay_pct, heading, subheading,
                button1_label, button1_url, button2_label, button2_url
         FROM home_slides
         WHERE is_active = 1 AND (audience = 'all' OR audience = ?)
         ORDER BY sort_order ASC, id ASC"
    );
    if (!$st) {
        return [];
    }
    $st->bind_param("s", $aud);
    $st->execute();
    $res = $st->get_result();
    $out = [];
    $repl = $viewerName !== "" ? $viewerName : "Member";
    while ($row = $res->fetch_assoc()) {
        $row["heading"] = str_replace("{{name}}", $repl, (string)($row["heading"] ?? ""));
        $row["subheading"] = str_replace("{{name}}", $repl, (string)($row["subheading"] ?? ""));
        $out[] = $row;
    }
    $st->close();

    return $out;
}
