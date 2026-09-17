<?php

require_once "../includes/auth.php";
requireStudent();

require_once "../config/database.php";
require_once "../includes/functions.php";

$user_id = getLoggedInUserId();


/* =========================
   POST REQUEST ONLY
   ========================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../student/profile.php");
    exit;
}


$action = $_POST["action"] ?? "";


/* =========================
   UPDATE PROFILE
   ========================= */

if ($action === "update_profile") {

    $full_name = cleanInput(
        $_POST["full_name"] ?? ""
    );

    $mobile = cleanInput(
        $_POST["mobile"] ?? ""
    );

    $address = cleanInput(
        $_POST["address"] ?? ""
    );


    /* Validate Full Name */

    if ($full_name === "") {

        header(
            "Location: ../student/profile.php?error=invalid_name"
        );

        exit;
    }


    /* Validate Mobile Number */

    if (!isValidMobileNumber($mobile)) {

        header(
            "Location: ../student/profile.php?error=invalid_mobile"
        );

        exit;
    }


    /* Get Profile */

    $profile = getStudentProfile(
        $conn,
        $user_id
    );


    if (!$profile) {

        header(
            "Location: ../student/profile.php?error=profile_not_found"
        );

        exit;
    }


    /* =========================
       DATABASE TRANSACTION
       ========================= */

    pg_query(
        $conn,
        "BEGIN"
    );


    try {

        /* Update Full Name */

        $result = pg_query_params(
            $conn,
            "UPDATE students
             SET full_name = $1
             WHERE user_id = $2",
            [
                $full_name,
                $user_id
            ]
        );


        if (!$result) {

            throw new Exception(
                "Failed to update full name."
            );
        }


        /* Update Mobile Number */

        $result = pg_query_params(
            $conn,
            "UPDATE users
             SET mobile_number = $1,
                 updated_at = CURRENT_TIMESTAMP
             WHERE user_id = $2",
            [
                $mobile !== "" ? $mobile : null,
                $user_id
            ]
        );


        if (!$result) {

            throw new Exception(
                "Failed to update mobile number."
            );
        }


        /* Update Address */

        $result = pg_query_params(
            $conn,
            "UPDATE students
             SET address = $1
             WHERE user_id = $2",
            [
                $address !== "" ? $address : null,
                $user_id
            ]
        );


        if (!$result) {

            throw new Exception(
                "Failed to update address."
            );
        }


        /* Commit */

        pg_query(
            $conn,
            "COMMIT"
        );


        header(
            "Location: ../student/profile.php?success=profile_updated"
        );

        exit;


    } catch (Exception $e) {

        pg_query(
            $conn,
            "ROLLBACK"
        );


        header(
            "Location: ../student/profile.php?error="
            . urlencode($e->getMessage())
        );

        exit;
    }
}


/* =========================
   UPDATE PROFILE PHOTO
   ========================= */

if ($action === "update_photo") {

    if (
        !isset($_FILES["profile_photo"]) ||
        $_FILES["profile_photo"]["error"] === UPLOAD_ERR_NO_FILE
    ) {

        header(
            "Location: ../student/profile.php?error=no_photo"
        );

        exit;
    }


    $file = $_FILES["profile_photo"];


    /* Upload Error */

    if ($file["error"] !== UPLOAD_ERR_OK) {

        header(
            "Location: ../student/profile.php?error=photo_upload_failed"
        );

        exit;
    }


    /* File Size */

    if (
        !isAllowedProfilePhotoSize(
            $file["size"]
        )
    ) {

        header(
            "Location: ../student/profile.php?error=photo_size"
        );

        exit;
    }


    /* Detect MIME Type */

    if (function_exists("finfo_open")) {

        $finfo = finfo_open(
            FILEINFO_MIME_TYPE
        );

        $file_type = finfo_file(
            $finfo,
            $file["tmp_name"]
        );

        finfo_close(
            $finfo
        );

    } else {

        $file_type = $file["type"];
    }


    /* Validate File Type */

    if (
        !isAllowedProfilePhotoType(
            $file_type
        )
    ) {

        header(
            "Location: ../student/profile.php?error=photo_type"
        );

        exit;
    }


    /* Read File */

    $file_data = file_get_contents(
        $file["tmp_name"]
    );


    if ($file_data === false) {

        header(
            "Location: ../student/profile.php?error=photo_read_failed"
        );

        exit;
    }


    /* Convert Binary Data */

    $file_data = pg_escape_bytea(
        $conn,
        $file_data
    );


    /* Save Photo */

    $result = pg_query_params(
        $conn,
        "UPDATE users
         SET profile_photo = $1,
             updated_at = CURRENT_TIMESTAMP
         WHERE user_id = $2",
        [
            $file_data,
            $user_id
        ]
    );


    if (!$result) {

        header(
            "Location: ../student/profile.php?error=photo_update_failed"
        );

        exit;
    }


    header(
        "Location: ../student/profile.php?success=photo_updated"
    );

    exit;
}


