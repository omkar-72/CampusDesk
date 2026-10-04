<?php
/*
|--------------------------------------------------------------------------
| AUTHORITY - VIEW APPLICATION
|--------------------------------------------------------------------------
| This page allows an authority to:
| - View complete application details
| - View all supporting attachments
| - Send a reply/message to the student
| - Accept the application
| - Reject the application
| - Send notifications to the student
|--------------------------------------------------------------------------
*/

session_start();


/* ---------------------------------------------------------------
   AUTHORITY ACCESS CHECK
   --------------------------------------------------------------- */

if (
    !isset($_SESSION["role_name"]) ||
    $_SESSION["role_name"] !== "AUTHORITY"
) {
    header("Location: ../login.php");
    exit();
}


/* ---------------------------------------------------------------
   DATABASE CONNECTION
   --------------------------------------------------------------- */

require_once "../config/database.php";


/* ---------------------------------------------------------------
   GET APPLICATION ID
   --------------------------------------------------------------- */

$id = $_GET["id"] ?? "";

if (!is_numeric($id)) {
    die("Invalid application.");
}

$id = (int)$id;


/* ===============================================================
   HANDLE AUTHORITY ACTIONS
   =============================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    ---------------------------------------------------------------
    Get submitted values.
    ---------------------------------------------------------------
    */
    $application_id = $_POST["application_id"] ?? "";
    $action = $_POST["action"] ?? "";
    $message = trim($_POST["message"] ?? "");


    /*
    ---------------------------------------------------------------
    Validate application ID.
    ---------------------------------------------------------------
    */
    if (!is_numeric($application_id)) {
        die("Invalid application.");
    }

    $application_id = (int)$application_id;


    /*
    ---------------------------------------------------------------
    Validate the action.
    ---------------------------------------------------------------
    */
    if (!in_array(
        $action,
        ["send_reply", "approve", "reject"],
        true
    )) {
        die("Invalid action.");
    }


    /* ===========================================================
       SEND REPLY / MESSAGE
       =========================================================== */

    if ($action === "send_reply") {

        /*
        -----------------------------------------------------------
        A reply must contain a message.
        -----------------------------------------------------------
        */
        if ($message === "") {
            die("Please enter a reply message.");
        }


        /*
        -----------------------------------------------------------
        Save the reply in applications.remarks.
        -----------------------------------------------------------
        */
        $update_result = pg_query_params(
            $conn,
            "
            UPDATE applications
            SET remarks = $1
            WHERE application_id = $2
            ",
            [
                $message,
                $application_id
            ]
        );


        if (!$update_result) {
            die("Failed to save the reply.");
        }


        /*
        -----------------------------------------------------------
        Get the student's user ID.

        students.user_id is used because notifications belong
        to users, not directly to students.
        -----------------------------------------------------------
        */
        $student_result = pg_query_params(
            $conn,
            "
            SELECT
                student_id,
                user_id
            FROM students
            WHERE student_id = (
                SELECT student_id
                FROM applications
                WHERE application_id = $1
            )
            LIMIT 1
            ",
            [
                $application_id
            ]
        );


        if (
            $student_result &&
            pg_num_rows($student_result) > 0
        ) {

            $student = pg_fetch_assoc($student_result);


            /*
            -------------------------------------------------------
            Create notification for the student.
            -------------------------------------------------------
            */
            $notification_result = pg_query_params(
                $conn,
                "
                INSERT INTO notifications
                (
                    user_id,
                    module_type,
                    reference_id,
                    title,
                    message,
                    notification_type,
                    is_read
                )
                VALUES
                (
                    $1,
                    'APPLICATION',
                    $2,
                    'Application Reply',
                    $3,
                    'REPLY',
                    FALSE
                )
                ",
                [
                    $student["user_id"],
                    $application_id,
                    "The authority has sent a reply regarding your application."
                ]
            );


            /*
            -------------------------------------------------------
            If notification insertion fails, report the error.
            -------------------------------------------------------
            */
            if (!$notification_result) {
                die("Reply saved, but notification could not be created.");
            }
        }


        /*
        -----------------------------------------------------------
        Return to the same application page.
        -----------------------------------------------------------
        */
        header(
            "Location: view_application.php?id=" .
                urlencode($application_id)
        );

        exit();
    }


    /* ===========================================================
       ACCEPT / REJECT APPLICATION
       =========================================================== */

    if (
        $action === "approve" ||
        $action === "reject"
    ) {

        /*
        -----------------------------------------------------------
        Determine the new application status.
        -----------------------------------------------------------
        */
        if ($action === "approve") {

            $status_name = "Approved";
            $notification_title = "Application Approved";
            $notification_message =
                "Your application has been approved by the authority.";
            $notification_type = "STATUS_UPDATE";
        } else {

            $status_name = "Rejected";
            $notification_title = "Application Rejected";
            $notification_message =
                "Your application has been rejected by the authority.";
            $notification_type = "STATUS_UPDATE";
        }


        /*
        -----------------------------------------------------------
        Find the correct APPLICATION status ID.
        -----------------------------------------------------------
        */
        $status_result = pg_query_params(
            $conn,
            "
            SELECT status_id
            FROM statuses
            WHERE module_type = 'APPLICATION'
              AND status_name = $1
              AND status = TRUE
            LIMIT 1
            ",
            [
                $status_name
            ]
        );


        if (
            !$status_result ||
            pg_num_rows($status_result) === 0
        ) {
            die("Application status not found.");
        }


        $status = pg_fetch_assoc($status_result);

        $status_id = $status["status_id"];


        /*
        -----------------------------------------------------------
        Update application status and review date.

        If the authority entered a message, save it as remarks.
        If no message was entered, keep the existing remarks.
        -----------------------------------------------------------
        */
        $update_result = pg_query_params(
            $conn,
            "
            UPDATE applications
            SET
                status_id = $1,
                review_date = CURRENT_TIMESTAMP,
                remarks =
                    CASE
                        WHEN $2 <> '' THEN $2
                        ELSE remarks
                    END
            WHERE application_id = $3
            ",
            [
                $status_id,
                $message,
                $application_id
            ]
        );


        if (!$update_result) {
            die("Failed to update application.");
        }


        /*
        -----------------------------------------------------------
        Get student's user ID for the notification.
        -----------------------------------------------------------
        */
        $student_result = pg_query_params(
            $conn,
            "
            SELECT
                s.user_id
            FROM students s
            INNER JOIN applications a
                ON a.student_id = s.student_id
            WHERE a.application_id = $1
            LIMIT 1
            ",
            [
                $application_id
            ]
        );


        if (
            $student_result &&
            pg_num_rows($student_result) > 0
        ) {

            $student = pg_fetch_assoc($student_result);


            /*
            -------------------------------------------------------
            Send application status notification.
            -------------------------------------------------------
            */
            $notification_result = pg_query_params(
                $conn,
                "
                INSERT INTO notifications
                (
                    user_id,
                    module_type,
                    reference_id,
                    title,
                    message,
                    notification_type,
                    is_read
                )
                VALUES
                (
                    $1,
                    'APPLICATION',
                    $2,
                    $3,
                    $4,
                    $5,
                    FALSE
                )
                ",
                [
                    $student["user_id"],
                    $application_id,
                    $notification_title,
                    $notification_message,
                    $notification_type
                ]
            );


            if (!$notification_result) {
                die("Application updated, but notification could not be created.");
            }
        }


        /*
        -----------------------------------------------------------
        Return to the application details page.
        -----------------------------------------------------------
        */
        header(
            "Location: view_application.php?id=" .
                urlencode($application_id)
        );

        exit();
    }
}


