/*
 * CampusDesk
 * Student Profile JavaScript
 */


/* =========================
   PASSWORD SHOW / HIDE
   ========================= */

function togglePassword(
    fieldId,
    button
) {

    const field =
        document.getElementById(fieldId);

    if (!field) {
        return;
    }


    if (field.type === "password") {

        field.type = "text";

        button.textContent = "🙈";

    } else {

        field.type = "password";

        button.textContent = "👁";
    }
}


/* =========================
   PROFILE FORM VALIDATION
   ========================= */

function validateProfileForm()
{
    const mobile =
        document.getElementById("mobile");


    if (!mobile) {
        return true;
    }


    const mobileValue =
        mobile.value.trim();


    /*
     * Mobile number is optional.
     */

    if (mobileValue === "") {
        return true;
    }


    /*
     * Allow 10 to 15 digits.
     */

    const mobilePattern =
        /^[0-9]{10,15}$/;


    if (!mobilePattern.test(mobileValue)) {

        alert(
            "Please enter a valid mobile number."
        );

        mobile.focus();

        return false;
    }


    return true;
}


/* =========================
   PASSWORD FORM VALIDATION
   ========================= */

function validatePasswordForm()
{
    const currentPassword =
        document.getElementById(
            "current_password"
        ).value;

    const newPassword =
        document.getElementById(
            "new_password"
        ).value;

    const confirmPassword =
        document.getElementById(
            "confirm_password"
        ).value;


    /* =========================
       EMPTY CHECK
       ========================= */

    if (
        currentPassword === "" ||
        newPassword === "" ||
        confirmPassword === ""
    ) {

        alert(
            "Please fill in all password fields."
        );

        return false;
    }


    /* =========================
       PASSWORD LENGTH
       ========================= */

    if (newPassword.length < 8) {

        alert(
            "New password must contain at least 8 characters."
        );

        document
            .getElementById("new_password")
            .focus();

        return false;
    }


    /* =========================
       CONFIRM PASSWORD
       ========================= */

    if (
        newPassword !==
        confirmPassword
    ) {

        alert(
            "New password and confirm password do not match."
        );

        document
            .getElementById("confirm_password")
            .focus();

        return false;
    }


    return true;
}


/* =========================
   PROFILE PHOTO VALIDATION
   ========================= */

function validateProfilePhoto()
{
    const photo =
        document.getElementById(
            "profile_photo"
        );


    if (!photo) {
        return true;
    }


    if (
        photo.files.length === 0
    ) {

        alert(
            "Please select a profile photo."
        );

        return false;
    }


    const file =
        photo.files[0];


    /* =========================
       FILE TYPE
       ========================= */

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
            "Only JPG and PNG profile photos are allowed."
        );

        photo.value = "";

        return false;
    }


    /* =========================
       FILE SIZE
       ========================= */

    const maxSize =
        5 * 1024 * 1024;


    if (
        file.size > maxSize
    ) {

        alert(
            "Profile photo must not exceed 5 MB."
        );

        photo.value = "";

        return false;
    }


    return true;
}


/* =========================
   PROFILE PHOTO PREVIEW
   ========================= */

function previewProfilePhoto()
{
    const photo =
        document.getElementById(
            "profile_photo"
        );

    const preview =
        document.getElementById(
            "profilePhotoPreview"
        );

    const defaultIcon =
        document.getElementById(
            "defaultProfileIcon"
        );


    if (
        !photo ||
        !preview
    ) {
        return;
    }


    if (
        photo.files.length === 0
    ) {
        return;
    }


    const file =
        photo.files[0];


    if (
        !validateProfilePhoto()
    ) {
        return;
    }


    const reader =
        new FileReader();


    reader.onload = function(event) {

        preview.src =
            event.target.result;

        preview.classList.remove(
            "hidden"
        );


        if (defaultIcon) {

            defaultIcon.classList.add(
                "hidden"
            );
        }
    };


    reader.readAsDataURL(file);
}


/* =========================
   REMOVE PHOTO CONFIRMATION
   ========================= */

function confirmRemovePhoto()
{
    return confirm(
        "Are you sure you want to remove your profile photo?"
    );
}


/* =========================
   PAGE LOAD
   ========================= */

document.addEventListener(
    "DOMContentLoaded",
    function()
    {

        const photo =
            document.getElementById(
                "profile_photo"
            );


        if (photo) {

            photo.addEventListener(
                "change",
                previewProfilePhoto
            );
        }


        const photoForm =
            document.querySelector(
                ".profile-photo-form"
            );


        if (photoForm) {

            photoForm.addEventListener(
                "submit",
                function(event)
                {

                    if (
                        !validateProfilePhoto()
                    ) {

                        event.preventDefault();
                    }

                }
            );
        }

    }
);