<?php

/*
|--------------------------------------------------------------------------
| CampusDesk - Authority View Suggestion
|--------------------------------------------------------------------------
| This page allows an authority to:
| 1. View complete suggestion details
| 2. View student academic information
| 3. View suggestion attachments
| 4. Change suggestion status
| 5. Save authority remarks
| 6. Assign the suggestion to the logged-in authority
| 7. Notify the student
| 8. Create an audit log
|--------------------------------------------------------------------------
*/

require_once "../includes/auth.php";
requireAuthority();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Get logged-in user ID
|--------------------------------------------------------------------------
*/
$user_id = getLoggedInUserId();

/*
|--------------------------------------------------------------------------
| Validate suggestion ID
|--------------------------------------------------------------------------
*/
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Suggestion not found.");
}

$suggestion_id = (int) $_GET["id"];

/*
|--------------------------------------------------------------------------
| Get logged-in authority
|--------------------------------------------------------------------------
| suggestions.reviewed_by stores authority_id.
|--------------------------------------------------------------------------
*/
$authority_query = "
    SELECT
        authority_id,
        name,
        status
    FROM authorities
    WHERE user_id = $1
    LIMIT 1
";

$authority_result = pg_query_params(
    $conn,
    $authority_query,
    [$user_id]
);

if (!$authority_result || pg_num_rows($authority_result) === 0) {
    die("Authority profile not found.");
}

$authority = pg_fetch_assoc($authority_result);

$authority_id = (int) $authority["authority_id"];

/*
|--------------------------------------------------------------------------
| Check authority account status
|--------------------------------------------------------------------------
*/
if (!$authority["status"]) {
    die("Your authority account is inactive.");
}

