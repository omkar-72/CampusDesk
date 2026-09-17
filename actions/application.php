<?php

/*
 * CampusDesk
 * Application Actions
 */

require_once "../includes/auth.php";
requireStudent();

require_once "../config/database.php";
require_once "../includes/functions.php";

$user_id = getLoggedInUserId();


/* =========================
   DELETE APPLICATION
   ========================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_application"])
) {

    $application_id = $_POST["application_id"] ?? "";

    if (!isValidId($application_id)) {
        die("Invalid application ID.");
    }


    /* Get Student */

    $student_id = getStudentId(
        $conn,
        $user_id
    );

    if (!$student_id) {
        die("Student record not found.");
    }


    /* Check Ownership and Status */

    $sql = "SELECT application_id
            FROM applications
            WHERE application_id = $1
            AND student_id = $2
            AND status_id = (
                SELECT status_id
                FROM statuses
                WHERE status_name = 'New'
                AND module_type = 'APPLICATION'
                AND status = TRUE
            )";

    $result = pg_query_params(
        $conn,
        $sql,
        [
            $application_id,
            $student_id
        ]
    );

    if (!$result || pg_num_rows($result) === 0) {
        die("Application cannot be deleted.");
    }


    /* Begin Transaction */

    pg_query($conn, "BEGIN");


    /* Delete Attachment */

    $delete_attachment_sql = "DELETE FROM attachments
                              WHERE user_id = $1
                              AND module_type = 'APPLICATION'
                              AND reference_id = $2";

    $attachment_result = pg_query_params(
        $conn,
        $delete_attachment_sql,
        [
            $user_id,
            $application_id
        ]
    );

    if (!$attachment_result) {

        pg_query($conn, "ROLLBACK");

        die("Failed to delete attachment.");
    }


    /* Delete Application */

    $delete_application_sql = "DELETE FROM applications
                               WHERE application_id = $1
                               AND student_id = $2";

    $application_result = pg_query_params(
        $conn,
        $delete_application_sql,
        [
            $application_id,
            $student_id
        ]
    );

    if (!$application_result) {

        pg_query($conn, "ROLLBACK");

        die("Failed to delete application.");
    }


    /* Commit */

    pg_query($conn, "COMMIT");


    header(
        "Location: ../student/applications.php?section=my"
    );

    exit;
}


/* =========================
   SUBMIT APPLICATION
   ========================= */

if (
    $_SERVER["REQUEST_METHOD"] !== "POST" ||
    !isset($_POST["submit_application"])
) {
    header(
        "Location: ../student/applications.php"
    );

    exit;
}


/* Get Form Data */

$application_type_id = $_POST["application_type_id"] ?? "";

$subject = cleanInput(
    $_POST["subject"] ?? ""
);

$description = cleanInput(
    $_POST["description"] ?? ""
);

$declaration = $_POST["declaration"] ?? "";


/* =========================
   VALIDATION
   ========================= */

if (!isValidId($application_type_id)) {
    die("Invalid application type.");
}

if ($subject === "") {
    die("Subject is required.");
}

if (strlen($subject) > 200) {
    die("Subject must not exceed 200 characters.");
}

if ($description === "") {
    die("Description is required.");
}

if ($declaration !== "1") {
    die("Please accept the declaration.");
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
   CHECK APPLICATION TYPE
   ========================= */

$type_sql = "SELECT application_type_id
             FROM application_types
             WHERE application_type_id = $1
             AND status = TRUE";

$type_result = pg_query_params(
    $conn,
    $type_sql,
    [$application_type_id]
);

if (
    !$type_result ||
    pg_num_rows($type_result) === 0
) {
    die("Invalid application type.");
}


/* =========================
   GET NEW STATUS
   ========================= */

$status_id = getStatusId(
    $conn,
    "New",
    "APPLICATION"
);

if (!$status_id) {
    die("Application status not found.");
}


/* =========================
   CHECK ATTACHMENT
   ========================= */

$file = $_FILES["attachment"] ?? null;

if (
    $file &&
    $file["error"] !== UPLOAD_ERR_NO_FILE
) {

    if ($file["error"] !== UPLOAD_ERR_OK) {
        die("File upload failed.");
    }


    /* File Size */

    if (!isAllowedFileSize($file["size"])) {
        die("File size must not exceed 5 MB.");
    }


    /* File Type */

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
        die(
            "Only JPG, PNG and PDF files are allowed."
        );
    }
}


/* =========================
   BEGIN TRANSACTION
   ========================= */

pg_query($conn, "BEGIN");


/* =========================
   INSERT APPLICATION
   ========================= */

$sql = "INSERT INTO applications
        (
            student_id,
            status_id,
            application_type_id,
            subject,
            description
        )
        VALUES
        (
            $1,
            $2,
            $3,
            $4,
            $5
        )
        RETURNING application_id";

$result = pg_query_params(
    $conn,
    $sql,
    [
        $student_id,
        $status_id,
        $application_type_id,
        $subject,
        $description
    ]
);

if (!$result) {

    pg_query($conn, "ROLLBACK");

    die("Failed to submit application.");
}


$application = pg_fetch_assoc(
    $result
);

$application_id =
    $application["application_id"];


/* =========================
   SAVE ATTACHMENT
   ========================= */

if (
    $file &&
    $file["error"] !== UPLOAD_ERR_NO_FILE
) {

    $file_data = file_get_contents(
        $file["tmp_name"]
    );

    if ($file_data === false) {

        pg_query($conn, "ROLLBACK");

        die("Unable to read attachment.");
    }


    /* Convert Binary Data for BYTEA */

    $file_data = pg_escape_bytea(
        $conn,
        $file_data
    );


    $file_name = basename(
        $file["name"]
    );


    $attachment_sql = "INSERT INTO attachments
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
                           'APPLICATION',
                           $2,
                           $3,
                           $4,
                           $5,
                           $6
                       )";

    $attachment_result = pg_query_params(
        $conn,
        $attachment_sql,
        [
            $user_id,
            $application_id,
            $file_name,
            $file_type,
            $file["size"],
            $file_data
        ]
    );

    if (!$attachment_result) {

        pg_query($conn, "ROLLBACK");

        die("Failed to save attachment.");
    }
}


/* =========================
   COMMIT
   ========================= */

pg_query($conn, "COMMIT");


/* =========================
   SUCCESS
   ========================= */

header(
    "Location: ../student/applications.php?section=success&id=" .
    $application_id
);

exit;

?>