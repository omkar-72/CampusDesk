<?php

/*
|--------------------------------------------------------------------------
| AUTHORITY - VIEW GRIEVANCE
|--------------------------------------------------------------------------
| This page allows an authority to:
| - View complete grievance details
| - View student and grievance information
| - View grievance attachments
| - See the assigned authority
| - Update grievance status
| - Add resolution remarks
| - Assign the grievance to the logged-in authority
| - Send a notification to the student
|--------------------------------------------------------------------------
*/


/* ===============================================================
   AUTHORITY ACCESS CHECK
   =============================================================== */

require_once "../includes/auth.php";
requireAuthority();


/* ===============================================================
   DATABASE CONNECTION
   =============================================================== */

require_once "../config/database.php";


/* ===============================================================
   GET LOGGED-IN USER
   =============================================================== */

$user_id = getLoggedInUserId();


/* ===============================================================
   GET AUTHORITY DETAILS
   =============================================================== */

$authority_query = "
    SELECT
        authority_id,
        name
    FROM authorities
    WHERE user_id = $1
      AND status = TRUE
    LIMIT 1
";

$authority_result = pg_query_params(
    $conn,
    $authority_query,
    [$user_id]
);

if (
    !$authority_result ||
    pg_num_rows($authority_result) === 0
) {
    die("Authority profile not found.");
}

$authority = pg_fetch_assoc($authority_result);

$authority_id = (int) $authority["authority_id"];


/* ===============================================================
   GET GRIEVANCE ID
   =============================================================== */

$id = $_GET["id"] ?? "";

if (!is_numeric($id)) {
    die("Invalid grievance.");
}

$id = (int) $id;


/* ===============================================================
   HANDLE AUTHORITY ACTION
   =============================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    ---------------------------------------------------------------
    Get submitted values.
    ---------------------------------------------------------------
    */

    $grievance_id = $_POST["grievance_id"] ?? "";
    $status_id = $_POST["status_id"] ?? "";
    $remarks = trim($_POST["remarks"] ?? "");


    /*
    ---------------------------------------------------------------
    Validate grievance ID.
    ---------------------------------------------------------------
    */

    if (!is_numeric($grievance_id)) {
        die("Invalid grievance.");
    }

    $grievance_id = (int) $grievance_id;


    /*
    ---------------------------------------------------------------
    Validate status ID.
    ---------------------------------------------------------------
    */

    if (!is_numeric($status_id)) {
        die("Invalid grievance status.");
    }

    $status_id = (int) $status_id;


    /* ===========================================================
       CHECK SELECTED GRIEVANCE STATUS
       =========================================================== */

    $status_result = pg_query_params(
        $conn,
        "
        SELECT
            status_id,
            status_name
        FROM statuses
        WHERE status_id = $1
          AND module_type = 'GRIEVANCE'
          AND status = TRUE
        LIMIT 1
        ",
        [$status_id]
    );

    if (
        !$status_result ||
        pg_num_rows($status_result) === 0
    ) {
        die("Invalid grievance status.");
    }

    $status_data = pg_fetch_assoc($status_result);

    $status_name = $status_data["status_name"];


    /* ===========================================================
       DETERMINE RESOLUTION DATE
       =========================================================== */

    $resolution_date = null;

    /*
    ---------------------------------------------------------------
    When the grievance is resolved, save the current timestamp.
    ---------------------------------------------------------------
    */

    if (
        strtolower(trim($status_name)) === "resolved"
    ) {
        $resolution_date = date("Y-m-d H:i:s");
    }


    /* ===========================================================
       UPDATE GRIEVANCE
       =========================================================== */

    $update_result = pg_query_params(
        $conn,
        "
        UPDATE grievances
        SET
            assigned_to = $1,
            status_id = $2,
            resolution_date = $3,
            resolution_remarks = $4
        WHERE grievance_id = $5
        ",
        [
            $authority_id,
            $status_id,
            $resolution_date,
            $remarks !== ""
                ? $remarks
                : null,
            $grievance_id
        ]
    );

    if (!$update_result) {
        die("Failed to update grievance.");
    }


    /* ===========================================================
       GET STUDENT USER ID
       =========================================================== */

    $student_result = pg_query_params(
        $conn,
        "
        SELECT
            s.user_id
        FROM students s
        INNER JOIN grievances g
            ON g.student_id = s.student_id
        WHERE g.grievance_id = $1
        LIMIT 1
        ",
        [$grievance_id]
    );


    /*
    ---------------------------------------------------------------
    Send notification to the student.
    ---------------------------------------------------------------
    */

    if (
        $student_result &&
        pg_num_rows($student_result) > 0
    ) {

        $student = pg_fetch_assoc($student_result);

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
                'GRIEVANCE',
                $2,
                $3,
                $4,
                'STATUS_UPDATE',
                FALSE
            )
            ",
            [
                $student["user_id"],
                $grievance_id,
                "Grievance Updated",
                "Your grievance #GRV" .
                    str_pad(
                        $grievance_id,
                        3,
                        "0",
                        STR_PAD_LEFT
                    ) .
                    " has been updated to " .
                    $status_name .
                    " by the authority."
            ]
        );

        /*
        -----------------------------------------------------------
        Notification failure should not silently be ignored.
        -----------------------------------------------------------
        */

        if (!$notification_result) {
            die("Grievance updated, but notification could not be created.");
        }
    }


    /* ===========================================================
       AUDIT LOG
       =========================================================== */

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
            'GRIEVANCE',
            $2,
            $3,
            $4
        )
        ",
        [
            $user_id,
            $grievance_id,
            "Updated grievance status to " . $status_name,
            $_SERVER["REMOTE_ADDR"] ?? null
        ]
    );


    /* ===========================================================
       RETURN TO SAME GRIEVANCE
       =========================================================== */

    header(
        "Location: view_grievance.php?id=" .
            urlencode($grievance_id)
    );

    exit();
}


