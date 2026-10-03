<?php
/*
|--------------------------------------------------------------------------
| AUTHORITY - VIEW ATTACHMENT
|--------------------------------------------------------------------------
| Displays a student's attachment inside the Authority interface.
|
| Supported:
| - Images
| - PDF files
|
| The actual file data is served by:
| ../actions/attachment.php
|--------------------------------------------------------------------------
*/

session_start();

/* ===============================================================
   AUTHORITY ACCESS CHECK
   =============================================================== */

if (
    !isset($_SESSION["role_name"]) ||
    $_SESSION["role_name"] !== "AUTHORITY"
) {
    header("Location: ../login.php");
    exit();
}


/* ===============================================================
   DATABASE CONNECTION
   =============================================================== */

require_once "../config/database.php";


/* ===============================================================
   GET ATTACHMENT ID
   =============================================================== */

$attachment_id = $_GET["attachment_id"] ?? "";

if (!is_numeric($attachment_id)) {
    die("Invalid attachment.");
}

$attachment_id = (int)$attachment_id;

if ($attachment_id <= 0) {
    die("Invalid attachment.");
}


/* ===============================================================
   GET ATTACHMENT DETAILS
   =============================================================== */

$attachment_result = pg_query_params(
    $conn,
    "
    SELECT
        attachment_id,
        user_id,
        module_type,
        reference_id,
        file_name,
        file_type,
        file_size,
        uploaded_at
    FROM attachments
    WHERE attachment_id = $1
    LIMIT 1
    ",
    [$attachment_id]
);


if (
    !$attachment_result ||
    pg_num_rows($attachment_result) === 0
) {
    die("Attachment not found.");
}


$attachment = pg_fetch_assoc($attachment_result);

$module_type = strtoupper(
    trim($attachment["module_type"] ?? "")
);

$reference_id = (int)$attachment["reference_id"];

$file_name = $attachment["file_name"] ?? "Attachment";
$file_type = strtolower(
    trim($attachment["file_type"] ?? "")
);

$file_size = (int)$attachment["file_size"];


/* ===============================================================
   VALIDATE MODULE TYPE
   =============================================================== */

$allowed_modules = [
    "GRIEVANCE",
    "SUGGESTION",
    "APPLICATION"
];

if (!in_array($module_type, $allowed_modules, true)) {
    die("Invalid attachment module.");
}


/* ===============================================================
   VERIFY REFERENCED RECORD EXISTS
   =============================================================== */

$record_exists = false;
$back_page = "dashboard.php";
$back_id_parameter = "";


/* ---------------------------------------------------------------
   GRIEVANCE
   --------------------------------------------------------------- */

if ($module_type === "GRIEVANCE") {

    $record_result = pg_query_params(
        $conn,
        "
        SELECT grievance_id
        FROM grievances
        WHERE grievance_id = $1
        LIMIT 1
        ",
        [$reference_id]
    );

    if (
        $record_result &&
        pg_num_rows($record_result) > 0
    ) {
        $record_exists = true;
        $back_page = "view_grievance.php";
        $back_id_parameter = "id=" . $reference_id;
    }
}


/* ---------------------------------------------------------------
   SUGGESTION
   --------------------------------------------------------------- */ elseif ($module_type === "SUGGESTION") {

    $record_result = pg_query_params(
        $conn,
        "
        SELECT suggestion_id
        FROM suggestions
        WHERE suggestion_id = $1
        LIMIT 1
        ",
        [$reference_id]
    );

    if (
        $record_result &&
        pg_num_rows($record_result) > 0
    ) {
        $record_exists = true;
        $back_page = "view_suggestion.php";
        $back_id_parameter = "id=" . $reference_id;
    }
}


/* ---------------------------------------------------------------
   APPLICATION
   --------------------------------------------------------------- */ elseif ($module_type === "APPLICATION") {

    $record_result = pg_query_params(
        $conn,
        "
        SELECT application_id
        FROM applications
        WHERE application_id = $1
        LIMIT 1
        ",
        [$reference_id]
    );

    if (
        $record_result &&
        pg_num_rows($record_result) > 0
    ) {
        $record_exists = true;
        $back_page = "view_application.php";
        $back_id_parameter = "id=" . $reference_id;
    }
}


if (!$record_exists) {
    die("The attachment reference could not be found.");
}


/* ===============================================================
   FORMAT FILE SIZE
   =============================================================== */

function formatAttachmentSize($bytes)
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

    return number_format(
        $bytes / 1073741824,
        1
    ) . " GB";
}


