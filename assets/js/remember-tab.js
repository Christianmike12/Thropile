document.addEventListener("DOMContentLoaded", function () {

    const pageKey = window.location.pathname;

    // Kembalikan tab terakhir
    const savedTab = localStorage.getItem(pageKey);
    if (savedTab) {
        const trigger = document.querySelector(
            '[data-bs-target="' + savedTab + '"]'
        );

        if (trigger) {
            new bootstrap.Tab(trigger).show();
        }
    }

    // Simpan tab yang sedang dibuka
    document.querySelectorAll('[data-bs-toggle="pill"], [data-bs-toggle="tab"]').forEach(function (tab) {
        tab.addEventListener("shown.bs.tab", function (e) {
            localStorage.setItem(pageKey, e.target.getAttribute("data-bs-target"));
        });
    });

});