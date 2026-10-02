<?php

/*
|--------------------------------------------------------------------------
| CAMPUSDESK - STUDENT DASHBOARD
|--------------------------------------------------------------------------
| This page keeps the existing student dashboard database logic intact.
|
| UI changes only:
| - Uses the shared CampusDesk navigation/header/footer
| - Uses a dashboard layout similar to the Authority module
| - Displays existing student statistics as modern KPI cards
| - Displays the existing student information in a dashboard card
| - Keeps all existing links and functionality unchanged
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
   GET LOGGED-IN STUDENT
   ========================================================= */

$user_id = getLoggedInUserId();

$student_id = getStudentId(
    $conn,
    $user_id
);

if (!$student_id) {
    die("Student record not found.");
}


/* =========================================================
   STUDENT INFORMATION
   ========================================================= */

$sql = "SELECT
            full_name,
            course,
            year,
            semester,
            division
        FROM students
        WHERE student_id = $1";

$result = pg_query_params(
    $conn,
    $sql,
    [$student_id]
);

if (
    !$result ||
    pg_num_rows($result) === 0
) {
    die("Student information not found.");
}

$student = pg_fetch_assoc(
    $result
);


/* =========================================================
   COUNTS
   ========================================================= */

$grievance_count = 0;
$suggestion_count = 0;
$application_count = 0;
$notification_count = 0;


/* =========================================================
   GRIEVANCES
   ========================================================= */

$result = pg_query_params(
    $conn,
    "SELECT COUNT(*) AS total
     FROM grievances
     WHERE student_id = $1",
    [$student_id]
);

if ($result) {

    $row = pg_fetch_assoc(
        $result
    );

    $grievance_count =
        $row["total"];
}


/* =========================================================
   SUGGESTIONS
   ========================================================= */

$result = pg_query_params(
    $conn,
    "SELECT COUNT(*) AS total
     FROM suggestions
     WHERE student_id = $1",
    [$student_id]
);

if ($result) {

    $row = pg_fetch_assoc(
        $result
    );

    $suggestion_count =
        $row["total"];
}


/* =========================================================
   APPLICATIONS
   ========================================================= */

$result = pg_query_params(
    $conn,
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE student_id = $1",
    [$student_id]
);

if ($result) {

    $row = pg_fetch_assoc(
        $result
    );

    $application_count =
        $row["total"];
}


/* =========================================================
   UNREAD NOTIFICATIONS
   ========================================================= */

$result = pg_query_params(
    $conn,
    "SELECT COUNT(*) AS total
     FROM notifications
     WHERE user_id = $1
     AND is_read = FALSE",
    [$user_id]
);

if ($result) {

    $row = pg_fetch_assoc(
        $result
    );

    $notification_count =
        $row["total"];
}

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
        CampusDesk | Student Dashboard
    </title>


    <!-- =====================================================
         FONT AWESOME
         ===================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <!-- =====================================================
         SHARED CAMPUSDESK CSS
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


    <!-- =====================================================
         STUDENT DASHBOARD CSS
         ===================================================== -->

    <link
        rel="stylesheet"
        href="../css/student-dashboard.css">

</head>


