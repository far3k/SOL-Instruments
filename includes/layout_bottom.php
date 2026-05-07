<?php

declare(strict_types=1);

# Closes main layout (Bootstrap JS + footer scripts live in included partials if any).

$layout_extra_scripts = $layout_extra_scripts ?? "";
$nav_role_bottom = $nav_role ?? (function_exists("sol_nav_role") ? sol_nav_role() : "guest");
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="<?= htmlspecialchars(sol_url("assets/js/sol-swal.js"), ENT_QUOTES, "UTF-8") ?>"></script>
<?php if ($nav_role_bottom === "user"): ?>
<script src="<?= htmlspecialchars(sol_url("assets/js/nav-live.js"), ENT_QUOTES, "UTF-8") ?>" defer></script>
<?php endif; ?>
<?= $layout_extra_scripts ?>
</body>
</html>
