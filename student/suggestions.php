<?php

require_once "../includes/auth.php";
requireStudent();

require_once "../config/database.php";
require_once "../includes/functions.php";

$user_id = getLoggedInUserId();

$student_id = getStudentId(
    $conn,
    $user_id
);

if (!$student_id) {
    die("Student record not found.");
}


/* =========================
   SECTION
   ========================= */

$section = $_GET["section"] ?? "home";

$allowed_sections = [
    "home",
    "raise",
    "my",
    "view",
    "success"
];

if (!in_array($section, $allowed_sections, true)) {
    $section = "home";
}


/* =========================
   SUGGESTION ID
   ========================= */

$suggestion_id = $_GET["id"] ?? "";

if (
    in_array($section, ["view", "success"], true) &&
    !isValidId($suggestion_id)
) {
    die("Invalid suggestion ID.");
}


/* =========================
   VIEW SUGGESTION
   ========================= */

$suggestion = null;
$attachment = false;

if ($section === "view" || $section === "success") {

    $sql = "SELECT
                s.suggestion_id,
                s.title,
                s.description,
                s.submission_date,
                s.decision_date,
                s.remarks,
                sc.category_name,
                st.status_name
            FROM suggestions s
            INNER JOIN suggestion_categories sc
                ON s.category_id = sc.category_id
            INNER JOIN statuses st
                ON s.status_id = st.status_id
            WHERE s.suggestion_id = $1
            AND s.student_id = $2";

    $result = pg_query_params(
        $conn,
        $sql,
        [
            $suggestion_id,
            $student_id
        ]
    );

    if (!$result || pg_num_rows($result) === 0) {
        die("Suggestion not found.");
    }

    $suggestion = pg_fetch_assoc($result);


    /* Attachment */

    $attachment = getAttachment(
        $conn,
        $user_id,
        "SUGGESTION",
        $suggestion_id
    );
}


/* =========================
   MY SUGGESTIONS
   ========================= */

$suggestions = false;

if ($section === "my") {

    $sql = "SELECT
                s.suggestion_id,
                s.title,
                sc.category_name,
                st.status_name,
                s.submission_date
            FROM suggestions s
            INNER JOIN suggestion_categories sc
                ON s.category_id = sc.category_id
            INNER JOIN statuses st
                ON s.status_id = st.status_id
            WHERE s.student_id = $1
            ORDER BY s.submission_date DESC";

    $suggestions = pg_query_params(
        $conn,
        $sql,
        [$student_id]
    );
}


/* =========================
   CATEGORIES
   ========================= */

$categories = false;

