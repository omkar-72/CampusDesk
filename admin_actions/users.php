<?php

/*
 * CampusDesk
 * Admin User Actions
 *
 * Handles:
 * - Create User
 * - Update User
 * - Activate User
 * - Deactivate User
 * - Admin Profile Update
 * - Admin Profile Photo Update
 * - Admin Profile Photo Removal
 * - Admin Password Change
 */

require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/audit.php";
require_once "../admin/admin_auth.php";

requireAdmin();

$admin_user_id = getAdminUserId();


/* =========================================================
   REQUEST METHOD
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../admin/users.php");
    exit;
}


/* =========================================================
   ACTION
========================================================= */

$action = $_POST["action"] ?? "";


/* =========================================================
   HELPERS
========================================================= */

function redirectUsersSuccess($message)
{
    $_SESSION["admin_users_message"] = [
        "type" => "success",
        "message" => $message
    ];

    header("Location: ../admin/users.php");
    exit;
}


function redirectUsersError($message)
{
    $_SESSION["admin_users_message"] = [
        "type" => "error",
        "message" => $message
    ];

    header("Location: ../admin/users.php");
    exit;
}


function redirectProfileSuccess($message)
{
    header(
        "Location: ../admin/profile.php?success=" .
            urlencode($message)
    );

    exit;
}


function redirectProfileError($message)
{
    header(
        "Location: ../admin/profile.php?error=" .
            urlencode($message)
    );

    exit;
}


function getUploadedFileType($file)
{
    if (
        !isset($file["tmp_name"]) ||
        !is_file($file["tmp_name"])
    ) {
        return false;
    }

    if (function_exists("finfo_open")) {

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo !== false) {

            $file_type = finfo_file(
                $finfo,
                $file["tmp_name"]
            );

            finfo_close($finfo);

            return $file_type;
        }
    }

    return $file["type"] ?? false;
}


function prepareProfilePhoto($file, $conn)
{
    if (
        !isset($file) ||
        !isset($file["error"])
    ) {
        return [
            "success" => false,
            "message" => "Invalid photo upload."
        ];
    }

    if (
        $file["error"] === UPLOAD_ERR_NO_FILE
    ) {
        return [
            "success" => true,
            "uploaded" => false,
            "data" => null
        ];
    }

    if (
        $file["error"] !== UPLOAD_ERR_OK
    ) {
        return [
            "success" => false,
            "message" => "Profile photo upload failed."
        ];
    }

    if (
        !isAllowedProfilePhotoSize(
            $file["size"]
        )
    ) {
        return [
            "success" => false,
            "message" => "Profile photo must be 5 MB or smaller."
        ];
    }

    $file_type = getUploadedFileType($file);

    if (
        !$file_type ||
        !isAllowedProfilePhotoType($file_type)
    ) {
        return [
            "success" => false,
            "message" => "Only JPG and PNG profile photos are allowed."
        ];
    }

    $file_data = file_get_contents(
        $file["tmp_name"]
    );

    if ($file_data === false) {
        return [
            "success" => false,
            "message" => "Unable to read profile photo."
        ];
    }

    $escaped_data = pg_escape_bytea(
        $conn,
        $file_data
    );

    return [
        "success" => true,
        "uploaded" => true,
        "data" => $escaped_data
    ];
}


function getRoleIdByName($conn, $role_name)
{
    $result = pg_query_params(
        $conn,
        "
        SELECT role_id
        FROM roles
        WHERE role_name = $1
        LIMIT 1
        ",
        [$role_name]
    );

    if (
        !$result ||
        pg_num_rows($result) === 0
    ) {
        return false;
    }

    $row = pg_fetch_assoc($result);

    return (int) $row["role_id"];
}


function getDepartmentId($conn, $department_id)
{
    if (
        !is_numeric($department_id) ||
        (int) $department_id <= 0
    ) {
        return false;
    }

    $result = pg_query_params(
        $conn,
        "
        SELECT department_id
        FROM departments
        WHERE department_id = $1
        LIMIT 1
        ",
        [(int) $department_id]
    );

    if (
        !$result ||
        pg_num_rows($result) === 0
    ) {
        return false;
    }

    return (int) $department_id;
}


function userExists($conn, $user_id)
{
    if (
        !is_numeric($user_id) ||
        (int) $user_id <= 0
    ) {
        return false;
    }

    $result = pg_query_params(
        $conn,
        "
        SELECT user_id
        FROM users
        WHERE user_id = $1
        LIMIT 1
        ",
        [(int) $user_id]
    );

    return (
        $result &&
        pg_num_rows($result) > 0
    );
}


function emailExists(
    $conn,
    $email,
    $exclude_user_id = null
) {
    if ($exclude_user_id !== null) {

        $result = pg_query_params(
            $conn,
            "
            SELECT user_id
            FROM users
            WHERE LOWER(email) = LOWER($1)
              AND user_id <> $2
            LIMIT 1
            ",
            [
                $email,
                (int) $exclude_user_id
            ]
        );
    } else {

        $result = pg_query_params(
            $conn,
            "
            SELECT user_id
            FROM users
            WHERE LOWER(email) = LOWER($1)
            LIMIT 1
            ",
            [$email]
        );
    }

    return (
        $result &&
        pg_num_rows($result) > 0
    );
}


