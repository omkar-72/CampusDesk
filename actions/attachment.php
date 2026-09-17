<?php

require_once "../includes/auth.php";
requireStudent();

require_once "../config/database.php";
require_once "../includes/functions.php";

$user_id = getLoggedInUserId();


/* =========================
   UPLOAD ATTACHMENT
   ========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $module_type = $_POST["module_type"] ?? "";
    $reference_id = $_POST["reference_id"] ?? "";

    $allowed_modules = [
        "GRIEVANCE",
        "SUGGESTION",
        "APPLICATION"
    ];

    if (!in_array($module_type, $allowed_modules, true)) {
        die("Invalid module.");
    }

    if (!isValidId($reference_id)) {
        die("Invalid reference ID.");
    }


    /* Get Student */

    $student_id = getStudentId(
        $conn,
        $user_id
    );

    if (!$student_id) {
        die("Student record not found.");
    }


    /* Check Record Ownership */

    if ($module_type === "GRIEVANCE") {

        $sql = "SELECT grievance_id
                FROM grievances
                WHERE grievance_id = $1
                AND student_id = $2";

    } elseif ($module_type === "SUGGESTION") {

        $sql = "SELECT suggestion_id
                FROM suggestions
                WHERE suggestion_id = $1
                AND student_id = $2";

    } else {

        $sql = "SELECT application_id
                FROM applications
                WHERE application_id = $1
                AND student_id = $2";
    }


    $result = pg_query_params(
        $conn,
        $sql,
        [
            $reference_id,
            $student_id
        ]
    );

    if (!$result || pg_num_rows($result) === 0) {
        die("Record not found.");
    }


    /* Check File */

    if (
        !isset($_FILES["attachment"]) ||
        $_FILES["attachment"]["error"] === UPLOAD_ERR_NO_FILE
    ) {
        die("No file selected.");
    }

    $file = $_FILES["attachment"];


    if ($file["error"] !== UPLOAD_ERR_OK) {
        die("File upload failed.");
    }


    /* Check File Size */

    if (!isAllowedFileSize($file["size"])) {
        die("File size must not exceed 5 MB.");
    }


    /* Check File Type */

    if (function_exists("finfo_open")) {

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        $file_type = finfo_file(
            $finfo,
            $file["tmp_name"]
        );

        finfo_close($finfo);

    } else {

        $file_type = $file["type"];
    }


    if (!isAllowedFileType($file_type)) {
        die("Only JPG, PNG and PDF files are allowed.");
    }


    /* Check Existing Attachment */

    $result = pg_query_params(
        $conn,
        "SELECT attachment_id
         FROM attachments
         WHERE user_id = $1
         AND module_type = $2
         AND reference_id = $3",
        [
            $user_id,
            $module_type,
            $reference_id
        ]
    );

    if (!$result) {
        die("Unable to check attachment.");
    }

    if (pg_num_rows($result) > 0) {
        die("Only one attachment is allowed.");
    }


    /* Read File */

    $file_data = file_get_contents(
        $file["tmp_name"]
    );

    if ($file_data === false) {
        die("Unable to read file.");
    }


    /* Convert Binary Data for BYTEA */

    $file_data = pg_escape_bytea(
        $conn,
        $file_data
    );


    /* Save Attachment */

    $result = pg_query_params(
        $conn,
        "INSERT INTO attachments
        (
            user_id,
            module_type,
            reference_id,
            file_name,
            file_type,
            file_size,
            file_data
        )
        VALUES
        (
            $1, $2, $3, $4, $5, $6, $7
        )",
        [
            $user_id,
            $module_type,
            $reference_id,
            basename($file["name"]),
            $file_type,
            $file["size"],
            $file_data
        ]
    );

    if (!$result) {
        die("Failed to save attachment.");
    }

    echo "Attachment uploaded successfully.";

    exit;
}


/* =========================
   VIEW ATTACHMENT
   ========================= */

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $attachment_id = $_GET["attachment_id"] ?? "";

    if (!isValidId($attachment_id)) {
        die("Invalid attachment ID.");
    }


    /* Get Attachment */

    $result = pg_query_params(
        $conn,
        "SELECT
            attachment_id,
            module_type,
            reference_id,
            file_name,
            file_type,
            file_data
         FROM attachments
         WHERE attachment_id = $1
         AND user_id = $2",
        [
            $attachment_id,
            $user_id
        ]
    );

    if (!$result || pg_num_rows($result) === 0) {
        die("Attachment not found.");
    }

    $attachment = pg_fetch_assoc($result);


    /* Get Student */

    $student_id = getStudentId(
        $conn,
        $user_id
    );

    if (!$student_id) {
        die("Student record not found.");
    }


    /* Check Referenced Record */

    $module_type = $attachment["module_type"];
    $reference_id = $attachment["reference_id"];


    if ($module_type === "GRIEVANCE") {

        $sql = "SELECT grievance_id
                FROM grievances
                WHERE grievance_id = $1
                AND student_id = $2";

    } elseif ($module_type === "SUGGESTION") {

        $sql = "SELECT suggestion_id
                FROM suggestions
                WHERE suggestion_id = $1
                AND student_id = $2";

    } elseif ($module_type === "APPLICATION") {

        $sql = "SELECT application_id
                FROM applications
                WHERE application_id = $1
                AND student_id = $2";

    } else {

        die("Invalid module.");
    }


    $result = pg_query_params(
        $conn,
        $sql,
        [
            $reference_id,
            $student_id
        ]
    );

    if (!$result || pg_num_rows($result) === 0) {
        die("Access denied.");
    }


    /* Display File */

    header(
        "Content-Type: " .
        $attachment["file_type"]
    );

    header(
        "Content-Disposition: inline; filename=\"" .
        basename($attachment["file_name"]) .
        "\""
    );

    echo pg_unescape_bytea(
        $attachment["file_data"]
    );

    exit;
}


die("Invalid request.");

?>