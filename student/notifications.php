<?php

/*
|--------------------------------------------------------------------------
| CAMPUSDESK - STUDENT NOTIFICATIONS
|--------------------------------------------------------------------------
| This page keeps the existing notification functionality:
| - Load student's notifications
| - Show unread count
| - Mark one notification as read
| - Mark all notifications as read
| - Open the related grievance, suggestion or application
|
| Only the visual UI has been redesigned.
|--------------------------------------------------------------------------
*/


/* =========================================================
   AUTHENTICATION
   ========================================================= */

require_once "../includes/auth.php";
requireStudent();


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
   GET NOTIFICATIONS
   ========================================================= */

$sql = "
    SELECT
        notification_id,
        module_type,
        reference_id,
        title,
        message,
        notification_type,
        is_read,
        created_at
    FROM notifications
    WHERE user_id = $1
    ORDER BY created_at DESC
";

$result = pg_query_params(
    $conn,
    $sql,
    [$user_id]
);

if (!$result) {
    die("Unable to load notifications.");
}


/* =========================================================
   GET UNREAD COUNT
   ========================================================= */

$unread_count = 0;

$count_result = pg_query_params(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE user_id = $1
      AND is_read = FALSE
    ",
    [$user_id]
);

if ($count_result) {

    $count_row = pg_fetch_assoc(
        $count_result
    );

    $unread_count =
        (int) $count_row["total"];
}


/* =========================================================
   NOTIFICATION COUNT
   ========================================================= */

$total_notifications =
    pg_num_rows($result);

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
        Notifications | CampusDesk
    </title>


    <!-- =====================================================
         FONT AWESOME
         ===================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <!-- =====================================================
         COMMON CAMPUSDESK CSS
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
        href="../css/student-dashboard.css">


    <!-- =====================================================
         NOTIFICATION PAGE CSS
         ===================================================== -->

    <link
        rel="stylesheet"
        href="../css/style-notification.css">

</head>


