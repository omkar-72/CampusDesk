<?php

require_once "../includes/auth.php";
requireStudent();

require_once "../config/database.php";
require_once "../includes/functions.php";

$user_id = getLoggedInUserId();

$student_id = getStudentId(
    $conn,
    $user_id
);

if (!$student_id) {
    die("Student record not found.");
}


/* =========================
   STUDENT INFORMATION
   ========================= */

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


/* =========================
   COUNTS
   ========================= */

$grievance_count = 0;
$suggestion_count = 0;
$application_count = 0;


/* =========================
   GRIEVANCES
   ========================= */

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


/* =========================
   SUGGESTIONS
   ========================= */

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


/* =========================
   APPLICATIONS
   ========================= */

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
        Student Dashboard - CampusDesk
    </title>

    <link
        rel="stylesheet"
        href="../css/style-student-services.css"
    >

</head>

<body>

<div class="page-container">


    <!-- =========================
         HEADER
         ========================= -->

    <div class="page-header">

        <h1 class="page-title">
            Student Dashboard
        </h1>

        <p>
            Welcome,
            <?php
            echo escape(
                $student["full_name"]
            );
            ?>.
        </p>

    </div>


    <!-- =========================
         STUDENT INFORMATION
         ========================= -->

    <div class="section-box">

        <h2>
            Student Information
        </h2>

        <div class="details">


            <!-- Name -->

            <div class="detail-row">

                <div class="detail-label">
                    Name
                </div>

                <div class="detail-value">

                    <?php
                    echo escape(
                        $student["full_name"]
                    );
                    ?>

                </div>

            </div>


            <!-- Course -->

            <div class="detail-row">

                <div class="detail-label">
                    Course
                </div>

                <div class="detail-value">

                    <?php
                    echo escape(
                        $student["course"]
                    );
                    ?>

                </div>

            </div>


            <!-- Year -->

            <div class="detail-row">

                <div class="detail-label">
                    Year
                </div>

                <div class="detail-value">

                    <?php
                    echo escape(
                        $student["year"]
                    );
                    ?>

                </div>

            </div>


            <!-- Semester -->

            <div class="detail-row">

                <div class="detail-label">
                    Semester
                </div>

                <div class="detail-value">

                    <?php
                    echo escape(
                        $student["semester"]
                    );
                    ?>

                </div>

            </div>


            <!-- Division -->

            <div class="detail-row">

                <div class="detail-label">
                    Division
                </div>

                <div class="detail-value">

                    <?php
                    echo escape(
                        $student["division"]
                    );
                    ?>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================
         STUDENT SERVICES
         ========================= -->

    <div class="section-box">

        <h2>
            Student Services
        </h2>

        <div class="module-options">


            <!-- Grievances -->

            <a
                href="grievances.php"
                class="module-option"
            >

                <h2>
                    Grievances
                </h2>

                <p>
                    Raise and track your grievances.
                </p>

                <p>
                    Total:
                    <?php
                    echo escape(
                        $grievance_count
                    );
                    ?>
                </p>

            </a>


            <!-- Suggestions -->

            <a
                href="suggestions.php"
                class="module-option"
            >

                <h2>
                    Suggestions
                </h2>

                <p>
                    Submit and manage your suggestions.
                </p>

                <p>
                    Total:
                    <?php
                    echo escape(
                        $suggestion_count
                    );
                    ?>
                </p>

            </a>


            <!-- Applications -->

            <a
                href="applications.php"
                class="module-option"
            >

                <h2>
                    Applications
                </h2>

                <p>
                    Submit and track your applications.
                </p>

                <p>
                    Total:
                    <?php
                    echo escape(
                        $application_count
                    );
                    ?>
                </p>

            </a>


        </div>

    </div>


    <!-- =========================
         QUICK LINKS
         ========================= -->

    <div class="section-box">

        <h2>
            Quick Links
        </h2>

        <div class="button-group">


            <!-- Raise Grievance -->

            <a
                href="grievances.php?section=raise"
                class="button primary-button"
            >
                Raise Grievance
            </a>


            <!-- Submit Suggestion -->

            <a
                href="suggestions.php?section=raise"
                class="button primary-button"
            >
                Submit Suggestion
            </a>


            <!-- Submit Application -->

            <a
                href="applications.php?section=submit"
                class="button primary-button"
            >
                Submit Application
            </a>


            <!-- My Profile -->

            <a
                href="profile.php"
                class="button primary-button"
            >
                My Profile
            </a>


        </div>

    </div>


</div>

</body>

</html>