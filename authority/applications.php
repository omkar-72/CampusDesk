<?php
/*
|--------------------------------------------------------------------------
| AUTHORITY - APPLICATIONS
|--------------------------------------------------------------------------
| This page displays student applications for authority users.
| The Action column intentionally contains ONLY the View button.
|--------------------------------------------------------------------------
*/

session_start();

/* ---------------------------------------------------------------
   AUTHORITY ACCESS CHECK
   --------------------------------------------------------------- */
if (!isset($_SESSION["role_name"]) || $_SESSION["role_name"] !== "AUTHORITY") {
    header("Location: ../login.php");
    exit();
}

require_once "../config/database.php";


/* ---------------------------------------------------------------
   FILTERS
   --------------------------------------------------------------- */

$searchId = $_GET["application_id"] ?? "";
$search = $_GET["search"] ?? "";
$type = $_GET["type"] ?? "";
$statusFilter = $_GET["status"] ?? "";
$dateFilter = $_GET["date"] ?? "";


/* ---------------------------------------------------------------
   SUMMARY CARDS
   --------------------------------------------------------------- */

$newCount = pg_fetch_result(pg_query($conn, "
    SELECT COUNT(*)
    FROM applications a
    JOIN statuses s ON a.status_id = s.status_id
    WHERE s.status_name = 'New'
"), 0, 0);


$processingCount = pg_fetch_result(pg_query($conn, "
    SELECT COUNT(*)
    FROM applications a
    JOIN statuses s ON a.status_id = s.status_id
    WHERE s.status_name IN ('Processing', 'Under Review')
"), 0, 0);


$approvedCount = pg_fetch_result(pg_query($conn, "
    SELECT COUNT(*)
    FROM applications a
    JOIN statuses s ON a.status_id = s.status_id
    WHERE s.status_name = 'Approved'
"), 0, 0);


$rejectedCount = pg_fetch_result(pg_query($conn, "
    SELECT COUNT(*)
    FROM applications a
    JOIN statuses s ON a.status_id = s.status_id
    WHERE s.status_name = 'Rejected'
"), 0, 0);


/* ---------------------------------------------------------------
   APPLICATION TYPES
   --------------------------------------------------------------- */

$types = pg_query($conn, "
    SELECT
        application_type_id,
        type_name
    FROM application_types
    ORDER BY type_name
");


/* ---------------------------------------------------------------
   MAIN APPLICATION QUERY
   --------------------------------------------------------------- */

$query = "
    SELECT
        a.application_id,
        a.student_id,
        st.full_name,
        a.subject,
        a.submission_date,
        at.type_name,
        s.status_name

    FROM applications a

    LEFT JOIN students st
        ON a.student_id = st.student_id

    LEFT JOIN application_types at
        ON a.application_type_id = at.application_type_id

    LEFT JOIN statuses s
        ON a.status_id = s.status_id

    WHERE 1=1
";


/* ---------------------------------------------------------------
   QUERY PARAMETERS
   --------------------------------------------------------------- */

$params = [];
$count = 1;


/* Application ID filter */
if ($searchId != "") {

    $query .= " AND a.application_id=$" . $count++;
    $params[] = $searchId;
}


/* Subject search */
if ($search != "") {

    $query .= " AND a.subject ILIKE $" . $count++;
    $params[] = "%" . $search . "%";
}


/* Application type filter */
if ($type != "") {

    $query .= " AND a.application_type_id=$" . $count++;
    $params[] = $type;
}


/* Status filter */
if ($statusFilter != "") {

    $query .= " AND s.status_name=$" . $count++;
    $params[] = $statusFilter;
}


/* Today's applications */
if ($dateFilter == "today") {

    $query .= " AND DATE(a.submission_date)=CURRENT_DATE";
}


/* Newest applications first */
$query .= " ORDER BY a.submission_date DESC";


/* ---------------------------------------------------------------
   EXECUTE QUERY
   --------------------------------------------------------------- */

$result = pg_query_params($conn, $query, $params);

$totalRows = $result ? pg_num_rows($result) : 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>CampusDesk | Authority Applications</title>


    <!-- Font Awesome icons -->
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

    <link
        rel="stylesheet"
        href="../css/authority-table.css">

</head>


<body>

    <?php include "../includes/navbar.php"; ?>

    <?php include "../includes/header.php"; ?>


    <div class="dashboard-layout">

        <main class="dashboard-content">


            <!-- =================================================
                 PAGE HEADER
                 ================================================= -->

            <div class="page-header">

                <div>

                    <h2>
                        Application Management
                    </h2>

                    <p>
                        Review and approve student applications.
                    </p>

                </div>

            </div>


            <!-- =================================================
                 FILTERS
                 ================================================= -->

            <form
                method="GET"
                class="filter-form">

                <!-- Application ID -->
                <input
                    type="number"
                    name="application_id"
                    placeholder="Application ID"
                    value="<?= htmlspecialchars($searchId) ?>">


                <!-- Subject search -->
                <input
                    type="text"
                    name="search"
                    placeholder="Search subject..."
                    value="<?= htmlspecialchars($search) ?>">


                <!-- Application type -->
                <select name="type">

                    <option value="">
                        Application Type
                    </option>

                    <?php while ($t = pg_fetch_assoc($types)): ?>

                        <option
                            value="<?= $t["application_type_id"] ?>"
                            <?= $type == $t["application_type_id"] ? "selected" : "" ?>>

                            <?= htmlspecialchars($t["type_name"]) ?>

                        </option>

                    <?php endwhile; ?>

                </select>


                <!-- Status -->
                <select name="status">

                    <option value="">
                        All Status
                    </option>

                    <option value="New">
                        New
                    </option>

                    <option value="Processing">
                        Processing
                    </option>

                    <option value="Under Review">
                        Under Review
                    </option>

                    <option value="Approved">
                        Approved
                    </option>

                    <option value="Rejected">
                        Rejected
                    </option>

                </select>


                <!-- Date -->
                <select name="date">

                    <option value="">
                        Date
                    </option>

                    <option
                        value="today"
                        <?= $dateFilter == "today" ? "selected" : "" ?>>
                        Today
                    </option>

                </select>


                <!-- Search -->
                <button type="submit">
                    Search
                </button>


                <!-- Reset -->
                <a href="applications.php">

                    <button
                        type="button"
                        class="reset-btn">
                        Reset
                    </button>

                </a>

            </form>


            <!-- =================================================
                 SUMMARY CARDS
                 ================================================= -->

            <div class="summary-grid">


                <!-- New -->
                <div
                    class="summary-card"
                    style="border-color:#2563EB;">

                    <h3>
                        <?= $newCount ?>
                    </h3>

                    <p>
                        New
                    </p>

                </div>


                <!-- Processing -->
                <div
                    class="summary-card"
                    style="border-color:#F59E0B;">

                    <h3>
                        <?= $processingCount ?>
                    </h3>

                    <p>
                        Processing
                    </p>

                </div>


                <!-- Approved -->
                <div
                    class="summary-card"
                    style="border-color:#16A34A;">

                    <h3>
                        <?= $approvedCount ?>
                    </h3>

                    <p>
                        Approved
                    </p>

                </div>


                <!-- Rejected -->
                <div
                    class="summary-card"
                    style="border-color:#DC2626;">

                    <h3>
                        <?= $rejectedCount ?>
                    </h3>

                    <p>
                        Rejected
                    </p>

                </div>

            </div>


            <!-- =================================================
                 TABLE INFORMATION
                 ================================================= -->

            <div class="table-info">

                Showing 1–<?= $totalRows ?>
                of <?= $totalRows ?>

            </div>


            <!-- =================================================
                 APPLICATION TABLE
                 ================================================= -->

            <div class="table-card">

                <table>

                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Student
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Subject
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php if ($result && pg_num_rows($result) > 0): ?>


                            <?php while ($row = pg_fetch_assoc($result)): ?>


                                <tr>


                                    <!-- Application ID -->
                                    <td>

                                        AP<?= str_pad(
                                                $row["application_id"],
                                                3,
                                                "0",
                                                STR_PAD_LEFT
                                            ) ?>

                                    </td>


                                    <!-- Student -->
                                    <td>

                                        <?= htmlspecialchars(
                                            $row["full_name"] ?? "Unknown Student"
                                        ) ?>

                                    </td>


                                    <!-- Application Type -->
                                    <td>

                                        <?= htmlspecialchars(
                                            $row["type_name"]
                                        ) ?>

                                    </td>


                                    <!-- Subject -->
                                    <td>

                                        <?= htmlspecialchars(
                                            $row["subject"]
                                        ) ?>

                                    </td>


                                    <!-- Submission Date -->
                                    <td>

                                        <?= date(
                                            "d M Y",
                                            strtotime($row["submission_date"])
                                        ) ?>

                                    </td>


                                    <!-- Status -->
                                    <td>

                                        <?php

                                        /*
                                        ------------------------------------------------
                                        Determine the CSS class for the status badge.
                                        ------------------------------------------------
                                        */

                                        $status = $row["status_name"];

                                        $class = "badge-pending";


                                        if ($status == "Approved") {

                                            $class = "badge-success";
                                        } elseif ($status == "Rejected") {

                                            $class = "badge-danger";
                                        } elseif (
                                            $status == "Processing" ||
                                            $status == "Under Review"
                                        ) {

                                            $class = "badge-review";
                                        }

                                        ?>


                                        <span class="badge <?= $class ?>">

                                            <?= htmlspecialchars($status) ?>

                                        </span>

                                    </td>


                                    <!-- =================================================
                                         ACTION
                                         -------------------------------------------------
                                         Only View is available here.
                                         Approve / Reject are NOT shown in the table.
                                         ================================================= -->

                                    <td>

                                        <a
                                            href="view_application.php?id=<?= $row["application_id"] ?>"
                                            class="action-btn">

                                            <i class="fa-solid fa-eye"></i>

                                            View

                                        </a>

                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        <?php else: ?>


                            <!-- No applications -->
                            <tr>

                                <td
                                    colspan="7"
                                    style="text-align:center;padding:30px;">

                                    No applications found.

                                </td>

                            </tr>


                        <?php endif; ?>


                    </tbody>

                </table>

            </div>


        </main>

    </div>


    <?php include "../includes/footer.php"; ?>


</body>

</html>
