<?php

declare(strict_types=1);

# Public FAQ tabs backed by `faq` table; search filters current tab only.

require_once __DIR__ . "/includes/app.php";

$categories = ["Rental", "Store", "Account"];
$faqs = [];
$hasFaq = sol_db_table_exists($connect, "faq");

if ($hasFaq) {
    $faqOrder = sol_db_column_exists($connect, "faq", "sort_order")
        ? "category ASC, sort_order ASC, id ASC"
        : "category ASC, id ASC";
    $res = $connect->query("SELECT question, answer, category FROM faq ORDER BY " . $faqOrder);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cat = (string)($row["category"] ?? "Rental");
            if (!isset($faqs[$cat])) {
                $faqs[$cat] = [];
            }
            $faqs[$cat][] = $row;
        }
    }
}

# Title/nav + extra head styles for pill tabs and accordion behaviour.
$page_title = "FAQ — SOL";
$nav_role = sol_nav_role();
$active_nav = "faq";
$extra_head = '<style>
.sol-faq-tab .nav-link{color:#334155;border-radius:999px;padding:.4rem 1rem;border:1px solid rgba(15,23,42,.12);}
.sol-faq-tab .nav-link.active{background:var(--bs-primary);color:#fff;border-color:var(--bs-primary);}
.sol-faq-search{max-width:520px;}
.sol-faq-q{cursor:pointer;}
.sol-faq-arrow{transition:transform .2s ease;}
.sol-faq-item.active .sol-faq-arrow{transform:rotate(45deg);}
.sol-faq-answer{max-height:0;overflow:hidden;padding:0 1rem;transition:max-height .25s ease,padding .25s ease;}
.sol-faq-item.active .sol-faq-answer{max-height:1200px;padding:.75rem 1rem 1rem;}
</style>';
require_once __DIR__ . "/includes/layout_top.php";
?>

<div class="container py-4 py-lg-5" style="max-width: 900px;">
    <div class="text-center mb-4">
        <p class="text-secondary small mb-1 text-uppercase">Help</p>
        <h1 class="h2 fw-bold text-dark mb-2">Frequently asked questions</h1>
        <p class="text-muted small mb-0">Browse by topic or search keywords.</p>
    </div>

    <?php if (!$hasFaq): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-3">The FAQ table is not installed yet. Run <code>schema_updates.sql</code> on your database.</div>
    <?php else: ?>
        <div class="mb-4 mx-auto sol-faq-search">
            <label for="faqSearch" class="form-label visually-hidden">Search FAQs</label>
            <input type="search" class="form-control form-control-lg shadow-sm" id="faqSearch" placeholder="Search questions and answers…" autocomplete="off">
        </div>
        <div id="faqNoResults" class="alert alert-light border text-center small" style="display:none;">No matching questions in this tab.</div>

        <ul class="nav nav-pills justify-content-center flex-wrap gap-2 mb-4 sol-faq-tab" id="faqTabs" role="tablist">
            <?php foreach ($categories as $i => $cat): ?>
                <?php $slug = strtolower($cat); ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $i === 0 ? "active" : "" ?>" id="tab-<?= htmlspecialchars($slug, ENT_QUOTES, "UTF-8") ?>" data-bs-toggle="tab" data-bs-target="#pane-<?= htmlspecialchars($slug, ENT_QUOTES, "UTF-8") ?>" type="button" role="tab"><?= htmlspecialchars($cat, ENT_QUOTES, "UTF-8") ?></button>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content" id="faqTabContent">
            <?php foreach ($categories as $i => $cat): ?>
                <?php
                $slug = strtolower($cat);
                $list = $faqs[$cat] ?? [];
                ?>
                <div class="tab-pane fade <?= $i === 0 ? "show active" : "" ?>" id="pane-<?= htmlspecialchars($slug, ENT_QUOTES, "UTF-8") ?>" role="tabpanel">
                    <?php if ($list === []): ?>
                        <div class="alert alert-light border rounded-3">No questions in this category yet.</div>
                    <?php else: ?>
                        <?php foreach ($list as $faq): ?>
                            <?php
                            $q = (string)($faq["question"] ?? "");
                            $a = (string)($faq["answer"] ?? "");
                            $qEsc = htmlspecialchars($q, ENT_QUOTES, "UTF-8");
                            $aEsc = htmlspecialchars($a, ENT_QUOTES, "UTF-8");
                            ?>
                            <div class="card border-0 shadow-sm mb-3 sol-faq-item sol-card-shell">
                                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center sol-faq-q py-3" role="button" tabindex="0" aria-expanded="false">
                                    <span class="d-flex align-items-start gap-2 sol-faq-qtext">
                                        <i class="bi bi-question-circle mt-1 flex-shrink-0"></i>
                                        <span><?= $qEsc ?></span>
                                    </span>
                                    <span class="sol-faq-arrow fs-5 lh-1 transition-all">+</span>
                                </div>
                                <div class="sol-faq-answer text-secondary small border-top bg-white"><?= nl2br($aEsc) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p class="text-center text-muted small mt-4 mb-0">
        <a href="<?= htmlspecialchars(sol_url("index.php"), ENT_QUOTES, "UTF-8") ?>">← Home</a>
        &nbsp;·&nbsp;
        <a href="<?= htmlspecialchars(sol_url("contact.php"), ENT_QUOTES, "UTF-8") ?>">Contact us</a>
    </p>
</div>

<?php
# Client-side accordion + search scoped to the visible tab (no extra build step).
if ($hasFaq) {
    $layout_extra_scripts = <<<'JS'
<script>
(function () {
  document.querySelectorAll(".sol-faq-item .sol-faq-q").forEach(function (head) {
    function toggle() {
      var item = head.closest(".sol-faq-item");
      var open = !item.classList.contains("active");
      item.classList.toggle("active", open);
      head.setAttribute("aria-expanded", open ? "true" : "false");
    }
    head.addEventListener("click", toggle);
    head.addEventListener("keydown", function (e) {
      if (e.key === "Enter" || e.key === " ") { e.preventDefault(); toggle(); }
    });
  });
  var search = document.getElementById("faqSearch");
  var noRes = document.getElementById("faqNoResults");
  if (!search || !noRes) return;
  function activePane() {
    return document.querySelector("#faqTabContent .tab-pane.active");
  }
  function runSearch() {
    var q = search.value.trim().toLowerCase();
    var pane = activePane();
    if (!pane) return;
    var items = pane.querySelectorAll(".sol-faq-item");
    var n = 0;
    items.forEach(function (item) {
      var qt = item.querySelector(".sol-faq-qtext");
      var ans = item.querySelector(".sol-faq-answer");
      var text = ((qt && qt.textContent) ? qt.textContent : "") + " " + ((ans && ans.textContent) ? ans.textContent : "");
      var match = q === "" || text.toLowerCase().indexOf(q) !== -1;
      item.style.display = match ? "" : "none";
      if (match) n++;
    });
    noRes.style.display = n === 0 ? "block" : "none";
  }
  search.addEventListener("input", runSearch);
  document.querySelectorAll('#faqTabs button[data-bs-toggle="tab"]').forEach(function (btn) {
    btn.addEventListener("shown.bs.tab", function () { search.value = ""; runSearch(); });
  });
})();
</script>
JS;
}
require_once __DIR__ . "/includes/layout_bottom.php";