/* ===============================================================
   GET APPLICATION DETAILS
   =============================================================== */

$query = "
SELECT
    a.application_id,
    a.student_id,
    a.subject,
    a.description,
    a.submission_date,
    a.review_date,
    a.remarks,

    st.full_name,
    st.prn,
    st.course,
    st.year,
    st.semester,
    st.division,

    at.type_name,

    s.status_name

FROM applications a

LEFT JOIN students st
    ON a.student_id = st.student_id

LEFT JOIN application_types at
    ON a.application_type_id = at.application_type_id

LEFT JOIN statuses s
    ON a.status_id = s.status_id

WHERE a.application_id = $1

LIMIT 1
";


$result = pg_query_params(
    $conn,
    $query,
    [$id]
);


if (
    !$result ||
    pg_num_rows($result) === 0
) {
    die("Application not found.");
}


$row = pg_fetch_assoc($result);


/* ===============================================================
   GET ALL APPLICATION ATTACHMENTS
   =============================================================== */

$attachment_result = pg_query_params(
    $conn,
    "
    SELECT
        attachment_id,
        file_name,
        file_type,
        file_size,
        uploaded_at
    FROM attachments
    WHERE module_type = 'APPLICATION'
      AND reference_id = $1
    ORDER BY uploaded_at DESC
    ",
    [$id]
);


/* ---------------------------------------------------------------
   Convert attachment size into readable format.
   --------------------------------------------------------------- */

