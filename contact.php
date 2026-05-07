<?php

declare(strict_types=1);

# Guest-friendly contact form; CSRF + insert into contact_messages when table exists.

require_once __DIR__ . "/includes/app.php";

$name = $email = $subject = $message = "";
$errors = [];
$success = isset($_GET["success"]);

$hasTable = sol_db_table_exists($connect, "contact_messages");

# Persist message when table exists; PRG redirect on success to avoid repost.
if ($hasTable && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["send_contact"])) {
    if (!sol_csrf_verify()) {
        $errors[] = "Security check failed. Please try again.";
    } else {
        $name = trim((string)($_POST["name"] ?? ""));
        $email = trim((string)($_POST["email"] ?? ""));
        $subject = trim((string)($_POST["subject"] ?? ""));
        $message = trim((string)($_POST["message"] ?? ""));

        if ($name === "" || strlen($name) > 100) {
            $errors[] = $name === "" ? "Name is required." : "Name is too long.";
        }
        if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "A valid email is required.";
        }
        if ($subject === "" || strlen($subject) > 150) {
            $errors[] = $subject === "" ? "Subject is required." : "Subject is too long.";
        }
        if ($message === "" || strlen($message) > 1000) {
            $errors[] = $message === "" ? "Message is required." : "Message is too long.";
        }

        if ($errors === []) {
            $st = $connect->prepare(
                "INSERT INTO contact_messages (name, email, subject, message, is_read) VALUES (?, ?, ?, ?, 0)"
            );
            if ($st) {
                $st->bind_param("ssss", $name, $email, $subject, $message);
                if ($st->execute()) {
                    $st->close();
                    header("Location: " . sol_url("contact.php?success=1"));
                    exit;
                }
                $errors[] = "Could not save your message. Please try again later.";
                $st->close();
            } else {
                $errors[] = "Database error.";
            }
        }
    }
}

$page_title = "Contact us — SOL";
$nav_role = sol_nav_role();
$active_nav = "contact";
require_once __DIR__ . "/includes/layout_top.php";
?>

<div class="container py-4 py-lg-5" style="max-width: 560px;">
    <div class="text-center mb-4">
        <p class="text-secondary small mb-1 text-uppercase">Support</p>
        <h1 class="h2 fw-bold text-dark mb-2">Contact us</h1>
        <p class="text-muted small mb-0">Send a message — we read every submission.</p>
    </div>

    <?php if (!$hasTable): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-3">The messages table is not installed. Run <code>schema_updates.sql</code>.</div>
    <?php else: ?>
        <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-3">Message sent successfully. Thank you!</div>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-3">
                <?php foreach ($errors as $e): ?>
                    <div><?= htmlspecialchars($e, ENT_QUOTES, "UTF-8") ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm sol-card-shell">
            <div class="card-body p-4">
                <form method="post" novalidate>
                    <?= sol_csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="cname">Your name</label>
                        <input type="text" class="form-control" id="cname" name="name" maxlength="100" required value="<?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="cemail">Email</label>
                        <input type="email" class="form-control" id="cemail" name="email" required value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="csub">Subject</label>
                        <input type="text" class="form-control" id="csub" name="subject" maxlength="150" required value="<?= htmlspecialchars($subject, ENT_QUOTES, "UTF-8") ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="cmsg">Message</label>
                        <textarea class="form-control" id="cmsg" name="message" rows="5" maxlength="1000" required placeholder="How can we help?"><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></textarea>
                    </div>
                    <button type="submit" name="send_contact" value="1" class="btn btn-primary w-100 rounded-pill py-2">Send message</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <p class="text-center text-muted small mt-4 mb-0">
        <a href="<?= htmlspecialchars(sol_url("index.php"), ENT_QUOTES, "UTF-8") ?>">← Home</a>
        &nbsp;·&nbsp;
        <a href="<?= htmlspecialchars(sol_url("faq.php"), ENT_QUOTES, "UTF-8") ?>">FAQ</a>
    </p>
</div>

<?php require_once __DIR__ . "/includes/layout_bottom.php";
