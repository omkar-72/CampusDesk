<?php

require_once "../includes/auth.php";
requireStudent();

require_once "../config/database.php";
require_once "../includes/functions.php";

$user_id = getLoggedInUserId();

$profile = getStudentProfile(
    $conn,
    $user_id
);

if (!$profile) {
    die("Student profile not found.");
}

$profile_photo = getProfilePhoto(
    $conn,
    $user_id
);


/* =========================
   PROFILE PHOTO DATA
   ========================= */

$profile_photo_type = "image/jpeg";
$profile_photo_data = null;

if ($profile_photo) {

    $profile_photo_data =
        pg_unescape_bytea($profile_photo);

    if (function_exists("finfo_open")) {

        $finfo = finfo_open(
            FILEINFO_MIME_TYPE
        );

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
                $profile_photo_type =
                    $detected_type;
            }
        }
    }
}


/* =========================
   MESSAGES
   ========================= */

$success_message = "";
$error_message = "";

$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";


/* =========================
   SUCCESS MESSAGES
   ========================= */

if ($success === "profile_updated") {

    $success_message =
        "Personal information updated successfully.";

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


/* =========================
   ERROR MESSAGES
   ========================= */

if ($error === "invalid_mobile") {

    $error_message =
        "Please enter a valid mobile number.";

} elseif ($error === "profile_not_found") {

    $error_message =
        "Student profile not found.";

} elseif ($error === "no_photo") {

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

} elseif ($error === "user_not_found") {

    $error_message =
        "User account not found.";

} elseif ($error === "current_password") {

    $error_message =
        "Current password is incorrect.";

} elseif ($error === "same_password") {

    $error_message =
        "New password must be different from the current password.";

} elseif ($error === "password_update_failed") {

    $error_message =
        "Unable to change password.";

} elseif ($error === "invalid_action") {

    $error_message =
        "Invalid profile action.";
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        My Profile - CampusDesk
    </title>

    <link
        rel="stylesheet"
        href="../css/style-profile.css"
    >

</head>

<body>


<div class="profile-container">


    <!-- =========================
         HEADER
         ========================= -->

    <div class="profile-header">

        <a
            href="dashboard.php"
            class="back-link"
        >
            ← Back to Dashboard
        </a>

        <h1>
            My Profile
        </h1>

        <p>
            View and manage your personal information
        </p>

    </div>


    <!-- =========================
         SUCCESS MESSAGE
         ========================= -->

    <?php if ($success_message !== ""): ?>

        <div class="profile-message success-message">

            <?php
            echo escape(
                $success_message
            );
            ?>

        </div>

    <?php endif; ?>


    <!-- =========================
         ERROR MESSAGE
         ========================= -->

    <?php if ($error_message !== ""): ?>

        <div class="profile-message error-message">

            <?php
            echo escape(
                $error_message
            );
            ?>

        </div>

    <?php endif; ?>


    <!-- =========================
         PROFILE PHOTO
         ========================= -->

    <div class="profile-section">

        <h2>
            Profile Photo
        </h2>


        <div class="profile-photo-area">


            <?php if ($profile_photo_data): ?>

                <img
                    id="profilePhotoPreview"
                    class="profile-photo"
                    src="data:<?php echo escape($profile_photo_type); ?>;base64,<?php echo base64_encode($profile_photo_data); ?>"
                    alt="Profile Photo"
                >

                <div
                    id="defaultProfileIcon"
                    class="default-profile-icon hidden"
                >
                    👤
                </div>

            <?php else: ?>

                <img
                    id="profilePhotoPreview"
                    class="profile-photo hidden"
                    src=""
                    alt="Profile Photo"
                >

                <div
                    id="defaultProfileIcon"
                    class="default-profile-icon"
                >
                    👤
                </div>

            <?php endif; ?>


        </div>


        <!-- Upload Photo -->

        <form
            action="../actions/profile.php"
            method="POST"
            enctype="multipart/form-data"
            class="profile-photo-form"
        >

            <input
                type="hidden"
                name="action"
                value="update_photo"
            >

            <div class="profile-field">

                <label for="profile_photo">
                    Select Profile Photo
                </label>

                <input
                    type="file"
                    id="profile_photo"
                    name="profile_photo"
                    accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                >

            </div>

            <p class="form-help">
                JPG or PNG only. Maximum size: 5 MB.
            </p>

            <button
                type="submit"
                class="profile-button primary-button"
            >
                Update Photo
            </button>

        </form>


        <!-- Remove Photo -->

        <?php if ($profile_photo_data): ?>

            <form
                action="../actions/profile.php"
                method="POST"
                class="remove-photo-form"
                onsubmit="return confirmRemovePhoto();"
            >

                <input
                    type="hidden"
                    name="action"
                    value="remove_photo"
                >

                <button
                    type="submit"
                    class="profile-button danger-button"
                >
                    Remove Photo
                </button>

            </form>

        <?php endif; ?>


    </div>


    <!-- =========================
         PERSONAL INFORMATION
         ========================= -->

    <div class="profile-section">

        <h2>
            Personal Information
        </h2>


        <form
            id="profileInformationForm"
            action="../actions/profile.php"
            method="POST"
            onsubmit="return validateProfileForm();"
        >

            <input
                type="hidden"
                name="action"
                value="update_profile"
            >


            <div class="profile-grid">


                <!-- Full Name -->

                <div class="profile-field">

                    <label for="full_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?php echo escape($profile["full_name"]); ?>"
                        readonly
                    >

                </div>


                <!-- PRN -->

                <div class="profile-field">

                    <label for="prn">
                        PRN
                    </label>

                    <input
                        type="text"
                        id="prn"
                        value="<?php echo escape($profile["prn"]); ?>"
                        readonly
                    >

                </div>


                <!-- Roll Number -->

                <div class="profile-field">

                    <label for="roll_no">
                        Roll No
                    </label>

                    <input
                        type="text"
                        id="roll_no"
                        value="<?php echo escape($profile["roll_no"]); ?>"
                        readonly
                    >

                </div>


                <!-- Email -->

                <div class="profile-field">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        value="<?php echo escape($profile["email"]); ?>"
                        readonly
                    >

                </div>


                <!-- Course -->

                <div class="profile-field">

                    <label for="course">
                        Course
                    </label>

                    <input
                        type="text"
                        id="course"
                        value="<?php echo escape($profile["course"]); ?>"
                        readonly
                    >

                </div>


                <!-- Department -->

                <div class="profile-field">

                    <label for="department">
                        Department
                    </label>

                    <input
                        type="text"
                        id="department"
                        value="<?php echo escape($profile["department_name"]); ?>"
                        readonly
                    >

                </div>


                <!-- Year -->

                <div class="profile-field">

                    <label for="year">
                        Year
                    </label>

                    <input
                        type="text"
                        id="year"
                        value="<?php echo escape($profile["year"]); ?>"
                        readonly
                    >

                </div>


                <!-- Semester -->

                <div class="profile-field">

                    <label for="semester">
                        Semester
                    </label>

                    <input
                        type="text"
                        id="semester"
                        value="<?php echo escape($profile["semester"]); ?>"
                        readonly
                    >

                </div>


                <!-- Division -->

                <div class="profile-field">

                    <label for="division">
                        Division
                    </label>

                    <input
                        type="text"
                        id="division"
                        value="<?php echo escape($profile["division"]); ?>"
                        readonly
                    >

                </div>


                <!-- Mobile Number -->

                <div class="profile-field">

                    <label for="mobile">
                        Mobile Number
                    </label>

                    <input
                        type="text"
                        id="mobile"
                        name="mobile"
                        value="<?php echo escape($profile["mobile_number"] ?? ""); ?>"
                        readonly
                    >

                </div>


                <!-- Address -->

                <div class="profile-field profile-full-width">

                    <label for="address">
                        Address
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        readonly
                    ><?php echo escape($profile["address"] ?? ""); ?></textarea>

                </div>


            </div>


            <!-- Edit Button -->

            <div
                class="button-group"
                id="editInformationButtons"
            >

                <button
                    type="button"
                    class="profile-button primary-button"
                    onclick="enableProfileEditing();"
                >
                    Edit Information
                </button>

            </div>


            <!-- Save / Cancel Buttons -->

            <div
                class="button-group hidden"
                id="saveInformationButtons"
            >

                <button
                    type="submit"
                    class="profile-button primary-button"
                >
                    Save Information
                </button>

                <button
                    type="button"
                    class="profile-button secondary-button"
                    onclick="cancelProfileEditing();"
                >
                    Cancel
                </button>

            </div>


        </form>

    </div>


    <!-- =========================
         SECURITY
         ========================= -->

    <div class="profile-section">

        <h2>
            Security
        </h2>


        <!-- Change Password Button -->

        <div id="changePasswordButton">

            <button
                type="button"
                class="profile-button primary-button"
                onclick="showChangePassword();"
            >
                Change Password
            </button>

        </div>


        <!-- Password Form -->

        <div
            id="changePasswordSection"
            class="hidden"
        >

            <form
                action="../actions/profile.php"
                method="POST"
                onsubmit="return validatePasswordForm();"
            >

                <input
                    type="hidden"
                    name="action"
                    value="change_password"
                >


                <!-- Current Password -->

                <div class="profile-field">

                    <label for="current_password">
                        Current Password
                    </label>

                    <div class="password-box">

                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            autocomplete="current-password"
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword('current_password', this);"
                            aria-label="Show or hide current password"
                        >
                            👁
                        </button>

                    </div>

                </div>


                <!-- New Password -->

                <div class="profile-field">

                    <label for="new_password">
                        New Password
                    </label>

                    <div class="password-box">

                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            autocomplete="new-password"
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword('new_password', this);"
                            aria-label="Show or hide new password"
                        >
                            👁
                        </button>

                    </div>

                    <p class="form-help">
                        Password must contain at least 8 characters.
                    </p>

                </div>


                <!-- Confirm Password -->

                <div class="profile-field">

                    <label for="confirm_password">
                        Confirm New Password
                    </label>

                    <div class="password-box">

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            autocomplete="new-password"
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword('confirm_password', this);"
                            aria-label="Show or hide confirm password"
                        >
                            👁
                        </button>

                    </div>

                </div>


                <div class="button-group">

                    <button
                        type="submit"
                        class="profile-button primary-button"
                    >
                        Change Password
                    </button>

                    <button
                        type="button"
                        class="profile-button secondary-button"
                        onclick="hideChangePassword();"
                    >
                        Cancel
                    </button>

                </div>


            </form>

        </div>

    </div>


    <!-- =========================
         ACCOUNT INFORMATION
         ========================= -->

    <div class="profile-section">

        <h2>
            Account Information
        </h2>


        <div class="profile-grid">


            <!-- Account Status -->

            <div class="profile-field">

                <label>
                    Account Status
                </label>

                <input
                    type="text"
                    value="<?php echo $profile["account_status"] ? "Active" : "Inactive"; ?>"
                    readonly
                >

            </div>


            <!-- Last Login -->

            <div class="profile-field">

                <label>
                    Last Login
                </label>

                <input
                    type="text"
                    value="<?php echo escape(formatDateTime($profile["last_login"])); ?>"
                    readonly
                >

            </div>


            <!-- Account Created -->

            <div class="profile-field">

                <label>
                    Account Created
                </label>

                <input
                    type="text"
                    value="<?php echo escape(formatDateTime($profile["created_at"])); ?>"
                    readonly
                >

            </div>


        </div>

    </div>


    <!-- =========================
         LOGOUT
         ========================= -->

    <div class="profile-logout-section">

        <a
            href="../logout.php"
            class="profile-button danger-button"
        >
            Logout
        </a>

    </div>


</div>


<script src="../js/script-profile.js"></script>


<script>

/* =========================
   PROFILE EDITING
   ========================= */

function enableProfileEditing() {

    const editableFields = [
        "full_name",
        "mobile",
        "address"
    ];

    editableFields.forEach(function(fieldId) {

        const field =
            document.getElementById(fieldId);

        if (field) {

            field.removeAttribute("readonly");

            field.classList.add(
                "editable-field"
            );
        }

    });


    document.getElementById(
        "editInformationButtons"
    ).classList.add("hidden");


    document.getElementById(
        "saveInformationButtons"
    ).classList.remove("hidden");


    document.getElementById(
        "full_name"
    ).focus();
}


/* =========================
   CANCEL PROFILE EDITING
   ========================= */

function cancelProfileEditing() {

    const form =
        document.getElementById(
            "profileInformationForm"
        );

    form.reset();


    const editableFields = [
        "full_name",
        "mobile",
        "address"
    ];

    editableFields.forEach(function(fieldId) {

        const field =
            document.getElementById(fieldId);

        if (field) {

            field.setAttribute(
                "readonly",
                "readonly"
            );

            field.classList.remove(
                "editable-field"
            );
        }

    });


    document.getElementById(
        "saveInformationButtons"
    ).classList.add("hidden");


    document.getElementById(
        "editInformationButtons"
    ).classList.remove("hidden");

}


/* =========================
   CHANGE PASSWORD
   ========================= */

function showChangePassword() {

    document.getElementById(
        "changePasswordButton"
    ).classList.add("hidden");


    document.getElementById(
        "changePasswordSection"
    ).classList.remove("hidden");

}


function hideChangePassword() {

    document.getElementById(
        "changePasswordSection"
    ).classList.add("hidden");


    document.getElementById(
        "changePasswordButton"
    ).classList.remove("hidden");

}

</script>


</body>

</html>