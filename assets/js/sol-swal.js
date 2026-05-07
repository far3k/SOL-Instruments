/**
 * SweetAlert2 defaults + SOL navy palette (reads CSS variables from main.css).
 * @see https://sweetalert2.github.io/
 */
(function () {
    "use strict";

    function readCssVar(name, fallback) {
        var v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return v || fallback;
    }

    function initSwal() {
        if (typeof Swal === "undefined") {
            return;
        }

        var navy = readCssVar("--sol-navy", "#0f2744");
        var slate = readCssVar("--sol-swal-cancel", "#64748b");

        Swal.mixin({
            confirmButtonColor: navy,
            denyButtonColor: slate,
            cancelButtonColor: slate,
            reverseButtons: true,
            focusCancel: true,
            color: "#0f172a",
            background: "#ffffff",
            backdrop: "rgba(15, 23, 42, 0.45)",
            customClass: {
                popup: "sol-swal-popup",
                confirmButton: "sol-swal-confirm",
                cancelButton: "sol-swal-cancel",
            },
        });

        document.addEventListener("click", function (e) {
            var btn = e.target && e.target.closest ? e.target.closest('button[type="submit"],input[type="submit"]') : null;
            if (!btn) {
                return;
            }
            var f = btn.form;
            if (f instanceof HTMLFormElement) {
                f._solLastSubmitter = btn;
            }
        }, true);

        document.addEventListener("submit", function (e) {
            var form = e.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }
            var submitter = e.submitter instanceof HTMLElement ? e.submitter : (form._solLastSubmitter || null);
            if (form.dataset.solConfirmBypass === "1") {
                delete form.dataset.solConfirmBypass;
                return;
            }
            var msg = form.getAttribute("data-sol-confirm");
            if (!msg) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();

            Swal.fire({
                title: "Please confirm",
                text: msg,
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Yes, continue",
                cancelButtonText: "Cancel",
            }).then(function (res) {
                if (res.isConfirmed) {
                    form.dataset.solConfirmBypass = "1";
                    if (typeof form.requestSubmit === "function") {
                        form.requestSubmit(submitter || undefined);
                        return;
                    }
                    if (submitter && submitter.getAttribute("name")) {
                        var tmpName = submitter.getAttribute("name");
                        var tmpValue = submitter.getAttribute("value") || "1";
                        var hidden = document.createElement("input");
                        hidden.type = "hidden";
                        hidden.name = tmpName;
                        hidden.value = tmpValue;
                        hidden.setAttribute("data-sol-submit-proxy", "1");
                        form.appendChild(hidden);
                    }
                    HTMLFormElement.prototype.submit.call(form);
                }
            });
        }, true);

        document.addEventListener("click", function (e) {
            var a = e.target.closest("a.sol-confirm-link");
            if (!a || !a.getAttribute("data-sol-confirm")) {
                return;
            }
            var msg = a.getAttribute("data-sol-confirm");
            var href = a.getAttribute("href");
            if (!href || href === "#") {
                return;
            }
            e.preventDefault();
            Swal.fire({
                title: "Please confirm",
                text: msg,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, continue",
                cancelButtonText: "Cancel",
            }).then(function (res) {
                if (res.isConfirmed) {
                    window.location.href = href;
                }
            });
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initSwal);
    } else {
        initSwal();
    }
})();
