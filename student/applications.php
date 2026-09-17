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
    "submit",
    "my",
    "view",
    "success"
];

if (!in_array($section, $allowed_sections, true)) {
    $section = "home";
}


/* =========================
   APPLICATION ID
   ========================= */

$application_id = $_GET["id"] ?? "";

if (
    in_array($section, ["view", "success"], true) &&
    !isValidId($application_id)
) {
    die("Invalid application ID.");
}


/* =========================
   VIEW APPLICATION
   ========================= */

$application = null;
$attachment = false;

if (
    $section === "view" ||
    $section === "success"
) {

    $sql = "SELECT
                a.application_id,
                a.subject,
                a.description,
                a.submission_date,
                a.review_date,
                a.remarks,
                at.type_name,
                s.status_name
            FROM applications a

            INNER JOIN application_types at
                ON a.application_type_id =
                   at.application_type_id

            INNER JOIN statuses s
                ON a.status_id = s.status_id

            WHERE a.application_id = $1
            AND a.student_id = $2";

    $result = pg_query_params(
        $conn,
        $sql,
        [
            $application_id,
            $student_id
        ]
    );

    if (
        !$result ||
        pg_num_rows($result) === 0
    ) {
        die("Application not found.");
    }

    $application = pg_fetch_assoc(
        $result
    );


    /* Attachment */

    $attachment = getAttachment(
        $conn,
        $user_id,
        "APPLICATION",
        $application_id
    );
}


/* =========================
   MY APPLICATIONS
   ========================= */

$applications = false;

if ($section === "my") {

    $sql = "SELECT
                a.application_id,
                a.subject,
                at.type_name,
                s.status_name,
                a.submission_date
            FROM applications a

            INNER JOIN application_types at
                ON a.application_type_id =
                   at.application_type_id

            INNER JOIN statuses s
                ON a.status_id = s.status_id

            WHERE a.student_id = $1

            ORDER BY a.submission_date DESC";

    $applications = pg_query_params(
        $conn,
        $sql,
        [$student_id]
    );
}


/* =========================
   APPLICATION TYPES
   ========================= */

$application_types = false;

