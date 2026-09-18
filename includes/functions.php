<?php

/*
 * CampusDesk
 * Common Functions
 */


/* =========================
   CLEAN INPUT
   ========================= */

function cleanInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);

    return $data;
}


/* =========================
   VALIDATE ID
   ========================= */

function isValidId($id)
{
    return filter_var(
        $id,
        FILTER_VALIDATE_INT
    ) !== false && $id > 0;
}


/* =========================
   ESCAPE OUTPUT
   ========================= */

function escape($data)
{
    return htmlspecialchars(
        $data ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================
   GET STUDENT ID
   ========================= */

function getStudentId($conn, $user_id)
{
    if (!isValidId($user_id)) {
        return false;
    }

    $result = pg_query_params(
        $conn,
        "SELECT student_id
         FROM students
         WHERE user_id = $1",
        [$user_id]
    );

    if (
        !$result ||
        pg_num_rows($result) === 0
    ) {
        return false;
    }

    $student = pg_fetch_assoc($result);

    return (int) $student["student_id"];
}


/* =========================
   GET STUDENT PROFILE
   ========================= */

function getStudentProfile($conn, $user_id)
{
    if (!isValidId($user_id)) {
        return false;
    }

    $sql = "SELECT
                u.user_id,
                u.email,
                u.mobile_number,
                u.account_status,
                u.last_login,
                u.created_at,
                u.updated_at,
                s.student_id,
                s.full_name,
                s.prn,
                s.roll_no,
                s.course,
                s.year,
                s.semester,
                s.division,
                s.address,
                s.department_id,
                d.department_name
            FROM users u
            INNER JOIN students s
                ON u.user_id = s.user_id
            LEFT JOIN departments d
                ON s.department_id = d.department_id
            WHERE u.user_id = $1";

    $result = pg_query_params(
        $conn,
        $sql,
        [$user_id]
    );

    if (
        !$result ||
        pg_num_rows($result) === 0
    ) {
        return false;
    }

    return pg_fetch_assoc($result);
}


/* =========================
   GET PROFILE PHOTO
   ========================= */

function getProfilePhoto($conn, $user_id)
{
    if (!isValidId($user_id)) {
        return false;
    }

    $result = pg_query_params(
        $conn,
        "SELECT profile_photo
         FROM users
         WHERE user_id = $1",
        [$user_id]
    );

    if (
        !$result ||
        pg_num_rows($result) === 0
    ) {
        return false;
    }

    $row = pg_fetch_assoc($result);

    return $row["profile_photo"] ?? false;
}


/* =========================
   PROFILE PHOTO TYPE
   ========================= */

function isAllowedProfilePhotoType($file_type)
{
    $allowed_types = [
        "image/jpeg",
        "image/png"
    ];

    return in_array(
        strtolower($file_type),
        $allowed_types,
        true
    );
}


/* =========================
   PROFILE PHOTO SIZE
   ========================= */

function isAllowedProfilePhotoSize($file_size)
{
    $max_size = 5 * 1024 * 1024;

    return $file_size > 0 &&
           $file_size <= $max_size;
}


/* =========================
   VALIDATE MOBILE NUMBER
   ========================= */

function isValidMobileNumber($mobile)
{
    if ($mobile === "") {
        return true;
    }

    return preg_match(
        "/^[0-9]{10,15}$/",
        $mobile
    );
}


/* =========================
   VALIDATE PASSWORD
   ========================= */

function isValidPassword($password)
{
    return strlen($password) >= 8;
}


/* =========================
   GET GRIEVANCE CATEGORIES
   ========================= */

function getGrievanceCategories($conn)
{
    $result = pg_query(
        $conn,
        "SELECT
            category_id,
            category_name
         FROM grievance_categories
         WHERE status = TRUE
         ORDER BY category_name"
    );

    if (!$result) {
        return [];
    }

    return pg_fetch_all($result) ?: [];
}


/* =========================
   GET SUGGESTION CATEGORIES
   ========================= */

function getSuggestionCategories($conn)
{
    $result = pg_query(
        $conn,
        "SELECT
            category_id,
            category_name
         FROM suggestion_categories
         WHERE status = TRUE
         ORDER BY category_name"
    );

    if (!$result) {
        return [];
    }

    return pg_fetch_all($result) ?: [];
}


/* =========================
   GET APPLICATION TYPES
   ========================= */

function getApplicationTypes($conn)
{
    $result = pg_query(
        $conn,
        "SELECT
            application_type_id,
            type_name
         FROM application_types
         WHERE status = TRUE
         ORDER BY type_name"
    );

    if (!$result) {
        return [];
    }

    return pg_fetch_all($result) ?: [];
}


/* =========================
   GET STATUS
   ========================= */

function getStatusId(
    $conn,
    $status_name,
    $module_type
) {
    $result = pg_query_params(
        $conn,
        "SELECT status_id
         FROM statuses
         WHERE status_name = $1
         AND module_type = $2
         AND status = TRUE",
        [
            $status_name,
            $module_type
        ]
    );

    if (
        !$result ||
        pg_num_rows($result) === 0
    ) {
        return false;
    }

    $row = pg_fetch_assoc($result);

    return (int) $row["status_id"];
}


/* =========================
   FORMAT GRIEVANCE ID
   ========================= */

function formatGrievanceId($id)
{
    return "GRV-" .
        str_pad(
            $id,
            5,
            "0",
            STR_PAD_LEFT
        );
}


/* =========================
   FORMAT SUGGESTION ID
   ========================= */

function formatSuggestionId($id)
{
    return "SGT-" .
        str_pad(
            $id,
            5,
            "0",
            STR_PAD_LEFT
        );
}


/* =========================
   FORMAT APPLICATION ID
   ========================= */

function formatApplicationId($id)
{
    return "APP-" .
        str_pad(
            $id,
            5,
            "0",
            STR_PAD_LEFT
        );
}


/* =========================
   FORMAT DATE TIME
   ========================= */

function formatDateTime($datetime)
{
    if (empty($datetime)) {
        return "-";
    }

    return date(
        "d M Y, h:i A",
        strtotime($datetime)
    );
}


/* =========================
   ATTACHMENT VALIDATION
   ========================= */

function isAllowedAttachmentType($file_type)
{
    $allowed_types = [
        "image/jpeg",
        "image/png",
        "application/pdf"
    ];

    return in_array(
        strtolower($file_type),
        $allowed_types,
        true
    );
}


/* =========================
   ATTACHMENT SIZE
   ========================= */

function isAllowedAttachmentSize($file_size)
{
    $max_size = 5 * 1024 * 1024;

    return $file_size > 0 &&
           $file_size <= $max_size;
}


/* =========================
   GET ATTACHMENT
   ========================= */

function getAttachment(
    $conn,
    $attachment_id
) {
    if (!isValidId($attachment_id)) {
        return false;
    }

    $result = pg_query_params(
        $conn,
        "SELECT
            attachment_id,
            user_id,
            module_type,
            reference_id,
            file_name,
            file_type,
            file_size,
            file_data,
            uploaded_at
         FROM attachments
         WHERE attachment_id = $1",
        [$attachment_id]
    );

    if (
        !$result ||
        pg_num_rows($result) === 0
    ) {
        return false;
    }

    return pg_fetch_assoc($result);
}
/* =========================================================
   GET AUTHORITY PROFILE
   ========================================================= */

function getAuthorityProfile($conn, $user_id)
{
    $query = "
        SELECT
            u.user_id,
            a.name AS full_name,
            u.email,
            u.mobile_number,
            u.created_at,
            u.last_login,
            u.account_status,
            u.profile_photo,

            d.department_name,
            a.designation,

            0 AS grievance_count,
            0 AS suggestion_count,
            0 AS application_count

        FROM users u

        INNER JOIN authorities a
            ON a.user_id = u.user_id

        LEFT JOIN departments d
            ON a.department_id = d.department_id

        WHERE u.user_id = $1

        LIMIT 1;
    ";

    $result = pg_query_params($conn, $query, [$user_id]);

    if (!$result || pg_num_rows($result) === 0) {
        return null;
    }

    return pg_fetch_assoc($result);
}
?>