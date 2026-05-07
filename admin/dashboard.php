<?php

declare(strict_types=1);

# Admin home: attention metrics + Rent / Shop / Content summaries with counts.

require_once dirname(__DIR__) . "/includes/app.php";
sol_require_admin();

$adminId = (int)$_SESSION["adm"];

$adminName = "Admin";
if ($adminId > 0) {
    $admRes = $connect->prepare("SELECT first_name, last_name FROM users WHERE id = ? LIMIT 1");
    $admRes->bind_param("i", $adminId);
    $admRes->execute();
    $adm = $admRes->get_result()->fetch_assoc();
    $admRes->close();
    if ($adm) {
        $adminName = trim(($adm["first_name"] ?? "") . " " . ($adm["last_name"] ?? ""));
    }
}

$page_title = "Admin dashboard";
$nav_role = "admin";
$active_nav = "dashboard";
$extra_head = <<<'HTML'
<style>
.stat-card{border:1px solid var(--sol-card-border);border-radius:1rem;transition:transform .2s,box-shadow .2s;overflow:hidden}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 12px 40px rgba(15,23,42,.12)}
.stat-card .icon-wrap{width:3rem;height:3rem;border-radius:.75rem;display:flex;align-items:center;justify-content:center;font-size:1.35rem}
.dash-link-card{border:1px solid var(--sol-card-border);border-radius:.75rem;background:#fff;transition:box-shadow .15s,border-color .15s}
.dash-link-card:hover{box-shadow:0 8px 24px rgba(15,23,42,.1);border-color:rgba(13,110,253,.35)}
</style>
HTML;
require_once dirname(__DIR__) . "/includes/layout_top.php";

/** @var array<string,int> $adminNavCounts */
$ac = $adminNavCounts ?? sol_admin_nav_counts($connect);
$attentionTotal = (int)$ac["pending_rentals"] + (int)$ac["unread_messages"] + (int)$ac["pending_orders"];
?>

<div class="container py-4 py-lg-5" style="max-width: 1140px;">
    <div class="mb-4">
        <p class="text-secondary small mb-1 text-uppercase">Overview</p>
        <h1 class="h2 fw-bold text-dark mb-0">Dashboard</h1>
        <p class="text-muted mb-0 mt-1">Signed in as <span class="fw-semibold text-dark"><?= htmlspecialchars($adminName, ENT_QUOTES, "UTF-8") ?></span><?php if ($attentionTotal > 0): ?> — <span class="text-warning-emphasis fw-semibold"><?= (int)$attentionTotal ?> item(s) need attention</span><?php endif; ?></p>
    </div>

    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card bg-white p-3 p-lg-4 shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-secondary small mb-1">Pending rentals</p>
                        <p class="h3 fw-bold mb-0 <?= (int)$ac["pending_rentals"] > 0 ? "text-warning-emphasis" : "text-dark" ?>"><?= (int)$ac["pending_rentals"] ?></p>
                    </div>
                    <div class="icon-wrap bg-primary bg-opacity-10 text-primary"><i class="bi bi-calendar2-week"></i></div>
                </div>
                <a href="<?= htmlspecialchars(sol_url("admin/rentals_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="small fw-semibold text-decoration-none">Open queue →</a>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card bg-white p-3 p-lg-4 shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-secondary small mb-1">Unread messages</p>
                        <p class="h3 fw-bold mb-0 <?= (int)$ac["unread_messages"] > 0 ? "text-danger" : "text-dark" ?>"><?= (int)$ac["unread_messages"] ?></p>
                    </div>
                    <div class="icon-wrap bg-danger bg-opacity-10 text-danger"><i class="bi bi-envelope-open"></i></div>
                </div>
                <a href="<?= htmlspecialchars(sol_url("admin/contact_messages.php"), ENT_QUOTES, "UTF-8") ?>" class="small fw-semibold text-decoration-none">Inbox →</a>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card bg-white p-3 p-lg-4 shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-secondary small mb-1">Shop orders (pending)</p>
                        <p class="h3 fw-bold mb-0 <?= (int)$ac["pending_orders"] > 0 ? "text-warning-emphasis" : "text-dark" ?>"><?= (int)$ac["pending_orders"] ?></p>
                    </div>
                    <div class="icon-wrap bg-success bg-opacity-10 text-success"><i class="bi bi-bag-check"></i></div>
                </div>
                <a href="<?= htmlspecialchars(sol_url("admin/shop_orders_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="small fw-semibold text-decoration-none">Open shop orders →</a>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card bg-white p-3 p-lg-4 shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-secondary small mb-1">User accounts</p>
                        <p class="h3 fw-bold mb-0 text-dark"><?= (int)$ac["users_total"] ?></p>
                        <?php if ((int)$ac["blocked_users"] > 0): ?>
                            <p class="small text-danger mb-0 fw-semibold"><?= (int)$ac["blocked_users"] ?> blocked</p>
                        <?php endif; ?>
                    </div>
                    <div class="icon-wrap bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-people-fill"></i></div>
                </div>
                <a href="<?= htmlspecialchars(sol_url("admin/users_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="small fw-semibold text-decoration-none">Manage users →</a>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <h2 class="h6 text-uppercase text-muted mb-3">Rent &amp; catalog</h2>
            <div class="row g-2">
                <div class="col-md-6">
                    <a href="<?= htmlspecialchars(sol_url("admin/rentals_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="dash-link-card d-block p-3 text-decoration-none text-dark h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold"><i class="bi bi-calendar2-check me-1 text-primary"></i> Rental queue</span>
                            <span class="badge <?= (int)$ac["pending_rentals"] > 0 ? "bg-warning text-dark" : "bg-light text-secondary border" ?>"><?= (int)$ac["pending_rentals"] ?> pending</span>
                        </div>
                        <p class="small text-muted mb-0">Approve or reject customer rental requests.</p>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="<?= htmlspecialchars(sol_url("admin/instruments_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="dash-link-card d-block p-3 text-decoration-none text-dark h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold"><i class="bi bi-music-note-beamed me-1 text-primary"></i> Instruments</span>
                            <span class="badge bg-light text-secondary border"><?= (int)$ac["instruments_active"] ?> on / <?= (int)$ac["instruments_inactive"] ?> off</span>
                        </div>
                        <p class="small text-muted mb-0">Rent fleet: active vs inactive listings.</p>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="<?= htmlspecialchars(sol_url("admin/categories_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="dash-link-card d-block p-3 text-decoration-none text-dark h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold"><i class="bi bi-folder2 me-1 text-primary"></i> Categories</span>
                            <span class="badge bg-light text-secondary border"><?= (int)$ac["categories"] ?></span>
                        </div>
                        <p class="small text-muted mb-0">Instrument categories for the rent catalog.</p>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <h2 class="h6 text-uppercase text-muted mb-3">Shop &amp; suppliers</h2>
            <div class="row g-2">
                <div class="col-md-6">
                    <a href="<?= htmlspecialchars(sol_url("products/index.php"), ENT_QUOTES, "UTF-8") ?>" class="dash-link-card d-block p-3 text-decoration-none text-dark h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold"><i class="bi bi-box-seam me-1 text-success"></i> Products</span>
                            <span class="badge bg-light text-secondary border"><?= (int)$ac["products"] ?></span>
                        </div>
                        <p class="small text-muted mb-0">Accessory catalog for the shop storefront.</p>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="<?= htmlspecialchars(sol_url("products/suppliers.php"), ENT_QUOTES, "UTF-8") ?>" class="dash-link-card d-block p-3 text-decoration-none text-dark h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold"><i class="bi bi-truck me-1 text-success"></i> Suppliers</span>
                            <span class="badge bg-light text-secondary border"><?= (int)$ac["suppliers"] ?></span>
                        </div>
                        <p class="small text-muted mb-0">Supplier records linked to products.</p>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="<?= htmlspecialchars(sol_url("products/create.php"), ENT_QUOTES, "UTF-8") ?>" class="dash-link-card d-block p-3 text-decoration-none text-dark h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold"><i class="bi bi-plus-circle me-1 text-success"></i> Add product</span>
                        </div>
                        <p class="small text-muted mb-0">Create a new shop product row.</p>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="<?= htmlspecialchars(sol_url("admin/shop_orders_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="dash-link-card d-block p-3 text-decoration-none text-dark h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold"><i class="bi bi-bag-check me-1 text-success"></i> Shop orders</span>
                            <span class="badge <?= (int)$ac["pending_orders"] > 0 ? "bg-warning text-dark" : "bg-light text-secondary border" ?>"><?= (int)$ac["pending_orders"] ?> pending</span>
                        </div>
                        <p class="small text-muted mb-0">Status, line items, shipping, staff notes.</p>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <h2 class="h6 text-uppercase text-muted mb-3">Content</h2>
            <div class="row g-2">
                <div class="col-md-6">
                    <a href="<?= htmlspecialchars(sol_url("admin/faq_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="dash-link-card d-block p-3 text-decoration-none text-dark h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold"><i class="bi bi-question-circle me-1 text-info"></i> FAQ admin</span>
                            <span class="badge bg-light text-secondary border"><?= (int)$ac["faq_entries"] ?> entries</span>
                        </div>
                        <p class="small text-muted mb-0">Public FAQ categories and answers.</p>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="<?= htmlspecialchars(sol_url("admin/contact_messages.php"), ENT_QUOTES, "UTF-8") ?>" class="dash-link-card d-block p-3 text-decoration-none text-dark h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold"><i class="bi bi-inbox me-1 text-info"></i> Contact messages</span>
                            <span class="badge <?= (int)$ac["unread_messages"] > 0 ? "bg-danger" : "bg-light text-secondary border" ?>"><?= (int)$ac["unread_messages"] ?> unread</span>
                        </div>
                        <p class="small text-muted mb-0">Submissions from the site contact form.</p>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="<?= htmlspecialchars(sol_url("admin/home_slides_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="dash-link-card d-block p-3 text-decoration-none text-dark h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-semibold"><i class="bi bi-images me-1 text-info"></i> Home slides</span>
                        </div>
                        <p class="small text-muted mb-0">Background images, titles, buttons, and audience for the landing carousel.</p>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="<?= htmlspecialchars(sol_url("admin/rentals_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-primary btn-sm rounded-pill"><i class="bi bi-calendar2-week me-1"></i> Rental queue</a>
        <a href="<?= htmlspecialchars(sol_url("admin/shop_orders_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-primary btn-sm rounded-pill"><i class="bi bi-bag-check me-1"></i> Shop orders</a>
        <a href="<?= htmlspecialchars(sol_url("admin/users_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-primary btn-sm rounded-pill"><i class="bi bi-people me-1"></i> User accounts</a>
        <a href="<?= htmlspecialchars(sol_url("admin/faq_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-primary btn-sm rounded-pill"><i class="bi bi-journal-text me-1"></i> FAQ</a>
        <a href="<?= htmlspecialchars(sol_url("admin/home_slides_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-primary btn-sm rounded-pill"><i class="bi bi-images me-1"></i> Hero slides</a>
        <a href="<?= htmlspecialchars(sol_url("admin/contact_messages.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-primary btn-sm rounded-pill"><i class="bi bi-inbox me-1"></i> Messages</a>
        <a href="<?= htmlspecialchars(sol_url("admin/categories_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm rounded-pill">Categories</a>
        <a href="<?= htmlspecialchars(sol_url("admin/instruments_admin.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm rounded-pill">Instruments</a>
        <a href="<?= htmlspecialchars(sol_url("products/index.php"), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm rounded-pill">Products</a>
    </div>
</div>

<?php require_once dirname(__DIR__) . "/includes/layout_bottom.php";
