<?php

session_start();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Admin Login Action
|--------------------------------------------------------------------------
|
| Authentication rules:
|
| 1. Email + password must be valid.
| 2. User must have role ADMIN.
| 3. Account must be active.
| 4. Successful login creates the normal CampusDesk session.
| 5. Successful login is recorded in audit_logs.
|
|--------------------------------------------------------------------------
*/


/* ----------------------------------------------------------------------
   Helper: Redirect Back to Admin Login
---------------------------------------------------------------------- */

function redirectToAdminLogin()
{
    header("Location: ../admin/admin_login.php");
    exit();
}


/* ----------------------------------------------------------------------
   Store Login Error
---------------------------------------------------------------------- */

function setAdminLoginError($message, $email = "")
{
    $_SESSION["admin_login_error"] = $message;
    $_SESSION["admin_login_email"] = $email;
}


/* ----------------------------------------------------------------------
   Request Method
---------------------------------------------------------------------- */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectToAdminLogin();
}


/* ----------------------------------------------------------------------
   Get Form Data
---------------------------------------------------------------------- */

$email = isset($_POST["email"])
    ? trim($_POST["email"])
    : "";

$password = isset($_POST["password"])
    ? $_POST["password"]
    : "";


/* ----------------------------------------------------------------------
   Basic Validation
---------------------------------------------------------------------- */

if ($email === "") {

    setAdminLoginError(
        "Please enter your email address.",
        $email
    );

    redirectToAdminLogin();
}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    setAdminLoginError(
        "Please enter a valid email address.",
        $email
    );

    redirectToAdminLogin();
}


if ($password === "") {

    setAdminLoginError(
        "Please enter your password.",
        $email
    );

    redirectToAdminLogin();
}


/* ----------------------------------------------------------------------
   Find Admin User
---------------------------------------------------------------------- */

$query = "
    SELECT
        u.user_id,
        u.email,
        u.password,
        u.account_status,
        u.last_login,
        r.role_name
    FROM users u

    INNER JOIN roles r
        ON r.role_id = u.role_id

    WHERE LOWER(u.email) = LOWER($1)

    LIMIT 1
";


$result = pg_query_params(
    $conn,
    $query,
    [$email]
);


/* ----------------------------------------------------------------------
   Database Query Error
---------------------------------------------------------------------- */

if (!$result) {

    setAdminLoginError(
        "Unable to process login at this time. Please try again.",
        $email
    );

    redirectToAdminLogin();
}


/* ----------------------------------------------------------------------
   User Not Found
---------------------------------------------------------------------- */

if (pg_num_rows($result) === 0) {

    setAdminLoginError(
        "Invalid email or password.",
        $email
    );

    redirectToAdminLogin();
}


$user = pg_fetch_assoc($result);


/* ----------------------------------------------------------------------
   Verify Admin Role
---------------------------------------------------------------------- */

if ($user["role_name"] !== "ADMIN") {

    setAdminLoginError(
        "Invalid email or password.",
        $email
    );

    redirectToAdminLogin();
}


/* ----------------------------------------------------------------------
   Verify Account Status
---------------------------------------------------------------------- */

$isActive =
    $user["account_status"] === "t"
    || $user["account_status"] === "1"
    || $user["account_status"] === true;


if (!$isActive) {

    setAdminLoginError(
        "Your Admin account is inactive. Please contact the system administrator.",
        $email
    );

    redirectToAdminLogin();
}


/* ----------------------------------------------------------------------
   Verify Password
---------------------------------------------------------------------- */

if (!password_verify($password, $user["password"])) {

    setAdminLoginError(
        "Invalid email or password.",
        $email
    );

    redirectToAdminLogin();
}


/* ----------------------------------------------------------------------
   Password Rehash
---------------------------------------------------------------------- */

if (password_needs_rehash(
    $user["password"],
    PASSWORD_DEFAULT
)) {

    $newPasswordHash =
        password_hash(
            $password,
            PASSWORD_DEFAULT
        );

    if ($newPasswordHash !== false) {

        pg_query_params(
            $conn,
            "
            UPDATE users
            SET
                password = $1,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = $2
            ",
            [
                $newPasswordHash,
                $user["user_id"]
            ]
        );
    }
}


/* ----------------------------------------------------------------------
   Secure Session
---------------------------------------------------------------------- */

session_regenerate_id(true);


/* ----------------------------------------------------------------------
   Create CampusDesk Session
---------------------------------------------------------------------- */

$_SESSION["user_id"] =
    (int)$user["user_id"];

$_SESSION["email"] =
    $user["email"];

$_SESSION["role_name"] =
    $user["role_name"];


/*
 * Optional Admin-specific session values.
 */
$_SESSION["admin_user_id"] =
    (int)$user["user_id"];

$_SESSION["admin_email"] =
    $user["email"];


/* ----------------------------------------------------------------------
   Update Last Login
---------------------------------------------------------------------- */

$lastLoginResult =
    pg_query_params(
        $conn,
        "
        UPDATE users
        SET
            last_login = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP
        WHERE user_id = $1
        ",
        [
            $user["user_id"]
        ]
    );


/*
 * Do not prevent login if updating last_login fails.
 * Authentication has already succeeded.
 */


/* ----------------------------------------------------------------------
   Successful Login Audit
---------------------------------------------------------------------- */

$ipAddress =
    !empty($_SERVER["REMOTE_ADDR"])
    ? $_SERVER["REMOTE_ADDR"]
    : null;


$auditResult =
    pg_query_params(
        $conn,
        "
        INSERT INTO audit_logs
        (
            user_id,
            module_type,
            reference_id,
            action,
            ip_address
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
            (int)$user["user_id"],
            "ADMIN",
            (int)$user["user_id"],
            "ADMIN_LOGIN",
            $ipAddress
        ]
    );


/*
 * Login should not be rejected if audit logging fails.
 * The audit failure should not prevent a valid Admin from
 * accessing the system.
 */


/* ----------------------------------------------------------------------
   Clear Login Error Data
---------------------------------------------------------------------- */

unset(
    $_SESSION["admin_login_error"],
    $_SESSION["admin_login_email"]
);


/* ----------------------------------------------------------------------
   Redirect to Dashboard
---------------------------------------------------------------------- */

header("Location: ../admin/dashboard.php");
exit();
