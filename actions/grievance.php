<?php

require_once "../includes/auth.php";
requireStudent();

require_once "../config/database.php";
require_once "../includes/functions.php";

$user_id = getLoggedInUserId();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../student/grievances.php");
    exit;
}

$action = $_POST["action"] ?? "";


/* =========================
   DELETE GRIEVANCE
   ========================= */

if ($action === "delete") {

    $grievance_id = $_POST["grievance_id"] ?? "";

    if (!isValidId($grievance_id)) {
        die("Invalid grievance ID.");
    }

    $student_id = getStudentId($conn, $user_id);

    if (!$student_id) {
        die("Student record not found.");
    }


    /* Check Grievance */

    $result = pg_query_params(
        $conn,
        "SELECT g.grievance_id
         FROM grievances g
         JOIN statuses s
             ON g.status_id = s.status_id
         WHERE g.grievance_id = $1
         AND g.student_id = $2
         AND s.status_name = 'New'
         AND s.module_type = 'GRIEVANCE'",
        [
            $grievance_id,
            $student_id
        ]
    );

    if (!$result || pg_num_rows($result) === 0) {
        die("Grievance cannot be deleted.");
    }


    /* Begin Transaction */

    if (!pg_query($conn, "BEGIN")) {
        die("Unable to start transaction.");
    }


    /* Delete Attachment */

    $result = pg_query_params(
        $conn,
        "DELETE FROM attachments
         WHERE user_id = $1
         AND module_type = 'GRIEVANCE'
         AND reference_id = $2",
        [
            $user_id,
            $grievance_id
        ]
    );

    if (!$result) {
        pg_query($conn, "ROLLBACK");
        die("Failed to delete attachment.");
    }


    /* Delete Grievance */

    $result = pg_query_params(
        $conn,
        "DELETE FROM grievances
         WHERE grievance_id = $1
         AND student_id = $2",
        [
            $grievance_id,
            $student_id
        ]
    );

    if (!$result) {
        pg_query($conn, "ROLLBACK");
        die("Failed to delete grievance.");
    }


    /* Complete Transaction */

    if (!pg_query($conn, "COMMIT")) {
        die("Failed to complete deletion.");
    }

    header(
        "Location: ../student/grievances.php?section=my"
    );

    exit;
}


/* =========================
   SUBMIT GRIEVANCE
   ========================= */

if ($action === "submit") {

    $title = cleanInput($_POST["title"] ?? "");
    $category_id = $_POST["category_id"] ?? "";
    $description = cleanInput($_POST["description"] ?? "");
    $anonymous_status = $_POST["anonymous_status"] ?? "";
    $declaration = $_POST["declaration"] ?? "";


    /* Validate Input */

    if ($title === "" || strlen($title) > 200) {
        die("Please enter a valid title.");
    }

    if (!isValidId($category_id)) {
        die("Invalid category.");
    }

    if ($description === "") {
        die("Please enter grievance description.");
    }

    if ($anonymous_status !== "0" && $anonymous_status !== "1") {
        die("Please select identity preference.");
    }

    if ($declaration !== "1") {
        die("Please accept the declaration.");
    }


    /* Get Student */

    $student_id = getStudentId($conn, $user_id);

    if (!$student_id) {
        die("Student record not found.");
    }


    /* Check Category */

    $result = pg_query_params(
        $conn,
        "SELECT category_id
         FROM grievance_categories
         WHERE category_id = $1
         AND status = TRUE",
        [$category_id]
    );

    if (!$result || pg_num_rows($result) === 0) {
        die("Invalid grievance category.");
    }


    /* Get New Status */

    $status_id = getStatusId(
        $conn,
        "New",
        "GRIEVANCE"
    );

    if (!$status_id) {
        die("Grievance status not found.");
    }


    /* Begin Transaction */

    if (!pg_query($conn, "BEGIN")) {
        die("Unable to start transaction.");
    }


    /* Insert Grievance */

    $result = pg_query_params(
        $conn,
        "INSERT INTO grievances
        (
            student_id,
            category_id,
            status_id,
            title,
            description,
            anonymous_status
        )
        VALUES
        (
            $1, $2, $3, $4, $5, $6
        )
        RETURNING grievance_id",
        [
            $student_id,
            $category_id,
            $status_id,
            $title,
            $description,
            $anonymous_status
        ]
    );

    if (!$result) {
        pg_query($conn, "ROLLBACK");
        die("Failed to submit grievance.");
    }

    $grievance = pg_fetch_assoc($result);

    $grievance_id = $grievance["grievance_id"];


    /* Optional Attachment */

    if (
        isset($_FILES["attachment"]) &&
        $_FILES["attachment"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES["attachment"];


        if ($file["error"] !== UPLOAD_ERR_OK) {
            pg_query($conn, "ROLLBACK");
            die("File upload failed.");
        }


        if (!isAllowedFileSize($file["size"])) {
            pg_query($conn, "ROLLBACK");
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
            pg_query($conn, "ROLLBACK");
            die("Only JPG, PNG and PDF files are allowed.");
        }


        /* Read File */

        $file_data = file_get_contents(
            $file["tmp_name"]
        );

        if ($file_data === false) {
            pg_query($conn, "ROLLBACK");
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
                "GRIEVANCE",
                $grievance_id,
                basename($file["name"]),
                $file_type,
                $file["size"],
                $file_data
            ]
        );

        if (!$result) {
            pg_query($conn, "ROLLBACK");
            die("Failed to save attachment.");
        }
    }


    /* Complete Transaction */

    if (!pg_query($conn, "COMMIT")) {
        die("Failed to complete grievance submission.");
    }


    /* Redirect to Success Page */

    header(
        "Location: ../student/grievances.php?section=success&id=" .
        $grievance_id
    );

    exit;
}


die("Invalid action.");

?>