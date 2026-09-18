<?php

require_once "../includes/auth.php";
requireStudent();

require_once "../config/database.php";
require_once "../includes/functions.php";

$user_id = getLoggedInUserId();


/* =========================
   NOTIFICATIONS
   ========================= */

$sql = "SELECT
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
        ORDER BY created_at DESC";

$result = pg_query_params(
    $conn,
    $sql,
    [$user_id]
);

if (!$result) {
    die("Unable to load notifications.");
}


/* =========================
   UNREAD COUNT
   ========================= */

$unread_count = 0;

$count_result = pg_query_params(
    $conn,
    "SELECT COUNT(*) AS total
     FROM notifications
     WHERE user_id = $1
     AND is_read = FALSE",
    [$user_id]
);

if ($count_result) {

    $count_row = pg_fetch_assoc(
        $count_result
    );

    $unread_count =
        (int) $count_row["total"];
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
        Notifications - CampusDesk
    </title>

    <link
        rel="stylesheet"
        href="../css/style-notification.css">

</head>

<body>

    <div class="page-container">


        <!-- =========================
         HEADER
         ========================= -->

        <div class="page-header">

            <a
                href="dashboard.php"
                class="back-link">
                ← Back to Dashboard
            </a>


            <div class="notification-header">

                <div>

                    <h1 class="page-title">
                        Notifications
                    </h1>

                    <p>
                        View updates related to your
                        grievances, suggestions and applications.
                    </p>

                </div>


                <?php if ($unread_count > 0): ?>

                    <span class="unread-count">

                        <?= escape($unread_count) ?>

                        Unread

                    </span>

                <?php endif; ?>

            </div>

        </div>


        <!-- =========================
         NOTIFICATIONS
         ========================= -->

        <div class="section-box">


            <!-- Mark All as Read -->

            <?php if ($unread_count > 0): ?>

                <div class="notification-actions">

                    <form
                        action="../actions/notification.php"
                        method="POST">

                        <input
                            type="hidden"
                            name="action"
                            value="mark_all_read">

                        <button
                            type="submit"
                            class="button secondary-button">
                            Mark All as Read
                        </button>

                    </form>

                </div>

            <?php endif; ?>


            <!-- Notification List -->

            <?php if (pg_num_rows($result) > 0): ?>

                <div class="notification-list">


                    <?php while ($notification = pg_fetch_assoc($result)): ?>


                        <?php

                        $module_type =
                            strtoupper(
                                $notification["module_type"]
                            );

                        $is_read =
                            $notification["is_read"] === "t";

                        $view_url = "";


                        /* =========================
                       RELATED PAGE
                       ========================= */

                        if ($module_type === "GRIEVANCE") {

                            $view_url =
                                "grievances.php?section=view&view="
                                . urlencode(
                                    $notification["reference_id"]
                                );
                        } elseif ($module_type === "SUGGESTION") {

                            $view_url =
                                "suggestions.php?section=view&id="
                                . urlencode(
                                    $notification["reference_id"]
                                );
                        } elseif ($module_type === "APPLICATION") {

                            $view_url =
                                "applications.php?section=view&id="
                                . urlencode(
                                    $notification["reference_id"]
                                );
                        }

                        ?>


                        <!-- Notification -->

                        <div
                            class="notification-item
                        <?= $is_read ? "read" : "unread" ?>">


                            <!-- Module + Unread -->

                            <div class="notification-top">

                                <span class="notification-module">

                                    <?= escape(
                                        $module_type
                                    ) ?>

                                </span>


                                <?php if (!$is_read): ?>

                                    <span class="notification-badge">
                                        Unread
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- Title -->

                            <h2 class="notification-title">

                                <?= escape(
                                    $notification["title"]
                                ) ?>

                            </h2>


                            <!-- Message -->

                            <p class="notification-message">

                                <?= nl2br(
                                    escape(
                                        $notification["message"]
                                    )
                                ) ?>

                            </p>


                            <!-- Footer -->

                            <div class="notification-footer">


                                <!-- Date -->

                                <span class="notification-date">

                                    <?= escape(
                                        formatDateTime(
                                            $notification["created_at"]
                                        )
                                    ) ?>

                                </span>


                                <!-- Buttons -->

                                <div class="notification-buttons">


                                    <!-- View -->

                                    <?php if ($view_url !== ""): ?>

                                        <a
                                            href="<?= escape($view_url) ?>"
                                            class="button primary-button">
                                            View
                                        </a>

                                    <?php endif; ?>


                                    <!-- Mark as Read -->

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
                                                value="<?= escape(
                                                            $notification["notification_id"]
                                                        ) ?>">

                                            <button
                                                type="submit"
                                                class="button secondary-button">
                                                Mark as Read
                                            </button>

                                        </form>

                                    <?php endif; ?>


                                </div>

                            </div>

                        </div>


                    <?php endwhile; ?>


                </div>


            <?php else: ?>


                <!-- No Notifications -->

                <div class="no-data">

                    <h2>
                        No Notifications
                    </h2>

                    <p>
                        You currently have no notifications.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>


    <script
        src="../js/script-notification.js"></script>

</body>

</html>
