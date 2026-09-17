<?php

/*
 * CampusDesk
 * Authentication and Authorization
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn()
{
    return isset($_SESSION["user_id"]);
}

function getLoggedInUserId()
{
    if (!isLoggedIn()) {
        return false;
    }

    return $_SESSION["user_id"];
}

function getLoggedInUserRole()
{
    if (!isLoggedIn()) {
        return false;
    }

    return $_SESSION["role_name"] ?? false;
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit;
    }
}

function requireStudent()
{
    requireLogin();

    if (getLoggedInUserRole() !== "STUDENT") {
        header("Location: ../access_denied.php");
        exit;
    }
}

function requireAuthority()
{
    requireLogin();

    if (getLoggedInUserRole() !== "AUTHORITY") {
        header("Location: ../access_denied.php");
        exit;
    }
}

function requireAdmin()
{
    requireLogin();

    if (getLoggedInUserRole() !== "ADMIN") {
        header("Location: ../access_denied.php");
        exit;
    }
}

function logoutUser()
{
    $_SESSION = [];

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

    session_destroy();
}

?>