/* ===============================================================
   GET GRIEVANCE DETAILS
   =============================================================== */

$query = "
    SELECT
        g.grievance_id,
        g.student_id,
        g.title,
        g.description,
        g.submission_date,
        g.resolution_date,
        g.resolution_remarks,
        g.anonymous_status,

        st.full_name,
        st.prn,
        st.course,
        st.year,
        st.semester,
        st.division,

        gc.category_name,

        s.status_id,
        s.status_name,

        a.authority_id,
        a.name AS authority_name

    FROM grievances g

    LEFT JOIN students st
        ON g.student_id = st.student_id

    LEFT JOIN grievance_categories gc
        ON g.category_id = gc.category_id

    LEFT JOIN statuses s
        ON g.status_id = s.status_id

    LEFT JOIN authorities a
        ON g.assigned_to = a.authority_id

    WHERE g.grievance_id = $1

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
    die("Grievance not found.");
}

$row = pg_fetch_assoc($result);


/* ===============================================================
   GET AVAILABLE GRIEVANCE STATUSES
   =============================================================== */

$status_query = "
    SELECT
        status_id,
        status_name
    FROM statuses
    WHERE module_type = 'GRIEVANCE'
      AND status = TRUE
    ORDER BY status_id ASC
";

$status_result = pg_query(
    $conn,
    $status_query
);

$status_options = [];

if ($status_result) {

    while (
        $status_row = pg_fetch_assoc($status_result)
    ) {
        $status_options[] = $status_row;
    }
}


/* ===============================================================
   GET GRIEVANCE ATTACHMENTS
   =============================================================== */

$attachment_query = "
    SELECT
        attachment_id,
        file_name,
        file_type,
        file_size,
        uploaded_at
    FROM attachments
    WHERE module_type = 'GRIEVANCE'
      AND reference_id = $1
    ORDER BY uploaded_at DESC
";

$attachment_result = pg_query_params(
    $conn,
    $attachment_query,
    [$id]
);


/* ===============================================================
   FORMAT FILE SIZE
   =============================================================== */

function formatFileSize($bytes)
{
    $bytes = (int) $bytes;

    if ($bytes <= 0) {
        return "0 Bytes";
    }

    $units = [
        "Bytes",
        "KB",
        "MB",
        "GB"
    ];

    $power = floor(log($bytes, 1024));

    $power = min(
        $power,
        count($units) - 1
    );

    return round(
        $bytes / pow(1024, $power),
        2
    ) . " " . $units[$power];
}


/* ===============================================================
   STATUS CSS CLASS
   =============================================================== */

$status = $row["status_name"] ?? "Unknown";

$status_class = "status-new";

