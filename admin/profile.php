<?php

require_once "admin_auth.php";

requireAdmin();

require_once "../config/database.php";
require_once "../includes/functions.php";

$user_id = getAdminUserId();


/*
|--------------------------------------------------------------------------
| GET ADMIN PROFILE
|--------------------------------------------------------------------------
|
| Admin information is stored in:
|
| users
|   - user_id
|   - email
|   - mobile_number
|   - account_status
|   - last_login
|   - created_at
|   - updated_at
|   - profile_photo
|
| authorities
|   - authority_id
|   - user_id
|   - department_id
|   - name
|   - designation
|   - status
|
|--------------------------------------------------------------------------
*/

$profile_query = "
    SELECT
        u.user_id,
        u.email,
        u.mobile_number,
        u.account_status,
        u.last_login,
        u.created_at,
        u.updated_at,
        u.profile_photo,

        r.role_name,

        a.authority_id,
        a.name,
        a.designation,
        a.status AS authority_status,

        d.department_id,
        d.department_name

    FROM users u

    LEFT JOIN roles r
        ON u.role_id = r.role_id

    LEFT JOIN authorities a
        ON u.user_id = a.user_id

    LEFT JOIN departments d
        ON a.department_id = d.department_id

    WHERE u.user_id = $1
";

$profile_result = pg_query_params(
    $conn,
    $profile_query,
    [$user_id]
);

if (
    !$profile_result ||
    pg_num_rows($profile_result) === 0
) {
    die("Admin profile not found.");
}

$profile = pg_fetch_assoc($profile_result);


/*
|--------------------------------------------------------------------------
| PROFILE PHOTO
|--------------------------------------------------------------------------
*/

$profile_photo_data = null;
$profile_photo_type = "image/jpeg";