<body>


    <!-- =====================================================
         COMMON STUDENT NAVBAR
         ===================================================== -->

    <?php include "../includes/navbar.php"; ?>


    <!-- =====================================================
         COMMON CAMPUSDESK HEADER
         ===================================================== -->

    <?php include "../includes/header.php"; ?>


    <!-- =====================================================
         COMMON STUDENT PAGE LAYOUT
         ===================================================== -->

    <div class="dashboard-layout">

        <main class="dashboard-content">


            <div class="notification-page">


                <!-- =================================================
                     PAGE TOP
                     ================================================= -->

                <div class="notification-page-top">

            

                </div>


                <!-- =================================================
                     PAGE HERO
                     ================================================= -->

                <section class="notification-hero">

                    <div class="notification-hero-content">

                        <div class="notification-hero-icon">

                            <i class="fa-solid fa-bell"></i>

                        </div>


                        <div>

                            <span class="notification-eyebrow">
                                STUDENT UPDATES
                            </span>

                            <h1>
                                Notifications
                            </h1>

                            <p>
                                Stay updated with the latest changes
                                to your grievances, suggestions and applications.
                            </p>

                        </div>

                    </div>


                    <div class="notification-summary">

                        <div class="summary-item">

                            <span>
                                Total
                            </span>

                            <strong>
                                <?= (int) $total_notifications ?>
                            </strong>

                        </div>


                        <div class="summary-divider"></div>


                        <div class="summary-item">

                            <span>
                                Unread
                            </span>

                            <strong class="unread-number">
                                <?= (int) $unread_count ?>
                            </strong>

                        </div>

                    </div>

                </section>


                <!-- =================================================
                     NOTIFICATION PANEL
                     ================================================= -->

                <section class="notification-panel">


                    <!-- Panel Header -->

                    <div class="notification-panel-header">

                        <div>

                            <h2>
                                Recent Notifications
                            </h2>

                            <p>
                                Your latest CampusDesk activity is shown below.
                            </p>

                        </div>


                        <?php if ($unread_count > 0): ?>

                            <form
                                action="../actions/notification.php"
                                method="POST">

                                <input
                                    type="hidden"
                                    name="action"
                                    value="mark_all_read">

                                <button
                                    type="submit"
                                    class="notification-mark-all">

                                    <i class="fa-solid fa-check-double"></i>

                                    Mark all as read

                                </button>

                            </form>

                        <?php endif; ?>

                    </div>


                    <!-- =================================================
                         NOTIFICATION LIST
                         ================================================= -->

                    <?php if ($total_notifications > 0): ?>

                        <div class="notification-list">


                            <?php while (
                                $notification =
                                pg_fetch_assoc($result)
                            ): ?>


                                <?php

                                /*
                                 * Convert PostgreSQL boolean value.
                                 */
                                $is_read =
                                    $notification["is_read"] === "t";


                                /*
                                 * Determine notification module.
                                 */
                                $module_type =
                                    strtoupper(
                                        $notification["module_type"]
                                    );


                                /*
                                 * Default icon and label.
                                 */
                                $module_icon =
                                    "fa-bell";

                                $module_label =
                                    $module_type;


                                /*
                                 * Related page.
                                 */
                                $view_url = "";


                                if (
                                    $module_type === "GRIEVANCE"
                                ) {

                                    $module_icon =
                                        "fa-circle-exclamation";

                                    $module_label =
                                        "Grievance";

                                    $view_url =
                                        "grievances.php?section=view&view=" .
                                        urlencode(
                                            $notification["reference_id"]
                                        );
                                } elseif (
                                    $module_type === "SUGGESTION"
                                ) {

                                    $module_icon =
                                        "fa-lightbulb";

                                    $module_label =
                                        "Suggestion";

                                    $view_url =
                                        "suggestions.php?section=view&id=" .
                                        urlencode(
                                            $notification["reference_id"]
                                        );
                                } elseif (
                                    $module_type === "APPLICATION"
                                ) {

                                    $module_icon =
                                        "fa-file-lines";

                                    $module_label =
                                        "Application";

                                    $view_url =
                                        "applications.php?section=view&id=" .
                                        urlencode(
                                            $notification["reference_id"]
                                        );
                                }

                                ?>


                                <!-- =====================================
                                     NOTIFICATION CARD
                                     ===================================== -->

                                <article
                                    class="notification-card <?= $is_read ? "is-read" : "is-unread" ?>">


                                    <!-- Notification Icon -->

                                    <div class="notification-card-icon">

                                        <i
                                            class="fa-solid <?= $module_icon ?>"></i>

                                    </div>


                                    <!-- Main Content -->

                                    <div class="notification-card-content">


                                        <!-- Module + Status -->

                                        <div class="notification-card-meta">

                                            <span class="notification-type">

                                                <?= htmlspecialchars(
                                                    $module_label,
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>

                                            </span>


                                            <?php if (!$is_read): ?>

                                                <span class="notification-unread-dot">

                                                    <span></span>

                                                    Unread

                                                </span>

                                            <?php else: ?>

                                                <span class="notification-read-label">

                                                    Read

                                                </span>

                                            <?php endif; ?>

                                        </div>


                                        <!-- Title -->

                                        <h3>

                                            <?= htmlspecialchars(
                                                $notification["title"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>

                                        </h3>


                                        <!-- Message -->

                                        <p>

                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $notification["message"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                )
                                            ) ?>

                                        </p>


                                        <!-- Bottom Information -->

                                        <div class="notification-card-bottom">


                                            <span class="notification-time">

                                                <i class="fa-regular fa-clock"></i>

                                                <?= htmlspecialchars(
                                                    formatDateTime(
                                                        $notification["created_at"]
                                                    ),
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>

                                            </span>


                                            <div class="notification-actions">


                                                <?php if (
                                                    $view_url !== ""
                                                ): ?>

                                                    <a
                                                        href="<?= htmlspecialchars(
                                                                    $view_url,
                                                                    ENT_QUOTES,
                                                                    "UTF-8"
                                                                ) ?>"
                                                        class="notification-view-btn">

                                                        View

                                                        <i class="fa-solid fa-arrow-right"></i>

                                                    </a>

                                                <?php endif; ?>


                                                <?php if (!$is_read): ?>

                                                    <form
                                                        action="../actions/notification.php"
                                                        method="POST">

                                                        <input
                                                            type="hidden"
                                                            name="action"
                                                            value="mark_read">

                                                        <input
                                                            type="hidden"
                                                            name="notification_id"
                                                            value="<?= (int) $notification["notification_id"] ?>">

                                                        <button
                                                            type="submit"
                                                            class="notification-read-btn">

                                                            <i class="fa-solid fa-check"></i>

                                                            Mark as read

                                                        </button>

                                                    </form>

                                                <?php endif; ?>


                                            </div>

                                        </div>


                                    </div>

                                </article>


                            <?php endwhile; ?>


                        </div>


                    <?php else: ?>


                        <!-- =================================================
                             EMPTY STATE
                             ================================================= -->

                        <div class="notification-empty">

                            <div class="empty-icon">

                                <i class="fa-regular fa-bell-slash"></i>

                            </div>


                            <h2>
                                You're all caught up
                            </h2>


                            <p>
                                There are no notifications to show right now.
                                New updates from the college will appear here.
                            </p>


                            <a
                                href="dashboard.php"
                                class="empty-dashboard-btn">

                                <i class="fa-solid fa-house"></i>

                                Go to Dashboard

                            </a>

                        </div>


                    <?php endif; ?>


                </section>


            </div>

        </main>

    </div>


    <!-- =====================================================
         COMMON STUDENT FOOTER
         ===================================================== -->

    <?php include "../includes/footer.php"; ?>


</body>

</html>