if ($section === "raise") {

    $categories = getSuggestionCategories(
        $conn
    );

    if (!$categories) {
        die("Unable to load suggestion categories.");
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Suggestions - CampusDesk</title>

    <link
        rel="stylesheet"
        href="../css/style-student-services.css"
    >

</head>

<body>


<div class="page-container">


    <?php if ($section === "home"): ?>

        <div class="page-header">

            <h1 class="page-title">
                Suggestions
            </h1>

            <p>
                Submit and manage your suggestions.
            </p>

        </div>


        <div class="section-box">

            <div class="module-options">

                <a
                    href="suggestions.php?section=raise"
                    class="module-option"
                >

                    <h2>
                        Submit Suggestion
                    </h2>

                    <p>
                        Submit a new suggestion to the college.
                    </p>

                </a>


                <a
                    href="suggestions.php?section=my"
                    class="module-option"
                >

                    <h2>
                        My Suggestions
                    </h2>

                    <p>
                        View your submitted suggestions.
                    </p>

                </a>

            </div>

        </div>


    <?php elseif ($section === "raise"): ?>


        <div class="page-header">

            <a
                href="suggestions.php"
                class="back-link"
            >
                ← Back to Suggestions
            </a>

            <h1 class="page-title">
                Submit Suggestion
            </h1>

        </div>


        <div class="section-box">

            <form
                id="suggestionForm"
                action="../actions/suggestion.php"
                method="POST"
                enctype="multipart/form-data"
            >

                <input
                    type="hidden"
                    name="submit_suggestion"
                    value="1"
                >


                <div class="form-group">

                    <label for="title">
                        Title
                    </label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="200"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="category_id">
                        Category
                    </label>

                    <select
                        id="category_id"
                        name="category_id"
                        required
                    >

                        <option value="">
                            Select Category
                        </option>

                        <?php while ($category = pg_fetch_assoc($categories)): ?>

                            <option
                                value="<?php echo escape($category["category_id"]); ?>"
                            >
                                <?php echo escape($category["category_name"]); ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        required
                    ></textarea>

                </div>


                <div class="form-group">

                    <label for="attachment">
                        Supporting Document
                    </label>

                    <input
                        type="file"
                        id="attachment"
                        name="attachment"
                        accept=".jpg,.jpeg,.png,.pdf"
                        onchange="validateFile(this)"
                    >

                    <small>
                        Optional. JPG, PNG or PDF. Maximum 5 MB.
                    </small>

                </div>


                <div class="declaration">

                    <input
                        type="checkbox"
                        id="declaration"
                        name="declaration"
                        value="1"
                        required
                    >

                    <label for="declaration">
                        I confirm that the information provided
                        is correct.
                    </label>

                </div>


                <div class="button-group">

                    <button
                        type="button"
                        class="button primary-button"
                        onclick="showSuggestionConfirmationPopup()"
                    >
                        Submit Suggestion
                    </button>

                    <a
                        href="suggestions.php"
                        class="button secondary-button"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>


        <!-- Confirmation Modal -->

        <div
            id="suggestionConfirmationModal"
            class="modal"
        >

            <div class="modal-content">

                <h2>
                    Confirm Submission
                </h2>

                <p>
                    Are you sure you want to submit this suggestion?
                </p>

                <div class="modal-buttons">

                    <button
                        type="button"
                        class="button primary-button"
                        onclick="submitSuggestion()"
                    >
                        Confirm
                    </button>

                    <button
                        type="button"
                        class="button secondary-button"
                        onclick="closeSuggestionConfirmationPopup()"
                    >
                        Cancel
                    </button>

                </div>

            </div>

        </div>


    <?php elseif ($section === "my"): ?>


        <div class="page-header">

            <a
                href="suggestions.php"
                class="back-link"
            >
                ← Back to Suggestions
            </a>

            <h1 class="page-title">
                My Suggestions
            </h1>

        </div>


        <div class="section-box">

            <?php if ($suggestions && pg_num_rows($suggestions) > 0): ?>

                <div class="table-container">

                    <table class="data-table">

                        <thead>

                            <tr>

                                <th>
                                    Suggestion ID
                                </th>

                                <th>
                                    Title
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Submission Date
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php while ($row = pg_fetch_assoc($suggestions)): ?>

                                <tr>

                                    <td>
                                        <?php
                                        echo escape(
                                            formatSuggestionId(
                                                $row["suggestion_id"]
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo escape(
                                            $row["title"]
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo escape(
                                            $row["category_name"]
                                        );
                                        ?>
                                    </td>

                                    <td>

                                        <span class="status">

                                            <?php
                                            echo escape(
                                                $row["status_name"]
                                            );
                                            ?>

                                        </span>

                                    </td>

                                    <td>
                                        <?php
                                        echo escape(
                                            formatDateTime(
                                                $row["submission_date"]
                                            )
                                        );
                                        ?>
                                    </td>

                                    <td>

                                        <div class="action-buttons">

                                            <a
                                                href="suggestions.php?section=view&id=<?php echo escape($row["suggestion_id"]); ?>"
                                                class="view-button"
                                            >
                                                View
                                            </a>


                                            <?php if ($row["status_name"] === "New"): ?>

                                                <form
                                                    action="../actions/suggestion.php"
                                                    method="POST"
                                                    onsubmit="return confirmSuggestionDelete();"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="delete_suggestion"
                                                        value="1"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="suggestion_id"
                                                        value="<?php echo escape($row["suggestion_id"]); ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="delete-button"
                                                    >
                                                        Delete
                                                    </button>

                                                </form>

                                            <?php else: ?>

                                                <button
                                                    type="button"
                                                    class="delete-button"
                                                    onclick="showSuggestionDeleteNotPossible('<?php echo escape($row["status_name"]); ?>')"
                                                >
                                                    Delete
                                                </button>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="no-data">

                    No suggestions found.

                </div>

            <?php endif; ?>

        </div>


    <?php elseif ($section === "view"): ?>


        <div class="page-header">

            <a
                href="suggestions.php?section=my"
                class="back-link"
            >
                ← Back to My Suggestions
            </a>

            <h1 class="page-title">
                View Suggestion
            </h1>

        </div>


        <div class="section-box">

            <div class="details">


                <div class="detail-row">

                    <div class="detail-label">
                        Suggestion ID
                    </div>

                    <div class="detail-value">

                        <?php
                        echo escape(
                            formatSuggestionId(
                                $suggestion["suggestion_id"]
                            )
                        );
                        ?>

                    </div>

                </div>


                <div class="detail-row">

                    <div class="detail-label">
                        Title
                    </div>

                    <div class="detail-value">

                        <?php
                        echo escape(
                            $suggestion["title"]
                        );
                        ?>

                    </div>

                </div>


                <div class="detail-row">

                    <div class="detail-label">
                        Category
                    </div>

                    <div class="detail-value">

                        <?php
                        echo escape(
                            $suggestion["category_name"]
                        );
                        ?>

                    </div>

                </div>


                <div class="detail-row">

                    <div class="detail-label">
                        Description
                    </div>

                    <div class="detail-value">

                        <?php
                        echo nl2br(
                            escape(
                                $suggestion["description"]
                            )
                        );
                        ?>

                    </div>

                </div>


                <div class="detail-row">

                    <div class="detail-label">
                        Status
                    </div>

                    <div class="detail-value">

                        <span class="status">

                            <?php
                            echo escape(
                                $suggestion["status_name"]
                            );
                            ?>

                        </span>

                    </div>

                </div>


                <div class="detail-row">

                    <div class="detail-label">
                        Submission Date
                    </div>

                    <div class="detail-value">

                        <?php
                        echo escape(
                            formatDateTime(
                                $suggestion["submission_date"]
                            )
                        );
                        ?>

                    </div>

                </div>


                <?php if (!empty($suggestion["decision_date"])): ?>

                    <div class="detail-row">

                        <div class="detail-label">
                            Decision Date
                        </div>

                        <div class="detail-value">

                            <?php
                            echo escape(
                                formatDateTime(
                                    $suggestion["decision_date"]
                                )
                            );
                            ?>

                        </div>

                    </div>

                <?php endif; ?>


                <?php if (!empty($suggestion["remarks"])): ?>

                    <div class="detail-row">

                        <div class="detail-label">
                            Remarks
                        </div>

                        <div class="detail-value">

                            <?php
                            echo nl2br(
                                escape(
                                    $suggestion["remarks"]
                                )
                            );
                            ?>

                        </div>

                    </div>

                <?php endif; ?>


            </div>


            <?php if ($attachment): ?>

                <div class="attachment-box">

                    <strong>
                        Supporting Document
                    </strong>

                    <p>

                        <?php
                        echo escape(
                            $attachment["file_name"]
                        );
                        ?>

                    </p>

                    <a
                        href="../actions/attachment.php?attachment_id=<?php echo escape($attachment["attachment_id"]); ?>"
                        target="_blank"
                        class="button secondary-button"
                    >
                        View Attachment
                    </a>

                </div>

            <?php endif; ?>


        </div>


    <?php elseif ($section === "success"): ?>


        <div class="success-page">

            <div class="success-card">

                <div class="success-icon">
                    ✓
                </div>

                <h1>
                    Suggestion Submitted!
                </h1>

                <p class="success-text">
                    Your suggestion has been submitted successfully.
                </p>


                <div class="grievance-id-box">

                    <span>
                        Suggestion ID
                    </span>

                    <strong>

                        <?php
                        echo escape(
                            formatSuggestionId(
                                $suggestion["suggestion_id"]
                            )
                        );
                        ?>

                    </strong>

                </div>


                <div class="button-group success-buttons">

                    <a
                        href="suggestions.php?section=view&id=<?php echo escape($suggestion_id); ?>"
                        class="button primary-button"
                    >
                        View Suggestion
                    </a>

                    <a
                        href="suggestions.php?section=my"
                        class="button secondary-button"
                    >
                        My Suggestions
                    </a>

                </div>

            </div>

        </div>

    <?php endif; ?>


</div>


<!-- Delete Not Possible Modal -->

<div
    id="suggestionDeleteNotPossibleModal"
    class="modal"
>

    <div class="modal-content">

        <h2>
            Delete Not Possible
        </h2>

        <p id="suggestionDeleteNotPossibleMessage"></p>

        <div class="modal-buttons">

            <button
                type="button"
                class="button secondary-button"
                onclick="closeSuggestionDeleteNotPossible()"
            >
                Close
            </button>

        </div>

    </div>

</div>


<script src="../js/script-student-services.js"></script>

<script>

/* Suggestion Confirmation */

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


/* Delete */

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

</script>

</body>

</html>