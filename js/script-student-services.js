/* CampusDesk - Student Services */


/* =========================
   GRIEVANCE
   ========================= */

function showConfirmationPopup()
{
    const form = document.getElementById(
        "grievanceForm"
    );

    if (!form) {
        return;
    }

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const modal = document.getElementById(
        "confirmationModal"
    );

    if (modal) {
        modal.style.display = "flex";
    }
}


function closeConfirmationPopup()
{
    const modal = document.getElementById(
        "confirmationModal"
    );

    if (modal) {
        modal.style.display = "none";
    }
}


function showIdentityPopup()
{
    closeConfirmationPopup();

    const modal = document.getElementById(
        "identityModal"
    );

    if (modal) {
        modal.style.display = "flex";
    }
}


function closeIdentityPopup()
{
    const modal = document.getElementById(
        "identityModal"
    );

    if (modal) {
        modal.style.display = "none";
    }
}


function setIdentity(value)
{
    const input = document.getElementById(
        "anonymous_status"
    );

    const form = document.getElementById(
        "grievanceForm"
    );

    if (!input || !form) {
        return;
    }

    input.value = value;

    form.submit();
}


function confirmDelete()
{
    return confirm(
        "Are you sure you want to delete this grievance?"
    );
}


function showDeleteNotPossible(status)
{
    const modal = document.getElementById(
        "deleteNotPossibleModal"
    );

    const message = document.getElementById(
        "deleteNotPossibleMessage"
    );

    if (!modal) {
        return;
    }

    if (message) {

        message.textContent =
            "This grievance cannot be deleted because its current status is " +
            status +
            ".";
    }

    modal.style.display = "flex";
}


function closeDeleteNotPossible()
{
    const modal = document.getElementById(
        "deleteNotPossibleModal"
    );

    if (modal) {
        modal.style.display = "none";
    }
}


/* =========================
   SUGGESTION
   ========================= */

function showSuggestionConfirmationPopup()
{
    const form = document.getElementById(
        "suggestionForm"
    );

    if (!form) {
        return;
    }

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const modal = document.getElementById(
        "suggestionConfirmationModal"
    );

    if (modal) {
        modal.style.display = "flex";
    }
}


function closeSuggestionConfirmationPopup()
{
    const modal = document.getElementById(
        "suggestionConfirmationModal"
    );

    if (modal) {
        modal.style.display = "none";
    }
}


function submitSuggestion()
{
    const form = document.getElementById(
        "suggestionForm"
    );

    if (form) {
        form.submit();
    }
}


function confirmSuggestionDelete()
{
    return confirm(
        "Are you sure you want to delete this suggestion?"
    );
}


function showSuggestionDeleteNotPossible(status)
{
    const modal = document.getElementById(
        "suggestionDeleteNotPossibleModal"
    );

    const message = document.getElementById(
        "suggestionDeleteNotPossibleMessage"
    );

    if (!modal) {
        return;
    }

    if (message) {

        message.textContent =
            "This suggestion cannot be deleted because its current status is " +
            status +
            ".";
    }

    modal.style.display = "flex";
}


function closeSuggestionDeleteNotPossible()
{
    const modal = document.getElementById(
        "suggestionDeleteNotPossibleModal"
    );

    if (modal) {
        modal.style.display = "none";
    }
}


/* =========================
   FILE VALIDATION
   ========================= */

function validateFile(input)
{
    if (!input.files || input.files.length === 0) {
        return true;
    }

    const file = input.files[0];

    const allowedTypes = [
        "image/jpeg",
        "image/png",
        "application/pdf"
    ];

    const maxSize = 5 * 1024 * 1024;


    if (!allowedTypes.includes(file.type)) {

        alert(
            "Only JPG, PNG and PDF files are allowed."
        );

        input.value = "";

        return false;
    }


    if (file.size > maxSize) {

        alert(
            "File size must not exceed 5 MB."
        );

        input.value = "";

        return false;
    }


    return true;
}


/* =========================
   CLOSE MODALS
   ========================= */

window.addEventListener(
    "click",
    function (event) {

        const modals = document.querySelectorAll(
            ".modal"
        );

        modals.forEach(
            function (modal) {

                if (event.target === modal) {
                    modal.style.display = "none";
                }

            }
        );

    }
);