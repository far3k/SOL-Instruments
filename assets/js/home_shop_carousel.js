/**
 * Horizontal product scroller (home page Accessories).
 */
(function () {
    "use strict";

    document.querySelectorAll("[data-sol-scroll]").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var sel = btn.getAttribute("data-sol-scroll");
            var dir = parseInt(btn.getAttribute("data-sol-dir") || "1", 10);
            var track = sel ? document.querySelector(sel) : null;
            if (!track) {
                return;
            }
            var step = Math.max(220, Math.floor(track.clientWidth * 0.72));
            track.scrollBy({ left: dir * step, behavior: "smooth" });
        });
    });
})();
