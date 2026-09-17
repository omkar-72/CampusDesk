<?php

/*
 * CampusDesk
 * Suggestion Actions
 */

require_once "../config/database.php";
require_once "../includes/auth.php";
require_once "../includes/functions.php";

requireStudent();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../student/suggestions.php");
    exit;
}


/* Delete Suggestion */

if (isset($_POST["delete_suggestion"])) {

    $suggestion_id = $_POST["suggestion_id"] ?? "";

    if (!isValidId($suggestion_id)) {
        die("Invalid suggestion ID");
    }

    $user_id = getLoggedInUserId();

    $student_id = getStudentId(
        $conn,
        $user_id
    );

    if (!$student_id) {
        die("Student not found");
    }

    $sql = "SELECT suggestion_id
            FROM suggestions
            WHERE suggestion_id = $1
            AND student_id = $2
            AND status_id = (
                SELECT status_id
                FROM statuses
                WHERE status_name = 'New'
                AND module_type = 'SUGGESTION'
                AND status = TRUE
            )";

    $result = pg_query_params(
        $conn,
        $sql,
        [
            $suggestion_id,
            $student_id
        ]
    );

    if (!$result || pg_num_rows($result) === 0) {
        die("Suggestion cannot be deleted");
    }

    pg_query($conn, "BEGIN");

    $delete_attachment_sql = "DELETE FROM attachments
                              WHERE user_id = $1
                              AND module_type = 'SUGGESTION'
                              AND reference_id = $2";

    $attachment_result = pg_query_params(
        $conn,
        $delete_attachment_sql,
        [
            $user_id,
            $suggestion_id
        ]
    );

    if (!$attachment_result) {
        pg_query($conn, "ROLLBACK");
        die("Failed to delete attachment");
    }

    $delete_suggestion_sql = "DELETE FROM suggestions
                              WHERE suggestion_id = $1
                              AND student_id = $2";

    $suggestion_result = pg_query_params(
        $conn,
        $delete_suggestion_sql,
        [
            $suggestion_id,
            $student_id
        ]
    );

    if (!$suggestion_result) {
        pg_query($conn, "ROLLBACK");
        die("Failed to delete suggestion");
    }

    pg_query($conn, "COMMIT");

    header(
        "Location: ../student/suggestions.php?section=my"
    );
    exit;
}


/* Submit Suggestion */

$title = cleanInput(
    $_POST["title"] ?? ""
);

$category_id = $_POST["category_id"] ?? "";

$description = cleanInput(
    $_POST["description"] ?? ""
);

$declaration = $_POST["declaration"] ?? "";


/* Basic Validation */

if ($title === "") {
    die("Title is required");
}

if (strlen($title) > 200) {
    die("Title must not exceed 200 characters");
}

if (!isValidId($category_id)) {
    die("Invalid category");
}

if ($description === "") {
    die("Description is required");
}

if ($declaration !== "1") {
    die("Please accept the declaration");
}


/* Student */

$user_id = getLoggedInUserId();

$student_id = getStudentId(
    $conn,
    $user_id
);

if (!$student_id) {
    die("Student not found");
}


/* Category */

$category_sql = "SELECT category_id
                 FROM suggestion_categories
                 WHERE category_id = $1
                 AND status = TRUE";

$category_result = pg_query_params(
    $conn,
    $category_sql,
    [$category_id]
);

if (
    !$category_result ||
    pg_num_rows($category_result) === 0
) {
    die("Invalid suggestion category");
}


/* Status */

$status_id = getStatusId(
    $conn,
    "New",
    "SUGGESTION"
);

if (!$status_id) {
    die("Suggestion status not found");
}


/* Attachment Validation */

$file = $_FILES["attachment"] ?? null;

if ($file && $file["error"] !== UPLOAD_ERR_NO_FILE) {

    if ($file["error"] !== UPLOAD_ERR_OK) {
        die("File upload failed");
    }

    if (!isAllowedFileSize($file["size"])) {
        die("File size must not exceed 5 MB");
    }

    $file_type = "";

    if (class_exists("finfo")) {

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $file_type = $finfo->file(
            $file["tmp_name"]
        );

    } else {

        $file_type = $file["type"];
    }

    if (!isAllowedFileType($file_type)) {
        die("Only JPG, PNG and PDF files are allowed");
    }
}


/* Insert Suggestion */

pg_query($conn, "BEGIN");

$sql = "INSERT INTO suggestions
        (
            student_id,
            category_id,
            status_id,
            title,
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
        RETURNING suggestion_id";

$result = pg_query_params(
    $conn,
    $sql,
    [
        $student_id,
        $category_id,
        $status_id,
        $title,
        $description
    ]
);

if (!$result) {
    pg_query($conn, "ROLLBACK");
    die("Failed to submit suggestion");
}

$suggestion = pg_fetch_assoc($result);

$suggestion_id = $suggestion["suggestion_id"];


/* Save Attachment */

if ($file && $file["error"] !== UPLOAD_ERR_NO_FILE) {

    $file_data = file_get_contents(
        $file["tmp_name"]
    );

    if ($file_data === false) {
        pg_query($conn, "ROLLBACK");
        die("Failed to read attachment");
    }

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
                           'SUGGESTION',
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
            $suggestion_id,
            $file_name,
            $file_type,
            $file["size"],
            $file_data
        ]
    );

    if (!$attachment_result) {
        pg_query($conn, "ROLLBACK");
        die("Failed to save attachment");
    }
}


/* Commit */

pg_query($conn, "COMMIT");


/* Success */

header(
    "Location: ../student/suggestions.php?section=success&id=" .
    $suggestion_id
);

exit;

?>