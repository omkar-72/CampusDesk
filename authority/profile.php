<?php

/*
|--------------------------------------------------------------------------
| CAMPUSDESK - AUTHORITY PROFILE
|--------------------------------------------------------------------------
| This page allows an authenticated authority to:
|
| - View profile information
| - View and update profile photo
| - View personal information
| - Update mobile number
| - Change password
| - View department and designation
| - View activity statistics
| - View account information
| - Logout
|
| Administrator-managed fields:
| - Full Name
| - Email
| - Department
| - Designation
|
| These fields cannot be changed by the authority.
|--------------------------------------------------------------------------
*/


/* =========================================================
   AUTHENTICATION
   ========================================================= */

require_once "../includes/auth.php";

/*
 * Only authenticated authority users can access this page.
 */
requireAuthority();


/* =========================================================
   DATABASE AND COMMON FUNCTIONS
   ========================================================= */

require_once "../config/database.php";
require_once "../includes/functions.php";


/* =========================================================
   GET LOGGED-IN USER
   ========================================================= */

$user_id = getLoggedInUserId();


/* =========================================================
   MESSAGE VARIABLES
   ========================================================= */

$success_message = "";
$error_message = "";


/* =========================================================
   HANDLE POST ACTIONS
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
     * Get the requested action.
     *
     * Multiple forms are present on this page, therefore
     * every form sends a different action value.
     */
    $action = $_POST["action"] ?? "";


    /* =====================================================
       UPDATE PERSONAL PROFILE
       ===================================================== */

    if ($action === "update_profile") {

        $full_name = trim($_POST["full_name"] ?? "");
        $mobile = trim($_POST["mobile"] ?? "");

        /* Validate full name */
        if ($full_name === "") {
            $error_message = "Full name is required.";
        }

        /* Validate mobile number */
        if ($error_message === "" && $mobile !== "") {
            if (!preg_match('/^[0-9+\-\s()]{7,15}$/', $mobile)) {
                $error_message = "Please enter a valid mobile number.";
            }
        }

        if ($error_message === "") {

            /*
         * Update authority name and user mobile number.
         */
            $update_query = "
            UPDATE authorities
            SET name = $1
            WHERE user_id = $2
        ";

            $update_result = pg_query_params(
                $conn,
                $update_query,
                [
                    $full_name,
                    $user_id
                ]
            );

            if ($update_result) {

                $update_user_query = "
                UPDATE users
                SET
                    mobile_number = $1,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = $2
            ";

                $update_user_result = pg_query_params(
                    $conn,
                    $update_user_query,
                    [
                        $mobile !== "" ? $mobile : null,
                        $user_id
                    ]
                );

                if ($update_user_result) {
                    $success_message =
                        "Profile information saved successfully.";
                } else {
                    $error_message =
                        "Unable to save profile information. Please try again.";
                }
            } else {
                $error_message =
                    "Unable to save profile information. Please try again.";
            }
        }
    }


    /* =====================================================
       UPDATE PROFILE PHOTO
       ===================================================== */ elseif ($action === "update_photo") {

        /*
         * Check whether a file was selected.
         */
        if (
            !isset($_FILES["profile_photo"]) ||
            $_FILES["profile_photo"]["error"] !== UPLOAD_ERR_OK
        ) {

            $error_message =
                "Please select a profile photo.";
        } else {

            $photo = $_FILES["profile_photo"];


            /*
             * Maximum allowed size:
             * 5 MB
             */
            $max_size = 5 * 1024 * 1024;


            /*
             * Validate file size.
             */
            if ($photo["size"] > $max_size) {

                $error_message =
                    "Profile photo must not exceed 5 MB.";
            } else {

                /*
                 * Detect the actual image type.
                 *
                 * getimagesize() is used instead of finfo_open()
                 * because the PHP Fileinfo extension is not
                 * available in this installation.
                 */
                $image_info = @getimagesize(
                    $photo["tmp_name"]
                );

                $detected_type =
                    $image_info["mime"] ?? false;


                /*
                 * Only JPG and PNG are allowed.
                 */
                $allowed_types = [
                    "image/jpeg",
                    "image/png"
                ];


                if (
                    !$detected_type ||
                    !in_array(
                        $detected_type,
                        $allowed_types,
                        true
                    )
                ) {

                    $error_message =
                        "Only JPG or PNG profile photos are allowed.";
                } else {

                    /*
                     * Read the uploaded image.
                     */
                    $photo_data = file_get_contents(
                        $photo["tmp_name"]
                    );


                    if ($photo_data === false) {

                        $error_message =
                            "Unable to read the uploaded photo.";
                    } else {

                        /*
                         * Escape BYTEA data before storing it
                         * in PostgreSQL.
                         */
                        $escaped_photo =
                            pg_escape_bytea(
                                $conn,
                                $photo_data
                            );


                        $photo_query = "
                            UPDATE users
                            SET
                                profile_photo = $1,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE user_id = $2
                        ";


                        $photo_result = pg_query_params(
                            $conn,
                            $photo_query,
                            [
                                $escaped_photo,
                                $user_id
                            ]
                        );


                        if ($photo_result) {

                            $success_message =
                                "Profile photo updated successfully.";
                        } else {

                            $error_message =
                                "Unable to update profile photo.";
                        }
                    }
                }
            }
        }
    }


    /* =====================================================
       CHANGE PASSWORD
       ===================================================== */ elseif ($action === "change_password") {

        /*
         * Get password fields.
         */
        $current_password =
            $_POST["current_password"] ?? "";

        $new_password =
            $_POST["new_password"] ?? "";

        $confirm_password =
            $_POST["confirm_password"] ?? "";


        /*
         * Validate required fields.
         */
        if (
            $current_password === "" ||
            $new_password === "" ||
            $confirm_password === ""
        ) {

            $error_message =
                "Please fill in all password fields.";
        }


        /*
         * New password must contain at least 8 characters.
         */ elseif (strlen($new_password) < 8) {

            $error_message =
                "New password must contain at least 8 characters.";
        }


        /*
         * Confirm password must match.
         */ elseif ($new_password !== $confirm_password) {

            $error_message =
                "New password and confirm password do not match.";
        }


        /*
         * Continue only when basic validation succeeds.
         */
        if ($error_message === "") {

            /*
             * Get the current password hash.
             */
            $password_query = "
                SELECT password
                FROM users
                WHERE user_id = $1
                LIMIT 1
            ";


            $password_result = pg_query_params(
                $conn,
                $password_query,
                [$user_id]
            );


            if (
                !$password_result ||
                pg_num_rows($password_result) === 0
            ) {

                $error_message =
                    "Unable to verify your current password.";
            } else {

                $password_row =
                    pg_fetch_assoc(
                        $password_result
                    );


                /*
                 * Verify the current password.
                 */
                if (
                    !password_verify(
                        $current_password,
                        $password_row["password"]
                    )
                ) {

                    $error_message =
                        "Current password is incorrect.";
                } else {

                    /*
                     * Hash the new password securely.
                     */
                    $new_password_hash =
                        password_hash(
                            $new_password,
                            PASSWORD_DEFAULT
                        );


                    /*
                     * Update password.
                     */
                    $password_update_query = "
                        UPDATE users
                        SET
                            password = $1,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE user_id = $2
                    ";


                    $password_update_result =
                        pg_query_params(
                            $conn,
                            $password_update_query,
                            [
                                $new_password_hash,
                                $user_id
                            ]
                        );


                    if ($password_update_result) {

                        $success_message =
                            "Password changed successfully.";
                    } else {

                        $error_message =
                            "Unable to change password. Please try again.";
                    }
                }
            }
        }
    }
}


