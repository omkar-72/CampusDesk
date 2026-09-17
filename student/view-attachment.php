<?php

require_once "../includes/auth.php";
requireStudent();

require_once "../config/database.php";
require_once "../includes/functions.php";

$user_id = getLoggedInUserId();


/* =========================
   ATTACHMENT ID
   ========================= */

$attachment_id = $_GET["attachment_id"] ?? "";

if (!isValidId($attachment_id)) {
    die("Invalid attachment ID.");
}


/* =========================
   GET ATTACHMENT
   ========================= */

$sql = "SELECT
            attachment_id,
            module_type,
            reference_id,
            file_name,
            file_type,
            file_size
        FROM attachments
        WHERE attachment_id = $1
        AND user_id = $2";

$result = pg_query_params(
    $conn,
    $sql,
    [
        $attachment_id,
        $user_id
    ]
);

if (
    !$result ||
    pg_num_rows($result) === 0
) {
    die("Attachment not found.");
}

$attachment = pg_fetch_assoc($result);


/* =========================
   ATTACHMENT INFORMATION
   ========================= */

$module_type = $attachment["module_type"];
$reference_id = $attachment["reference_id"];

$back_url = "";
$back_text = "";


/* =========================
   GET STUDENT
   ========================= */

$student_id = getStudentId(
    $conn,
    $user_id
);

if (!$student_id) {
    die("Student record not found.");
}


/* =========================
   CHECK MODULE
   ========================= */

if ($module_type === "GRIEVANCE") {

    $sql = "SELECT grievance_id
            FROM grievances
            WHERE grievance_id = $1
            AND student_id = $2";

    /*
     * Grievance View page uses
     * the "view" parameter.
     */

    $back_url =
        "grievances.php?section=view&view=" .
        urlencode($reference_id);

    $back_text =
        "← Back to Grievance";


} elseif ($module_type === "SUGGESTION") {

    $sql = "SELECT suggestion_id
            FROM suggestions
            WHERE suggestion_id = $1
            AND student_id = $2";

    /*
     * Suggestion View page uses
     * the "id" parameter.
     */

    $back_url =
        "suggestions.php?section=view&id=" .
        urlencode($reference_id);

    $back_text =
        "← Back to Suggestion";


} elseif ($module_type === "APPLICATION") {

    $sql = "SELECT application_id
            FROM applications
            WHERE application_id = $1
            AND student_id = $2";

    /*
     * Application View page uses
     * the "id" parameter.
     */

    $back_url =
        "applications.php?section=view&id=" .
        urlencode($reference_id);

    $back_text =
        "← Back to Application";


} else {

    die("Invalid module.");
}


/* =========================
   VERIFY OWNERSHIP
   ========================= */

$result = pg_query_params(
    $conn,
    $sql,
    [
        $reference_id,
        $student_id
    ]
);

if (
    !$result ||
    pg_num_rows($result) === 0
) {
    die("Access denied.");
}


/* =========================
   FILE TYPE
   ========================= */

$file_type = $attachment["file_type"];

$is_pdf =
    $file_type === "application/pdf";

$is_image =
    in_array(
        $file_type,
        [
            "image/jpeg",
            "image/png"
        ],
        true
    );

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
        View Attachment - CampusDesk
    </title>

    <link
        rel="stylesheet"
        href="../css/style-student-services.css"
    >

</head>

<body>


<div class="page-container">


    <!-- =========================
         PAGE HEADER
         ========================= -->

    <div class="page-header">

        <a
            href="<?php echo escape($back_url); ?>"
            class="back-link"
        >
            <?php echo escape($back_text); ?>
        </a>

        <h1 class="page-title">
            View Attachment
        </h1>

    </div>


    <!-- =========================
         ATTACHMENT
         ========================= -->

    <div class="section-box">

        <div class="attachment-box">


            <!-- =========================
                 FILE NAME
                 ========================= -->

            <div class="detail-row">

                <div class="detail-label">
                    File Name
                </div>

                <div class="detail-value">

                    <?php
                    echo escape(
                        $attachment["file_name"]
                    );
                    ?>

                </div>

            </div>


            <!-- =========================
                 FILE TYPE
                 ========================= -->

            <div class="detail-row">

                <div class="detail-label">
                    File Type
                </div>

                <div class="detail-value">

                    <?php
                    echo escape(
                        $attachment["file_type"]
                    );
                    ?>

                </div>

            </div>


            <!-- =========================
                 PDF VIEWER
                 ========================= -->

            <?php if ($is_pdf): ?>

                <div style="margin-top: 20px;">

                    <iframe
                        src="../actions/attachment.php?attachment_id=<?php
                        echo escape(
                            $attachment["attachment_id"]
                        );
                        ?>"
                        width="100%"
                        height="700"
                        style="border: 1px solid #ddd;"
                        title="PDF Attachment"
                    ></iframe>

                </div>


            <!-- =========================
                 IMAGE VIEWER
                 ========================= -->

            <?php elseif ($is_image): ?>

                <div
                    style="
                        margin-top: 20px;
                        text-align: center;
                    "
                >

                    <img
                        src="../actions/attachment.php?attachment_id=<?php
                        echo escape(
                            $attachment["attachment_id"]
                        );
                        ?>"
                        alt="<?php
                        echo escape(
                            $attachment["file_name"]
                        );
                        ?>"
                        style="
                            max-width: 100%;
                            height: auto;
                            display: block;
                            margin: auto;
                        "
                    >

                </div>


            <!-- =========================
                 OTHER FILE TYPE
                 ========================= -->

            <?php else: ?>

                <p class="no-data">

                    This file type cannot be displayed.

                </p>

                <a
                    href="../actions/attachment.php?attachment_id=<?php
                    echo escape(
                        $attachment["attachment_id"]
                    );
                    ?>"
                    class="button secondary-button"
                >
                    Open Attachment
                </a>

            <?php endif; ?>


        </div>


        <!-- =========================
             BACK BUTTON
             ========================= -->

        <div
            class="button-group"
            style="margin-top: 20px;"
        >

            <a
                href="<?php echo escape($back_url); ?>"
                class="button secondary-button"
            >
                <?php echo escape($back_text); ?>
            </a>

        </div>


    </div>


</div>


</body>

</html>