/* =========================
   REMOVE PROFILE PHOTO
   ========================= */

if ($action === "remove_photo") {

    $result = pg_query_params(
        $conn,
        "UPDATE users
         SET profile_photo = NULL,
             updated_at = CURRENT_TIMESTAMP
         WHERE user_id = $1",
        [
            $user_id
        ]
    );


    if (!$result) {

        header(
            "Location: ../student/profile.php?error=photo_remove_failed"
        );

        exit;
    }


    header(
        "Location: ../student/profile.php?success=photo_removed"
    );

    exit;
}


/* =========================
   CHANGE PASSWORD
   ========================= */

if ($action === "change_password") {

    $current_password =
        $_POST["current_password"] ?? "";

    $new_password =
        $_POST["new_password"] ?? "";

    $confirm_password =
        $_POST["confirm_password"] ?? "";


    /* Required Fields */

    if (
        $current_password === "" ||
        $new_password === "" ||
        $confirm_password === ""
    ) {

        header(
            "Location: ../student/profile.php?error=password_fields_required"
        );

        exit;
    }


    /* Password Length */

    if (
        !isValidPassword(
            $new_password
        )
    ) {

        header(
            "Location: ../student/profile.php?error=password_length"
        );

        exit;
    }


    /* Password Match */

    if (
        $new_password !== $confirm_password
    ) {

        header(
            "Location: ../student/profile.php?error=password_mismatch"
        );

        exit;
    }


    /* Get Current Password */

    $result = pg_query_params(
        $conn,
        "SELECT password
         FROM users
         WHERE user_id = $1
         AND account_status = TRUE",
        [
            $user_id
        ]
    );


    if (
        !$result ||
        pg_num_rows($result) === 0
    ) {

        header(
            "Location: ../student/profile.php?error=user_not_found"
        );

        exit;
    }


    $user = pg_fetch_assoc(
        $result
    );


    /* Verify Current Password */

    if (
        !password_verify(
            $current_password,
            $user["password"]
        )
    ) {

        header(
            "Location: ../student/profile.php?error=current_password"
        );

        exit;
    }


    /* Prevent Same Password */

    if (
        password_verify(
            $new_password,
            $user["password"]
        )
    ) {

        header(
            "Location: ../student/profile.php?error=same_password"
        );

        exit;
    }


    /* Hash New Password */

    $hashed_password = password_hash(
        $new_password,
        PASSWORD_DEFAULT
    );


    /* Update Password */

    $result = pg_query_params(
        $conn,
        "UPDATE users
         SET password = $1,
             updated_at = CURRENT_TIMESTAMP
         WHERE user_id = $2",
        [
            $hashed_password,
            $user_id
        ]
    );


    if (!$result) {

        header(
            "Location: ../student/profile.php?error=password_update_failed"
        );

        exit;
    }


    header(
        "Location: ../student/profile.php?success=password_changed"
    );

    exit;
}


/* =========================
   INVALID ACTION
   ========================= */

header(
    "Location: ../student/profile.php?error=invalid_action"
);

exit;

?>