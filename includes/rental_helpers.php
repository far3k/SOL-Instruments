<?php

declare(strict_types=1);

# Pure helpers for rental date overlap, validation, and labels (rent + admin scripts).

/** Bootstrap class for status badges (pending, approved, …). */
function sol_rental_status_badge_class(string $status): string
{
    return match ($status) {
        "pending" => "bg-warning text-dark",
        "approved" => "bg-success",
        "rejected" => "bg-danger",
        "completed" => "bg-primary",
        "cancelled" => "bg-secondary",
        default => "bg-secondary",
    };
}

/**
 * Customer-facing booking date rules: Y-m-d, end after start, start not before today.
 *
 * @return non-empty-string|null error message, or null when valid
 */
function sol_rental_validate_booking_dates(string $start, string $end): ?string
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
        return "Please use valid calendar dates (YYYY-MM-DD).";
    }
    if ($end <= $start) {
        return "End date must be after the start date.";
    }
    $today = date("Y-m-d");
    if ($start < $today) {
        return "Start date cannot be in the past.";
    }

    return null;
}

/** Escape % and _ for use inside SQL LIKE patterns. */
function sol_rental_like_fragment(string $q): string
{
    $q = str_replace(["\\", "%", "_"], ["\\\\", "\\%", "\\_"], $q);

    return $q;
}

/**
 * True if another approved rental for the same instrument overlaps [start, end] (inclusive dates).
 */
function sol_rental_has_approved_overlap(mysqli $db, int $instrumentId, string $start, string $end, ?int $excludeRequestId = null): bool
{
    if ($instrumentId < 1 || $start === "" || $end === "" || $end < $start) {
        return false;
    }

    $sql = "SELECT id FROM rental_requests
            WHERE instrument_id = ?
              AND status = 'approved'
              AND start_date <= ?
              AND end_date >= ?";
    $params = [$instrumentId, $end, $start];
    $types = "iss";

    if ($excludeRequestId !== null && $excludeRequestId > 0) {
        $sql .= " AND id <> ?";
        $types .= "i";
        $params[] = $excludeRequestId;
    }
    $sql .= " LIMIT 1";

    $st = $db->prepare($sql);
    if (!$st) {
        return true;
    }
    $st->bind_param($types, ...$params);
    $st->execute();
    $overlap = $st->get_result()->num_rows > 0;
    $st->close();

    return $overlap;
}

/**
 * True if another pending or approved rental overlaps [start, end] (customer booking conflict).
 */
function sol_rental_has_customer_block_overlap(mysqli $db, int $instrumentId, string $start, string $end, ?int $excludeRequestId = null): bool
{
    if ($instrumentId < 1 || $start === "" || $end === "" || $end < $start) {
        return false;
    }

    $sql = "SELECT id FROM rental_requests
            WHERE instrument_id = ?
              AND status IN ('approved','pending')
              AND start_date <= ?
              AND end_date >= ?";
    $params = [$instrumentId, $end, $start];
    $types = "iss";

    if ($excludeRequestId !== null && $excludeRequestId > 0) {
        $sql .= " AND id <> ?";
        $types .= "i";
        $params[] = $excludeRequestId;
    }
    $sql .= " LIMIT 1";

    $st = $db->prepare($sql);
    if (!$st) {
        return true;
    }
    $st->bind_param($types, ...$params);
    $st->execute();
    $overlap = $st->get_result()->num_rows > 0;
    $st->close();

    return $overlap;
}

function sol_rental_payment_label(string $code): string
{
    return sol_payment_method_label($code);
}

function sol_rental_delivery_label(string $code): string
{
    return match ($code) {
        "pickup" => "Pickup at our store",
        "courier" => "Courier delivery (you pay the courier)",
        default => $code,
    };
}

function sol_rental_log_status(
    mysqli $db,
    int $rentalRequestId,
    ?string $oldStatus,
    string $newStatus,
    string $reason,
    int $changedByUserId
): void
{
    if ($oldStatus === null) {
        $st = $db->prepare("
            INSERT INTO rental_status_logs
            (rental_request_id, old_status, new_status, change_reason, changed_by_user_id, changed_at)
            VALUES (?, NULL, ?, ?, ?, NOW())
        ");
        if (!$st) {
            return;
        }
        $st->bind_param("issi", $rentalRequestId, $newStatus, $reason, $changedByUserId);
        $st->execute();
        $st->close();

        return;
    }

    $st = $db->prepare("
        INSERT INTO rental_status_logs
        (rental_request_id, old_status, new_status, change_reason, changed_by_user_id, changed_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    if (!$st) {
        return;
    }
    $st->bind_param("isssi", $rentalRequestId, $oldStatus, $newStatus, $reason, $changedByUserId);
    $st->execute();
    $st->close();
}
