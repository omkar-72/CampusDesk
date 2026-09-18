<?php

require_once "../includes/auth.php";
requireStudent();

require_once "../config/database.php";
require_once "../includes/functions.php";

$user_id = getLoggedInUserId();
$student_id = getStudentId($conn, $user_id);

if (!$student_id) {
    die("Student record not found.");
}

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
   VIEW GRIEVANCE
   ========================= */

$grievance = false;
$attachment = false;

if ($section === "view") {

    $grievance_id = $_GET["view"] ?? "";

    if (!isValidId($grievance_id)) {
        die("Invalid grievance ID.");
    }

    $sql = "SELECT
                g.grievance_id,
                g.title,
                g.description,
                g.anonymous_status,
                g.submission_date,
                g.resolution_date,
                g.resolution_remarks,
                c.category_name,
                s.status_name
            FROM grievances g
            JOIN grievance_categories c
                ON g.category_id = c.category_id
            JOIN statuses s
                ON g.status_id = s.status_id
            WHERE g.grievance_id = $1
            AND g.student_id = $2
            AND s.module_type = 'GRIEVANCE'";

    $result = pg_query_params(
        $conn,
        $sql,
        [
            $grievance_id,
            $student_id
        ]
    );

    if (!$result || pg_num_rows($result) === 0) {
        die("Grievance not found.");
    }

    $grievance = pg_fetch_assoc($result);

    $attachment = getAttachment(
        $conn,
        $user_id,
        "GRIEVANCE",
        $grievance_id
    );
}


/* =========================
   MY GRIEVANCES
   ========================= */

$grievances = false;