if (!empty($profile["profile_photo"])) {

    $profile_photo_data = pg_unescape_bytea(
        $profile["profile_photo"]
    );

    if (
        $profile_photo_data !== false &&
        function_exists("finfo_open")
    ) {

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo) {

            $detected_type = finfo_buffer(
                $finfo,
                $profile_photo_data
            );

            finfo_close($finfo);

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
                $profile_photo_type = $detected_type;
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| MESSAGE HANDLING
|--------------------------------------------------------------------------
*/

$success_message = "";
$error_message = "";

$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGES
|--------------------------------------------------------------------------
*/

if ($success === "profile_updated") {

    $success_message =
        "Profile information updated successfully.";
} elseif ($success === "photo_updated") {

    $success_message =
        "Profile photo updated successfully.";
} elseif ($success === "photo_removed") {

    $success_message =
        "Profile photo removed successfully.";
} elseif ($success === "password_changed") {

    $success_message =
        "Password changed successfully.";
}


/*
|--------------------------------------------------------------------------
| ERROR MESSAGES
|--------------------------------------------------------------------------
*/

if ($error === "invalid_mobile") {

    $error_message =
        "Please enter a valid mobile number.";
} elseif ($error === "profile_not_found") {

    $error_message =
        "Admin profile not found.";
} elseif ($error === "invalid_email") {

    $error_message =
        "Please enter a valid email address.";
} elseif ($error === "email_exists") {

    $error_message =
        "This email address is already registered.";
} elseif ($error === "name_required") {

    $error_message =
        "Please enter your full name.";
} elseif ($error === "department_required") {

    $error_message =
        "Please select your department.";
} elseif ($error === "designation_required") {

    $error_message =
        "Please enter your designation.";
} elseif ($error === "photo_required") {

    $error_message =
        "Please select a profile photo.";
} elseif ($error === "photo_upload_failed") {

    $error_message =
        "Profile photo upload failed.";
} elseif ($error === "photo_size") {

    $error_message =
        "Profile photo must not exceed 5 MB.";
} elseif ($error === "photo_type") {

    $error_message =
        "Only JPG and PNG profile photos are allowed.";
} elseif ($error === "photo_read_failed") {

    $error_message =
        "Unable to read the selected profile photo.";
} elseif ($error === "photo_update_failed") {

    $error_message =
        "Unable to update profile photo.";
} elseif ($error === "photo_remove_failed") {

    $error_message =
        "Unable to remove profile photo.";
} elseif ($error === "password_fields_required") {

    $error_message =
        "Please fill in all password fields.";
} elseif ($error === "password_length") {

    $error_message =
        "New password must contain at least 8 characters.";
} elseif ($error === "password_mismatch") {

    $error_message =
        "New password and confirm password do not match.";
} elseif ($error === "password_update_failed") {

    $error_message =
        "Unable to change password.";
} elseif ($error === "profile_update_failed") {

    $error_message =
        "Unable to update profile information.";
} elseif ($error === "invalid_action") {

    $error_message =
        "Invalid profile action.";
}


/*
|--------------------------------------------------------------------------
| DISPLAY VALUES
|--------------------------------------------------------------------------
*/

$admin_name = trim(
    $profile["name"] ?? ""
);

$department_name = trim(
    $profile["department_name"] ?? ""
);

$designation = trim(
    $profile["designation"] ?? ""
);

$mobile = trim(
    $profile["mobile_number"] ?? ""
);

$email = trim(
    $profile["email"] ?? ""
);

$role_name = $profile["role_name"] ?? "ADMIN";


/*
|--------------------------------------------------------------------------
| FIRST-TIME PROFILE VALUES
|--------------------------------------------------------------------------
|
| Empty values are intentionally allowed here.
| The Admin will complete them from the profile page.
|
*/

if ($admin_name === "") {
    $admin_name = "";
}

if ($department_name === "") {
    $department_name = "";
}

if ($designation === "") {
    $designation = "";
}


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

$account_is_active =
    !empty($profile["account_status"]);

$account_status =
    $account_is_active
    ? "Active"
    : "Inactive";


$authority_is_active =
    !empty($profile["authority_status"]);

$authority_status =
    $authority_is_active
    ? "Active"
    : "Inactive";


/*
|--------------------------------------------------------------------------
| PROFILE COMPLETION
|--------------------------------------------------------------------------
*/

$profile_complete =
    $admin_name !== "" &&
    !empty($profile["department_id"]) &&
    $designation !== "";


/*
|--------------------------------------------------------------------------
| INITIAL
|--------------------------------------------------------------------------
*/

$profile_initial = "A";

if ($admin_name !== "") {

    $profile_initial = strtoupper(
        substr(
            trim($admin_name),
            0,
            1
        )
    );
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        My Profile - Admin - CampusDesk
    </title>


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <!-- Admin Common -->

    <link
        rel="stylesheet"
        href="css/admin-common.css">


    <!-- Admin Navbar -->

    <link
        rel="stylesheet"
        href="css/admin-navbar.css">


    <!-- Admin Profile -->

    <link
        rel="stylesheet"
        href="css/admin-profile.css">

</head>


<body>


    <div class="admin-layout">


        <!-- =====================================================
         ADMIN NAVBAR
    ====================================================== -->

        <?php
        include "includes/navbar.php";
        ?>


        <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

        <main class="admin-main">


            <!-- =================================================
             PAGE HEADER
        ================================================== -->

            <div class="profile-page-header">

                <div>

                    <h1>
                        My Profile
                    </h1>

                    <p>
                        View and manage your administrator profile.
                    </p>

                </div>


                <div class="profile-header-icon">

                    <i class="fa-solid fa-user-shield"></i>

                </div>

            </div>



            <!-- =================================================
             FIRST-TIME PROFILE MESSAGE
        ================================================== -->

            <?php if (!$profile_complete): ?>

                <div class="admin-alert admin-alert-info">

                    <i class="fa-solid fa-circle-info"></i>

                    <span>
                        Welcome to CampusDesk Administration.
                        Please complete your administrator profile information.
                    </span>

                </div>

            <?php endif; ?>



            <!-- =================================================
             SUCCESS MESSAGE
        ================================================== -->

            <?php if ($success_message !== ""): ?>

                <div class="admin-alert admin-alert-success">

                    <i class="fa-solid fa-circle-check"></i>

                    <span>

                        <?php
                        echo htmlspecialchars(
                            $success_message
                        );
                        ?>

                    </span>

                </div>

            <?php endif; ?>



            <!-- =================================================
             ERROR MESSAGE
        ================================================== -->

            <?php if ($error_message !== ""): ?>

                <div class="admin-alert admin-alert-error">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>

                        <?php
                        echo htmlspecialchars(
                            $error_message
                        );
                        ?>

                    </span>

                </div>

            <?php endif; ?>



            <!-- =================================================
             PROFILE OVERVIEW
        ================================================== -->

            <section class="profile-overview-card">


                <div class="profile-overview-photo">

                    <?php if ($profile_photo_data): ?>

                        <img
                            src="data:<?php
                                        echo htmlspecialchars(
                                            $profile_photo_type
                                        );
                                        ?>;base64,<?php
                                                echo base64_encode(
                                                    $profile_photo_data
                                                );
                                                ?>"
                            alt="Admin Profile Photo"
                            class="profile-photo-large">

                    <?php else: ?>

                        <div class="profile-default-avatar">

                            <?php
                            echo htmlspecialchars(
                                $profile_initial
                            );
                            ?>

                        </div>

                    <?php endif; ?>

                </div>


                <div class="profile-overview-info">

                    <h2>

                        <?php

                        echo htmlspecialchars(
                            $admin_name !== ""
                                ? $admin_name
                                : "Administrator"
                        );

                        ?>

                    </h2>


                    <p class="profile-designation">

                        <?php

                        echo htmlspecialchars(
                            $designation !== ""
                                ? $designation
                                : "Designation not set"
                        );

                        ?>

                    </p>


                    <p>

                        <i class="fa-solid fa-building-columns"></i>

                        Fergusson College

                    </p>


                    <p>

                        <i class="fa-solid fa-building"></i>

                        <?php

                        echo htmlspecialchars(
                            $department_name !== ""
                                ? $department_name
                                : "Department Not Assigned"
                        );

                        ?>

                    </p>


                    <p>

                        <i class="fa-solid fa-envelope"></i>

                        <?php
                        echo htmlspecialchars(
                            $email
                        );
                        ?>

                    </p>


                    <div class="profile-status-row">

                        <span class="profile-role-badge">

                            <?php
                            echo htmlspecialchars(
                                $role_name
                            );
                            ?>

                        </span>


                        <span
                            class="
                        profile-status-badge
                        <?php
                        echo $account_is_active
                            ? "active"
                            : "inactive";
                        ?>
                        ">

                            <?php
                            echo htmlspecialchars(
                                $account_status
                            );
                            ?>

                        </span>

                    </div>

                </div>

            </section>



            <!-- =================================================
             PROFILE PHOTO
        ================================================== -->

            <section class="profile-content-card">


                <div class="profile-card-header">

                    <div>

                        <h2>
                            Profile Photo
                        </h2>

                        <p>
                            Update your administrator profile picture.
                        </p>

                    </div>

                </div>


                <div class="profile-photo-section">


                    <div class="profile-photo-preview">

                        <?php if ($profile_photo_data): ?>

                            <img
                                id="profilePhotoPreview"
                                src="data:<?php
                                            echo htmlspecialchars(
                                                $profile_photo_type
                                            );
                                            ?>;base64,<?php
                                                    echo base64_encode(
                                                        $profile_photo_data
                                                    );
                                                    ?>"
                                alt="Profile Photo">

                        <?php else: ?>

                            <div
                                id="defaultProfileIcon"
                                class="profile-default-avatar">

                                <?php
                                echo htmlspecialchars(
                                    $profile_initial
                                );
                                ?>

                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="profile-photo-form-area">


                        <!-- UPDATE PHOTO -->

                        <form
                            action="../admin_actions/users.php"
                            method="POST"
                            enctype="multipart/form-data"
                            onsubmit="return validateProfilePhoto();">

                            <input
                                type="hidden"
                                name="action"
                                value="update_photo">

                            <input
                                type="hidden"
                                name="user_id"
                                value="<?php
                                        echo (int) $user_id;
                                        ?>">

                            <input
                                type="hidden"
                                name="profile_source"
                                value="admin_profile">


                            <div class="admin-form-group">

                                <label for="profile_photo">

                                    Select Profile Photo

                                </label>


                                <input
                                    type="file"
                                    id="profile_photo"
                                    name="profile_photo"
                                    accept=".jpg,.jpeg,.png,image/jpeg,image/png">


                                <small>

                                    JPG or PNG only.
                                    Maximum size: 5 MB.

                                </small>

                            </div>


                            <button
                                type="submit"
                                class="admin-btn admin-btn-primary">

                                <i class="fa-solid fa-camera"></i>

                                Update Photo

                            </button>

                        </form>



                        <!-- REMOVE PHOTO -->

                        <?php if ($profile_photo_data): ?>

                            <form
                                action="../admin_actions/users.php"
                                method="POST"
                                class="remove-photo-form"
                                onsubmit="return confirm('Are you sure you want to remove your profile photo?');">

                                <input
                                    type="hidden"
                                    name="action"
                                    value="remove_photo">

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?php
                                            echo (int) $user_id;
                                            ?>">

                                <input
                                    type="hidden"
                                    name="profile_source"
                                    value="admin_profile">


                                <button
                                    type="submit"
                                    class="admin-btn admin-btn-danger">

                                    <i class="fa-solid fa-trash"></i>

                                    Remove Photo

                                </button>

                            </form>

                        <?php endif; ?>


                    </div>

                </div>

            </section>



            <!-- =================================================
             PERSONAL INFORMATION
        ================================================== -->

            <section class="profile-content-card">


                <div class="profile-card-header">

                    <div>

                        <h2>
                            Personal Information
                        </h2>

                        <p>
                            Complete and manage your administrator information.
                        </p>

                    </div>


                    <button
                        type="button"
                        id="editProfileButton"
                        class="admin-btn admin-btn-secondary"
                        onclick="enableProfileEditing();">

                        <i class="fa-solid fa-pen"></i>

                        Edit Information

                    </button>

                </div>


                <form
                    id="adminProfileForm"
                    action="../admin_actions/users.php"
                    method="POST"
                    onsubmit="return validateAdminProfile();">


                    <input
                        type="hidden"
                        name="action"
                        value="edit">


                    <input
                        type="hidden"
                        name="user_id"
                        value="<?php
                                echo (int) $user_id;
                                ?>">


                    <input
                        type="hidden"
                        name="profile_source"
                        value="admin_profile">


                    <div class="profile-form-grid">


                        <!-- Full Name -->

                        <div class="admin-form-group">

                            <label for="name">

                                Full Name

                                <span class="required-mark">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="<?php
                                        echo htmlspecialchars(
                                            $admin_name
                                        );
                                        ?>"
                                readonly
                                maxlength="150"
                                placeholder="Enter your full name">

                        </div>



                        <!-- Email -->

                        <div class="admin-form-group">

                            <label for="email">

                                Email Address

                                <span class="required-mark">
                                    *
                                </span>

                            </label>


                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?php
                                        echo htmlspecialchars(
                                            $email
                                        );
                                        ?>"
                                readonly
                                maxlength="150"
                                placeholder="Enter your email address">

                        </div>



                        <!-- Mobile -->

                        <div class="admin-form-group">

                            <label for="mobile_number">

                                Mobile Number

                            </label>


                            <input
                                type="text"
                                id="mobile_number"
                                name="mobile_number"
                                value="<?php
                                        echo htmlspecialchars(
                                            $mobile
                                        );
                                        ?>"
                                readonly
                                maxlength="15"
                                placeholder="Enter your mobile number">

                        </div>



                        <!-- Department -->

                        <div class="admin-form-group">

                            <label for="department_id">

                                Department

                                <span class="required-mark">
                                    *
                                </span>

                            </label>


                            <select
                                id="department_id"
                                name="department_id"
                                disabled>

                                <option value="">

                                    Select Department

                                </option>


                                <?php

                                $department_query = "
                                SELECT
                                    department_id,
                                    department_name
                                FROM departments
                                WHERE status = TRUE
                                ORDER BY department_name
                            ";

                                $department_result = pg_query(
                                    $conn,
                                    $department_query
                                );


                                if ($department_result) {

                                    while (
                                        $department =
                                        pg_fetch_assoc(
                                            $department_result
                                        )
                                    ):

                                        $selected =
                                            (
                                                (int) (
                                                    $profile["department_id"] ?? 0
                                                )
                                                ===
                                                (int) (
                                                    $department["department_id"]
                                                )
                                            )
                                            ? "selected"
                                            : "";

                                ?>

                                        <option
                                            value="<?php
                                                    echo (int)
                                                    $department["department_id"];
                                                    ?>"
                                            <?php
                                            echo $selected;
                                            ?>>

                                            <?php
                                            echo htmlspecialchars(
                                                $department["department_name"]
                                            );
                                            ?>

                                        </option>

                                <?php

                                    endwhile;
                                }

                                ?>

                            </select>

                        </div>



                        <!-- Designation -->

                        <div class="admin-form-group">

                            <label for="designation">

                                Designation

                                <span class="required-mark">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                id="designation"
                                name="designation"
                                value="<?php
                                        echo htmlspecialchars(
                                            $designation
                                        );
                                        ?>"
                                readonly
                                maxlength="100"
                                placeholder="Enter your designation">

                        </div>



                        <!-- College -->

                        <div class="admin-form-group">

                            <label>

                                College

                            </label>


                            <input
                                type="text"
                                value="Fergusson College"
                                readonly
                                class="readonly-field">

                            <small>
                                College information is fixed for this CampusDesk installation.
                            </small>

                        </div>


                    </div>



                    <!-- Save / Cancel -->

                    <div
                        id="profileEditActions"
                        class="profile-form-actions hidden">


                        <button
                            type="submit"
                            class="admin-btn admin-btn-primary">

                            <i class="fa-solid fa-floppy-disk"></i>

                            Save Changes

                        </button>


                        <button
                            type="button"
                            class="admin-btn admin-btn-secondary"
                            onclick="cancelProfileEditing();">

                            <i class="fa-solid fa-xmark"></i>

                            Cancel

                        </button>

                    </div>


                </form>

            </section>



            <!-- =================================================
             PROFESSIONAL INFORMATION
        ================================================== -->

            <section class="profile-content-card">


                <div class="profile-card-header">

                    <div>

                        <h2>
                            Professional Information
                        </h2>

                        <p>
                            Information associated with your administrator role.
                        </p>

                    </div>

                </div>


                <div class="profile-info-grid">


                    <!-- Authority ID -->

                    <div class="profile-info-item">

                        <span>
                            Authority ID
                        </span>

                        <strong>

                            <?php

                            if (
                                !empty($profile["authority_id"])
                            ) {

                                echo "AUTH-" .
                                    str_pad(
                                        $profile["authority_id"],
                                        5,
                                        "0",
                                        STR_PAD_LEFT
                                    );
                            } else {

                                echo "Will be created after profile completion.";
                            }

                            ?>

                        </strong>

                    </div>



                    <!-- Designation -->

                    <div class="profile-info-item">

                        <span>
                            Designation
                        </span>

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $designation !== ""
                                    ? $designation
                                    : "Not Set"
                            );

                            ?>

                        </strong>

                    </div>



                    <!-- Department -->

                    <div class="profile-info-item">

                        <span>
                            Department
                        </span>

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $department_name !== ""
                                    ? $department_name
                                    : "Not Assigned"
                            );

                            ?>

                        </strong>

                    </div>



                    <!-- Authority Status -->

                    <div class="profile-info-item">

                        <span>
                            Authority Status
                        </span>

                        <strong
                            class="
                        <?php

                        echo $authority_is_active
                            ? "status-active"
                            : "status-inactive";

                        ?>
                        ">

                            <?php

                            if (
                                !empty($profile["authority_id"])
                            ) {

                                echo htmlspecialchars(
                                    $authority_status
                                );
                            } else {

                                echo "Profile Not Completed";
                            }

                            ?>

                        </strong>

                    </div>


                </div>

            </section>



            <!-- =================================================
             SECURITY
        ================================================== -->

            <section class="profile-content-card">


                <div class="profile-card-header">

                    <div>

                        <h2>
                            Security
                        </h2>

                        <p>
                            Set or change your administrator account password.
                        </p>

                    </div>

                </div>


                <div id="passwordButtonArea">

                    <button
                        type="button"
                        class="admin-btn admin-btn-primary"
                        onclick="showPasswordForm();">

                        <i class="fa-solid fa-key"></i>

                        Change Password

                    </button>

                </div>


                <div
                    id="passwordFormArea"
                    class="hidden">


                    <form
                        action="../admin_actions/users.php"
                        method="POST"
                        onsubmit="return validatePasswordForm();">


                        <input
                            type="hidden"
                            name="action"
                            value="change_password">


                        <input
                            type="hidden"
                            name="user_id"
                            value="<?php
                                    echo (int) $user_id;
                                    ?>">


                        <input
                            type="hidden"
                            name="profile_source"
                            value="admin_profile">


                        <div class="profile-password-grid">


                            <!-- New Password -->

                            <div class="admin-form-group">

                                <label for="new_password">

                                    New Password

                                    <span class="required-mark">
                                        *
                                    </span>

                                </label>


                                <div class="password-input-wrapper">

                                    <input
                                        type="password"
                                        id="new_password"
                                        name="new_password"
                                        autocomplete="new-password"
                                        required>


                                    <button
                                        type="button"
                                        onclick="togglePassword('new_password', this);"
                                        class="password-toggle"
                                        aria-label="Show or hide password">

                                        <i class="fa-solid fa-eye"></i>

                                    </button>

                                </div>


                                <small>
                                    Minimum 8 characters.
                                </small>

                            </div>



                            <!-- Confirm Password -->

                            <div class="admin-form-group">

                                <label for="confirm_password">

                                    Confirm New Password

                                    <span class="required-mark">
                                        *
                                    </span>

                                </label>


                                <div class="password-input-wrapper">

                                    <input
                                        type="password"
                                        id="confirm_password"
                                        name="confirm_password"
                                        autocomplete="new-password"
                                        required>


                                    <button
                                        type="button"
                                        onclick="togglePassword('confirm_password', this);"
                                        class="password-toggle"
                                        aria-label="Show or hide password">

                                        <i class="fa-solid fa-eye"></i>

                                    </button>

                                </div>

                            </div>


                        </div>



                        <div class="profile-form-actions">


                            <button
                                type="submit"
                                class="admin-btn admin-btn-primary">

                                <i class="fa-solid fa-lock"></i>

                                Update Password

                            </button>


                            <button
                                type="button"
                                class="admin-btn admin-btn-secondary"
                                onclick="hidePasswordForm();">

                                Cancel

                            </button>


                        </div>


                    </form>

                </div>


            </section>



            <!-- =================================================
             ACCOUNT INFORMATION
        ================================================== -->

            <section class="profile-content-card">


                <div class="profile-card-header">

                    <div>

                        <h2>
                            Account Information
                        </h2>

                        <p>
                            Basic information about your CampusDesk account.
                        </p>

                    </div>

                </div>


                <div class="profile-info-grid">


                    <!-- Account Status -->

                    <div class="profile-info-item">

                        <span>
                            Account Status
                        </span>

                        <strong
                            class="
                        <?php

                        echo $account_is_active
                            ? "status-active"
                            : "status-inactive";

                        ?>
                        ">

                            <?php

                            echo htmlspecialchars(
                                $account_status
                            );

                            ?>

                        </strong>

                    </div>



                    <!-- Last Login -->

                    <div class="profile-info-item">

                        <span>
                            Last Login
                        </span>

                        <strong>

                            <?php

                            if (
                                !empty($profile["last_login"])
                            ) {

                                echo htmlspecialchars(
                                    formatDateTime(
                                        $profile["last_login"]
                                    )
                                );
                            } else {

                                echo "First Login";
                            }

                            ?>

                        </strong>

                    </div>



                    <!-- Account Created -->

                    <div class="profile-info-item">

                        <span>
                            Account Created
                        </span>

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                formatDateTime(
                                    $profile["created_at"]
                                )
                            );

                            ?>

                        </strong>

                    </div>



                    <!-- Last Updated -->

                    <div class="profile-info-item">

                        <span>
                            Last Updated
                        </span>

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                formatDateTime(
                                    $profile["updated_at"]
                                )
                            );

                            ?>

                        </strong>

                    </div>


                </div>

            </section>



            <!-- =================================================
             LOGOUT
        ================================================== -->

            <section class="profile-logout-section">

                <a
                    href="../logout.php"
                    class="admin-btn admin-btn-danger"
                    onclick="return confirm('Are you sure you want to logout?');">

                    <i class="fa-solid fa-right-from-bracket"></i>

                    Logout

                </a>

            </section>


        </main>

    </div>



    <script src="js/admin-script.js"></script>


    <script>
        /*
|--------------------------------------------------------------------------
| PROFILE EDITING
|--------------------------------------------------------------------------
*/

        function enableProfileEditing() {
            const editableFields = [
                "name",
                "email",
                "mobile_number",
                "designation"
            ];


            editableFields.forEach(
                function(fieldId) {
                    const field =
                        document.getElementById(fieldId);


                    if (field) {
                        field.removeAttribute("readonly");

                        field.classList.add(
                            "editable-field"
                        );
                    }
                }
            );


            const department =
                document.getElementById(
                    "department_id"
                );


            if (department) {
                department.disabled = false;

                department.classList.add(
                    "editable-field"
                );
            }


            const editButton =
                document.getElementById(
                    "editProfileButton"
                );


            if (editButton) {
                editButton.classList.add(
                    "hidden"
                );
            }


            const editActions =
                document.getElementById(
                    "profileEditActions"
                );


            if (editActions) {
                editActions.classList.remove(
                    "hidden"
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | CANCEL PROFILE EDIT
        |--------------------------------------------------------------------------
        */

        function cancelProfileEditing() {
            window.location.reload();
        }


        /*
        |--------------------------------------------------------------------------
        | PROFILE VALIDATION
        |--------------------------------------------------------------------------
        */

        function validateAdminProfile() {
            const name =
                document.getElementById(
                    "name"
                ).value.trim();


            const email =
                document.getElementById(
                    "email"
                ).value.trim();


            const mobile =
                document.getElementById(
                    "mobile_number"
                ).value.trim();


            const designation =
                document.getElementById(
                    "designation"
                ).value.trim();


            const department =
                document.getElementById(
                    "department_id"
                ).value;


            if (name === "") {
                alert(
                    "Please enter your full name."
                );

                return false;
            }


            if (email === "") {
                alert(
                    "Please enter your email address."
                );

                return false;
            }


            const emailPattern =
                /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


            if (
                !emailPattern.test(email)
            ) {
                alert(
                    "Please enter a valid email address."
                );

                return false;
            }


            if (
                mobile !== "" &&
                !/^\d{10,15}$/.test(mobile)
            ) {
                alert(
                    "Mobile number must contain 10 to 15 digits."
                );

                return false;
            }


            if (department === "") {
                alert(
                    "Please select your department."
                );

                return false;
            }


            if (designation === "") {
                alert(
                    "Please enter your designation."
                );

                return false;
            }


            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | PROFILE PHOTO VALIDATION
        |--------------------------------------------------------------------------
        */

        function validateProfilePhoto() {
            const input =
                document.getElementById(
                    "profile_photo"
                );


            if (
                !input ||
                !input.files ||
                input.files.length === 0
            ) {
                alert(
                    "Please select a profile photo."
                );

                return false;
            }


            const file =
                input.files[0];


            const allowedTypes = [
                "image/jpeg",
                "image/png"
            ];


            if (
                !allowedTypes.includes(
                    file.type
                )
            ) {
                alert(
                    "Only JPG and PNG images are allowed."
                );

                return false;
            }


            const maxSize =
                5 * 1024 * 1024;


            if (
                file.size > maxSize
            ) {
                alert(
                    "Profile photo must not exceed 5 MB."
                );

                return false;
            }


            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | PASSWORD FORM
        |--------------------------------------------------------------------------
        */

        function showPasswordForm() {
            document
                .getElementById(
                    "passwordButtonArea"
                )
                .classList.add(
                    "hidden"
                );


            document
                .getElementById(
                    "passwordFormArea"
                )
                .classList.remove(
                    "hidden"
                );
        }


        function hidePasswordForm() {
            document
                .getElementById(
                    "passwordFormArea"
                )
                .classList.add(
                    "hidden"
                );


            document
                .getElementById(
                    "passwordButtonArea"
                )
                .classList.remove(
                    "hidden"
                );
        }


        /*
        |--------------------------------------------------------------------------
        | PASSWORD VALIDATION
        |--------------------------------------------------------------------------
        */

        function validatePasswordForm() {
            const newPassword =
                document.getElementById(
                    "new_password"
                ).value;


            const confirmPassword =
                document.getElementById(
                    "confirm_password"
                ).value;


            if (
                newPassword === "" ||
                confirmPassword === ""
            ) {
                alert(
                    "Please fill in all password fields."
                );

                return false;
            }


            if (
                newPassword.length < 8
            ) {
                alert(
                    "New password must contain at least 8 characters."
                );

                return false;
            }


            if (
                newPassword !==
                confirmPassword
            ) {
                alert(
                    "New password and confirm password do not match."
                );

                return false;
            }


            return confirm(
                "Are you sure you want to change your password?"
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PASSWORD TOGGLE
        |--------------------------------------------------------------------------
        */

        function togglePassword(
            fieldId,
            button
        ) {
            const field =
                document.getElementById(
                    fieldId
                );


            if (!field) {
                return;
            }


            if (
                field.type === "password"
            ) {
                field.type = "text";

                button.innerHTML =
                    '<i class="fa-solid fa-eye-slash"></i>';

            } else {
                field.type = "password";

                button.innerHTML =
                    '<i class="fa-solid fa-eye"></i>';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | PHOTO PREVIEW
        |--------------------------------------------------------------------------
        */

        const profilePhotoInput =
            document.getElementById(
                "profile_photo"
            );


        if (profilePhotoInput) {
            profilePhotoInput.addEventListener(
                "change",
                function() {
                    const file =
                        this.files[0];


                    if (!file) {
                        return;
                    }


                    const reader =
                        new FileReader();


                    reader.onload =
                        function(event) {
                            let preview =
                                document.getElementById(
                                    "profilePhotoPreview"
                                );


                            const defaultIcon =
                                document.getElementById(
                                    "defaultProfileIcon"
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | IF DEFAULT AVATAR EXISTS
                            |--------------------------------------------------------------------------
                            */

                            if (!preview) {
                                const previewContainer =
                                    document.querySelector(
                                        ".profile-photo-preview"
                                    );


                                if (previewContainer) {
                                    preview =
                                        document.createElement(
                                            "img"
                                        );

                                    preview.id =
                                        "profilePhotoPreview";

                                    preview.alt =
                                        "Profile Photo";

                                    previewContainer
                                        .appendChild(
                                            preview
                                        );
                                }
                            }


                            if (preview) {
                                preview.src =
                                    event.target.result;

                                preview.classList.remove(
                                    "hidden"
                                );
                            }


                            if (defaultIcon) {
                                defaultIcon.classList.add(
                                    "hidden"
                                );
                            }
                        };


                    reader.readAsDataURL(file);
                }
            );
        }
    </script>


</body>

</html>