/* ===============================================================
   DETERMINE FILE TYPE
   =============================================================== */

$is_image = in_array(
    $file_type,
    [
        "image/jpeg",
        "image/png",
        "image/jpg"
    ],
    true
);

$is_pdf = ($file_type === "application/pdf");


/* ===============================================================
   ATTACHMENT URL
   =============================================================== */

$attachment_url =
    "../actions/attachment.php?attachment_id=" .
    urlencode($attachment_id);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        CampusDesk | View Attachment
    </title>


    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <!-- Existing Project CSS -->
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


    <!-- Attachment Viewer CSS -->
    <style>
        .attachment-viewer-page {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }


        .attachment-viewer-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }


        .attachment-viewer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 18px;
            border-bottom: 1px solid #e5e7eb;
        }


        .attachment-viewer-title {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }


        .attachment-file-info {
            margin-top: 6px;
            color: #6b7280;
            font-size: 14px;
        }


        .attachment-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 15px;
            border-radius: 6px;
            text-decoration: none;
            white-space: nowrap;
        }


        .attachment-content {
            width: 100%;
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            box-sizing: border-box;
        }


        .attachment-image {
            display: block;
            max-width: 100%;
            max-height: 75vh;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 6px;
        }


        .attachment-pdf {
            width: 100%;
            height: 75vh;
            min-height: 600px;
            border: none;
            border-radius: 6px;
            background: #ffffff;
        }


        .attachment-unsupported {
            text-align: center;
            padding: 50px 20px;
        }


        .attachment-unsupported i {
            font-size: 50px;
            margin-bottom: 15px;
        }


        .attachment-unsupported p {
            margin: 6px 0;
        }


        @media (max-width: 768px) {

            .attachment-viewer-card {
                padding: 15px;
            }

            .attachment-viewer-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .attachment-content {
                padding: 10px;
                min-height: 350px;
            }

            .attachment-pdf {
                height: 65vh;
                min-height: 450px;
            }

        }
    </style>

</head>


<body>


    <!-- Existing Authority Navbar -->
    <?php include "../includes/navbar.php"; ?>


    <!-- Existing Common Header -->
    <?php include "../includes/header.php"; ?>


    <div class="dashboard-layout">

        <main class="dashboard-content">

            <div class="attachment-viewer-page">

                <div class="attachment-viewer-card">


                    <!-- =================================================
                         HEADER
                         ================================================= -->

                    <div class="attachment-viewer-header">

                        <div>

                            <h2 class="attachment-viewer-title">

                                <i class="fa-solid fa-paperclip"></i>

                                View Attachment

                            </h2>


                            <div class="attachment-file-info">

                                <?= htmlspecialchars($file_name) ?>

                                &nbsp; • &nbsp;

                                <?= htmlspecialchars($file_type) ?>

                                &nbsp; • &nbsp;

                                <?= htmlspecialchars(
                                    formatAttachmentSize($file_size)
                                ) ?>

                            </div>

                        </div>


                        <!-- Back Button -->
                        <a
                            href="<?= htmlspecialchars(
                                        $back_page . "?" . $back_id_parameter
                                    ) ?>"
                            class="authority-btn btn-view attachment-back-btn">

                            <i class="fa-solid fa-arrow-left"></i>

                            Back

                        </a>

                    </div>


                    <!-- =================================================
                         ATTACHMENT CONTENT
                         ================================================= -->

                    <div class="attachment-content">


                        <?php if ($is_image): ?>

                            <!-- IMAGE -->

                            <img
                                src="<?= htmlspecialchars($attachment_url) ?>"
                                alt="<?= htmlspecialchars($file_name) ?>"
                                class="attachment-image">


                        <?php elseif ($is_pdf): ?>

                            <!-- PDF -->

                            <iframe
                                src="<?= htmlspecialchars($attachment_url) ?>"
                                class="attachment-pdf"
                                title="<?= htmlspecialchars($file_name) ?>">
                            </iframe>


                        <?php else: ?>

                            <!-- UNSUPPORTED FILE TYPE -->

                            <div class="attachment-unsupported">

                                <i class="fa-solid fa-file"></i>

                                <h3>
                                    File Preview Not Available
                                </h3>

                                <p>
                                    This file type cannot be displayed
                                    directly in the browser.
                                </p>

                                <p>
                                    <?= htmlspecialchars($file_name) ?>
                                </p>

                            </div>

                        <?php endif; ?>


                    </div>


                </div>

            </div>

        </main>

    </div>


    <!-- Existing Footer -->
    <?php include "../includes/footer.php"; ?>


</body>

</html>
