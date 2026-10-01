<?php
/*
|--------------------------------------------------------------------------
| CAMPUSDESK - AUTHORITY PROFILE
|--------------------------------------------------------------------------
| This page allows an authenticated authority to:
|
| - View profile information
| - View profile photo
| - View department and designation
| - View activity statistics
| - Update mobile number
| - View administrator-managed fields
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
   HANDLE PROFILE UPDATE
   ========================================================= */

$success_message = "";
$error_message = "";


/*
 * Process the form only when it is submitted using POST.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
     * Get the requested action.
     */
    $action = $_POST["action"] ?? "";


    /*
     * Only process the profile update action.
     */
    if ($action === "update_profile") {

        /*
         * Mobile number is the authority-editable field
         * that is available in the database.
         */
        $mobile = trim($_POST["mobile"] ?? "");


        /*
         * Validate mobile number when supplied.
         */
        if ($mobile !== "") {

            /*
             * Allow digits, spaces, +, -, and brackets.
             */
            if (
                !preg_match(
                    '/^[0-9+\-\s()]{7,15}$/',
                    $mobile
                )
            ) {

                $error_message =
                    "Please enter a valid mobile number.";
            }
        }


        /*
         * Continue only when validation succeeded.
         */
        if ($error_message === "") {

            /*
             * Update the mobile number in the users table.
             *
             * The authority's name, email, department and
             * designation are intentionally NOT updated here.
             */
            $update_query = "
                UPDATE users
                SET
                    mobile_number = $1,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = $2
            ";


            $update_result = pg_query_params(
                $conn,
                $update_query,
                [
                    $mobile !== "" ? $mobile : null,
                    $user_id
                ]
            );


            /*
             * Check whether PostgreSQL successfully updated
             * the record.
             */
            if ($update_result) {

                $success_message =
                    "Profile details saved successfully.";
            } else {

                $error_message =
                    "Unable to save profile details. Please try again.";
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
 * This query also retrieves authorities.status so that
 * Authority Status can correctly show Active or Inactive.
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
 * Convert the PostgreSQL result into an associative array.
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
             * Only allow JPEG and PNG profile photos.
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
 * Count all grievances assigned to the logged-in authority.
 *
 * The grievances table stores the authority ID in assigned_to.
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

    $grievance_count = (int) pg_fetch_result(
        $grievance_result,
        0,
        "total"
    );
}


/*
 * Count all suggestions reviewed by the logged-in authority.
 *
 * The suggestions table stores the authority ID in reviewed_by.
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

    $suggestion_count = (int) pg_fetch_result(
        $suggestion_result,
        0,
        "total"
    );
}


/*
 * Count all applications assigned to the logged-in authority.
 *
 * The applications table stores the authority ID in assigned_to.
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

    $application_count = (int) pg_fetch_result(
        $application_result,
        0,
        "total"
    );
}


/*
 * Get the first letter of the authority name.
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

/*
 * This value comes directly from authorities.status.
 *
 * If authorities.status is TRUE, the page displays Active.
 */
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

                    <!-- Back to Dashboard -->

                    <a
                        href="dashboard.php"
                        class="profile-back-link">

                        <i class="fa-solid fa-arrow-left"></i>

                        Back to Dashboard

                    </a>


                    <!-- Logout -->

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
                     PROFILE INFORMATION
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
                                Mobile
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
                                    <?= (int)$grievance_count ?>
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
                                    <?= (int)$suggestion_count ?>
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
                                    <?= (int)$application_count ?>
                                </strong>

                                <span>
                                    Applications Processed
                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     EDIT PROFILE
                     ================================================= -->

                <div class="profile-card edit-card">

                    <div class="profile-card-header">

                        <div>

                            <h3>
                                Edit Profile
                            </h3>

                            <p>
                                Update the information you are allowed to change
                            </p>

                        </div>


                        <div class="profile-card-icon">

                            <i class="fa-solid fa-pen"></i>

                        </div>

                    </div>


                    <!-- =================================================
                         PROFILE UPDATE FORM
                         ================================================= -->

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
                                    value="<?= htmlspecialchars(
                                                $full_name,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                    readonly
                                    class="admin-managed-field">

                                <small class="form-help admin-managed-message">

                                    <i class="fa-solid fa-lock"></i>

                                    This field is managed by the administrator
                                    and cannot be changed here.

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

                                    This field is managed by the administrator
                                    and cannot be changed here.

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

                                    This field is managed by the administrator
                                    and cannot be changed here.

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

                                    This field is managed by the administrator
                                    and cannot be changed here.

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


                            <!-- Address -->

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


                        <!-- Save Button -->

                        <div class="profile-action-row">

                            <button
                                type="submit"
                                class="save-btn">

                                <i class="fa-solid fa-floppy-disk"></i>

                                Save Changes

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


</body>

</html>