function prnExists(
    $conn,
    $prn,
    $exclude_student_id = null
) {
    if ($prn === "") {
        return false;
    }

    if ($exclude_student_id !== null) {

        $result = pg_query_params(
            $conn,
            "
            SELECT student_id
            FROM students
            WHERE LOWER(prn) = LOWER($1)
              AND student_id <> $2
            LIMIT 1
            ",
            [
                $prn,
                (int) $exclude_student_id
            ]
        );
    } else {

        $result = pg_query_params(
            $conn,
            "
            SELECT student_id
            FROM students
            WHERE LOWER(prn) = LOWER($1)
            LIMIT 1
            ",
            [$prn]
        );
    }

    return (
        $result &&
        pg_num_rows($result) > 0
    );
}


/* =========================================================
   CREATE USER
========================================================= */

if ($action === "create") {

    $email = trim(
        $_POST["email"] ?? ""
    );

    $mobile = trim(
        $_POST["mobile"] ??
            $_POST["mobile_number"] ??
            ""
    );

    $password = $_POST["password"] ?? "";

    $role_name = strtoupper(
        trim(
            $_POST["role_name"] ??
                $_POST["role"] ??
                ""
        )
    );

    $account_status = isset(
        $_POST["account_status"]
    )
        ? (bool) $_POST["account_status"]
        : true;


    /* -----------------------------------------------------
       BASIC VALIDATION
    ----------------------------------------------------- */

    if (
        $email === "" ||
        $password === "" ||
        $role_name === ""
    ) {
        redirectUsersError(
            "Email, password and role are required."
        );
    }


    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        redirectUsersError(
            "Please enter a valid email address."
        );
    }


    if (!isValidMobileNumber($mobile)) {

        redirectUsersError(
            "Please enter a valid mobile number."
        );
    }


    if (!isValidPassword($password)) {

        redirectUsersError(
            "Password must contain at least 8 characters."
        );
    }


    if (
        !in_array(
            $role_name,
            [
                "STUDENT",
                "AUTHORITY",
                "ADMIN"
            ],
            true
        )
    ) {
        redirectUsersError(
            "Invalid user role."
        );
    }


    /* -----------------------------------------------------
       DUPLICATE EMAIL
    ----------------------------------------------------- */

    if (
        emailExists(
            $conn,
            $email
        )
    ) {
        redirectUsersError(
            "This email address is already registered."
        );
    }


    /* -----------------------------------------------------
       ROLE
    ----------------------------------------------------- */

    $role_id = getRoleIdByName(
        $conn,
        $role_name
    );

    if ($role_id === false) {

        redirectUsersError(
            "Selected role does not exist."
        );
    }


    /* -----------------------------------------------------
       PASSWORD HASH
    ----------------------------------------------------- */

    $hashed_password = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    if ($hashed_password === false) {

        redirectUsersError(
            "Unable to create password."
        );
    }


    /* -----------------------------------------------------
       PROFILE PHOTO
    ----------------------------------------------------- */

    $photo_data = null;

    if (
        isset($_FILES["profile_photo"]) &&
        $_FILES["profile_photo"]["error"] !==
        UPLOAD_ERR_NO_FILE
    ) {

        $photo = prepareProfilePhoto(
            $_FILES["profile_photo"],
            $conn
        );

        if (!$photo["success"]) {

            redirectUsersError(
                $photo["message"]
            );
        }

        if ($photo["uploaded"]) {
            $photo_data = $photo["data"];
        }
    }


    /* -----------------------------------------------------
       ROLE-SPECIFIC VALIDATION
    ----------------------------------------------------- */

    $department_id = null;

    if (
        $role_name === "STUDENT" ||
        $role_name === "AUTHORITY" ||
        $role_name === "ADMIN"
    ) {

        $department_id = getDepartmentId(
            $conn,
            $_POST["department_id"] ?? ""
        );

        if ($department_id === false) {

            redirectUsersError(
                "Please select a valid department."
            );
        }
    }


    /* =====================================================
       STUDENT DATA
    ===================================================== */

    $student_data = [];

    if ($role_name === "STUDENT") {

        $full_name = trim(
            $_POST["full_name"] ?? ""
        );

        $prn = trim(
            $_POST["prn"] ?? ""
        );

        $roll_no = trim(
            $_POST["roll_no"] ?? ""
        );

        $course = trim(
            $_POST["course"] ?? ""
        );

        $year = trim(
            $_POST["year"] ?? ""
        );

        $semester = trim(
            $_POST["semester"] ?? ""
        );

        $division = trim(
            $_POST["division"] ?? ""
        );

        $address = trim(
            $_POST["address"] ?? ""
        );


        if ($full_name === "") {

            redirectUsersError(
                "Student name is required."
            );
        }


        if ($prn !== "" && prnExists(
            $conn,
            $prn
        )) {
            redirectUsersError(
                "This PRN is already registered."
            );
        }


        if (
            $year !== "" &&
            (
                !ctype_digit($year) ||
                (int) $year <= 0
            )
        ) {
            redirectUsersError(
                "Invalid student year."
            );
        }


        if (
            $semester !== "" &&
            (
                !ctype_digit($semester) ||
                (int) $semester <= 0
            )
        ) {
            redirectUsersError(
                "Invalid student semester."
            );
        }


        $student_data = [
            "full_name" => $full_name,
            "prn" => $prn !== "" ? $prn : null,
            "roll_no" => $roll_no !== "" ? $roll_no : null,
            "course" => $course !== "" ? $course : null,
            "year" => $year !== ""
                ? (int) $year
                : null,
            "semester" => $semester !== ""
                ? (int) $semester
                : null,
            "division" => $division !== ""
                ? $division
                : null,
            "address" => $address !== ""
                ? $address
                : null
        ];
    }


    /* =====================================================
       AUTHORITY / ADMIN DATA
    ===================================================== */

    $authority_data = [];

    if (
        $role_name === "AUTHORITY" ||
        $role_name === "ADMIN"
    ) {

        $name = trim(
            $_POST["name"] ??
                $_POST["full_name"] ??
                ""
        );

        $designation = trim(
            $_POST["designation"] ?? ""
        );


        if ($name === "") {

            redirectUsersError(
                "Name is required."
            );
        }


        $authority_data = [
            "name" => $name,
            "designation" =>
            $designation !== ""
                ? $designation
                : null
        ];
    }


    /* =====================================================
       TRANSACTION
    ===================================================== */

    pg_query(
        $conn,
        "BEGIN"
    );

    try {

        /* -------------------------------------------------
           CREATE USER
        ------------------------------------------------- */

        $user_result = pg_query_params(
            $conn,
            "
            INSERT INTO users
            (
                role_id,
                email,
                password,
                mobile_number,
                account_status,
                created_at,
                updated_at,
                profile_photo
            )
            VALUES
            (
                $1,
                $2,
                $3,
                $4,
                $5,
                CURRENT_TIMESTAMP,
                CURRENT_TIMESTAMP,
                $6
            )
            RETURNING user_id
            ",
            [
                $role_id,
                $email,
                $hashed_password,
                $mobile !== ""
                    ? $mobile
                    : null,
                $account_status
                    ? "t"
                    : "f",
                $photo_data
            ]
        );


        if (!$user_result) {

            throw new Exception(
                "Failed to create user."
            );
        }


        $user_row = pg_fetch_assoc(
            $user_result
        );

        $new_user_id = (int)
        $user_row["user_id"];


        /* -------------------------------------------------
           CREATE STUDENT
        ------------------------------------------------- */

        if ($role_name === "STUDENT") {

            $student_result = pg_query_params(
                $conn,
                "
                INSERT INTO students
                (
                    user_id,
                    department_id,
                    full_name,
                    prn,
                    roll_no,
                    course,
                    year,
                    semester,
                    division,
                    address
                )
                VALUES
                (
                    $1,
                    $2,
                    $3,
                    $4,
                    $5,
                    $6,
                    $7,
                    $8,
                    $9,
                    $10
                )
                ",
                [
                    $new_user_id,
                    $department_id,
                    $student_data["full_name"],
                    $student_data["prn"],
                    $student_data["roll_no"],
                    $student_data["course"],
                    $student_data["year"],
                    $student_data["semester"],
                    $student_data["division"],
                    $student_data["address"]
                ]
            );


            if (!$student_result) {

                throw new Exception(
                    "Failed to create student profile."
                );
            }
        }


        /* -------------------------------------------------
           CREATE AUTHORITY / ADMIN PROFILE
        ------------------------------------------------- */

        if (
            $role_name === "AUTHORITY" ||
            $role_name === "ADMIN"
        ) {

            $authority_status = $account_status
                ? "t"
                : "f";


            $authority_result = pg_query_params(
                $conn,
                "
                INSERT INTO authorities
                (
                    user_id,
                    department_id,
                    name,
                    designation,
                    status
                )
                VALUES
                (
                    $1,
                    $2,
                    $3,
                    $4,
                    $5
                )
                ",
                [
                    $new_user_id,
                    $department_id,
                    $authority_data["name"],
                    $authority_data["designation"],
                    $authority_status
                ]
            );


            if (!$authority_result) {

                throw new Exception(
                    "Failed to create professional profile."
                );
            }
        }


        /* -------------------------------------------------
           AUDIT
        ------------------------------------------------- */

        addAuditLog(
            $conn,
            $admin_user_id,
            "USER",
            $new_user_id,
            "CREATE_USER"
        );


        pg_query(
            $conn,
            "COMMIT"
        );


        redirectUsersSuccess(
            "User created successfully."
        );
    } catch (Exception $e) {

        pg_query(
            $conn,
            "ROLLBACK"
        );

        redirectUsersError(
            $e->getMessage()
        );
    }
}


