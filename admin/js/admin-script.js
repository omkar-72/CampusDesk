/*
|--------------------------------------------------------------------------
| CampusDesk - Admin JavaScript
|--------------------------------------------------------------------------
| Common JavaScript for all Admin pages.
|
| Used by:
| - Dashboard
| - Users
| - Categories
| - Reports & Analysis
| - Audit Logs
| - Profile
|
| This file contains only common Admin-side UI/utility functionality.
|--------------------------------------------------------------------------
*/

/* =========================================================
   DOM READY
========================================================= */

document.addEventListener("DOMContentLoaded", function () {
    initializeSidebar();
    initializeModals();
    initializeTabs();
    initializeForms();
    initializeAlerts();
    initializePasswordValidation();
    initializeSearchInputs();
    initializeFilterForms();
    initializeTooltips();
    initializeConfirmActions();
    initializeTableInteractions();
});

/* =========================================================
   SIDEBAR
========================================================= */

function initializeSidebar() {
    const sidebar = document.querySelector(".admin-sidebar");

    if (!sidebar) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Create Mobile Menu Button
    |--------------------------------------------------------------------------
    */

    let menuButton = document.querySelector(".admin-mobile-menu");

    if (!menuButton) {
        menuButton = document.createElement("button");

        menuButton.type = "button";
        menuButton.className = "admin-mobile-menu";
        menuButton.innerHTML = '<i class="fa-solid fa-bars"></i>';
        menuButton.setAttribute("aria-label", "Open navigation");
        menuButton.setAttribute("aria-expanded", "false");

        document.body.appendChild(menuButton);
    }

    /*
    |--------------------------------------------------------------------------
    | Mobile Menu Click
    |--------------------------------------------------------------------------
    */

    menuButton.addEventListener("click", function () {
        const isOpen = sidebar.classList.toggle("sidebar-open");

        menuButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });

    /*
    |--------------------------------------------------------------------------
    | Close Sidebar When Navigation Link Is Clicked
    |--------------------------------------------------------------------------
    */

    const sidebarLinks = sidebar.querySelectorAll("a");

    sidebarLinks.forEach(function (link) {
        link.addEventListener("click", function () {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove("sidebar-open");
                menuButton.setAttribute("aria-expanded", "false");
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Close Sidebar When Clicking Outside
    |--------------------------------------------------------------------------
    */

    document.addEventListener("click", function (event) {
        if (window.innerWidth > 768) {
            return;
        }

        if (
            sidebar.classList.contains("sidebar-open") &&
            !sidebar.contains(event.target) &&
            !menuButton.contains(event.target)
        ) {
            sidebar.classList.remove("sidebar-open");
            menuButton.setAttribute("aria-expanded", "false");
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Escape Key
    |--------------------------------------------------------------------------
    */

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            sidebar.classList.remove("sidebar-open");
            menuButton.setAttribute("aria-expanded", "false");
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Responsive State
    |--------------------------------------------------------------------------
    */

    function updateSidebarState() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove("sidebar-open");
            menuButton.setAttribute("aria-expanded", "false");
        }
    }

    updateSidebarState();

    window.addEventListener("resize", updateSidebarState);
}

/* =========================================================
   MODALS
========================================================= */

function initializeModals() {
    /*
    |--------------------------------------------------------------------------
    | Click Outside Modal
    |--------------------------------------------------------------------------
    */

    document.addEventListener("click", function (event) {
        if (event.target.classList.contains("modal")) {
            event.target.classList.remove("show");
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Escape Key
    |--------------------------------------------------------------------------
    */

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeAllModals();
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Close Buttons
    |--------------------------------------------------------------------------
    */

    document.querySelectorAll(".modal-close").forEach(function (button) {
        button.addEventListener("click", function () {
            const modal = button.closest(".modal");

            if (modal) {
                modal.classList.remove("show");
            }
        });
    });
}

/* =========================================================
   OPEN MODAL
========================================================= */

function openModal(modalId) {
    const modal = document.getElementById(modalId);

    if (!modal) {
        return;
    }

    modal.classList.add("show");

    document.body.classList.add("modal-open");

    const firstInput = modal.querySelector(
        "input:not([type='hidden']), select, textarea",
    );

    if (firstInput) {
        setTimeout(function () {
            firstInput.focus();
        }, 100);
    }
}

/* =========================================================
   CLOSE MODAL
========================================================= */

function closeModal(modalId) {
    const modal = document.getElementById(modalId);

    if (!modal) {
        return;
    }

    modal.classList.remove("show");

    if (!document.querySelector(".modal.show")) {
        document.body.classList.remove("modal-open");
    }
}

/* =========================================================
   CLOSE ALL MODALS
========================================================= */

function closeAllModals() {
    document.querySelectorAll(".modal").forEach(function (modal) {
        modal.classList.remove("show");
    });

    document.body.classList.remove("modal-open");
}

/* =========================================================
   CATEGORY MODAL
========================================================= */

function openCategoryForm(type) {
    const modal = document.getElementById("categoryModal");

    if (!modal) {
        return;
    }

    const form = document.getElementById("categoryForm");
    const title = document.getElementById("categoryModalTitle");
    const categoryId = document.getElementById("categoryId");
    const categoryType = document.getElementById("categoryType");
    const categoryName = document.getElementById("categoryName");
    const action = document.getElementById("categoryAction");

    if (form) {
        form.reset();
    }

    if (title) {
        title.textContent = getCategoryTypeTitle(type);
    }

    if (categoryId) {
        categoryId.value = "";
    }

    if (categoryType) {
        categoryType.value = type;
    }

    if (categoryName) {
        categoryName.value = "";
    }

    if (action) {
        action.value = "create";
    }

    openModal("categoryModal");
}

/* =========================================================
   CATEGORY TYPE TITLE
========================================================= */

function getCategoryTypeTitle(type) {
    switch (type) {
        case "grievance":
            return "Add Grievance Category";

        case "suggestion":
            return "Add Suggestion Category";

        case "application":
            return "Add Application Type";

        default:
            return "Add Category";
    }
}

/* =========================================================
   EDIT CATEGORY
========================================================= */

function editCategory(id, name, type) {
    const modal = document.getElementById("categoryModal");

    if (!modal) {
        return;
    }

    const title = document.getElementById("categoryModalTitle");
    const categoryId = document.getElementById("categoryId");
    const categoryType = document.getElementById("categoryType");
    const categoryName = document.getElementById("categoryName");
    const action = document.getElementById("categoryAction");

    if (title) {
        title.textContent = getEditCategoryTypeTitle(type);
    }

    if (categoryId) {
        categoryId.value = id;
    }

    if (categoryType) {
        categoryType.value = type;
    }

    if (categoryName) {
        categoryName.value = name;
    }

    if (action) {
        action.value = "update";
    }

    openModal("categoryModal");
}

/* =========================================================
   EDIT CATEGORY TITLE
========================================================= */

function getEditCategoryTypeTitle(type) {
    switch (type) {
        case "grievance":
            return "Edit Grievance Category";

        case "suggestion":
            return "Edit Suggestion Category";

        case "application":
            return "Edit Application Type";

        default:
            return "Edit Category";
    }
}

/* =========================================================
   CLOSE CATEGORY FORM
========================================================= */

function closeCategoryForm() {
    closeModal("categoryModal");
}

/* =========================================================
   USER MODAL
========================================================= */

function openUserModal() {
    const modal = document.getElementById("userModal");

    if (!modal) {
        return;
    }

    const form = document.getElementById("userForm");
    const userId = document.getElementById("userId");
    const action = document.getElementById("userAction");
    const modalTitle = document.getElementById("userModalTitle");

    if (form) {
        form.reset();
    }

    if (userId) {
        userId.value = "";
    }

    if (action) {
        action.value = "create";
    }

    if (modalTitle) {
        modalTitle.textContent = "Add User";
    }

    clearFormValidation(modal);

    openModal("userModal");
}

/* =========================================================
   EDIT USER
========================================================= */

function editUser(userId) {
    if (!userId) {
        return;
    }

    window.location.href = "users.php?edit=" + encodeURIComponent(userId);
}

/* =========================================================
   VIEW USER
========================================================= */

function viewUser(userId) {
    if (!userId) {
        return;
    }

    window.location.href = "users.php?view=" + encodeURIComponent(userId);
}

/* =========================================================
   CLOSE USER MODAL
========================================================= */

function closeUserModal() {
    closeModal("userModal");
}

/* =========================================================
   CONFIRM ACTION
========================================================= */

function confirmAction(message) {
    if (!message) {
        message = "Are you sure you want to perform this action?";
    }

    return window.confirm(message);
}

/* =========================================================
   CONFIRM DELETE
========================================================= */

function confirmDelete(message) {
    return confirmAction(
        message || "Are you sure you want to delete this record?",
    );
}

/* =========================================================
   CONFIRM DEACTIVATE
========================================================= */

function confirmDeactivate() {
    return confirmAction("Are you sure you want to deactivate this account?");
}

/* =========================================================
   CONFIRM ACTIVATE
========================================================= */

function confirmActivate() {
    return confirmAction("Are you sure you want to activate this account?");
}

/* =========================================================
   CONFIRM LOGOUT
========================================================= */

function confirmLogout() {
    return confirmAction("Are you sure you want to logout?");
}

/* =========================================================
   PASSWORD CONFIRMATION
========================================================= */

function validatePasswordMatch(passwordId, confirmPasswordId) {
    const password = document.getElementById(passwordId);
    const confirmPassword = document.getElementById(confirmPasswordId);

    if (!password || !confirmPassword) {
        return true;
    }

    if (password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity("Passwords do not match.");

        return false;
    }

    confirmPassword.setCustomValidity("");

    return true;
}

/* =========================================================
   PASSWORD VALIDATION
========================================================= */

function initializePasswordValidation() {
    const passwordPairs = [
        ["new_password", "confirm_password"],
        ["password", "confirm_password"],
        ["admin_password", "admin_confirm_password"],
    ];

    passwordPairs.forEach(function (fields) {
        const password = document.getElementById(fields[0]);
        const confirmPassword = document.getElementById(fields[1]);

        if (!password || !confirmPassword) {
            return;
        }

        function checkPassword() {
            validatePasswordMatch(fields[0], fields[1]);
        }

        password.addEventListener("input", checkPassword);

        confirmPassword.addEventListener("input", checkPassword);
    });
}

/* =========================================================
   PASSWORD SHOW / HIDE
========================================================= */

function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);

    if (!input) {
        return;
    }

    if (input.type === "password") {
        input.type = "text";

        if (button) {
            button.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
        }
    } else {
        input.type = "password";

        if (button) {
            button.innerHTML = '<i class="fa-solid fa-eye"></i>';
        }
    }
}

/* =========================================================
   FORM INITIALIZATION
========================================================= */

function initializeForms() {
    document.querySelectorAll("form").forEach(function (form) {
        form.addEventListener("submit", function (event) {
            /*
            |--------------------------------------------------------------------------
            | Password Validation
            |--------------------------------------------------------------------------
            */

            const passwordFields = [
                ["new_password", "confirm_password"],
                ["password", "confirm_password"],
                ["admin_password", "admin_confirm_password"],
            ];

            for (let i = 0; i < passwordFields.length; i++) {
                const password = document.getElementById(passwordFields[i][0]);

                const confirmPassword = document.getElementById(
                    passwordFields[i][1],
                );

                if (
                    password &&
                    confirmPassword &&
                    password.value !== confirmPassword.value
                ) {
                    event.preventDefault();

                    confirmPassword.setCustomValidity(
                        "Passwords do not match.",
                    );

                    confirmPassword.reportValidity();

                    return;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent Double Submission
            |--------------------------------------------------------------------------
            */

            if (form.dataset.submitted === "true") {
                event.preventDefault();
                return;
            }

            form.dataset.submitted = "true";

            const submitButtons = form.querySelectorAll(
                'button[type="submit"], input[type="submit"]',
            );

            submitButtons.forEach(function (button) {
                if (!button.dataset.originalText) {
                    button.dataset.originalText = button.innerHTML;
                }

                button.disabled = true;

                button.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
            });
        });
    });
}

/* =========================================================
   CLEAR FORM VALIDATION
========================================================= */

function clearFormValidation(container) {
    if (!container) {
        return;
    }

    container
        .querySelectorAll("input, select, textarea")
        .forEach(function (field) {
            field.setCustomValidity("");
        });
}

/* =========================================================
   TABS
========================================================= */

function initializeTabs() {
    const tabs = document.querySelectorAll(".tab");

    tabs.forEach(function (tab) {
        tab.addEventListener("click", function () {
            const target = tab.getAttribute("data-target");

            tabs.forEach(function (item) {
                item.classList.remove("active");
            });

            tab.classList.add("active");

            document
                .querySelectorAll(".tab-content")
                .forEach(function (content) {
                    content.style.display = "none";
                });

            if (target) {
                const targetElement = document.getElementById(target);

                if (targetElement) {
                    targetElement.style.display = "block";
                }
            }
        });
    });
}

/* =========================================================
   ALERT AUTO HIDE
========================================================= */

function initializeAlerts() {
    const alerts = document.querySelectorAll(".alert-success, .alert-info");

    alerts.forEach(function (alert) {
        setTimeout(function () {
            if (!alert.parentNode) {
                return;
            }

            alert.style.transition = "opacity 0.4s ease, transform 0.4s ease";

            alert.style.opacity = "0";
            alert.style.transform = "translateY(-5px)";

            setTimeout(function () {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 400);
        }, 5000);
    });
}

/* =========================================================
   CATEGORY NAME VALIDATION
========================================================= */

function validateCategoryName(input) {
    if (!input) {
        return false;
    }

    const value = input.value.trim();

    if (value.length < 2) {
        input.setCustomValidity("Name must contain at least 2 characters.");

        return false;
    }

    input.setCustomValidity("");

    return true;
}

/* =========================================================
   REPORT TYPE CHANGE
========================================================= */

function changeReportType(selectElement) {
    if (!selectElement) {
        return;
    }

    const reportType = selectElement.value;

    const reportSections = document.querySelectorAll(".report-type-section");

    reportSections.forEach(function (section) {
        section.style.display = "none";
    });

    if (!reportType) {
        return;
    }

    const selected = document.getElementById(reportType + "Report");

    if (selected) {
        selected.style.display = "block";
    }
}

/* =========================================================
   REPORT TABS
========================================================= */

function switchReportTab(tabElement, targetId) {
    if (!tabElement || !targetId) {
        return;
    }

    document.querySelectorAll(".report-tab").forEach(function (tab) {
        tab.classList.remove("active");
    });

    tabElement.classList.add("active");

    document.querySelectorAll(".report-panel").forEach(function (panel) {
        panel.style.display = "none";
    });

    const target = document.getElementById(targetId);

    if (target) {
        target.style.display = "block";
    }
}

/* =========================================================
   SELECT ALL CHECKBOXES
========================================================= */

function toggleSelectAll(sourceCheckbox, checkboxClass) {
    if (!sourceCheckbox || !checkboxClass) {
        return;
    }

    const checkboxes = document.querySelectorAll("." + checkboxClass);

    checkboxes.forEach(function (checkbox) {
        if (!checkbox.disabled) {
            checkbox.checked = sourceCheckbox.checked;
        }
    });
}

/* =========================================================
   UPDATE SELECT ALL CHECKBOX
========================================================= */

function updateSelectAll(sourceCheckbox, checkboxClass) {
    if (!sourceCheckbox || !checkboxClass) {
        return;
    }

    const checkboxes = document.querySelectorAll(
        "." + checkboxClass + ":not(:disabled)",
    );

    const checked = document.querySelectorAll(
        "." + checkboxClass + ":not(:disabled):checked",
    );

    sourceCheckbox.checked =
        checkboxes.length > 0 && checked.length === checkboxes.length;
}

/* =========================================================
   SEARCH INPUTS
========================================================= */

function initializeSearchInputs() {
    document.querySelectorAll("[data-table-search]").forEach(function (input) {
        input.addEventListener("input", function () {
            const tableId = input.getAttribute("data-table-search");

            filterTable(tableId, input.value);
        });
    });
}

/* =========================================================
   FILTER TABLE
========================================================= */

function filterTable(tableId, searchValue) {
    const table = document.getElementById(tableId);

    if (!table) {
        return;
    }

    const value = String(searchValue || "")
        .trim()
        .toLowerCase();

    const rows = table.querySelectorAll("tbody tr");

    rows.forEach(function (row) {
        const text = row.textContent.toLowerCase();

        row.style.display = text.includes(value) ? "" : "none";
    });
}

/* =========================================================
   FILTER FORMS
========================================================= */

function initializeFilterForms() {
    document.querySelectorAll(".filter-form").forEach(function (form) {
        form.addEventListener("submit", function () {
            const button = form.querySelector('button[type="submit"]');

            if (!button) {
                return;
            }

            button.disabled = true;

            if (!button.dataset.originalText) {
                button.dataset.originalText = button.innerHTML;
            }

            button.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Applying...';
        });
    });
}

/* =========================================================
   CLEAR FILTERS
========================================================= */

function clearFilters(formId) {
    const form = document.getElementById(formId);

    if (!form) {
        return;
    }

    form.querySelectorAll("input, select").forEach(function (field) {
        if (field.type === "checkbox" || field.type === "radio") {
            field.checked = false;
        } else {
            field.value = "";
        }
    });
}

/* =========================================================
   TOOLTIP INITIALIZATION
========================================================= */

function initializeTooltips() {
    document.querySelectorAll("[title]").forEach(function (element) {
        element.addEventListener("mouseenter", function () {
            element.setAttribute("data-tooltip-visible", "true");
        });

        element.addEventListener("mouseleave", function () {
            element.removeAttribute("data-tooltip-visible");
        });
    });
}

/* =========================================================
   CONFIRM ACTION INITIALIZATION
========================================================= */

function initializeConfirmActions() {
    document.querySelectorAll("[data-confirm]").forEach(function (element) {
        element.addEventListener("click", function (event) {
            const message = element.getAttribute("data-confirm");

            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
}

/* =========================================================
   TABLE INTERACTIONS
========================================================= */

function initializeTableInteractions() {
    document.querySelectorAll(".admin-table tbody tr").forEach(function (row) {
        row.addEventListener("mouseenter", function () {
            row.classList.add("row-hover");
        });

        row.addEventListener("mouseleave", function () {
            row.classList.remove("row-hover");
        });
    });
}

/* =========================================================
   COPY TEXT
========================================================= */

function copyText(text) {
    if (!text) {
        return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
            .writeText(text)
            .then(function () {
                showTemporaryMessage("Copied successfully.");
            })
            .catch(function () {
                fallbackCopy(text);
            });
    } else {
        fallbackCopy(text);
    }
}

/* =========================================================
   FALLBACK COPY
========================================================= */

function fallbackCopy(text) {
    const textarea = document.createElement("textarea");

    textarea.value = text;

    textarea.style.position = "fixed";
    textarea.style.left = "-9999px";
    textarea.style.top = "0";

    document.body.appendChild(textarea);

    textarea.focus();
    textarea.select();

    try {
        document.execCommand("copy");

        showTemporaryMessage("Copied successfully.");
    } catch (error) {
        alert("Unable to copy text.");
    }

    document.body.removeChild(textarea);
}

/* =========================================================
   TEMPORARY MESSAGE
========================================================= */

function showTemporaryMessage(message) {
    if (!message) {
        return;
    }

    const existing = document.querySelector(".admin-temporary-message");

    if (existing) {
        existing.remove();
    }

    const notification = document.createElement("div");

    notification.className = "admin-temporary-message";

    notification.textContent = message;

    notification.style.cssText = `
        position: fixed;
        right: 20px;
        bottom: 20px;
        z-index: 5000;
        padding: 12px 18px;
        border-radius: 8px;
        background: #111827;
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    `;

    document.body.appendChild(notification);

    setTimeout(function () {
        notification.style.transition =
            "opacity 0.3s ease, transform 0.3s ease";

        notification.style.opacity = "0";
        notification.style.transform = "translateY(5px)";

        setTimeout(function () {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 2000);
}

/* =========================================================
   FILE SIZE VALIDATION
========================================================= */

function validateFileSize(input, maxSizeMB) {
    if (!input || !input.files || !input.files.length) {
        return true;
    }

    const maxSize = maxSizeMB * 1024 * 1024;

    const file = input.files[0];

    if (file.size > maxSize) {
        input.setCustomValidity(
            "File size must not exceed " + maxSizeMB + " MB.",
        );

        input.reportValidity();

        return false;
    }

    input.setCustomValidity("");

    return true;
}

/* =========================================================
   FILE TYPE VALIDATION
========================================================= */

function validateFileType(input, allowedTypes) {
    if (!input || !input.files || !input.files.length) {
        return true;
    }

    const file = input.files[0];

    const fileType = file.type.toLowerCase();

    if (Array.isArray(allowedTypes) && !allowedTypes.includes(fileType)) {
        input.setCustomValidity("This file type is not allowed.");

        input.reportValidity();

        return false;
    }

    input.setCustomValidity("");

    return true;
}

/* =========================================================
   PROFILE PHOTO PREVIEW
========================================================= */

function previewProfilePhoto(input, previewId) {
    if (!input || !input.files || !input.files.length) {
        return;
    }

    const preview = document.getElementById(previewId);

    if (!preview) {
        return;
    }

    const file = input.files[0];

    if (!file.type.startsWith("image/")) {
        return;
    }

    const reader = new FileReader();

    reader.onload = function (event) {
        preview.src = event.target.result;
    };

    reader.readAsDataURL(file);
}

/* =========================================================
   CHARACTER COUNT
========================================================= */

function initializeCharacterCount(inputId, counterId, maxLength) {
    const input = document.getElementById(inputId);

    const counter = document.getElementById(counterId);

    if (!input || !counter) {
        return;
    }

    function updateCounter() {
        const length = input.value.length;

        counter.textContent = length + " / " + maxLength;
    }

    input.addEventListener("input", updateCounter);

    updateCounter();
}

/* =========================================================
   NUMBER FORMAT
========================================================= */

function formatNumber(number) {
    const value = Number(number);

    if (Number.isNaN(value)) {
        return "0";
    }

    return value.toLocaleString("en-IN");
}

/* =========================================================
   CONFIRM FORM SUBMISSION
========================================================= */

function confirmFormSubmit(form, message) {
    if (!form) {
        return false;
    }

    return confirmAction(
        message || "Are you sure you want to submit this form?",
    );
}

/* =========================================================
   PRINT CURRENT PAGE
========================================================= */

function printPage() {
    window.print();
}

/* =========================================================
   EXPORT / DOWNLOAD HELPER
========================================================= */

function downloadUrl(url) {
    if (!url) {
        return;
    }

    window.location.href = url;
}

/* =========================================================
   REPORT DATE VALIDATION
========================================================= */

function validateDateRange(fromDateId, toDateId) {
    const fromDate = document.getElementById(fromDateId);

    const toDate = document.getElementById(toDateId);

    if (!fromDate || !toDate) {
        return true;
    }

    if (fromDate.value && toDate.value && fromDate.value > toDate.value) {
        toDate.setCustomValidity("To date cannot be earlier than From date.");

        return false;
    }

    toDate.setCustomValidity("");

    return true;
}

/* =========================================================
   RESET FORM
========================================================= */

function resetForm(formId) {
    const form = document.getElementById(formId);

    if (!form) {
        return;
    }

    form.reset();

    clearFormValidation(form);
}

/* =========================================================
   DISABLE BUTTON
========================================================= */

function disableButton(button) {
    if (!button) {
        return;
    }

    if (!button.dataset.originalText) {
        button.dataset.originalText = button.innerHTML;
    }

    button.disabled = true;

    button.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
}

/* =========================================================
   ENABLE BUTTON
========================================================= */

function enableButton(button) {
    if (!button) {
        return;
    }

    button.disabled = false;

    if (button.dataset.originalText) {
        button.innerHTML = button.dataset.originalText;
    }
}

/* =========================================================
   SCROLL TO ELEMENT
========================================================= */

function scrollToElement(elementId) {
    const element = document.getElementById(elementId);

    if (!element) {
        return;
    }

    element.scrollIntoView({
        behavior: "smooth",
        block: "start",
    });
}

/* =========================================================
   FOCUS ELEMENT
========================================================= */

function focusElement(elementId) {
    const element = document.getElementById(elementId);

    if (!element) {
        return;
    }

    element.focus();
}

/* =========================================================
   PREVENT BACK BUTTON RESUBMISSION
========================================================= */

if (window.history && window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

function confirmCategoryStatus(action) {
    return window.confirm(
        action === "activate"
            ? "Are you sure you want to activate this record?"
            : "Are you sure you want to deactivate this record?",
    );
}