if ($section === "submit") {

    $application_types = getApplicationTypes(
        $conn
    );

    if (!$application_types) {
        die("Unable to load application types.");
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

    <title>
        Applications - CampusDesk
    </title>

    <link
        rel="stylesheet"
        href="../css/style-student-services.css"
    >

</head>

<body>


<div class="page-container">


    <?php if ($section === "home"): ?>


        <!-- =========================
             HOME
             ========================= -->

        <div class="page-header">

            <h1 class="page-title">
                Applications
            </h1>

            <p>
                Submit and manage your college applications.
            </p>

        </div>


        <div class="section-box">

            <div class="module-options">


                <!-- Submit Application -->

                <a
                    href="applications.php?section=submit"
                    class="module-option"
                >

                    <h2>
                        Submit Application
                    </h2>

                    <p>
                        Submit a new college application.
                    </p>

                </a>


                <!-- My Applications -->

                <a
                    href="applications.php?section=my"
                    class="module-option"
                >

                    <h2>
                        My Applications
                    </h2>

                    <p>
                        View your submitted applications.
                    </p>

                </a>


            </div>

        </div>


    <?php elseif ($section === "submit"): ?>


        <!-- =========================
             SUBMIT APPLICATION
             ========================= -->

        <div class="page-header">

            <a
                href="applications.php"
                class="back-link"
            >
                ← Back to Applications
            </a>

            <h1 class="page-title">
                Submit Application
            </h1>

        </div>


        <div class="section-box">

            <form
                id="applicationForm"
                action="../actions/application.php"
                method="POST"
                enctype="multipart/form-data"
            >


                <input
                    type="hidden"
                    name="submit_application"
                    value="1"
                >


                <!-- Application Type -->

                <div class="form-group">

                    <label for="application_type_id">
                        Application Type
                    </label>

                    <select
                        id="application_type_id"
                        name="application_type_id"
                        required
                    >

                        <option value="">
                            Select Application Type
                        </option>

                        <?php
                        while (
                            $type =
                            pg_fetch_assoc(
                                $application_types
                            )
                        ):
                        ?>

                            <option
                                value="<?php
                                echo escape(
                                    $type[
                                        "application_type_id"
                                    ]
                                );
                                ?>"
                            >

                                <?php
                                echo escape(
                                    $type["type_name"]
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- Subject -->

                <div class="form-group">

                    <label for="subject">
                        Subject
                    </label>

                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        maxlength="200"
                        required
                    >

                </div>


                <!-- Description -->

                <div class="form-group">

                    <label for="description">
                        Description / Reason
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
                        Optional. JPG, PNG or PDF.
                        Maximum 5 MB.
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

                        I confirm that the information
                        provided is correct.

                    </label>

                </div>


                <!-- Buttons -->

                <div class="button-group">

                    <button
                        type="button"
                        class="button primary-button"
                        onclick="
                            showApplicationConfirmationPopup()
                        "
                    >
                        Submit Application
                    </button>


                    <a
                        href="applications.php"
                        class="button secondary-button"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>


        <!-- Confirmation Modal -->

        <div
            id="applicationConfirmationModal"
            class="modal"
        >

            <div class="modal-content">

                <h2>
                    Confirm Submission
                </h2>

                <p>
                    Are you sure you want to submit
                    this application?
                </p>


                <div class="modal-buttons">

                    <button
                        type="button"
                        class="button primary-button"
                        onclick="
                            submitApplication()
                        "
                    >
                        Confirm
                    </button>


                    <button
                        type="button"
                        class="button secondary-button"
                        onclick="
                            closeApplicationConfirmationPopup()
                        "
                    >
                        Cancel
                    </button>

                </div>

            </div>

        </div>


    <?php elseif ($section === "my"): ?>


        <!-- =========================
             MY APPLICATIONS
             ========================= -->

        <div class="page-header">

            <a
                href="applications.php"
                class="back-link"
            >
                ← Back to Applications
            </a>

            <h1 class="page-title">
                My Applications
            </h1>

        </div>


        <div class="section-box">

            <?php
            if (
                $applications &&
                pg_num_rows($applications) > 0
            ):
            ?>


                <div class="table-container">

                    <table class="data-table">

                        <thead>

                            <tr>

                                <th>
                                    Application ID
                                </th>

                                <th>
                                    Application Type
                                </th>

                                <th>
                                    Subject
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


                            <?php
                            while (
                                $row =
                                pg_fetch_assoc(
                                    $applications
                                )
                            ):
                            ?>

                                <tr>

                                    <td>

                                        <?php
                                        echo escape(
                                            formatApplicationId(
                                                $row[
                                                    "application_id"
                                                ]
                                            )
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo escape(
                                            $row[
                                                "type_name"
                                            ]
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo escape(
                                            $row[
                                                "subject"
                                            ]
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <span class="status">

                                            <?php
                                            echo escape(
                                                $row[
                                                    "status_name"
                                                ]
                                            );
                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php
                                        echo escape(
                                            formatDateTime(
                                                $row[
                                                    "submission_date"
                                                ]
                                            )
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <div
                                            class="action-buttons"
                                        >


                                            <!-- View -->

                                            <a
                                                href="
                                                applications.php?section=view&id=<?php
                                                echo escape(
                                                    $row[
                                                        "application_id"
                                                    ]
                                                );
                                                ?>"
                                                class="view-button"
                                            >
                                                View
                                            </a>


                                            <!-- Delete -->

                                            <?php
                                            if (
                                                $row[
                                                    "status_name"
                                                ] === "New"
                                            ):
                                            ?>

                                                <form
                                                    action="../actions/application.php"
                                                    method="POST"
                                                    onsubmit="
                                                        return confirmApplicationDelete();
                                                    "
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="delete_application"
                                                        value="1"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="application_id"
                                                        value="<?php
                                                        echo escape(
                                                            $row[
                                                                "application_id"
                                                            ]
                                                        );
                                                        ?>"
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
                                                    onclick="
                                                        showApplicationDeleteNotPossible(
                                                            '<?php
                                                            echo escape(
                                                                $row[
                                                                    "status_name"
                                                                ]
                                                            );
                                                            ?>'
                                                        )
                                                    "
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

                    No applications found.

                </div>


            <?php endif; ?>

        </div>


    <?php elseif ($section === "view"): ?>


        <!-- =========================
             VIEW APPLICATION
             ========================= -->

        <div class="page-header">

            <a
                href="applications.php?section=my"
                class="back-link"
            >
                ← Back to My Applications
            </a>

            <h1 class="page-title">
                View Application
            </h1>

        </div>


        <div class="section-box">

            <div class="details">


                <!-- Application ID -->

                <div class="detail-row">

                    <div class="detail-label">
                        Application ID
                    </div>

                    <div class="detail-value">

                        <?php
                        echo escape(
                            formatApplicationId(
                                $application[
                                    "application_id"
                                ]
                            )
                        );
                        ?>

                    </div>

                </div>


                <!-- Application Type -->

                <div class="detail-row">

                    <div class="detail-label">
                        Application Type
                    </div>

                    <div class="detail-value">

                        <?php
                        echo escape(
                            $application[
                                "type_name"
                            ]
                        );
                        ?>

                    </div>

                </div>


                <!-- Subject -->

                <div class="detail-row">

                    <div class="detail-label">
                        Subject
                    </div>

                    <div class="detail-value">

                        <?php
                        echo escape(
                            $application[
                                "subject"
                            ]
                        );
                        ?>

                    </div>

                </div>


                <!-- Description -->

                <div class="detail-row">

                    <div class="detail-label">
                        Description / Reason
                    </div>

                    <div class="detail-value">

                        <?php
                        echo nl2br(
                            escape(
                                $application[
                                    "description"
                                ]
                            )
                        );
                        ?>

                    </div>

                </div>


                <!-- Status -->

                <div class="detail-row">

                    <div class="detail-label">
                        Status
                    </div>

                    <div class="detail-value">

                        <span class="status">

                            <?php
                            echo escape(
                                $application[
                                    "status_name"
                                ]
                            );
                            ?>

                        </span>

                    </div>

                </div>


                <!-- Submission Date -->

                <div class="detail-row">

                    <div class="detail-label">
                        Submission Date
                    </div>

                    <div class="detail-value">

                        <?php
                        echo escape(
                            formatDateTime(
                                $application[
                                    "submission_date"
                                ]
                            )
                        );
                        ?>

                    </div>

                </div>


                <!-- Review Date -->

                <?php
                if (
                    !empty(
                        $application[
                            "review_date"
                        ]
                    )
                ):
                ?>

                    <div class="detail-row">

                        <div class="detail-label">
                            Review Date
                        </div>

                        <div class="detail-value">

                            <?php
                            echo escape(
                                formatDateTime(
                                    $application[
                                        "review_date"
                                    ]
                                )
                            );
                            ?>

                        </div>

                    </div>

                <?php endif; ?>


                <!-- Remarks -->

                <?php
                if (
                    !empty(
                        $application[
                            "remarks"
                        ]
                    )
                ):
                ?>

                    <div class="detail-row">

                        <div class="detail-label">
                            Remarks
                        </div>

                        <div class="detail-value">

                            <?php
                            echo nl2br(
                                escape(
                                    $application[
                                        "remarks"
                                    ]
                                )
                            );
                            ?>

                        </div>

                    </div>

                <?php endif; ?>


            </div>


            <!-- Attachment -->

            <?php if ($attachment): ?>

                <div class="attachment-box">

                    <strong>
                        Supporting Document
                    </strong>

                    <p>

                        <?php
                        echo escape(
                            $attachment[
                                "file_name"
                            ]
                        );
                        ?>

                    </p>


                    <a
                        href="
                        ../actions/attachment.php?attachment_id=<?php
                        echo escape(
                            $attachment[
                                "attachment_id"
                            ]
                        );
                        ?>"
                        target="_blank"
                        class="button secondary-button"
                    >
                        View Attachment
                    </a>

                </div>

            <?php endif; ?>


        </div>


    <?php elseif ($section === "success"): ?>


        <!-- =========================
             SUCCESS
             ========================= -->

        <div class="success-page">

            <div class="success-card">


                <div class="success-icon">
                    ✓
                </div>


                <h1>
                    Application Submitted!
                </h1>


                <p class="success-text">

                    Your application has been
                    submitted successfully.

                </p>


                <div class="grievance-id-box">

                    <span>
                        Application ID
                    </span>

                    <strong>

                        <?php
                        echo escape(
                            formatApplicationId(
                                $application[
                                    "application_id"
                                ]
                            )
                        );
                        ?>

                    </strong>

                </div>


                <div
                    class="button-group success-buttons"
                >

                    <a
                        href="
                        applications.php?section=view&id=<?php
                        echo escape(
                            $application_id
                        );
                        ?>"
                        class="button primary-button"
                    >
                        View Application
                    </a>


                    <a
                        href="applications.php?section=my"
                        class="button secondary-button"
                    >
                        My Applications
                    </a>

                </div>


            </div>

        </div>

    <?php endif; ?>


</div>


<!-- =========================
     DELETE NOT POSSIBLE MODAL
     ========================= -->

<div
    id="applicationDeleteNotPossibleModal"
    class="modal"
>

    <div class="modal-content">

        <h2>
            Delete Not Possible
        </h2>

        <p
            id="applicationDeleteNotPossibleMessage"
        ></p>

        <div class="modal-buttons">

            <button
                type="button"
                class="button secondary-button"
                onclick="
                    closeApplicationDeleteNotPossible()
                "
            >
                Close
            </button>

        </div>

    </div>

</div>


<script src="../js/script-student-services.js"></script>

</body>

</html>