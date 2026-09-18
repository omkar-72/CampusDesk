/* CampusDesk - Student Notifications */

/* =========================
   DOM READY
   ========================= */

document.addEventListener("DOMContentLoaded", function () {
    /* =========================
       NOTIFICATION FORMS
       ========================= */

    const forms = document.querySelectorAll(
        ".notification-actions form, .notification-buttons form",
    );

    forms.forEach(function (form) {
        form.addEventListener("submit", function () {
            const button = form.querySelector("button");

            if (button) {
                button.disabled = true;

                button.textContent = "Processing...";
            }
        });
    });
});