if ($section === "my") {

    $sql = "SELECT
                g.grievance_id,
                g.title,
                c.category_name,
                s.status_name,
                g.submission_date
            FROM grievances g
            JOIN grievance_categories c
                ON g.category_id = c.category_id
            JOIN statuses s
                ON g.status_id = s.status_id
            WHERE g.student_id = $1
            AND s.module_type = 'GRIEVANCE'
            ORDER BY g.submission_date DESC";

    $grievances = pg_query_params(
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
    $categories = getGrievanceCategories($conn);
}


/* =========================
   SUCCESS
   ========================= */

$success_grievance_id = "";

if ($section === "success") {

    $success_id = $_GET["id"] ?? "";

    if (!isValidId($success_id)) {
        die("Invalid grievance ID.");
    }

    $result = pg_query_params(
        $conn,
        "SELECT grievance_id
         FROM grievances
         WHERE grievance_id = $1
         AND student_id = $2",
        [
            $success_id,
            $student_id
        ]
    );

    if (!$result || pg_num_rows($result) === 0) {
        die("Grievance not found.");
    }

    $success_grievance_id = pg_fetch_assoc(
        $result
    )["grievance_id"];
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

    <title>Grievances - CampusDesk</title>

    <link
        rel="stylesheet"
        href="../css/style-student-services.css"
    >

</head>

<body>

<div class="page-container">


    <!-- =========================
         GRIEVANCE HOME
         ========================= -->

    <?php if ($section === "home"): ?>

        <div class="page-header">

            <a
                href="dashboard.php"
                class="back-link"
            >
                ← Back to Dashboard
            </a>

            <h1 class="page-title">
                Grievances
            </h1>

            <p>
                Submit and track your grievances.
            </p>

        </div>


        <div class="section-box">

            <div class="module-options">

                <a
                    href="grievances.php?section=raise"
                    class="module-option"
                >

                    <h2>
                        Raise New Grievance
                    </h2>

                    <p>
                        Submit a new grievance to the college authority.
                    </p>

                </a>


                <a
                    href="grievances.php?section=my"
                    class="module-option"
                >

                    <h2>
                        My Grievances
                    </h2>

                    <p>
                        View and track your submitted grievances.
                    </p>

                </a>

            </div>

        </div>

    <?php endif; ?>


    <!-- =========================
         RAISE GRIEVANCE
         ========================= -->

    <?php if ($section === "raise"): ?>

        <div class="page-header">

            <a
                href="grievances.php"
                class="back-link"
            >
                ← Back to Grievances
            </a>

            <h1 class="page-title">
                Raise New Grievance
            </h1>

        </div>


        <div class="section-box">

            <form
                id="grievanceForm"
                method="POST"
                action="../actions/grievance.php"
                enctype="multipart/form-data"
            >

                <input
                    type="hidden"
                    name="action"
                    value="submit"
                >

                <input
                    type="hidden"
                    name="anonymous_status"
                    id="anonymous_status"
                    value=""
                >


                <!-- Title -->

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


                <!-- Category -->

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

                        <?php if (!empty($categories)): ?>

                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= escape($category["category_id"]) ?>"
                                >
                                    <?= escape($category["category_name"]) ?>
                                </option>

                                <?php endforeach; ?>

                        <?php endif; ?>

                    </select>

                </div>


                <!-- Description -->

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


                <!-- Attachment -->

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


                <!-- Declaration -->

                <div class="declaration">

                    <input
                        type="checkbox"
                        id="declaration"
                        name="declaration"
                        value="1"
                        required
                    >

                    <label for="declaration">
                        I confirm that the information provided is correct.
                    </label>

                </div>


                <!-- Buttons -->

                <div class="button-group">

                    <button
                        type="button"
                        class="button primary-button"
                        onclick="showConfirmationPopup()"
                    >
                        Submit
                    </button>

                    <button
                        type="reset"
                        class="button secondary-button"
                    >
                        Reset
                    </button>

                    <a
                        href="grievances.php"
                        class="button secondary-button button-link"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>


        <!-- Confirmation Popup -->

        <div
            id="confirmationModal"
            class="modal"
        >

            <div class="modal-content">

                <h2>
                    Confirm Submission
                </h2>

                <p>
                    Are you sure you want to submit this grievance?
                </p>

                <div class="modal-buttons">

                    <button
                        type="button"
                        class="button primary-button"
                        onclick="showIdentityPopup()"
                    >
                        Yes, Continue
                    </button>

                    <button
                        type="button"
                        class="button secondary-button"
                        onclick="closeConfirmationPopup()"
                    >
                        Cancel
                    </button>

                </div>

            </div>

        </div>


        <!-- Identity Preference Popup -->

        <div
            id="identityModal"
            class="modal"
        >

            <div class="modal-content">

                <h2>
                    Identity Preference
                </h2>

                <p>
                    Do you want to reveal your identity to the authority?
                </p>

                <div class="modal-buttons">

                    <button
                        type="button"
                        class="button primary-button"
                        onclick="setIdentity(0)"
                    >
                        Reveal Identity
                    </button>

                    <button
                        type="button"
                        class="button secondary-button"
                        onclick="setIdentity(1)"
                    >
                        Confidential
                    </button>

                </div>

            </div>

        </div>

    <?php endif; ?>


    <!-- =========================
         MY GRIEVANCES
         ========================= -->

    <?php if ($section === "my"): ?>

        <div class="page-header">

            <a
                href="grievances.php"
                class="back-link"
            >
                ← Back to Grievances
            </a>

            <h1 class="page-title">
                My Grievances
            </h1>

        </div>


        <div class="section-box">

            <?php if ($grievances && pg_num_rows($grievances) > 0): ?>

                <div class="table-container">

                    <table class="data-table">

                        <thead>

                            <tr>

                                <th>
                                    Grievance ID
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

                            <?php while ($row = pg_fetch_assoc($grievances)): ?>

                                <tr>

                                    <td>
                                        <?= escape(
                                            formatGrievanceId(
                                                $row["grievance_id"]
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= escape(
                                            $row["title"]
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= escape(
                                            $row["category_name"]
                                        ) ?>
                                    </td>

                                    <td>

                                        <span class="status">
                                            <?= escape(
                                                $row["status_name"]
                                            ) ?>
                                        </span>

                                    </td>

                                    <td>
                                        <?= escape(
                                            formatDateTime(
                                                $row["submission_date"]
                                            )
                                        ) ?>
                                    </td>

                                    <td>

                                        <div class="action-buttons">


                                            <!-- View -->

                                            <a
                                                href="grievances.php?section=view&view=<?= escape($row["grievance_id"]) ?>"
                                                class="view-button"
                                            >
                                                View
                                            </a>


                                            <!-- Delete -->

                                            <?php if ($row["status_name"] === "New"): ?>

                                                <form
                                                    method="POST"
                                                    action="../actions/grievance.php"
                                                    class="delete-form"
                                                    onsubmit="return confirmDelete()"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="delete"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="grievance_id"
                                                        value="<?= escape($row["grievance_id"]) ?>"
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
                                                    onclick="showDeleteNotPossible('<?= escape($row["status_name"]) ?>')"
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

                    <p>
                        You have not submitted any grievances yet.
                    </p>

                    <a
                        href="grievances.php?section=raise"
                        class="button primary-button button-link"
                    >
                        Raise New Grievance
                    </a>

                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <!-- =========================
         VIEW GRIEVANCE
         ========================= -->

    <?php if ($section === "view"): ?>

        <div class="page-header">

            <h1 class="page-title">
                View Grievance
            </h1>

        </div>


        <div class="section-box">

            <a
                href="grievances.php?section=my"
                class="back-link"
            >
                ← Back to My Grievances
            </a>


            <div class="details">


                <!-- Grievance ID -->

                <div class="detail-row">

                    <div class="detail-label">
                        Grievance ID
                    </div>

                    <div class="detail-value">
                        <?= escape(
                            formatGrievanceId(
                                $grievance["grievance_id"]
                            )
                        ) ?>
                    </div>

                </div>


                <!-- Title -->

                <div class="detail-row">

                    <div class="detail-label">
                        Title
                    </div>

                    <div class="detail-value">
                        <?= escape(
                            $grievance["title"]
                        ) ?>
                    </div>

                </div>


                <!-- Category -->

                <div class="detail-row">

                    <div class="detail-label">
                        Category
                    </div>

                    <div class="detail-value">
                        <?= escape(
                            $grievance["category_name"]
                        ) ?>
                    </div>

                </div>


                <!-- Description -->

                <div class="detail-row">

                    <div class="detail-label">
                        Description
                    </div>

                    <div class="detail-value">
                        <?= nl2br(
                            escape(
                                $grievance["description"]
                            )
                        ) ?>
                    </div>

                </div>


                <!-- Identity Preference -->

                <div class="detail-row">

                    <div class="detail-label">
                        Identity Preference
                    </div>

                    <div class="detail-value">

                    <?php

                    if ($grievance["anonymous_status"] === "t") {
                        echo "Confidential";
                    } else {
                        echo "Identity Revealed";
                    }

                    ?>

                    </div>

                </div>


                <!-- Current Status -->

                <div class="detail-row">

                    <div class="detail-label">
                        Current Status
                    </div>

                    <div class="detail-value">

                        <span class="status">
                            <?= escape(
                                $grievance["status_name"]
                            ) ?>
                        </span>

                    </div>

                </div>


                <!-- Submission Date -->

                <div class="detail-row">

                    <div class="detail-label">
                        Submission Date
                    </div>

                    <div class="detail-value">
                        <?= escape(
                            formatDateTime(
                                $grievance["submission_date"]
                            )
                        ) ?>
                    </div>

                </div>


                <!-- Resolution Date -->

                <?php if (!empty($grievance["resolution_date"])): ?>

                    <div class="detail-row">

                        <div class="detail-label">
                            Resolution Date
                        </div>

                        <div class="detail-value">
                            <?= escape(
                                formatDateTime(
                                    $grievance["resolution_date"]
                                )
                            ) ?>
                        </div>

                    </div>

                <?php endif; ?>


                <!-- Resolution Remarks -->

                <?php if (!empty($grievance["resolution_remarks"])): ?>

                    <div class="detail-row">

                        <div class="detail-label">
                            Resolution Remarks
                        </div>

                        <div class="detail-value">
                            <?= nl2br(
                                escape(
                                    $grievance["resolution_remarks"]
                                )
                            ) ?>
                        </div>

                    </div>

                <?php endif; ?>

            </div>


            <!-- Attachment -->

            <div class="attachment-box">

                <h3>
                    Supporting Document
                </h3>

                <?php if ($attachment): ?>

                    <p>
                        <?= escape(
                            $attachment["file_name"]
                        ) ?>
                    </p>

                    <a
                        href="view-attachment.php?attachment_id=<?php echo escape($attachment["attachment_id"]); ?>"
                        class="button secondary-button"
                    >
                        View Attachment
                    </a>

                <?php else: ?>

                    <p>
                        No supporting document uploaded.
                    </p>

                <?php endif; ?>

            </div>


            <!-- Timeline -->

            <div class="timeline">

                <h3>
                    Timeline
                </h3>


                <div class="timeline-item">

                    <strong>
                        Submitted
                    </strong>

                    <p>
                        <?= escape(
                            formatDateTime(
                                $grievance["submission_date"]
                            )
                        ) ?>
                    </p>

                </div>


                <div class="timeline-item">

                    <strong>
                        Current Status
                    </strong>

                    <p>
                        <?= escape(
                            $grievance["status_name"]
                        ) ?>
                    </p>

                </div>


                <?php if (!empty($grievance["resolution_date"])): ?>

                    <div class="timeline-item">

                        <strong>
                            Resolved
                        </strong>

                        <p>
                            <?= escape(
                                formatDateTime(
                                    $grievance["resolution_date"]
                                )
                            ) ?>
                        </p>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    <?php endif; ?>


    <!-- =========================
         SUCCESS
         ========================= -->

    <?php if ($section === "success"): ?>

        <div class="success-page">

            <div class="success-card">

                <a
                    href="grievances.php?section=my"
                    class="back-link"
                >
                    ← Back to My Grievances
                </a>

                <div class="success-icon">
                    ✓
                </div>

                <h1>
                    Grievance Submitted!
                </h1>

                <p class="success-text">
                    Your grievance has been submitted successfully.
                </p>

                <div class="reference-id-box">

                    <span>
                        Grievance ID
                    </span>

                    <strong>
                        <?= escape(
                            formatGrievanceId(
                                $success_grievance_id
                            )
                        ) ?>
                    </strong>

                </div>

                <div class="button-group success-buttons">

                    <a
                        href="grievances.php?section=view&view=<?= escape($success_grievance_id) ?>"
                        class="button primary-button button-link"
                    >
                        View Grievance
                    </a>

                    <a
                        href="grievances.php?section=my"
                        class="button secondary-button button-link"
                    >
                        My Grievances
                    </a>

                </div>

            </div>

        </div>

    <?php endif; ?>


</div>


<!-- =========================
     DELETE NOT POSSIBLE
     ========================= -->

<div
    id="deleteNotPossibleModal"
    class="modal"
>

    <div class="modal-content">

        <h2>
            Delete Not Possible
        </h2>

        <p id="deleteNotPossibleMessage">
            This grievance cannot be deleted.
        </p>

        <div class="modal-buttons">

            <button
                type="button"
                class="button secondary-button"
                onclick="closeDeleteNotPossible()"
            >
                Close
            </button>

        </div>

    </div>

</div>


<script src="../js/script-student-services.js"></script>

</body>

</html>