/* =========================================================
   LOAD AUTHORITY PROFILE
   ========================================================= */

/*
 * Load the authority profile directly.
 *
 * This query retrieves:
 * - User information
 * - Profile photo
 * - Authority information
 * - Department
 * - Account information
 */
$profile_query = "
    SELECT
        u.user_id,
        u.email,
        u.mobile_number,
        u.created_at,
        u.last_login,
        u.account_status,
        u.profile_photo,

        a.authority_id,
        a.name AS full_name,
        a.designation,
        a.status AS authority_status,

        d.department_name

    FROM users u

    INNER JOIN authorities a
        ON a.user_id = u.user_id

    LEFT JOIN departments d
        ON a.department_id = d.department_id

    WHERE u.user_id = $1

    LIMIT 1
";


$profile_result = pg_query_params(
    $conn,
    $profile_query,
    [$user_id]
);


/*
 * Stop if the authority profile cannot be found.
 */
if (
    !$profile_result ||
    pg_num_rows($profile_result) === 0
) {

    die("Authority profile not found.");
}


/*
 * Convert PostgreSQL result into an associative array.
 */
$profile = pg_fetch_assoc(
    $profile_result
);


/* =========================================================
   PROFILE PHOTO
   ========================================================= */

$profile_photo =
    $profile["profile_photo"] ?? null;