function formatFileSize($bytes)
{
    $bytes = (int)$bytes;

    if ($bytes <= 0) {
        return "0 KB";
    }

    if ($bytes < 1024) {
        return $bytes . " B";
    }

    if ($bytes < 1048576) {
        return number_format($bytes / 1024, 1) . " KB";
    }

    if ($bytes < 1073741824) {
        return number_format($bytes / 1048576, 1) . " MB";
    }

    return number_format($bytes / 1073741824, 1) . " GB";
}


/* ===============================================================
   APPLICATION STATUS
   =============================================================== */

$status = $row["status_name"] ?? "Unknown";

$status_class = "status-new";


if ($status === "Approved") {

    $status_class = "status-approved";
} elseif ($status === "Rejected") {

    $status_class = "status-rejected";
} elseif (
    $status === "Processing" ||
    $status === "Under Review"
) {

    $status_class = "status-processing";
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
        CampusDesk | View Application
    </title>


    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <!-- Existing project CSS -->
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
        href="../css/authority-dashboard.css">

    <!-- External authority detail-page CSS -->
    <link
        rel="stylesheet"
        href="css/details.css">

</head>


<body>


    <?php include "../includes/navbar.php"; ?>

    <?php include "../includes/header.php"; ?>


    <div class="dashboard-layout">

        <main class="dashboard-content">

            <div class="authority-page">


                <!-- =================================================
                     BACK BUTTON
                     ================================================= -->

                <a
                    href="applications.php"
                    class="page-back">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Applications

                </a>


                <!-- =================================================
                     APPLICATION DETAIL CARD
                     ================================================= -->

                <div class="detail-card">


                    <!-- =================================================
                         HEADER
                         ================================================= -->

                    <div class="detail-header">

                        <h2>

                            Application #APP<?= str_pad(
                                                $row["application_id"],
                                                3,
                                                "0",
                                                STR_PAD_LEFT
                                            ) ?>

                        </h2>


                        <p>

                            <?= htmlspecialchars(
                                $row["subject"]
                            ) ?>

                        </p>


                        <span
                            class="status-badge <?= htmlspecialchars($status_class) ?>">

                            <?= htmlspecialchars($status) ?>

                        </span>

                    </div>


                    <!-- =================================================
                         BODY
                         ================================================= -->

                    <div class="detail-body">


                        <!-- =================================================
                             STUDENT / APPLICATION INFORMATION
                             ================================================= -->

                        <div class="detail-grid">


                            <div class="detail-item">

                                <small>
                                    Student
                                </small>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row["full_name"] ?? "Unknown Student"
                                    ) ?>

                                </strong>

                            </div>


                            <div class="detail-item">

                                <small>
                                    PRN
                                </small>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row["prn"] ?? "-"
                                    ) ?>

                                </strong>

                            </div>


                            <div class="detail-item">

                                <small>
                                    Course
                                </small>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row["course"] ?? "-"
                                    ) ?>

                                </strong>

                            </div>


                            <div class="detail-item">

                                <small>
                                    Year
                                </small>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row["year"] ?? "-"
                                    ) ?>

                                </strong>

                            </div>


                            <div class="detail-item">

                                <small>
                                    Semester
                                </small>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row["semester"] ?? "-"
                                    ) ?>

                                </strong>

                            </div>


                            <div class="detail-item">

                                <small>
                                    Division
                                </small>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row["division"] ?? "-"
                                    ) ?>

                                </strong>

                            </div>


                            <div class="detail-item">

                                <small>
                                    Application Type
                                </small>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row["type_name"] ?? "-"
                                    ) ?>

                                </strong>

                            </div>


                            <div class="detail-item">

                                <small>
                                    Submitted On
                                </small>

                                <strong>

                                    <?= !empty($row["submission_date"])
                                        ? date(
                                            "d M Y",
                                            strtotime(
                                                $row["submission_date"]
                                            )
                                        )
                                        : "-"
                                    ?>

                                </strong>

                            </div>


                            <?php if (!empty($row["review_date"])): ?>

                                <div class="detail-item">

                                    <small>
                                        Review Date
                                    </small>

                                    <strong>

                                        <?= date(
                                            "d M Y",
                                            strtotime(
                                                $row["review_date"]
                                            )
                                        ) ?>

                                    </strong>

                                </div>

                            <?php endif; ?>


                        </div>


                        <!-- =================================================
                             APPLICATION DESCRIPTION
                             ================================================= -->

                        <div class="detail-section">

                            <h3>
                                Description
                            </h3>


                            <div class="detail-content">

                                <?= nl2br(
                                    htmlspecialchars(
                                        $row["description"]
                                    )
                                ) ?>

                            </div>

                        </div>


                        <!-- =================================================
                             SUPPORTING ATTACHMENTS
                             ================================================= -->

                        <div class="detail-section">

                            <h3>
                                Supporting Documents
                            </h3>


                            <?php if (
                                $attachment_result &&
                                pg_num_rows($attachment_result) > 0
                            ): ?>


                                <div class="attachment-list">


                                    <?php while (
                                        $attachment =
                                        pg_fetch_assoc(
                                            $attachment_result
                                        )
                                    ): ?>


                                        <div class="attachment-item">


                                            <div class="attachment-name">

                                                <i
                                                    class="fa-solid fa-paperclip"></i>


                                                <div>

                                                    <div>
                                                        <?= htmlspecialchars(
                                                            $attachment["file_name"]
                                                        ) ?>
                                                    </div>

                                                    <small>
                                                        <?= htmlspecialchars(
                                                            $attachment["file_type"]
                                                        ) ?>

                                                        &nbsp;•&nbsp;

                                                        <?= formatFileSize(
                                                            $attachment["file_size"]
                                                        ) ?>
                                                    </small>

                                                </div>

                                            </div>

                                            <a
                                                href="view-attachment.php?attachment_id=<?= (int)$attachment["attachment_id"] ?>&application_id=<?= (int)$row["application_id"] ?>"
                                                class="authority-btn btn-view">

                                                <i class="fa-solid fa-eye"></i>

                                                View
                                            </a>


                                        </div>


                                    <?php endwhile; ?>


                                </div>


                            <?php else: ?>


                                <div class="detail-content">

                                    No attachment was submitted
                                    with this application.

                                </div>


                            <?php endif; ?>


                        </div>


                        <!-- =================================================
                             EXISTING AUTHORITY REMARKS
                             ================================================= -->

                        <?php if (!empty($row["remarks"])): ?>

                            <div class="detail-section">

                                <h3>
                                    Authority Reply / Remarks
                                </h3>


                                <div class="detail-content">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $row["remarks"]
                                        )
                                    ) ?>

                                </div>

                            </div>

                        <?php endif; ?>


                        <!-- =================================================
                             AUTHORITY REPLY
                             ================================================= -->

                        <div class="detail-section authority-reply">

                            <h3>
                                Send Reply / Message to Student
                            </h3>


                            <form
                                method="POST">

                                <input
                                    type="hidden"
                                    name="application_id"
                                    value="<?= (int)$row["application_id"] ?>">


                                <textarea
                                    name="message"
                                    placeholder="Write a message or reply to the student..."></textarea>


                                <div class="authority-actions">

                                    <button
                                        type="submit"
                                        name="action"
                                        value="send_reply"
                                        class="authority-btn btn-view">

                                        <i
                                            class="fa-solid fa-paper-plane"></i>

                                        Send Reply

                                    </button>

                                </div>

                            </form>

                        </div>


                        <!-- =================================================
                             ACCEPT / REJECT APPLICATION
                             ================================================= -->

                        <?php if (
                            $status !== "Approved" &&
                            $status !== "Rejected"
                        ): ?>


                            <div class="detail-section">

                                <h3>
                                    Application Decision
                                </h3>


                                <div class="authority-actions">


                                    <!-- Approve -->
                                    <form
                                        method="POST">

                                        <input
                                            type="hidden"
                                            name="application_id"
                                            value="<?= (int)$row["application_id"] ?>">


                                        <input
                                            type="hidden"
                                            name="message"
                                            value="">


                                        <button
                                            type="submit"
                                            name="action"
                                            value="approve"
                                            class="authority-btn btn-approve">

                                            <i
                                                class="fa-solid fa-check"></i>

                                            Accept Application

                                        </button>

                                    </form>


                                    <!-- Reject -->
                                    <form
                                        method="POST">

                                        <input
                                            type="hidden"
                                            name="application_id"
                                            value="<?= (int)$row["application_id"] ?>">


                                        <input
                                            type="hidden"
                                            name="message"
                                            value="">


                                        <button
                                            type="submit"
                                            name="action"
                                            value="reject"
                                            class="authority-btn btn-reject">

                                            <i
                                                class="fa-solid fa-xmark"></i>

                                            Reject Application

                                        </button>

                                    </form>


                                </div>

                            </div>


                        <?php endif; ?>


                    </div>

                </div>

            </div>

        </main>

    </div>


    <?php include "../includes/footer.php"; ?>


</body>

</html>
