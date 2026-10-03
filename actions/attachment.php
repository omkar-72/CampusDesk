<?php

require_once "../includes/auth.php";
requireLogin();

require_once "../config/database.php";
require_once "../includes/functions.php";

$user_id = getLoggedInUserId();
$role_name = getLoggedInUserRole();


/* =========================================================
   UPLOAD ATTACHMENT
   Student Only
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
     * Only students can upload attachments.
     */
    if ($role_name !== "STUDENT") {
        die("Access denied.");
    }

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
       CHECK RECORD OWNERSHIP
    ========================= */

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

    if (
        !$result ||
        pg_num_rows($result) === 0
    ) {
        die("Record not found.");
    }


    /* =========================
       CHECK FILE
    ========================= */

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


    /* =========================
       CHECK FILE SIZE
    ========================= */

    if (!isAllowedFileSize($file["size"])) {
        die("File size must not exceed 5 MB.");
    }


    /* =========================
       CHECK FILE TYPE
    ========================= */

    if (function_exists("finfo_open")) {

        $finfo = finfo_open(
            FILEINFO_MIME_TYPE
        );

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


    /* =========================
       CHECK EXISTING ATTACHMENT
    ========================= */

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


    /* =========================
       READ FILE
    ========================= */

    $file_data = file_get_contents(
        $file["tmp_name"]
    );

    if ($file_data === false) {
        die("Unable to read file.");
    }


    /* =========================
       CONVERT BINARY DATA FOR BYTEA
    ========================= */

    $file_data = pg_escape_bytea(
        $conn,
        $file_data
    );


    /* =========================
       SAVE ATTACHMENT
    ========================= */

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
            $1,
            $2,
            $3,
            $4,
            $5,
            $6,
            $7
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


/* =========================================================
   VIEW ATTACHMENT
   Student + Authority
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $attachment_id = $_GET["attachment_id"] ?? "";

    if (!isValidId($attachment_id)) {
        die("Invalid attachment ID.");
    }


    /* =========================
       GET ATTACHMENT
    ========================= */

    if ($role_name === "STUDENT") {

        /*
         * Student can only retrieve
         * their own attachment.
         */
        $result = pg_query_params(
            $conn,
            "SELECT
                attachment_id,
                module_type,
                reference_id,
                file_name,
                file_type,
                encode(file_data, 'base64') AS file_data_base64
             FROM attachments
             WHERE attachment_id = $1
             AND user_id = $2",
            [
                $attachment_id,
                $user_id
            ]
        );
    } elseif ($role_name === "AUTHORITY") {

        /*
         * Authority can retrieve the attachment
         * because Authority is allowed to view
         * student service records.
         *
         * The referenced Grievance/Suggestion/
         * Application is verified below.
         */
        $result = pg_query_params(
            $conn,
            "SELECT
                attachment_id,
                module_type,
                reference_id,
                file_name,
                file_type,
                encode(file_data, 'base64') AS file_data_base64
             FROM attachments
             WHERE attachment_id = $1",
            [
                $attachment_id
            ]
        );
    } else {

        die("Access denied.");
    }


    if (
        !$result ||
        pg_num_rows($result) === 0
    ) {
        die("Attachment not found.");
    }

    $attachment = pg_fetch_assoc($result);


    /* =========================
       CHECK REFERENCED RECORD
    ========================= */

    $module_type = $attachment["module_type"];
    $reference_id = $attachment["reference_id"];


    if ($module_type === "GRIEVANCE") {

        if ($role_name === "STUDENT") {

            /*
             * Student must own the grievance.
             */
            $student_id = getStudentId(
                $conn,
                $user_id
            );

            if (!$student_id) {
                die("Student record not found.");
            }

            $sql = "SELECT grievance_id
                    FROM grievances
                    WHERE grievance_id = $1
                    AND student_id = $2";

            $params = [
                $reference_id,
                $student_id
            ];
        } else {

            /*
             * Authority can view the grievance.
             */
            $sql = "SELECT grievance_id
                    FROM grievances
                    WHERE grievance_id = $1";

            $params = [
                $reference_id
            ];
        }
    } elseif ($module_type === "SUGGESTION") {

        if ($role_name === "STUDENT") {

            /*
             * Student must own the suggestion.
             */
            $student_id = getStudentId(
                $conn,
                $user_id
            );

            if (!$student_id) {
                die("Student record not found.");
            }

            $sql = "SELECT suggestion_id
                    FROM suggestions
                    WHERE suggestion_id = $1
                    AND student_id = $2";

            $params = [
                $reference_id,
                $student_id
            ];
        } else {

            /*
             * Authority can view the suggestion.
             */
            $sql = "SELECT suggestion_id
                    FROM suggestions
                    WHERE suggestion_id = $1";

            $params = [
                $reference_id
            ];
        }
    } elseif ($module_type === "APPLICATION") {

        if ($role_name === "STUDENT") {

            /*
             * Student must own the application.
             */
            $student_id = getStudentId(
                $conn,
                $user_id
            );

            if (!$student_id) {
                die("Student record not found.");
            }

            $sql = "SELECT application_id
                    FROM applications
                    WHERE application_id = $1
                    AND student_id = $2";

            $params = [
                $reference_id,
                $student_id
            ];
        } else {

            /*
             * Authority can view the application.
             */
            $sql = "SELECT application_id
                    FROM applications
                    WHERE application_id = $1";

            $params = [
                $reference_id
            ];
        }
    } else {

        die("Invalid module.");
    }


    /* =========================
       VERIFY REFERENCED RECORD
    ========================= */

    $result = pg_query_params(
        $conn,
        $sql,
        $params
    );

    if (
        !$result ||
        pg_num_rows($result) === 0
    ) {
        die("Access denied.");
    }


    /* =========================
       DECODE FILE
    ========================= */

    $file_data = base64_decode(
        $attachment["file_data_base64"],
        true
    );

    if ($file_data === false) {
        die("Unable to decode attachment data.");
    }


    /* =========================
       CLEAR OUTPUT BUFFER
    ========================= */

    while (ob_get_level() > 0) {
        ob_end_clean();
    }


    /* =========================
       SEND FILE TO BROWSER
    ========================= */

    header(
        "Content-Type: " .
            $attachment["file_type"]
    );

    header(
        "Content-Length: " .
            strlen($file_data)
    );

    header(
        "Content-Disposition: inline; filename=\"" .
            basename($attachment["file_name"]) .
            "\""
    );

    header(
        "Cache-Control: no-store, no-cache, must-revalidate"
    );

    header(
        "Pragma: no-cache"
    );

    echo $file_data;

    exit;
}


die("Invalid request.");