if (
    strtolower($status) === "resolved"
) {

    $status_class = "status-resolved";
} elseif (
    strtolower($status) === "rejected"
) {

    $status_class = "status-rejected";
} elseif (
    strtolower($status) === "accepted"
) {

    $status_class = "status-accepted";
} elseif (
    strtolower($status) === "approved"
) {

    $status_class = "status-approved";
} elseif (
    strtolower($status) === "processing" ||
    strtolower($status) === "under review" ||
    strtolower($status) === "in progress"
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
        CampusDesk | View Grievance
    </title>


    <!-- =======================================================
         FONT AWESOME
         ======================================================= -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <!-- =======================================================
         PROJECT CSS
         ======================================================= -->

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

    <!-- Authority detail page CSS -->
    <link
        rel="stylesheet"
        href="../authority/css/details.css">

</head>


<body>


    <!-- =======================================================
         NAVBAR
         ======================================================= -->

    <?php include "../includes/navbar.php"; ?>


    <!-- =======================================================
         HEADER
         ======================================================= -->

    <?php include "../includes/header.php"; ?>


    <div class="dashboard-layout">


        <main class="dashboard-content">


            <div class="authority-page">


                <!-- =================================================
                     BACK BUTTON
                     ================================================= -->

                <a
                    href="grievances.php"
                    class="page-back">

                    <i class="fa-solid fa-arrow-left"></i>

                    Back to Grievances

                </a>


                <!-- =================================================
                     MAIN DETAIL CARD
                     ================================================= -->

                <div class="detail-card">


                    <!-- =================================================
                         DETAIL HEADER
                         ================================================= -->

                    <div class="detail-header">

                        <h2>

                            Grievance #GRV<?= str_pad(
                                                $row["grievance_id"],
                                                3,
                                                "0",
                                                STR_PAD_LEFT
                                            ) ?>

                        </h2>


                        <p>

                            <?= htmlspecialchars(
                                $row["title"]
                            ) ?>

                        </p>


                        <span
                            class="status-badge <?= $status_class ?>">

                            <?= htmlspecialchars(
                                $status
                            ) ?>

                        </span>

                    </div>


                    <!-- =================================================
                         DETAIL BODY
                         ================================================= -->

                    <div class="detail-body">


                        <!-- =================================================
                             INFORMATION GRID
                             ================================================= -->

                        <div class="detail-grid">


                            <!-- Student -->

                            <div class="detail-item">

                                <small>
                                    Student
                                </small>

                                <strong>

                                    <?php if (
                                        $row["anonymous_status"]
                                    ): ?>

                                        Anonymous

                                    <?php else: ?>

                                        <?= htmlspecialchars(
                                            $row["full_name"] ??
                                                "Unknown Student"
                                        ) ?>

                                    <?php endif; ?>

                                </strong>

                            </div>


                            <!-- PRN -->

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


                            <!-- Course -->

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


                            <!-- Year -->

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


                            <!-- Semester -->

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


                            <!-- Division -->

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


                            <!-- Category -->

                            <div class="detail-item">

                                <small>
                                    Category
                                </small>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row["category_name"] ?? "-"
                                    ) ?>

                                </strong>

                            </div>


                            <!-- Submission Date -->

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


                            <!-- Assigned Authority -->

                            <div class="detail-item">

                                <small>
                                    Assigned Authority
                                </small>

                                <strong>

                                    <?= !empty($row["authority_name"])
                                        ? htmlspecialchars(
                                            $row["authority_name"]
                                        )
                                        : "Not Assigned"
                                    ?>

                                </strong>

                            </div>


                            <!-- Resolution Date -->

                            <?php if (
                                !empty($row["resolution_date"])
                            ): ?>

                                <div class="detail-item">

                                    <small>
                                        Resolution Date
                                    </small>

                                    <strong>

                                        <?= date(
                                            "d M Y",
                                            strtotime(
                                                $row["resolution_date"]
                                            )
                                        ) ?>

                                    </strong>

                                </div>

                            <?php endif; ?>


                        </div>


                        <!-- =================================================
                             DESCRIPTION
                             ================================================= -->

                        <div class="detail-section">

                            <h3>

                                <i class="fa-solid fa-align-left"></i>

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
                             ATTACHMENTS
                             ================================================= -->

                        <?php if (
                            $attachment_result &&
                            pg_num_rows($attachment_result) > 0
                        ): ?>

                            <div class="detail-section">

                                <h3>

                                    <i class="fa-solid fa-paperclip"></i>

                                    Attachments

                                </h3>


                                <div class="attachment-list">


                                    <?php while (
                                        $attachment =
                                        pg_fetch_assoc(
                                            $attachment_result
                                        )
                                    ): ?>


                                        <div class="attachment-item">


                                            <!-- Attachment information -->

                                            <div class="attachment-info">

                                                <div class="attachment-icon">

                                                    <i class="fa-solid fa-file"></i>

                                                </div>


                                                <div>

                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            $attachment["file_name"]
                                                        ) ?>

                                                    </strong>


                                                    <span>

                                                        <?= htmlspecialchars(
                                                            $attachment["file_type"]
                                                        ) ?>

                                                        &nbsp;•&nbsp;

                                                        <?= formatFileSize(
                                                            $attachment["file_size"]
                                                        ) ?>

                                                        &nbsp;•&nbsp;

                                                        <?= date(
                                                            "d M Y, h:i A",
                                                            strtotime(
                                                                $attachment["uploaded_at"]
                                                            )
                                                        ) ?>

                                                    </span>

                                                </div>

                                            </div>


                                            <!-- View attachment -->

                                            <a
                                                href="download_attachment.php?id=<?= (int) $attachment["attachment_id"] ?>"
                                                target="_blank"
                                                class="authority-btn btn-view">

                                                <i class="fa-solid fa-eye"></i>

                                                View

                                            </a>


                                        </div>


                                    <?php endwhile; ?>


                                </div>

                            </div>

                        <?php endif; ?>


                        <!-- =================================================
                             RESOLUTION REMARKS
                             ================================================= -->

                        <?php if (
                            !empty($row["resolution_remarks"])
                        ): ?>

                            <div class="detail-section">

                                <h3>

                                    <i class="fa-solid fa-comment-dots"></i>

                                    Resolution Remarks

                                </h3>


                                <div class="detail-content">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $row["resolution_remarks"]
                                        )
                                    ) ?>

                                </div>

                            </div>

                        <?php endif; ?>


                        <!-- =================================================
                             AUTHORITY MANAGEMENT
                             ================================================= -->

                        <div class="detail-section">


                            <h3>

                                <i
                                    class="fa-solid fa-pen-to-square"></i>

                                Manage Grievance

                            </h3>


                            <div class="management-form">


                                <!-- =========================================
                                     CURRENT ASSIGNMENT
                                     ========================================= -->

                                <div class="assignment-info">

                                    <i
                                        class="fa-solid fa-user-shield"></i>


                                    <span>

                                        <?php if (
                                            !empty($row["authority_name"])
                                        ): ?>

                                            Currently assigned to:

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $row["authority_name"]
                                                ) ?>

                                            </strong>

                                        <?php else: ?>

                                            This grievance is not
                                            currently assigned.

                                        <?php endif; ?>

                                    </span>

                                </div>


                                <!-- =========================================
                                     UPDATE FORM
                                     ========================================= -->

                                <form
                                    method="POST"
                                    action="view_grievance.php?id=<?= $id ?>">


                                    <!-- Hidden grievance ID -->

                                    <input
                                        type="hidden"
                                        name="grievance_id"
                                        value="<?= $id ?>">


                                    <!-- =====================================
                                         STATUS
                                         ===================================== -->

                                    <div class="form-group">

                                        <label
                                            for="status_id">

                                            Grievance Status

                                        </label>


                                        <select
                                            name="status_id"
                                            id="status_id"
                                            required>

                                            <option value="">

                                                Select Status

                                            </option>


                                            <?php foreach (
                                                $status_options
                                                as $status_option
                                            ): ?>

                                                <option
                                                    value="<?= (int) $status_option["status_id"] ?>"
                                                    <?= (
                                                        (int) $row["status_id"]
                                                        ===
                                                        (int) $status_option["status_id"]
                                                    )
                                                        ? "selected"
                                                        : ""
                                                    ?>>

                                                    <?= htmlspecialchars(
                                                        $status_option["status_name"]
                                                    ) ?>

                                                </option>

                                            <?php endforeach; ?>


                                        </select>

                                    </div>


                                    <!-- =====================================
                                         REMARKS
                                         ===================================== -->

                                    <div class="form-group">

                                        <label
                                            for="remarks">

                                            Resolution / Authority Remarks

                                        </label>


                                        <textarea
                                            name="remarks"
                                            id="remarks"
                                            placeholder="Enter your remarks or resolution details..."><?= htmlspecialchars(
                                                                                                            $row["resolution_remarks"] ?? ""
                                                                                                        ) ?></textarea>

                                    </div>


                                    <!-- =====================================
                                         SAVE BUTTON
                                         ===================================== -->

                                    <div class="management-actions">


                                        <button
                                            type="submit"
                                            class="authority-btn save-btn">

                                            <i
                                                class="fa-solid fa-floppy-disk"></i>

                                            Update Grievance

                                        </button>


                                    </div>


                                </form>


                            </div>

                        </div>


                    </div>

                </div>

            </div>

        </main>

    </div>


    <!-- =======================================================
         FOOTER
         ======================================================= -->

    <?php include "../includes/footer.php"; ?>


</body>

</html>
