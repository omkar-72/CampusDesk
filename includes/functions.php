<?php

/*
 * CampusDesk
 * Common Functions
 */

function cleanInput($data)
{
    return trim($data);
}

function isValidId($id)
{
    return is_numeric($id) && $id > 0;
}

function escape($data)
{
    return htmlspecialchars(
        $data,
        ENT_QUOTES,
        "UTF-8"
    );
}


/* Student */

function getStudentId($conn, $user_id)
{
    $sql = "SELECT student_id
            FROM students
            WHERE user_id = $1";

    $result = pg_query_params(
        $conn,
        $sql,
        [$user_id]
    );

    if (!$result || pg_num_rows($result) === 0) {
        return false;
    }

    $student = pg_fetch_assoc($result);

    return $student["student_id"];
}


/* Categories and Types */

function getGrievanceCategories($conn)
{
    $sql = "SELECT category_id, category_name
            FROM grievance_categories
            WHERE status = TRUE
            ORDER BY category_name";

    return pg_query($conn, $sql);
}

function getSuggestionCategories($conn)
{
    $sql = "SELECT category_id, category_name
            FROM suggestion_categories
            WHERE status = TRUE
            ORDER BY category_name";
            
    return pg_query($conn, $sql);
}

function getApplicationTypes($conn)
{
    $sql = "SELECT application_type_id, type_name
            FROM application_types
            WHERE status = TRUE
            ORDER BY type_name";

    return pg_query($conn, $sql);
}


/* Status */

function getStatusId($conn, $status_name, $module_type)
{
    $sql = "SELECT status_id
            FROM statuses
            WHERE status_name = $1
            AND module_type = $2
            AND status = TRUE";

    $result = pg_query_params(
        $conn,
        $sql,
        [
            $status_name,
            $module_type
        ]
    );

    if (!$result || pg_num_rows($result) === 0) {
        return false;
    }

    $status = pg_fetch_assoc($result);

    return $status["status_id"];
}


/* Formatting */

function formatGrievanceId($grievance_id)
{
    return "GRV-" . str_pad(
        $grievance_id,
        3,
        "0",
        STR_PAD_LEFT
    );
}

function formatSuggestionId($suggestion_id)
{
    return "SUG-" . str_pad(
        $suggestion_id,
        3,
        "0",
        STR_PAD_LEFT
    );
}

function formatDateTime($date)
{
    if (empty($date)) {
        return "";
    }

    return date(
        "d-m-Y h:i A",
        strtotime($date)
    );
}


/* Attachment */

function isAllowedFileType($file_type)
{
    $allowed_types = [
        "image/jpeg",
        "image/png",
        "application/pdf"
    ];

    return in_array(
        $file_type,
        $allowed_types,
        true
    );
}

function isAllowedFileSize($file_size)
{
    $max_size = 5 * 1024 * 1024;

    return $file_size <= $max_size;
}

function getAttachment(
    $conn,
    $user_id,
    $module_type,
    $reference_id
) {
    $sql = "SELECT
                attachment_id,
                file_name,
                file_type,
                file_size,
                uploaded_at
            FROM attachments
            WHERE user_id = $1
            AND module_type = $2
            AND reference_id = $3
            ORDER BY uploaded_at DESC
            LIMIT 1";

    $result = pg_query_params(
        $conn,
        $sql,
        [
            $user_id,
            $module_type,
            $reference_id
        ]
    );

    if (!$result || pg_num_rows($result) === 0) {
        return false;
    }

    return pg_fetch_assoc($result);
}

?>