$profile_photo_type =
    "image/jpeg";

$profile_photo_data =
    null;


/*
 * Convert PostgreSQL BYTEA profile photo into binary data.
 */
if ($profile_photo) {

    $profile_photo_data =
        pg_unescape_bytea(
            $profile_photo
        );


    /*
     * Detect the actual image MIME type.
     */
    if (
        $profile_photo_data &&
        function_exists("finfo_open")
    ) {

        $finfo = finfo_open(
            FILEINFO_MIME_TYPE
        );


        if ($finfo) {

            $detected_type =
                finfo_buffer(
                    $finfo,
                    $profile_photo_data
                );


            finfo_close($finfo);


            /*
             * Only allow JPEG and PNG.
             */
            if (
                in_array(
                    $detected_type,
                    [
                        "image/jpeg",
                        "image/png"
                    ],
                    true
                )
            ) {

                $profile_photo_type =
                    $detected_type;
            }
        }
    }
}


/* =========================================================
   PROFILE VALUES
   ========================================================= */

$full_name =
    $profile["full_name"] ?? "Authority";

$email =
    $profile["email"] ?? "-";

$department =
    $profile["department_name"] ?? "-";

$designation =
    $profile["designation"] ?? "-";

$mobile =
    $profile["mobile_number"] ?? "";


/* =========================================================
   ACTIVITY COUNTS
   ========================================================= */

/*
 * Count grievances assigned to this authority.
 */
$grievance_query = "
    SELECT COUNT(*) AS total
    FROM grievances
    WHERE assigned_to = $1
";


$grievance_result = pg_query_params(
    $conn,
    $grievance_query,
    [$profile["authority_id"]]
);


$grievance_count = 0;


if ($grievance_result) {

    $grievance_count =
        (int) pg_fetch_result(
            $grievance_result,
            0,
            "total"
        );
}


/*
 * Count suggestions reviewed by this authority.
 */
$suggestion_query = "
    SELECT COUNT(*) AS total
    FROM suggestions
    WHERE reviewed_by = $1
";


$suggestion_result = pg_query_params(
    $conn,
    $suggestion_query,
    [$profile["authority_id"]]
);


$suggestion_count = 0;


if ($suggestion_result) {

    $suggestion_count =
        (int) pg_fetch_result(
            $suggestion_result,
            0,
            "total"
        );
}


/*
 * Count applications assigned to this authority.
 */
$application_query = "
    SELECT COUNT(*) AS total
    FROM applications
    WHERE assigned_to = $1
";


$application_result = pg_query_params(
    $conn,
    $application_query,
    [$profile["authority_id"]]
);


$application_count = 0;


if ($application_result) {

    $application_count =
        (int) pg_fetch_result(
            $application_result,
            0,
            "total"
        );
}


/*
 * Get first letter of authority name.
 */
$avatar_letter = strtoupper(
    substr(
        trim($full_name),
        0,
        1
    )
);


/* =========================================================
   ACCOUNT STATUS
   ========================================================= */

$account_status =
    !empty($profile["account_status"])
    ? "Active"
    : "Inactive";


/* =========================================================
   AUTHORITY STATUS
   ========================================================= */

$authority_status =
    !empty($profile["authority_status"])
    ? "Active"
    : "Inactive";

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <!-- =====================================================
         BASIC PAGE SETTINGS
         ===================================================== -->

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        CampusDesk | Authority Profile
    </title>


    <!-- =====================================================
         FONT AWESOME
         ===================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <!-- =====================================================
         CAMPUSDESK STYLESHEETS
         ===================================================== -->

    <link
        rel="stylesheet"
        href="../css/global.css">

    <link
        rel="stylesheet"
        href="../css/header.css">

    <link
        rel="stylesheet"
        href="../css/navbar.css">

    <link
        rel="stylesheet"
        href="../css/authority-dashboard.css">

    <link
        rel="stylesheet"
        href="../css/authority-profile.css">

</head>