<body>


    <!-- =====================================================
         SHARED NAVBAR
         ===================================================== -->

    <?php include "../includes/navbar.php"; ?>


    <!-- =====================================================
         SHARED HEADER
         ===================================================== -->

    <?php include "../includes/header.php"; ?>


    <!-- =====================================================
         DASHBOARD LAYOUT
         ===================================================== -->

    <div class="dashboard-layout">

        <main class="dashboard-content">


            <!-- =================================================
                 WELCOME BANNER
                 ================================================= -->

            <section class="student-welcome-banner">

                <div class="student-welcome-content">

                    <h1>

                        Welcome,
                        <?= htmlspecialchars(
                            $student["full_name"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                        👋

                    </h1>

                    <p>
                        Manage your CampusDesk services and track your requests.
                    </p>

                </div>


                <div class="student-welcome-date">

                    <i class="fa-solid fa-calendar-days"></i>

                    <?= date("d M Y") ?>

                </div>

            </section>


            <!-- =================================================
                 STATISTICS
                 ================================================= -->

            <section class="student-stats-grid">


                <!-- Grievances -->

                <a
                    href="grievances.php"
                    class="student-stat-box student-stat-blue">

                    <div class="student-stat-header">

                        <span>
                            Grievances
                        </span>

                        <i class="fa-solid fa-circle-exclamation"></i>

                    </div>

                    <strong>
                        <?= (int) $grievance_count ?>
                    </strong>

                    <small>
                        Total submitted
                    </small>

                </a>


                <!-- Suggestions -->

                <a
                    href="suggestions.php"
                    class="student-stat-box student-stat-purple">

                    <div class="student-stat-header">

                        <span>
                            Suggestions
                        </span>

                        <i class="fa-solid fa-lightbulb"></i>

                    </div>

                    <strong>
                        <?= (int) $suggestion_count ?>
                    </strong>

                    <small>
                        Total submitted
                    </small>

                </a>


                <!-- Applications -->

                <a
                    href="applications.php"
                    class="student-stat-box student-stat-orange">

                    <div class="student-stat-header">

                        <span>
                            Applications
                        </span>

                        <i class="fa-solid fa-file-lines"></i>

                    </div>

                    <strong>
                        <?= (int) $application_count ?>
                    </strong>

                    <small>
                        Total submitted
                    </small>

                </a>


                <!-- Notifications -->

                <a
                    href="notifications.php"
                    class="student-stat-box student-stat-red">

                    <div class="student-stat-header">

                        <span>
                            Unread Notifications
                        </span>

                        <i class="fa-solid fa-bell"></i>

                    </div>

                    <strong>
                        <?= (int) $notification_count ?>
                    </strong>

                    <small>
                        Need your attention
                    </small>

                </a>


            </section>


            <!-- =================================================
                 MAIN DASHBOARD CONTENT
                 ================================================= -->

            <section class="student-dashboard-grid">


                <!-- =================================================
                     STUDENT INFORMATION
                     ================================================= -->

                <div class="student-panel">

                    <div class="student-panel-header">

                        <div>

                            <h2>
                                Student Information
                            </h2>

                            <p>
                                Your academic profile
                            </p>

                        </div>

                        <div class="student-panel-icon">

                            <i class="fa-solid fa-user-graduate"></i>

                        </div>

                    </div>


                    <!-- Name -->

                    <div class="student-info-item">

                        <div class="student-info-icon">

                            <i class="fa-solid fa-user"></i>

                        </div>

                        <div>

                            <span>
                                Name
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $student["full_name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </strong>

                        </div>

                    </div>


                    <!-- Course -->

                    <div class="student-info-item">

                        <div class="student-info-icon">

                            <i class="fa-solid fa-graduation-cap"></i>

                        </div>

                        <div>

                            <span>
                                Course
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $student["course"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </strong>

                        </div>

                    </div>


                    <!-- Year -->

                    <div class="student-info-item">

                        <div class="student-info-icon">

                            <i class="fa-solid fa-calendar"></i>

                        </div>

                        <div>

                            <span>
                                Year
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $student["year"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </strong>

                        </div>

                    </div>


                    <!-- Semester -->

                    <div class="student-info-item">

                        <div class="student-info-icon">

                            <i class="fa-solid fa-book-open"></i>

                        </div>

                        <div>

                            <span>
                                Semester
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $student["semester"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </strong>

                        </div>

                    </div>


                    <!-- Division -->

                    <div class="student-info-item">

                        <div class="student-info-icon">

                            <i class="fa-solid fa-users"></i>

                        </div>

                        <div>

                            <span>
                                Division
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $student["division"] ?? "-",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </strong>

                        </div>

                    </div>


                </div>


                <!-- =================================================
                     QUICK ACTIONS
                     ================================================= -->

                <div class="student-panel">

                    <div class="student-panel-header">

                        <div>

                            <h2>
                                Quick Actions
                            </h2>

                            <p>
                                Frequently used services
                            </p>

                        </div>

                        <div class="student-panel-icon">

                            <i class="fa-solid fa-bolt"></i>

                        </div>

                    </div>


                    <div class="student-quick-actions">


                        <!-- Raise Grievance -->

                        <a
                            href="grievances.php?section=raise"
                            class="student-quick-btn">

                            <span class="student-quick-icon">

                                <i class="fa-solid fa-circle-exclamation"></i>

                            </span>

                            <span>

                                <strong>
                                    Raise Grievance
                                </strong>

                                <small>
                                    Report an issue
                                </small>

                            </span>

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>


                        <!-- Submit Suggestion -->

                        <a
                            href="suggestions.php?section=raise"
                            class="student-quick-btn">

                            <span class="student-quick-icon">

                                <i class="fa-solid fa-lightbulb"></i>

                            </span>

                            <span>

                                <strong>
                                    Submit Suggestion
                                </strong>

                                <small>
                                    Share an idea
                                </small>

                            </span>

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>


                        <!-- Submit Application -->

                        <a
                            href="applications.php?section=submit"
                            class="student-quick-btn">

                            <span class="student-quick-icon">

                                <i class="fa-solid fa-file-lines"></i>

                            </span>

                            <span>

                                <strong>
                                    Submit Application
                                </strong>

                                <small>
                                    Create a new application
                                </small>

                            </span>

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>


                        <!-- Notifications -->

                        <a
                            href="notifications.php"
                            class="student-quick-btn">

                            <span class="student-quick-icon">

                                <i class="fa-solid fa-bell"></i>

                            </span>

                            <span>

                                <strong>
                                    Notifications
                                </strong>

                                <small>
                                    View authority updates
                                </small>

                            </span>

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>


                        <!-- Profile -->

                        <a
                            href="profile.php"
                            class="student-quick-btn">

                            <span class="student-quick-icon">

                                <i class="fa-solid fa-user"></i>

                            </span>

                            <span>

                                <strong>
                                    My Profile
                                </strong>

                                <small>
                                    Manage your account
                                </small>

                            </span>

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>


                    </div>

                </div>

            </section>


        </main>

    </div>


    <!-- =====================================================
         FOOTER
         ===================================================== -->

    <?php include "../includes/footer.php"; ?>


</body>

</html>