/*
|--------------------------------------------------------------------------
| Handle suggestion update
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    |--------------------------------------------------------------------------
    | Validate submitted suggestion ID
    |--------------------------------------------------------------------------
    */
    $posted_suggestion_id = isset($_POST["suggestion_id"])
        ? (int) $_POST["suggestion_id"]
        : 0;

    if ($posted_suggestion_id !== $suggestion_id) {
        die("Invalid suggestion request.");
    }

    /*
    |--------------------------------------------------------------------------
    | Get selected status
    |--------------------------------------------------------------------------
    */
    $status_id = isset($_POST["status_id"])
        ? (int) $_POST["status_id"]
        : 0;

    /*
    |--------------------------------------------------------------------------
    | Get authority remarks
    |--------------------------------------------------------------------------
    */
    $remarks = trim($_POST["remarks"] ?? "");

    /*
    |--------------------------------------------------------------------------
    | Validate status
    |--------------------------------------------------------------------------
    */
    if ($status_id <= 0) {
        die("Please select a valid status.");
    }

    /*
    |--------------------------------------------------------------------------
    | Verify that the selected status belongs to SUGGESTION
    |--------------------------------------------------------------------------
    */
    $status_query = "
        SELECT
            status_id,
            status_name
        FROM statuses
        WHERE status_id = $1
          AND module_type = 'SUGGESTION'
          AND status = TRUE
        LIMIT 1
    ";

    $status_result = pg_query_params(
        $conn,
        $status_query,
        [$status_id]
    );

    if (!$status_result || pg_num_rows($status_result) === 0) {
        die("Invalid suggestion status.");
    }

    $selected_status = pg_fetch_assoc($status_result);

    $selected_status_name = $selected_status["status_name"];

    /*
    |--------------------------------------------------------------------------
    | Set decision date
    |--------------------------------------------------------------------------
    | Final statuses receive the current date/time.
    |--------------------------------------------------------------------------
    */
    $decision_date = null;

    if (
        $selected_status_name === "Accepted" ||
        $selected_status_name === "Rejected" ||
        $selected_status_name === "Implemented"
    ) {
        $decision_date = date("Y-m-d H:i:s");
    }

    /*
    |--------------------------------------------------------------------------
    | Start transaction
    |--------------------------------------------------------------------------
    */
    pg_query($conn, "BEGIN");

    /*
    |--------------------------------------------------------------------------
    | Update suggestion
    |--------------------------------------------------------------------------
    */
    $update_query = "
        UPDATE suggestions
        SET
            reviewed_by = $1,
            status_id = $2,
            decision_date = $3,
            remarks = $4
        WHERE suggestion_id = $5
    ";

    $update_result = pg_query_params(
        $conn,
        $update_query,
        [
            $authority_id,
            $status_id,
            $decision_date,
            $remarks !== "" ? $remarks : null,
            $suggestion_id
        ]
    );

    /*
    |--------------------------------------------------------------------------
    | Stop transaction if update failed
    |--------------------------------------------------------------------------
    */
    if (!$update_result) {
        pg_query($conn, "ROLLBACK");
        die("Unable to update suggestion.");
    }

    /*
    |--------------------------------------------------------------------------
    | Get student user ID
    |--------------------------------------------------------------------------
    */
    $student_query = "
        SELECT
            st.user_id
        FROM suggestions sg
        INNER JOIN students st
            ON st.student_id = sg.student_id
        WHERE sg.suggestion_id = $1
        LIMIT 1
    ";

    $student_result = pg_query_params(
        $conn,
        $student_query,
        [$suggestion_id]
    );

    /*
    |--------------------------------------------------------------------------
    | Create student notification
    |--------------------------------------------------------------------------
    */
    if ($student_result && pg_num_rows($student_result) > 0) {

        $student = pg_fetch_assoc($student_result);

        $student_user_id = (int) $student["user_id"];

        $notification_title = "Suggestion Updated";

        $notification_message =
            "Your suggestion #SG"
            . str_pad(
                $suggestion_id,
                3,
                "0",
                STR_PAD_LEFT
            )
            . " has been updated. Current status: "
            . $selected_status_name
            . ".";

        $notification_query = "
            INSERT INTO notifications (
                user_id,
                module_type,
                reference_id,
                title,
                message,
                notification_type,
                is_read
            )
            VALUES (
                $1,
                'SUGGESTION',
                $2,
                $3,
                $4,
                'SUGGESTION_UPDATE',
                FALSE
            )
        ";

        $notification_result = pg_query_params(
            $conn,
            $notification_query,
            [
                $student_user_id,
                $suggestion_id,
                $notification_title,
                $notification_message
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Rollback if notification creation fails
        |--------------------------------------------------------------------------
        */
        if (!$notification_result) {
            pg_query($conn, "ROLLBACK");
            die("Suggestion update failed because notification could not be created.");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create audit log
    |--------------------------------------------------------------------------
    */
    $audit_query = "
        INSERT INTO audit_logs (
            user_id,
            module_type,
            reference_id,
            action,
            ip_address
        )
        VALUES (
            $1,
            'SUGGESTION',
            $2,
            $3,
            $4
        )
    ";

    $audit_action =
        "Suggestion reviewed/updated. Status: "
        . $selected_status_name;

    $ip_address = $_SERVER["REMOTE_ADDR"] ?? null;

    if ($ip_address === "") {
        $ip_address = null;
    }

    $audit_result = pg_query_params(
        $conn,
        $audit_query,
        [
            $user_id,
            $suggestion_id,
            $audit_action,
            $ip_address
        ]
    );

    /*
    |--------------------------------------------------------------------------
    | Rollback if audit log fails
    |--------------------------------------------------------------------------
    */
    if (!$audit_result) {
        pg_query($conn, "ROLLBACK");
        die("Suggestion update could not be completed.");
    }

    /*
    |--------------------------------------------------------------------------
    | Commit transaction
    |--------------------------------------------------------------------------
    */
    pg_query($conn, "COMMIT");

    /*
    |--------------------------------------------------------------------------
    | Redirect after successful update
    |--------------------------------------------------------------------------
    | Prevents duplicate form submission after refresh.
    |--------------------------------------------------------------------------
    */
    header(
        "Location: view_suggestion.php?id="
            . $suggestion_id
            . "&updated=1"
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Get available suggestion statuses
|--------------------------------------------------------------------------
*/
$status_query = "
    SELECT
        status_id,
        status_name
    FROM statuses
    WHERE module_type = 'SUGGESTION'
      AND status = TRUE
    ORDER BY status_id
";

$status_result = pg_query($conn, $status_query);

/*
|--------------------------------------------------------------------------
| Get suggestion details
|--------------------------------------------------------------------------
| Student academic information is included for the same
| information-card layout used on the other detail pages.
|--------------------------------------------------------------------------
*/
$query = "
    SELECT
        sg.suggestion_id,
        sg.student_id,
        sg.reviewed_by,

        st.full_name,
        st.prn,
        st.course,
        st.year,
        st.semester,
        st.division,

        sc.category_name,

        sg.title,
        sg.description,

        sg.submission_date,
        sg.decision_date,
        sg.remarks,

        s.status_id,
        s.status_name,

        a.name AS reviewed_authority

    FROM suggestions sg

    LEFT JOIN students st
        ON sg.student_id = st.student_id

    LEFT JOIN suggestion_categories sc
        ON sg.category_id = sc.category_id

    LEFT JOIN statuses s
        ON sg.status_id = s.status_id

    LEFT JOIN authorities a
        ON sg.reviewed_by = a.authority_id

    WHERE sg.suggestion_id = $1

    LIMIT 1
";

$result = pg_query_params(
    $conn,
    $query,
    [$suggestion_id]
);

if (!$result || pg_num_rows($result) === 0) {
    die("Suggestion not found.");
}

$row = pg_fetch_assoc($result);

/*
|--------------------------------------------------------------------------
| Get suggestion attachments
|--------------------------------------------------------------------------
| Attachments are linked using:
| module_type = 'SUGGESTION'
| reference_id = suggestion_id
|--------------------------------------------------------------------------
*/
$attachment_query = "
    SELECT
        attachment_id,
        file_name,
        file_type,
        file_size,
        uploaded_at
    FROM attachments
    WHERE module_type = 'SUGGESTION'
      AND reference_id = $1
    ORDER BY uploaded_at DESC
";

$attachment_result = pg_query_params(
    $conn,
    $attachment_query,
    [$suggestion_id]
);

/*
|--------------------------------------------------------------------------
| Format file size
|--------------------------------------------------------------------------
*/
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

/*
|--------------------------------------------------------------------------
| Check current reviewer
|--------------------------------------------------------------------------
*/
$is_reviewed_by_me =
    !empty($row["reviewed_by"])
    && (int) $row["reviewed_by"] === $authority_id;

/*
|--------------------------------------------------------------------------
| Check successful update
|--------------------------------------------------------------------------
*/
$updated =
    isset($_GET["updated"])
    && $_GET["updated"] === "1";

/*
|--------------------------------------------------------------------------
| Determine status CSS class
|--------------------------------------------------------------------------
*/
$status_class = "status-new";

$current_status = strtolower(
    trim($row["status_name"] ?? "")
);

if (
    $current_status === "under review" ||
    $current_status === "in progress" ||
    $current_status === "processing"
) {
    $status_class = "status-processing";
} elseif (
    $current_status === "accepted" ||
    $current_status === "approved" ||
    $current_status === "implemented"
) {
    $status_class = "status-accepted";
} elseif ($current_status === "rejected") {
    $status_class = "status-rejected";
} elseif ($current_status === "resolved") {
    $status_class = "status-resolved";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>CampusDesk | View Suggestion</title>

    <!-- Font Awesome icons -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- CampusDesk common CSS -->
    <link
        rel="stylesheet"
        href="../css/global.css">

    <!-- Header CSS -->
    <link
        rel="stylesheet"
        href="../css/header.css">

    <!-- Navbar CSS -->
    <link
        rel="stylesheet"
        href="../css/navbar.css">

    <!-- Authority dashboard CSS -->
    <link
        rel="stylesheet"
        href="../css/authority-dashboard.css">

    <!--
        Authority detail-page CSS.

        IMPORTANT:
        This is the exact location provided for details.css.
    -->
    <link
        rel="stylesheet"
        href="../authority/css/details.css">

</head>

<body>

    <!-- Authority navigation -->
    <?php include "../includes/navbar.php"; ?>

    <!-- CampusDesk header -->
    <?php include "../includes/header.php"; ?>

    <div class="dashboard-layout">

        <main class="dashboard-content">

            <div class="authority-page">

                <!-- =====================================================
                     Back Button
                     ===================================================== -->

                <a
                    href="suggestions.php"
                    class="page-back">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Suggestions
                </a>

                <!-- =====================================================
                     Success Message
                     ===================================================== -->

                <?php if ($updated): ?>

                    <div class="success-message">

                        <i class="fa-solid fa-circle-check"></i>

                        Suggestion updated successfully.

                    </div>

                <?php endif; ?>

                <!-- =====================================================
                     Main Suggestion Card
                     ===================================================== -->

                <div class="detail-card">

                    <!-- =================================================
                         Suggestion Header
                         ================================================= -->

                    <div class="detail-header">

                        <div>

                            <h2>
                                Suggestion #SG<?= str_pad(
                                                    $row["suggestion_id"],
                                                    3,
                                                    "0",
                                                    STR_PAD_LEFT
                                                ) ?>
                            </h2>

                            <p>
                                <?= htmlspecialchars(
                                    $row["title"] ?? "Suggestion"
                                ) ?>
                            </p>

                        </div>

                        <!-- Current status -->
                        <span class="status-badge <?= $status_class ?>">

                            <?= htmlspecialchars(
                                $row["status_name"] ?? "Unknown"
                            ) ?>

                        </span>

                    </div>

                    <div class="detail-body">

                        <!-- =================================================
                             Student / Suggestion Information Cards
                             ================================================= -->

                        <div class="detail-grid">

                            <!-- Student -->
                            <div class="detail-item">

                                <span>Student</span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $row["full_name"] ?? "Anonymous"
                                    ) ?>
                                </strong>

                            </div>

                            <!-- PRN -->
                            <div class="detail-item">

                                <span>PRN</span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $row["prn"] ?? "Not Available"
                                    ) ?>
                                </strong>

                            </div>

                            <!-- Course -->
                            <div class="detail-item">

                                <span>Course</span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $row["course"] ?? "Not Available"
                                    ) ?>
                                </strong>

                            </div>

                            <!-- Year -->
                            <div class="detail-item">

                                <span>Year</span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $row["year"] ?? "Not Available"
                                    ) ?>
                                </strong>

                            </div>

                            <!-- Semester -->
                            <div class="detail-item">

                                <span>Semester</span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $row["semester"] ?? "Not Available"
                                    ) ?>
                                </strong>

                            </div>

                            <!-- Division -->
                            <div class="detail-item">

                                <span>Division</span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $row["division"] ?? "Not Available"
                                    ) ?>
                                </strong>

                            </div>

                            <!-- Category -->
                            <div class="detail-item">

                                <span>Category</span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $row["category_name"] ?? "Unknown"
                                    ) ?>
                                </strong>

                            </div>

                            <!-- Submitted On -->
                            <div class="detail-item">

                                <span>Submitted On</span>

                                <strong>
                                    <?= !empty($row["submission_date"])
                                        ? date(
                                            "d M Y",
                                            strtotime(
                                                $row["submission_date"]
                                            )
                                        )
                                        : "Not Available"
                                    ?>
                                </strong>

                            </div>

                            <!-- Assigned Authority -->
                            <div class="detail-item">

                                <span>Assigned Authority</span>

                                <strong>

                                    <?php if (!empty($row["reviewed_authority"])): ?>

                                        <?= htmlspecialchars(
                                            $row["reviewed_authority"]
                                        ) ?>

                                    <?php else: ?>

                                        Not Assigned

                                    <?php endif; ?>

                                </strong>

                            </div>

                        </div>

                        <!-- =================================================
                             Suggestion Description
                             ================================================= -->

                        <div class="detail-section">

                            <h3>
                                <i class="fa-solid fa-lightbulb"></i>
                                Suggestion Description
                            </h3>

                            <div class="detail-content">

                                <?= nl2br(
                                    htmlspecialchars(
                                        $row["description"] ?? ""
                                    )
                                ) ?>

                            </div>

                        </div>

                        <!-- =================================================
                             Attachments
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
                                        $attachment = pg_fetch_assoc(
                                            $attachment_result
                                        )
                                    ): ?>

                                        <div class="attachment-item">

                                            <!-- File information -->
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
                             Decision Date
                             ================================================= -->

                        <?php if (!empty($row["decision_date"])): ?>

                            <div class="detail-section">

                                <h3>
                                    <i class="fa-solid fa-calendar-check"></i>
                                    Decision Date
                                </h3>

                                <div class="detail-content">

                                    <?= date(
                                        "d M Y, h:i A",
                                        strtotime(
                                            $row["decision_date"]
                                        )
                                    ) ?>

                                </div>

                            </div>

                        <?php endif; ?>

                        <!-- =================================================
                             Existing Authority Remarks
                             ================================================= -->

                        <?php if (!empty($row["remarks"])): ?>

                            <div class="detail-section">

                                <h3>
                                    <i class="fa-solid fa-comment"></i>
                                    Authority Remarks
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
                             Manage Suggestion
                             ================================================= -->

                        <div class="detail-section management-section">

                            <h3>
                                <i class="fa-solid fa-user-shield"></i>
                                Manage Suggestion
                            </h3>

                            <form
                                method="POST"
                                action="view_suggestion.php?id=<?= $suggestion_id ?>"
                                class="management-form">

                                <!-- Hidden suggestion ID -->
                                <input
                                    type="hidden"
                                    name="suggestion_id"
                                    value="<?= $suggestion_id ?>">

                                <!-- Status selection -->
                                <div class="form-group">

                                    <label for="status_id">
                                        Suggestion Status
                                    </label>

                                    <select
                                        name="status_id"
                                        id="status_id"
                                        required>

                                        <option value="">
                                            Select Status
                                        </option>

                                        <?php if ($status_result): ?>

                                            <?php while (
                                                $status = pg_fetch_assoc(
                                                    $status_result
                                                )
                                            ): ?>

                                                <option
                                                    value="<?= (int) $status["status_id"] ?>"
                                                    <?= (
                                                        (int) $status["status_id"]
                                                        ===
                                                        (int) $row["status_id"]
                                                    )
                                                        ? "selected"
                                                        : ""
                                                    ?>>

                                                    <?= htmlspecialchars(
                                                        $status["status_name"]
                                                    ) ?>

                                                </option>

                                            <?php endwhile; ?>

                                        <?php endif; ?>

                                    </select>

                                </div>

                                <!-- Authority remarks -->
                                <div class="form-group">

                                    <label for="remarks">
                                        Authority Remarks
                                    </label>

                                    <textarea
                                        name="remarks"
                                        id="remarks"
                                        rows="5"
                                        placeholder="Enter your review or decision remarks..."><?= htmlspecialchars(
                                                                                                    $row["remarks"] ?? ""
                                                                                                ) ?></textarea>

                                </div>

                                <!-- Assignment information -->
                                <div class="assignment-info">

                                    <i class="fa-solid fa-circle-info"></i>

                                    <?php if ($is_reviewed_by_me): ?>

                                        This suggestion is currently
                                        assigned to you for review.

                                    <?php elseif (!empty($row["reviewed_authority"])): ?>

                                        Saving this form will reassign this
                                        suggestion to you for review.

                                    <?php else: ?>

                                        Saving this form will assign this
                                        suggestion to you for review.

                                    <?php endif; ?>

                                </div>

                                <!-- Save button -->
                                <div class="management-actions">

                                    <button
                                        type="submit"
                                        class="authority-btn save-btn">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                        Save Suggestion Update
                                    </button>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </main>

    </div>

    <!-- Common footer -->
    <?php include "../includes/footer.php"; ?>

</body>

</html>
