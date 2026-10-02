<?php
/*
|--------------------------------------------------------------------------
| CampusDesk - Admin Authentication
|--------------------------------------------------------------------------
| Admin-specific session and authorization functions.
|--------------------------------------------------------------------------
*/


/* =========================================================
   START SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   CHECK ADMIN LOGIN
========================================================= */

function isAdminLoggedIn()
{
    return isset($_SESSION["user_id"])
        && isset($_SESSION["role_name"])
        && $_SESSION["role_name"] === "ADMIN";
}


/* =========================================================
   GET ADMIN USER ID
========================================================= */

function getAdminUserId()
{
    if (!isAdminLoggedIn()) {
        return false;
    }

    return $_SESSION["user_id"];
}


/* =========================================================
   GET ADMIN EMAIL
========================================================= */

function getAdminEmail()
{
    if (!isAdminLoggedIn()) {
        return false;
    }

    return $_SESSION["email"] ?? false;
}


/* =========================================================
   GET ADMIN ROLE
========================================================= */

function getAdminRole()
{
    if (!isAdminLoggedIn()) {
        return false;
    }

    return $_SESSION["role_name"];
}


/* =========================================================
   REQUIRE ADMIN LOGIN
========================================================= */

function requireAdmin()
{
    if (!isAdminLoggedIn()) {

        header("Location: admin_login.php");

        exit();
    }
}


/* =========================================================
   GET ADMIN SESSION DATA
========================================================= */

function getAdminSession()
{
    if (!isAdminLoggedIn()) {
        return false;
    }

    return [
        "user_id"   => $_SESSION["user_id"],
        "email"     => $_SESSION["email"] ?? "",
        "role_name" => $_SESSION["role_name"] ?? ""
    ];
}


/* =========================================================
   LOGOUT ADMIN
========================================================= */

function logoutAdmin()
{
    /*
    |--------------------------------------------------------------------------
    | Clear Session Variables
    |--------------------------------------------------------------------------
    */

    $_SESSION = [];


    /*
    |--------------------------------------------------------------------------
    | Remove Session Cookie
    |--------------------------------------------------------------------------
    */

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Destroy Session
    |--------------------------------------------------------------------------
    */

    session_destroy();
}


/* =========================================================
   REDIRECT IF ALREADY LOGGED IN
========================================================= */

function redirectIfAdminLoggedIn()
{
    if (isAdminLoggedIn()) {

        header("Location: dashboard.php");

        exit();
    }
}
