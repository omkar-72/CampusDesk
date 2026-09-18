<?php

require_once "../includes/auth.php";
requireStudent();

require_once "../config/database.php";
require_once "../includes/functions.php";

$user_id = getLoggedInUserId();

$action = $_POST["action"] ?? "";


/* =========================
   MARK ONE AS READ
   ========================= */

if ($action === "mark_read") {

    $notification_id =
        $_POST["notification_id"] ?? "";

    if (!isValidId($notification_id)) {
        die("Invalid notification ID.");
    }


    $sql = "UPDATE notifications
            SET is_read = TRUE
            WHERE notification_id = $1
            AND user_id = $2";


    $result = pg_query_params(
        $conn,
        $sql,
        [
            $notification_id,
            $user_id
        ]
    );


    if (!$result) {
        die("Unable to update notification.");
    }


    header(
        "Location: ../student/notifications.php"
    );

    exit;
}


/* =========================
   MARK ALL AS READ
   ========================= */

if ($action === "mark_all_read") {


    $sql = "UPDATE notifications
            SET is_read = TRUE
            WHERE user_id = $1
            AND is_read = FALSE";


    $result = pg_query_params(
        $conn,
        $sql,
        [$user_id]
    );


    if (!$result) {
        die("Unable to update notifications.");
    }


    header(
        "Location: ../student/notifications.php"
    );

    exit;
}


/* =========================
   INVALID ACTION
   ========================= */

die("Invalid notification action.");
