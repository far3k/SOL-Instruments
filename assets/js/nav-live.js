/**
 * Live navbar badges (shop / rent / wishlist) + AJAX submit for .sol-ajax-cart forms.
 */
(function () {
    "use strict";

    var body = document.body;
    if (!body || body.getAttribute("data-sol-nav-live") !== "1") {
        return;
    }

    var countsUrl = body.getAttribute("data-sol-nav-counts-url") || "";
    var cartAddUrl = body.getAttribute("data-sol-cart-add-url") || "";
    var cartDeltaUrl = body.getAttribute("data-sol-cart-delta-url") || "";
    var wishlistUrl = body.getAttribute("data-sol-wishlist-url") || "";

    function setBadge(id, n) {
        var el = document.getElementById(id);
        if (!el) {
            return;
        }
        var v = Math.max(0, parseInt(String(n), 10) || 0);
        el.textContent = String(v);
        if (v < 1) {
            el.classList.add("d-none");
        } else {
            el.classList.remove("d-none");
        }
    }

    function applyCounts(data) {
        if (!data || typeof data.shop !== "number") {
            return;
        }
        setBadge("sol-nav-shop-count", data.shop);
        setBadge("sol-nav-rent-count", data.rent);
        setBadge("sol-nav-wish-count", data.wish);
    }

    function readMiniCsrf() {
        var el = document.getElementById("sol-mini-cart-csrf");
        return el ? el.value : "";
    }

    function applyMiniHtml(html) {
        if (!html || typeof html !== "string") {
            return;
        }
        var root = document.getElementById("sol-mini-cart-root");
        if (root) {
            root.innerHTML = html;
        }
    }

    function refreshNavBundle() {
        if (!countsUrl) {
            return;
        }
        var sep = countsUrl.indexOf("?") >= 0 ? "&" : "?";
        fetch(countsUrl + sep + "mini=1", { credentials: "same-origin", headers: { Accept: "application/json" } })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (data && data.ok !== false) {
                    applyCounts(data);
                    applyMiniHtml(data.mini_html);
                }
            })
            .catch(function () {});
    }

    function detectCartAction(form) {
        if (form.querySelector('[name="add_to_cart"]')) {
            return "shop";
        }
        if (form.querySelector('[name="add_to_rent_cart"]')) {
            return "rent";
        }
        if (form.querySelector('[name="remove_from_wishlist"]')) {
            var tr = form.getAttribute("data-sol-wish-type") || "product";
            return tr === "instrument" ? "wish_remove_instrument" : "wish_remove_product";
        }
        if (form.querySelector('[name="add_to_wishlist"]')) {
            var t = form.getAttribute("data-sol-wish-type") || "product";
            return t === "instrument" ? "wish_instrument" : "wish_product";
        }
        return "";
    }

    function escapeAttr(s) {
        return String(s).replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/</g, "&lt;");
    }

    function wishlistFabHtmlRemove(itemId, csrf, wishTypeAttr) {
        var w = wishTypeAttr === "instrument" ? "instrument" : "product";
        return (
            '<form method="post" class="sol-ajax-cart" data-sol-wish-type="' +
            w +
            '">' +
            '<input type="hidden" name="_csrf" value="' +
            escapeAttr(csrf) +
            '">' +
            '<input type="hidden" name="id" value="' +
            itemId +
            '">' +
            '<button type="submit" name="remove_from_wishlist" value="1" class="btn rounded-circle sol-wishlist-fab-btn sol-wishlist-fab-btn--saved shadow-sm border-0" title="Remove from wishlist" aria-label="Remove from wishlist"><i class="bi bi-heart-fill"></i></button></form>'
        );
    }

    function wishlistFabHtmlAdd(itemId, csrf, wishTypeAttr) {
        var w = wishTypeAttr === "instrument" ? "instrument" : "product";
        return (
            '<form method="post" class="sol-ajax-cart" data-sol-wish-type="' +
            w +
            '">' +
            '<input type="hidden" name="_csrf" value="' +
            escapeAttr(csrf) +
            '">' +
            '<input type="hidden" name="id" value="' +
            itemId +
            '">' +
            '<button type="submit" name="add_to_wishlist" value="1" class="btn btn-light border rounded-circle sol-wishlist-fab-btn shadow-sm" title="Add to wishlist" aria-label="Add to wishlist"><i class="bi bi-heart text-danger"></i></button></form>'
        );
    }

    function readItemId(form) {
        var idInput = form.querySelector('input[name="id"]');
        if (idInput && idInput.value) {
            return idInput.value;
        }
        var pid = form.querySelector('input[name="product_id"]');
        return pid ? pid.value : "";
    }

    function readCsrf(form) {
        var c = form.querySelector('input[name="_csrf"]');
        return c ? c.value : "";
    }

    document.body.addEventListener("submit", function (e) {
        var form = e.target && e.target.closest ? e.target.closest("form.sol-ajax-cart") : null;
        if (!form) {
            return;
        }
        if (!cartAddUrl) {
            return;
        }
        var action = detectCartAction(form);
        var itemId = readItemId(form);
        var csrf = readCsrf(form);
        if (!action || !itemId || !csrf) {
            return;
        }
        e.preventDefault();
        var fd = new FormData();
        fd.append("_csrf", csrf);
        fd.append("sol_cart_action", action);
        fd.append("item_id", itemId);

        fetch(cartAddUrl, {
            method: "POST",
            body: fd,
            credentials: "same-origin",
            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        })
            .then(function (r) {
                var httpStatus = r.status;
                return r.text().then(function (text) {
                    try {
                        return text ? JSON.parse(text) : null;
                    } catch (ignore) {
                        return { ok: false, error: "bad_json", _httpStatus: httpStatus, _raw: text };
                    }
                });
            })
            .then(function (data) {
                if (!data || data.ok === false) {
                    if (typeof Swal !== "undefined") {
                        var errTitle = "Could not update";
                        if (data && data.error === "bad_json") {
                            errTitle =
                                data._httpStatus === 404
                                    ? "API not found (check URL)."
                                    : "Invalid server response (not JSON).";
                        } else if (data && data.error === "csrf") {
                            errTitle = "Session expired. Refresh the page.";
                        } else if (data && data.error === "forbidden") {
                            errTitle = "Action not allowed for this account.";
                        } else if (data && (data.error === "db_wishlist" || data.error === "db")) {
                            errTitle =
                                "Wishlist DB update needed: run schema_updates_wishlist.sql (phpMyAdmin).";
                        } else if (data && data.error === "auth") {
                            errTitle = "Please log in again.";
                        }
                        Swal.fire({ icon: "error", title: errTitle, toast: true, position: "top-end", showConfirmButton: false, timer: 3800 });
                    }
                    return;
                }
                refreshNavBundle();
                if (typeof Swal !== "undefined") {
                    var msg = "Updated.";
                    if (action === "wish_remove_product" || action === "wish_remove_instrument") {
                        msg = "Removed from wishlist.";
                    } else if (action === "wish_product" || action === "wish_instrument") {
                        msg = data.already ? "Already in wishlist." : "Saved to wishlist.";
                    } else if (action === "shop") {
                        msg = "Added to shop cart.";
                    } else if (action === "rent") {
                        msg = "Added to rent cart.";
                    }
                    Swal.fire({ icon: "success", title: msg, toast: true, position: "top-end", showConfirmButton: false, timer: 1800 });
                }
                var wrap = form.closest(".sol-wishlist-add-wrap");
                var wishT = form.getAttribute("data-sol-wish-type") || "product";
                if (wrap && wrap.classList.contains("sol-wishlist-fab")) {
                    if ((action === "wish_product" || action === "wish_instrument") && !data.already) {
                        wrap.innerHTML = wishlistFabHtmlRemove(itemId, csrf, wishT);
                        return;
                    }
                    if (action === "wish_remove_product" || action === "wish_remove_instrument") {
                        wrap.innerHTML = wishlistFabHtmlAdd(itemId, csrf, wishT);
                        return;
                    }
                }
                if ((action === "wish_product" || action === "wish_instrument") && !data.already && wishlistUrl && wrap && !wrap.classList.contains("sol-wishlist-fab")) {
                    var link = document.createElement("a");
                    link.href = wishlistUrl;
                    link.className = "btn btn-outline-success btn-sm";
                    link.innerHTML = '<i class="bi bi-heart-fill me-1"></i> In wishlist';
                    wrap.replaceChildren(link);
                }
            })
            .catch(function () {
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        icon: "error",
                        title: "Connection failed",
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 2800,
                    });
                }
            });
    });

    document.body.addEventListener("click", function (e) {
        var step = e.target.closest(".sol-mini-step");
        var rem = e.target.closest(".sol-mini-remove");
        if (!step && !rem) {
            return;
        }
        if (!cartDeltaUrl) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        var csrf = readMiniCsrf();
        if (!csrf) {
            return;
        }
        var fd = new FormData();
        fd.append("_csrf", csrf);
        if (rem) {
            fd.append("bucket", rem.getAttribute("data-bucket") || "");
            fd.append("id", rem.getAttribute("data-id") || "0");
            fd.append("remove", "1");
        } else {
            fd.append("bucket", step.getAttribute("data-bucket") || "");
            fd.append("id", step.getAttribute("data-id") || "0");
            fd.append("delta", step.getAttribute("data-delta") || "0");
        }
        fetch(cartDeltaUrl, {
            method: "POST",
            body: fd,
            credentials: "same-origin",
            headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data || data.ok === false) {
                    return;
                }
                applyCounts(data);
                applyMiniHtml(data.mini_html);
            })
            .catch(function () {});
    });

    refreshNavBundle();
    document.addEventListener("visibilitychange", function () {
        if (document.visibilityState === "visible") {
            refreshNavBundle();
        }
    });
    window.addEventListener("focus", refreshNavBundle);
    window.setInterval(refreshNavBundle, 25000);

    window.SOL_NAV = { refresh: refreshNavBundle };
})();