<body>


    <!-- =====================================================
         NAVIGATION
         ===================================================== -->

    <?php include "../includes/navbar.php"; ?>


    <!-- =====================================================
         HEADER
         ===================================================== -->

    <?php include "../includes/header.php"; ?>


    <!-- =====================================================
         DASHBOARD LAYOUT
         ===================================================== -->

    <div class="dashboard-layout">

        <main class="dashboard-content">

            <div class="profile-container">


                <!-- =================================================
                     TOP ACTION BAR
                     ================================================= -->

                <div class="profile-page-top">

                    <a
                        href="dashboard.php"
                        class="profile-back-link">

                        <i class="fa-solid fa-arrow-left"></i>

                        Back to Dashboard

                    </a>


                    <a
                        href="../logout.php"
                        class="logout-btn top-logout-btn">

                        <i class="fa-solid fa-right-from-bracket"></i>

                        Logout

                    </a>

                </div>


                <!-- =================================================
                     SUCCESS MESSAGE
                     ================================================= -->

                <?php if ($success_message !== ""): ?>

                    <div class="profile-message success-message">

                        <i class="fa-solid fa-circle-check"></i>

                        <?= htmlspecialchars(
                            $success_message,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     ERROR MESSAGE
                     ================================================= -->

                <?php if ($error_message !== ""): ?>

                    <div class="profile-message error-message">

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <?= htmlspecialchars(
                            $error_message,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     PAGE TITLE
                     ================================================= -->

                <div class="profile-page-title">

                    <h1>
                        Authority Profile
                    </h1>

                    <p>
                        View and manage your CampusDesk authority account
                    </p>

                </div>


                <!-- =================================================
                     PROFILE BANNER
                     ================================================= -->

                <div class="profile-banner">


                    <!-- Profile Photo -->

                    <div class="profile-avatar-large">

                        <?php if ($profile_photo_data): ?>

                            <img
                                src="data:<?= htmlspecialchars(
                                                $profile_photo_type,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>;base64,<?= base64_encode(
                                                            $profile_photo_data
                                                        ) ?>"
                                alt="Authority Profile Photo"
                                class="profile-photo-large">

                        <?php else: ?>

                            <span class="profile-avatar-letter">

                                <?= htmlspecialchars(
                                    $avatar_letter,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </span>

                        <?php endif; ?>

                    </div>


                    <!-- Basic Profile Information -->

                    <div class="profile-details">

                        <h2>

                            <?= htmlspecialchars(
                                $full_name,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </h2>


                        <p class="profile-designation">

                            <i class="fa-solid fa-user-tie"></i>

                            <?= htmlspecialchars(
                                $designation,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </p>


                        <p class="profile-email">

                            <i class="fa-solid fa-envelope"></i>

                            <?= htmlspecialchars(
                                $email,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </p>


                        <p class="profile-department">

                            <i class="fa-solid fa-building-columns"></i>

                            <?= htmlspecialchars(
                                $department,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </p>


                        <span class="role-badge">

                            <i class="fa-solid fa-shield-halved"></i>

                            AUTHORITY

                        </span>

                    </div>


                    <!-- Account Status -->

                    <div class="profile-status-box">

                        <?php if ($profile["account_status"]): ?>

                            <span class="account-status active-status">

                                <i class="fa-solid fa-circle"></i>

                                Active Account

                            </span>

                        <?php else: ?>

                            <span class="account-status inactive-status">

                                <i class="fa-solid fa-circle"></i>

                                Inactive Account

                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- =================================================
                     PERSONAL INFORMATION + ACTIVITY
                     ================================================= -->

                <div class="profile-grid">


                    <!-- =================================================
                         PERSONAL INFORMATION
                         ================================================= -->

                    <div class="profile-card">

                        <div class="profile-card-header">

                            <div>

                                <h3>
                                    Personal Information
                                </h3>

                                <p>
                                    Your authority profile details
                                </p>

                            </div>


                            <div class="profile-card-icon">

                                <i class="fa-solid fa-user"></i>

                            </div>

                        </div>


                        <!-- Full Name -->

                        <div class="info-row">

                            <span>
                                Full Name
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $full_name,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </strong>

                        </div>


                        <!-- Email -->

                        <div class="info-row">

                            <span>
                                Email
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $email,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </strong>

                        </div>


                        <!-- Department -->

                        <div class="info-row">

                            <span>
                                Department
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $department,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </strong>

                        </div>


                        <!-- Designation -->

                        <div class="info-row">

                            <span>
                                Designation
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $designation,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </strong>

                        </div>


                        <!-- Mobile -->

                        <div class="info-row">

                            <span>
                                Mobile Number
                            </span>

                            <strong>

                                <?= $mobile !== ""
                                    ? htmlspecialchars(
                                        $mobile,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    )
                                    : "-"
                                ?>

                            </strong>

                        </div>

                    </div>


                    <!-- =================================================
                         ACTIVITY OVERVIEW
                         ================================================= -->

                    <div class="profile-card">

                        <div class="profile-card-header">

                            <div>

                                <h3>
                                    Activity Overview
                                </h3>

                                <p>
                                    Your CampusDesk activity
                                </p>

                            </div>


                            <div class="profile-card-icon">

                                <i class="fa-solid fa-chart-column"></i>

                            </div>

                        </div>


                        <!-- Grievances -->

                        <div class="stat-mini">

                            <div class="stat-mini-icon">

                                <i class="fa-solid fa-circle-exclamation"></i>

                            </div>


                            <div>

                                <strong>
                                    <?= (int) $grievance_count ?>
                                </strong>

                                <span>
                                    Grievances Managed
                                </span>

                            </div>

                        </div>


                        <!-- Suggestions -->

                        <div class="stat-mini">

                            <div class="stat-mini-icon">

                                <i class="fa-solid fa-lightbulb"></i>

                            </div>


                            <div>

                                <strong>
                                    <?= (int) $suggestion_count ?>
                                </strong>

                                <span>
                                    Suggestions Reviewed
                                </span>

                            </div>

                        </div>


                        <!-- Applications -->

                        <div class="stat-mini">

                            <div class="stat-mini-icon">

                                <i class="fa-solid fa-file-lines"></i>

                            </div>


                            <div>

                                <strong>
                                    <?= (int) $application_count ?>
                                </strong>

                                <span>
                                    Applications Processed
                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     PROFILE PHOTO
                     ================================================= -->

                <div class="profile-card edit-card">

                    <div class="profile-card-header">

                        <div>

                            <h3>
                                Profile Photo
                            </h3>

                            <p>
                                Update your CampusDesk profile photo
                            </p>

                        </div>


                        <div class="profile-card-icon">

                            <i class="fa-solid fa-camera"></i>

                        </div>

                    </div>


                    <div class="profile-photo-update">


                        <!-- Current Photo -->

                        <div class="profile-photo-preview">

                            <?php if ($profile_photo_data): ?>

                                <img
                                    src="data:<?= htmlspecialchars(
                                                    $profile_photo_type,
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>;base64,<?= base64_encode(
                                                                $profile_photo_data
                                                            ) ?>"
                                    alt="Current Profile Photo"
                                    class="profile-photo-large">

                            <?php else: ?>

                                <span class="profile-avatar-letter">

                                    <?= htmlspecialchars(
                                        $avatar_letter,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- Photo Upload Form -->

                        <form
                            action="profile.php"
                            method="POST"
                            enctype="multipart/form-data"
                            class="profile-photo-form">

                            <input
                                type="hidden"
                                name="action"
                                value="update_photo">


                            <div class="form-group">

                                <label for="profile_photo">

                                    Select Profile Photo

                                </label>


                                <input
                                    type="file"
                                    id="profile_photo"
                                    name="profile_photo"
                                    accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                                    required>


                                <small class="form-help">

                                    JPG or PNG only. Maximum size: 5 MB.

                                </small>

                            </div>


                            <div class="profile-action-row">

                                <button
                                    type="submit"
                                    class="save-btn">

                                    <i class="fa-solid fa-camera"></i>

                                    Update Photo

                                </button>

                            </div>

                        </form>

                    </div>

                </div>


                <!-- =================================================
                     EDIT PERSONAL INFORMATION
                     ================================================= -->

                <div class="profile-card edit-card">

                    <div class="profile-card-header">

                        <div>

                            <h3>
                                Personal Information
                            </h3>

                            <p>
                                Update the information you are allowed to change
                            </p>

                        </div>


                        <div class="profile-card-icon">

                            <i class="fa-solid fa-user-pen"></i>

                        </div>

                    </div>


                    <form
                        action="profile.php"
                        method="POST"
                        id="authorityProfileForm">

                        <input
                            type="hidden"
                            name="action"
                            value="update_profile">


                        <div class="form-grid">


                            <!-- Full Name -->

                            <div class="form-group">

                                <label for="full_name">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    id="full_name"
                                    name="full_name"
                                    value="<?= htmlspecialchars(
                                                $full_name,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                    maxlength="150"
                                    placeholder="Enter full name"
                                    required>

                                <small class="form-help">
                                    You can update your full name.
                                </small>

                            </div>


                            <!-- Email -->

                            <div class="form-group">

                                <label for="email">
                                    Email
                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    value="<?= htmlspecialchars(
                                                $email,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                    readonly
                                    class="admin-managed-field">

                                <small class="form-help admin-managed-message">

                                    <i class="fa-solid fa-lock"></i>

                                    Managed by administrator.

                                </small>

                            </div>


                            <!-- Department -->

                            <div class="form-group">

                                <label for="department">
                                    Department
                                </label>

                                <input
                                    type="text"
                                    id="department"
                                    value="<?= htmlspecialchars(
                                                $department,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                    readonly
                                    class="admin-managed-field">

                                <small class="form-help admin-managed-message">

                                    <i class="fa-solid fa-lock"></i>

                                    Managed by administrator.

                                </small>

                            </div>


                            <!-- Designation -->

                            <div class="form-group">

                                <label for="designation">
                                    Designation
                                </label>

                                <input
                                    type="text"
                                    id="designation"
                                    value="<?= htmlspecialchars(
                                                $designation,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                    readonly
                                    class="admin-managed-field">

                                <small class="form-help admin-managed-message">

                                    <i class="fa-solid fa-lock"></i>

                                    Managed by administrator.

                                </small>

                            </div>


                            <!-- Mobile Number -->

                            <div class="form-group">

                                <label for="mobile">
                                    Mobile Number
                                </label>

                                <input
                                    type="text"
                                    id="mobile"
                                    name="mobile"
                                    value="<?= htmlspecialchars(
                                                $mobile,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                    maxlength="15"
                                    placeholder="Enter mobile number">

                                <small class="form-help">

                                    You can update your mobile number.

                                </small>

                            </div>


                            <!-- Address

                            <div class="form-group">

                                <label for="address">
                                    Address
                                </label>

                                <input
                                    type="text"
                                    id="address"
                                    value=""
                                    readonly
                                    class="admin-managed-field"
                                    placeholder="Not available">

                                <small class="form-help admin-managed-message">

                                    <i class="fa-solid fa-circle-info"></i>

                                    Address is not available in the current
                                    authority database structure.

                                </small>

                            </div>


                        </div>

                            -->


                            <!-- Save Button -->

                            <div class="profile-action-row">

                                <button
                                    type="submit"
                                    class="save-btn">

                                    <i class="fa-solid fa-floppy-disk"></i>

                                    Save Information

                                </button>


                                <button
                                    type="reset"
                                    class="cancel-btn">

                                    Cancel

                                </button>

                            </div>

                    </form>

                </div>


                <!-- =================================================
                     SECURITY
                     ================================================= -->

                <div class="profile-card edit-card">

                    <div class="profile-card-header">

                        <div>

                            <h3>
                                Security
                            </h3>

                            <p>
                                Manage your CampusDesk account password
                            </p>

                        </div>


                        <div class="profile-card-icon">

                            <i class="fa-solid fa-shield-halved"></i>

                        </div>

                    </div>


                    <form
                        action="profile.php"
                        method="POST"
                        id="passwordForm">

                        <input
                            type="hidden"
                            name="action"
                            value="change_password">


                        <!-- Current Password -->

                        <div class="form-group password-group">

                            <label for="current_password">

                                Current Password

                            </label>


                            <div class="password-input-wrapper">

                                <input
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    required>

                                <button
                                    type="button"
                                    class="password-toggle"
                                    onclick="togglePassword('current_password', this)"
                                    aria-label="Show current password">

                                    <i class="fa-regular fa-eye"></i>

                                </button>

                            </div>

                        </div>


                        <!-- New Password -->

                        <div class="form-group password-group">

                            <label for="new_password">

                                New Password

                            </label>


                            <div class="password-input-wrapper">

                                <input
                                    type="password"
                                    id="new_password"
                                    name="new_password"
                                    minlength="8"
                                    required>

                                <button
                                    type="button"
                                    class="password-toggle"
                                    onclick="togglePassword('new_password', this)"
                                    aria-label="Show new password">

                                    <i class="fa-regular fa-eye"></i>

                                </button>

                            </div>


                            <small class="form-help">

                                Password must contain at least 8 characters.

                            </small>

                        </div>


                        <!-- Confirm New Password -->

                        <div class="form-group password-group">

                            <label for="confirm_password">

                                Confirm New Password

                            </label>


                            <div class="password-input-wrapper">

                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    minlength="8"
                                    required>

                                <button
                                    type="button"
                                    class="password-toggle"
                                    onclick="togglePassword('confirm_password', this)"
                                    aria-label="Show confirm password">

                                    <i class="fa-regular fa-eye"></i>

                                </button>

                            </div>

                        </div>


                        <!-- Security Buttons -->

                        <div class="profile-action-row">

                            <button
                                type="submit"
                                class="save-btn">

                                <i class="fa-solid fa-key"></i>

                                Change Password

                            </button>


                            <button
                                type="reset"
                                class="cancel-btn">

                                Cancel

                            </button>

                        </div>

                    </form>

                </div>


                <!-- =================================================
                     ACCOUNT INFORMATION
                     ================================================= -->

                <div class="profile-card">

                    <div class="profile-card-header">

                        <div>

                            <h3>
                                Account Information
                            </h3>

                            <p>
                                CampusDesk account details
                            </p>

                        </div>


                        <div class="profile-card-icon">

                            <i class="fa-solid fa-id-card"></i>

                        </div>

                    </div>


                    <!-- Account Status -->

                    <div class="info-row">

                        <span>
                            Account Status
                        </span>

                        <strong class="status-text">

                            <?= htmlspecialchars(
                                $account_status,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </strong>

                    </div>


                    <!-- Authority Status -->

                    <div class="info-row">

                        <span>
                            Authority Status
                        </span>

                        <strong class="status-text">

                            <?= htmlspecialchars(
                                $authority_status,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>

                        </strong>

                    </div>


                    <!-- Last Login -->

                    <div class="info-row">

                        <span>
                            Last Login
                        </span>

                        <strong>

                            <?= !empty($profile["last_login"])
                                ? htmlspecialchars(
                                    formatDateTime(
                                        $profile["last_login"]
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                )
                                : "Not available"
                            ?>

                        </strong>

                    </div>


                    <!-- Account Created -->

                    <div class="info-row">

                        <span>
                            Account Created
                        </span>

                        <strong>

                            <?= !empty($profile["created_at"])
                                ? htmlspecialchars(
                                    formatDateTime(
                                        $profile["created_at"]
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                )
                                : "Not available"
                            ?>

                        </strong>

                    </div>

                </div>


            </div>

        </main>

    </div>


    <!-- =====================================================
         FOOTER
         ===================================================== -->

    <?php include "../includes/footer.php"; ?>


    <!-- =====================================================
         PASSWORD SHOW / HIDE SCRIPT
         ===================================================== -->

    <script>
        /*
         * Toggle password visibility.
         */
        function togglePassword(fieldId, button) {

            const field =
                document.getElementById(fieldId);

            const icon =
                button.querySelector("i");


            if (field.type === "password") {

                field.type = "text";

                icon.classList.remove(
                    "fa-eye"
                );

                icon.classList.add(
                    "fa-eye-slash"
                );

                button.setAttribute(
                    "aria-label",
                    "Hide password"
                );

            } else {

                field.type = "password";

                icon.classList.remove(
                    "fa-eye-slash"
                );

                icon.classList.add(
                    "fa-eye"
                );

                button.setAttribute(
                    "aria-label",
                    "Show password"
                );
            }
        }


        /*
         * Confirm that both new password fields match
         * before submitting the form.
         */
        document
            .getElementById("passwordForm")
            .addEventListener(
                "submit",
                function(event) {

                    const newPassword =
                        document.getElementById(
                            "new_password"
                        ).value;

                    const confirmPassword =
                        document.getElementById(
                            "confirm_password"
                        ).value;


                    if (
                        newPassword !==
                        confirmPassword
                    ) {

                        event.preventDefault();

                        alert(
                            "New password and confirm password do not match."
                        );
                    }
                }
            );
    </script>


</body>

</html>