/* =========================================================
   UPDATE USER
========================================================= */

if ($action === "edit") {

    $user_id = $_POST["user_id"] ?? "";

    if (
        !is_numeric($user_id) ||
        (int) $user_id <= 0
    ) {
        redirectUsersError(
            "Invalid user ID."
        );
    }

    $user_id = (int) $user_id;


    if (!userExists(
        $conn,
        $user_id
    )) {
        redirectUsersError(
            "User not found."
        );
    }


    /* -----------------------------------------------------
       GET EXISTING USER + ROLE
    ----------------------------------------------------- */

    $existing_result = pg_query_params(
        $conn,
        "
        SELECT
            u.user_id,
            u.role_id,
            u.email,
            u.mobile_number,
            u.account_status,
            r.role_name
        FROM users u
        INNER JOIN roles r
            ON u.role_id = r.role_id
        WHERE u.user_id = $1
        LIMIT 1
        ",
        [$user_id]
    );


    if (
        !$existing_result ||
        pg_num_rows($existing_result) === 0
    ) {
        redirectUsersError(
            "User account not found."
        );
    }


    $existing_user = pg_fetch_assoc(
        $existing_result
    );

    $role_name = strtoupper(
        $existing_user["role_name"]
    );


    /* -----------------------------------------------------
       ADMIN PROFILE SOURCE
    ----------------------------------------------------- */

    $profile_source =
        $_POST["profile_source"] ?? "";


    /* -----------------------------------------------------
       COMMON USER DATA
    ----------------------------------------------------- */

    $email = trim(
        $_POST["email"] ??
            $existing_user["email"]
    );

    $mobile = trim(
        $_POST["mobile"] ??
            $_POST["mobile_number"] ??
            $existing_user["mobile_number"] ??
            ""
    );


    if (
        $email === "" ||
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {
        if ($profile_source === "admin_profile") {
            redirectProfileError(
                "Please enter a valid email address."
            );
        }

        redirectUsersError(
            "Please enter a valid email address."
        );
    }


    if (!isValidMobileNumber($mobile)) {

        if ($profile_source === "admin_profile") {
            redirectProfileError(
                "Please enter a valid mobile number."
            );
        }

        redirectUsersError(
            "Please enter a valid mobile number."
        );
    }


    if (
        emailExists(
            $conn,
            $email,
            $user_id
        )
    ) {

        if ($profile_source === "admin_profile") {
            redirectProfileError(
                "This email address is already registered."
            );
        }

        redirectUsersError(
            "This email address is already registered."
        );
    }


    /* -----------------------------------------------------
       PASSWORD
    ----------------------------------------------------- */

    $new_password = $_POST["password"] ?? "";

    $hashed_password = null;

    if ($new_password !== "") {

        if (!isValidPassword(
            $new_password
        )) {

            if ($profile_source === "admin_profile") {
                redirectProfileError(
                    "Password must contain at least 8 characters."
                );
            }

            redirectUsersError(
                "Password must contain at least 8 characters."
            );
        }


        $hashed_password = password_hash(
            $new_password,
            PASSWORD_DEFAULT
        );


        if ($hashed_password === false) {

            if ($profile_source === "admin_profile") {
                redirectProfileError(
                    "Unable to process password."
                );
            }

            redirectUsersError(
                "Unable to process password."
            );
        }
    }


    /* -----------------------------------------------------
       PHOTO
    ----------------------------------------------------- */

    $photo_uploaded = false;
    $photo_data = null;


    if (
        isset($_FILES["profile_photo"]) &&
        $_FILES["profile_photo"]["error"] !==
        UPLOAD_ERR_NO_FILE
    ) {

        $photo = prepareProfilePhoto(
            $_FILES["profile_photo"],
            $conn
        );


        if (!$photo["success"]) {

            if ($profile_source === "admin_profile") {
                redirectProfileError(
                    $photo["message"]
                );
            }

            redirectUsersError(
                $photo["message"]
            );
        }


        if ($photo["uploaded"]) {

            $photo_uploaded = true;
            $photo_data = $photo["data"];
        }
    }


    /* -----------------------------------------------------
       ADMIN PROFILE
    ----------------------------------------------------- */

    if (
        $profile_source === "admin_profile"
    ) {

        if ($role_name !== "ADMIN") {

            redirectProfileError(
                "Invalid administrator account."
            );
        }


        $name = trim(
            $_POST["name"] ??
                ""
        );

        $designation = trim(
            $_POST["designation"] ??
                ""
        );

        $department_id = getDepartmentId(
            $conn,
            $_POST["department_id"] ?? ""
        );


        if ($name === "") {

            redirectProfileError(
                "Administrator name is required."
            );
        }


        if ($department_id === false) {

            redirectProfileError(
                "Please select a valid department."
            );
        }


        pg_query(
            $conn,
            "BEGIN"
        );


        try {

            /* ---------------------------------------------
               UPDATE USER
            --------------------------------------------- */

            if (
                $hashed_password !== null
            ) {

                if ($photo_uploaded) {

                    $result = pg_query_params(
                        $conn,
                        "
                        UPDATE users
                        SET
                            email = $1,
                            mobile_number = $2,
                            password = $3,
                            profile_photo = $4,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE user_id = $5
                        ",
                        [
                            $email,
                            $mobile !== ""
                                ? $mobile
                                : null,
                            $hashed_password,
                            $photo_data,
                            $user_id
                        ]
                    );
                } elseif (
                    $profile_source ===
                    "admin_profile"
                ) {

                    $result = pg_query_params(
                        $conn,
                        "
                        UPDATE users
                        SET
                            email = $1,
                            mobile_number = $2,
                            password = $3,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE user_id = $4
                        ",
                        [
                            $email,
                            $mobile !== ""
                                ? $mobile
                                : null,
                            $hashed_password,
                            $user_id
                        ]
                    );
                }
            } elseif ($photo_uploaded) {

                $result = pg_query_params(
                    $conn,
                    "
                    UPDATE users
                    SET
                        email = $1,
                        mobile_number = $2,
                        profile_photo = $3,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = $4
                    ",
                    [
                        $email,
                        $mobile !== ""
                            ? $mobile
                            : null,
                        $photo_data,
                        $user_id
                    ]
                );
            } else {

                $result = pg_query_params(
                    $conn,
                    "
                    UPDATE users
                    SET
                        email = $1,
                        mobile_number = $2,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = $3
                    ",
                    [
                        $email,
                        $mobile !== ""
                            ? $mobile
                            : null,
                        $user_id
                    ]
                );
            }


            if (!$result) {

                throw new Exception(
                    "Failed to update administrator account."
                );
            }


            /* ---------------------------------------------
               CREATE OR UPDATE AUTHORITY PROFILE
            --------------------------------------------- */

            $authority_check = pg_query_params(
                $conn,
                "
                SELECT authority_id
                FROM authorities
                WHERE user_id = $1
                LIMIT 1
                ",
                [$user_id]
            );

            if (!$authority_check) {
                throw new Exception(
                    "Unable to check administrator professional profile."
                );
            }

            $authority_status = true;

            if (pg_num_rows($authority_check) > 0) {

                $authority_result = pg_query_params(
                    $conn,
                    "
                    UPDATE authorities
                    SET
                        name = $1,
                        department_id = $2,
                        designation = $3,
                        status = $4
                    WHERE user_id = $5
                    ",
                    [
                        $name,
                        $department_id,
                        $designation !== ""
                            ? $designation
                            : null,
                        $authority_status ? "t" : "f",
                        $user_id
                    ]
                );

                if (!$authority_result) {
                    throw new Exception(
                        "Failed to update administrator professional profile."
                    );
                }
            } else {

                $authority_result = pg_query_params(
                    $conn,
                    "
                    INSERT INTO authorities
                    (
                        user_id,
                        department_id,
                        name,
                        designation,
                        status
                    )
                    VALUES
                    ($1, $2, $3, $4, $5)
                    ",
                    [
                        $user_id,
                        $department_id,
                        $name,
                        $designation !== ""
                            ? $designation
                            : null,
                        $authority_status ? "t" : "f"
                    ]
                );

                if (!$authority_result) {
                    throw new Exception(
                        "Failed to create administrator professional profile."
                    );
                }
            }

            addAuditLog(
                $conn,
                $admin_user_id,
                "USER",
                $user_id,
                "UPDATE_ADMIN_PROFILE"
            );


            pg_query(
                $conn,
                "COMMIT"
            );


            redirectProfileSuccess(
                "profile_updated"
            );
        } catch (Exception $e) {

            pg_query(
                $conn,
                "ROLLBACK"
            );

            redirectProfileError(
                $e->getMessage()
            );
        }
    }


    /* -----------------------------------------------------
       NORMAL ADMIN USER EDIT
    ----------------------------------------------------- */

    $account_status = isset(
        $_POST["account_status"]
    )
        ? (bool) $_POST["account_status"]
        : (
            $existing_user["account_status"] === "t"
        );


    /* =====================================================
       STUDENT
    ===================================================== */

    if ($role_name === "STUDENT") {

        $full_name = trim(
            $_POST["full_name"] ?? ""
        );

        $prn = trim(
            $_POST["prn"] ?? ""
        );

        $roll_no = trim(
            $_POST["roll_no"] ?? ""
        );

        $course = trim(
            $_POST["course"] ?? ""
        );

        $year = trim(
            $_POST["year"] ?? ""
        );

        $semester = trim(
            $_POST["semester"] ?? ""
        );

        $division = trim(
            $_POST["division"] ?? ""
        );

        $address = trim(
            $_POST["address"] ?? ""
        );

        $department_id = getDepartmentId(
            $conn,
            $_POST["department_id"] ?? ""
        );


        if ($full_name === "") {
            redirectUsersError(
                "Student name is required."
            );
        }


        if ($department_id === false) {
            redirectUsersError(
                "Please select a valid department."
            );
        }


        /* -------------------------------------------------
           GET STUDENT ID
        ------------------------------------------------- */

        $student_result = pg_query_params(
            $conn,
            "
            SELECT student_id
            FROM students
            WHERE user_id = $1
            LIMIT 1
            ",
            [$user_id]
        );


        if (
            !$student_result ||
            pg_num_rows($student_result) === 0
        ) {
            redirectUsersError(
                "Student profile not found."
            );
        }


        $student_row = pg_fetch_assoc(
            $student_result
        );

        $student_id = (int)
        $student_row["student_id"];


        /* -------------------------------------------------
           PRN DUPLICATE
        ------------------------------------------------- */

        if (
            $prn !== "" &&
            prnExists(
                $conn,
                $prn,
                $student_id
            )
        ) {
            redirectUsersError(
                "This PRN is already registered."
            );
        }


        if (
            $year !== "" &&
            (
                !ctype_digit($year) ||
                (int) $year <= 0
            )
        ) {
            redirectUsersError(
                "Invalid student year."
            );
        }


        if (
            $semester !== "" &&
            (
                !ctype_digit($semester) ||
                (int) $semester <= 0
            )
        ) {
            redirectUsersError(
                "Invalid student semester."
            );
        }


        pg_query(
            $conn,
            "BEGIN"
        );


        try {

            /* ---------------------------------------------
               UPDATE USERS
            --------------------------------------------- */

            if (
                $hashed_password !== null &&
                $photo_uploaded
            ) {

                $result = pg_query_params(
                    $conn,
                    "
                    UPDATE users
                    SET
                        email = $1,
                        mobile_number = $2,
                        password = $3,
                        account_status = $4,
                        profile_photo = $5,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = $6
                    ",
                    [
                        $email,
                        $mobile !== ""
                            ? $mobile
                            : null,
                        $hashed_password,
                        $account_status
                            ? "t"
                            : "f",
                        $photo_data,
                        $user_id
                    ]
                );
            } elseif (
                $hashed_password !== null
            ) {

                $result = pg_query_params(
                    $conn,
                    "
                    UPDATE users
                    SET
                        email = $1,
                        mobile_number = $2,
                        password = $3,
                        account_status = $4,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = $5
                    ",
                    [
                        $email,
                        $mobile !== ""
                            ? $mobile
                            : null,
                        $hashed_password,
                        $account_status
                            ? "t"
                            : "f",
                        $user_id
                    ]
                );
            } elseif ($photo_uploaded) {

                $result = pg_query_params(
                    $conn,
                    "
                    UPDATE users
                    SET
                        email = $1,
                        mobile_number = $2,
                        account_status = $3,
                        profile_photo = $4,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = $5
                    ",
                    [
                        $email,
                        $mobile !== ""
                            ? $mobile
                            : null,
                        $account_status
                            ? "t"
                            : "f",
                        $photo_data,
                        $user_id
                    ]
                );
            } else {

                $result = pg_query_params(
                    $conn,
                    "
                    UPDATE users
                    SET
                        email = $1,
                        mobile_number = $2,
                        account_status = $3,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = $4
                    ",
                    [
                        $email,
                        $mobile !== ""
                            ? $mobile
                            : null,
                        $account_status
                            ? "t"
                            : "f",
                        $user_id
                    ]
                );
            }


            if (!$result) {

                throw new Exception(
                    "Failed to update user account."
                );
            }


            /* ---------------------------------------------
               UPDATE STUDENT
            --------------------------------------------- */

            $student_update = pg_query_params(
                $conn,
                "
                UPDATE students
                SET
                    department_id = $1,
                    full_name = $2,
                    prn = $3,
                    roll_no = $4,
                    course = $5,
                    year = $6,
                    semester = $7,
                    division = $8,
                    address = $9
                WHERE user_id = $10
                ",
                [
                    $department_id,
                    $full_name,
                    $prn !== ""
                        ? $prn
                        : null,
                    $roll_no !== ""
                        ? $roll_no
                        : null,
                    $course !== ""
                        ? $course
                        : null,
                    $year !== ""
                        ? (int) $year
                        : null,
                    $semester !== ""
                        ? (int) $semester
                        : null,
                    $division !== ""
                        ? $division
                        : null,
                    $address !== ""
                        ? $address
                        : null,
                    $user_id
                ]
            );


            if (!$student_update) {

                throw new Exception(
                    "Failed to update student information."
                );
            }


            addAuditLog(
                $conn,
                $admin_user_id,
                "USER",
                $user_id,
                "UPDATE_USER"
            );


            pg_query(
                $conn,
                "COMMIT"
            );


            redirectUsersSuccess(
                "Student information updated successfully."
            );
        } catch (Exception $e) {

            pg_query(
                $conn,
                "ROLLBACK"
            );

            redirectUsersError(
                $e->getMessage()
            );
        }
    }


    /* =====================================================
       AUTHORITY / ADMIN
    ===================================================== */

    if (
        $role_name === "AUTHORITY" ||
        $role_name === "ADMIN"
    ) {

        $name = trim(
            $_POST["name"] ??
                $_POST["full_name"] ??
                ""
        );

        $designation = trim(
            $_POST["designation"] ?? ""
        );

        $department_id = getDepartmentId(
            $conn,
            $_POST["department_id"] ?? ""
        );


        if ($name === "") {

            redirectUsersError(
                "Name is required."
            );
        }


        if ($department_id === false) {

            redirectUsersError(
                "Please select a valid department."
            );
        }


        /* -------------------------------------------------
           CHECK AUTHORITY PROFILE
        ------------------------------------------------- */

        $authority_result = pg_query_params(
            $conn,
            "
            SELECT authority_id
            FROM authorities
            WHERE user_id = $1
            LIMIT 1
            ",
            [$user_id]
        );


        if (!$authority_result) {
            redirectUsersError(
                "Unable to check professional profile."
            );
        }

        $authority_exists = pg_num_rows($authority_result) > 0;


        pg_query(
            $conn,
            "BEGIN"
        );


        try {

            /* ---------------------------------------------
               UPDATE USERS
            --------------------------------------------- */

            if (
                $hashed_password !== null &&
                $photo_uploaded
            ) {

                $result = pg_query_params(
                    $conn,
                    "
                    UPDATE users
                    SET
                        email = $1,
                        mobile_number = $2,
                        password = $3,
                        account_status = $4,
                        profile_photo = $5,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = $6
                    ",
                    [
                        $email,
                        $mobile !== ""
                            ? $mobile
                            : null,
                        $hashed_password,
                        $account_status
                            ? "t"
                            : "f",
                        $photo_data,
                        $user_id
                    ]
                );
            } elseif (
                $hashed_password !== null
            ) {

                $result = pg_query_params(
                    $conn,
                    "
                    UPDATE users
                    SET
                        email = $1,
                        mobile_number = $2,
                        password = $3,
                        account_status = $4,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = $5
                    ",
                    [
                        $email,
                        $mobile !== ""
                            ? $mobile
                            : null,
                        $hashed_password,
                        $account_status
                            ? "t"
                            : "f",
                        $user_id
                    ]
                );
            } elseif ($photo_uploaded) {

                $result = pg_query_params(
                    $conn,
                    "
                    UPDATE users
                    SET
                        email = $1,
                        mobile_number = $2,
                        account_status = $3,
                        profile_photo = $4,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = $5
                    ",
                    [
                        $email,
                        $mobile !== ""
                            ? $mobile
                            : null,
                        $account_status
                            ? "t"
                            : "f",
                        $photo_data,
                        $user_id
                    ]
                );
            } else {

                $result = pg_query_params(
                    $conn,
                    "
                    UPDATE users
                    SET
                        email = $1,
                        mobile_number = $2,
                        account_status = $3,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = $4
                    ",
                    [
                        $email,
                        $mobile !== ""
                            ? $mobile
                            : null,
                        $account_status
                            ? "t"
                            : "f",
                        $user_id
                    ]
                );
            }


            if (!$result) {

                throw new Exception(
                    "Failed to update user account."
                );
            }


            /* ---------------------------------------------
               CREATE OR UPDATE AUTHORITY
            --------------------------------------------- */

            if ($authority_exists) {

                $authority_update = pg_query_params(
                    $conn,
                    "
                    UPDATE authorities
                    SET
                        department_id = $1,
                        name = $2,
                        designation = $3,
                        status = $4
                    WHERE user_id = $5
                    ",
                    [
                        $department_id,
                        $name,
                        $designation !== ""
                            ? $designation
                            : null,
                        $account_status
                            ? "t"
                            : "f",
                        $user_id
                    ]
                );
            } else {

                $authority_update = pg_query_params(
                    $conn,
                    "
                    INSERT INTO authorities
                    (
                        user_id,
                        department_id,
                        name,
                        designation,
                        status
                    )
                    VALUES
                    ($1, $2, $3, $4, $5)
                    ",
                    [
                        $user_id,
                        $department_id,
                        $name,
                        $designation !== ""
                            ? $designation
                            : null,
                        $account_status
                            ? "t"
                            : "f"
                    ]
                );
            }

            if (!$authority_update) {
                throw new Exception(
                    "Failed to save professional information."
                );
            }


            addAuditLog(
                $conn,
                $admin_user_id,
                "USER",
                $user_id,
                "UPDATE_USER"
            );


            pg_query(
                $conn,
                "COMMIT"
            );


            redirectUsersSuccess(
                "User information updated successfully."
            );
        } catch (Exception $e) {

            pg_query(
                $conn,
                "ROLLBACK"
            );

            redirectUsersError(
                $e->getMessage()
            );
        }
    }


    redirectUsersError(
        "Unable to update user."
    );
}


/* =========================================================
   ACTIVATE USER
========================================================= */

if ($action === "activate") {

    $user_id = $_POST["user_id"] ?? "";

    if (
        !is_numeric($user_id) ||
        (int) $user_id <= 0
    ) {
        redirectUsersError(
            "Invalid user ID."
        );
    }

    $user_id = (int) $user_id;


    if (!userExists(
        $conn,
        $user_id
    )) {
        redirectUsersError(
            "User not found."
        );
    }


    pg_query(
        $conn,
        "BEGIN"
    );


    try {

        $user_result = pg_query_params(
            $conn,
            "
            UPDATE users
            SET
                account_status = TRUE,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = $1
            ",
            [$user_id]
        );


        if (!$user_result) {

            throw new Exception(
                "Failed to activate user."
            );
        }


        $authority_result = pg_query_params(
            $conn,
            "
            UPDATE authorities
            SET
                status = TRUE
            WHERE user_id = $1
            ",
            [$user_id]
        );


        if (!$authority_result) {

            throw new Exception(
                "Failed to update authority status."
            );
        }


        addAuditLog(
            $conn,
            $admin_user_id,
            "USER",
            $user_id,
            "ACTIVATE_USER"
        );


        pg_query(
            $conn,
            "COMMIT"
        );


        redirectUsersSuccess(
            "User account activated successfully."
        );
    } catch (Exception $e) {

        pg_query(
            $conn,
            "ROLLBACK"
        );

        redirectUsersError(
            $e->getMessage()
        );
    }
}


/* =========================================================
   DEACTIVATE USER
========================================================= */

if ($action === "deactivate") {

    $user_id = $_POST["user_id"] ?? "";

    if (
        !is_numeric($user_id) ||
        (int) $user_id <= 0
    ) {
        redirectUsersError(
            "Invalid user ID."
        );
    }

    $user_id = (int) $user_id;


    if (!userExists(
        $conn,
        $user_id
    )) {
        redirectUsersError(
            "User not found."
        );
    }


    /* -----------------------------------------------------
       PREVENT ADMIN FROM DEACTIVATING SELF
    ----------------------------------------------------- */

    if ($user_id === $admin_user_id) {

        redirectUsersError(
            "You cannot deactivate your own administrator account."
        );
    }


    pg_query(
        $conn,
        "BEGIN"
    );


    try {

        $user_result = pg_query_params(
            $conn,
            "
            UPDATE users
            SET
                account_status = FALSE,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = $1
            ",
            [$user_id]
        );


        if (!$user_result) {

            throw new Exception(
                "Failed to deactivate user."
            );
        }


        $authority_result = pg_query_params(
            $conn,
            "
            UPDATE authorities
            SET
                status = FALSE
            WHERE user_id = $1
            ",
            [$user_id]
        );


        if (!$authority_result) {

            throw new Exception(
                "Failed to update authority status."
            );
        }


        addAuditLog(
            $conn,
            $admin_user_id,
            "USER",
            $user_id,
            "DEACTIVATE_USER"
        );


        pg_query(
            $conn,
            "COMMIT"
        );


        redirectUsersSuccess(
            "User account deactivated successfully."
        );
    } catch (Exception $e) {

        pg_query(
            $conn,
            "ROLLBACK"
        );

        redirectUsersError(
            $e->getMessage()
        );
    }
}


/* =========================================================
   UPDATE ADMIN PROFILE PHOTO
========================================================= */

if ($action === "update_photo") {

    $target_user_id = $admin_user_id;


    if (
        isset($_POST["user_id"]) &&
        is_numeric($_POST["user_id"])
    ) {
        $target_user_id =
            (int) $_POST["user_id"];
    }


    /* Admin profile can only modify itself
       through this profile-specific action. */

    if ($target_user_id !== $admin_user_id) {

        redirectProfileError(
            "Invalid administrator account."
        );
    }


    if (
        !isset($_FILES["profile_photo"]) ||
        $_FILES["profile_photo"]["error"] ===
        UPLOAD_ERR_NO_FILE
    ) {

        redirectProfileError(
            "Please select a profile photo."
        );
    }


    $photo = prepareProfilePhoto(
        $_FILES["profile_photo"],
        $conn
    );


    if (!$photo["success"]) {

        redirectProfileError(
            $photo["message"]
        );
    }


    if (!$photo["uploaded"]) {

        redirectProfileError(
            "No profile photo was uploaded."
        );
    }


    $result = pg_query_params(
        $conn,
        "
        UPDATE users
        SET
            profile_photo = $1,
            updated_at = CURRENT_TIMESTAMP
        WHERE user_id = $2
        ",
        [
            $photo["data"],
            $admin_user_id
        ]
    );


    if (!$result) {

        redirectProfileError(
            "Unable to update profile photo."
        );
    }


    addAuditLog(
        $conn,
        $admin_user_id,
        "USER",
        $admin_user_id,
        "UPDATE_PROFILE_PHOTO"
    );


    redirectProfileSuccess(
        "photo_updated"
    );
}


/* =========================================================
   REMOVE ADMIN PROFILE PHOTO
========================================================= */

if ($action === "remove_photo") {

    $result = pg_query_params(
        $conn,
        "
        UPDATE users
        SET
            profile_photo = NULL,
            updated_at = CURRENT_TIMESTAMP
        WHERE user_id = $1
        ",
        [$admin_user_id]
    );


    if (!$result) {

        redirectProfileError(
            "Unable to remove profile photo."
        );
    }


    addAuditLog(
        $conn,
        $admin_user_id,
        "USER",
        $admin_user_id,
        "REMOVE_PROFILE_PHOTO"
    );


    redirectProfileSuccess(
        "photo_removed"
    );
}


/* =========================================================
   CHANGE ADMIN PASSWORD
========================================================= */

if ($action === "change_password") {

    $new_password =
        $_POST["new_password"] ?? "";

    $confirm_password =
        $_POST["confirm_password"] ?? "";


    if (
        $new_password === "" ||
        $confirm_password === ""
    ) {
        redirectProfileError(
            "password_fields_required"
        );
    }


    if (!isValidPassword($new_password)) {
        redirectProfileError(
            "password_length"
        );
    }


    if ($new_password !== $confirm_password) {
        redirectProfileError(
            "password_mismatch"
        );
    }


    $hashed_password = password_hash(
        $new_password,
        PASSWORD_DEFAULT
    );


    if ($hashed_password === false) {
        redirectProfileError(
            "password_update_failed"
        );
    }


    $update_result = pg_query_params(
        $conn,
        "
        UPDATE users
        SET
            password = $1,
            updated_at = CURRENT_TIMESTAMP
        WHERE user_id = $2
          AND account_status = TRUE
        ",
        [
            $hashed_password,
            $admin_user_id
        ]
    );


    if (!$update_result || pg_affected_rows($update_result) === 0) {
        redirectProfileError(
            "password_update_failed"
        );
    }


    addAuditLog(
        $conn,
        $admin_user_id,
        "USER",
        $admin_user_id,
        "CHANGE_PASSWORD"
    );


    redirectProfileSuccess(
        "password_changed"
    );
}

/* =========================================================
   INVALID ACTION
========================================================= */

redirectUsersError(
    "Invalid user action."
);
