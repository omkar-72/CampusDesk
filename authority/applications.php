<?php
/*
|--------------------------------------------------------------------------
| AUTHORITY - APPLICATIONS
|--------------------------------------------------------------------------
| This page displays student applications for authority users.
| The Action column intentionally contains ONLY the View button.
| UI updated to match the CampusDesk Friend Login/Register theme.
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


    <!-- Friend Login/Register UI Theme -->
    <style>
        /* =========================================================
           AUTHORITY APPLICATIONS
           FRIEND LOGIN / REGISTER UI THEME
           ========================================================= */

        .dashboard-content {
            padding-top: 100px;
            padding-bottom: 40px;
        }


        /* ---------------------------------------------------------
           PAGE HEADER
           --------------------------------------------------------- */

        .page-header {
            position: relative;
            background: var(--ink);
            color: #fff;
            border: 2px solid var(--ink);
            border-radius: 12px;
            padding: 22px 24px;
            margin-bottom: 20px;
            box-shadow: 5px 5px 0 var(--primary);
            overflow: hidden;
        }

        .page-header::after {
            content: "";
            position: absolute;
            right: -25px;
            top: -35px;
            width: 100px;
            height: 100px;
            background: var(--primary);
            transform: rotate(45deg);
            opacity: .9;
        }

        .page-header h2 {
            position: relative;
            z-index: 2;
            margin: 0;
            font-size: 25px;
            font-weight: 700;
            letter-spacing: -.3px;
        }

        .page-header p {
            position: relative;
            z-index: 2;
            margin: 6px 0 0;
            color: #C9CED5;
            font-size: 13px;
        }


        /* ---------------------------------------------------------
           FILTER FORM
           --------------------------------------------------------- */

        .filter-form {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            background: var(--card);
            border: 2px solid var(--ink);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: 4px 4px 0 var(--border);
        }

        .filter-form input,
        .filter-form select {
            min-height: 44px;
            padding: 10px 13px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            background: #FCFCF9;
            color: var(--ink);
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            outline: none;
            transition: .2s ease;
        }

        .filter-form input {
            min-width: 160px;
        }

        .filter-form select {
            min-width: 145px;
        }

        .filter-form input:focus,
        .filter-form select:focus {
            border-color: var(--ink);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(200, 241, 53, .35);
        }


        /* ---------------------------------------------------------
           SEARCH BUTTON
           --------------------------------------------------------- */

        .filter-form button[type="submit"] {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 18px;
            background: var(--ink);
            color: var(--primary);
            border: 2px solid var(--ink);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s ease;
        }

        .filter-form button[type="submit"]:hover {
            background: var(--primary);
            color: var(--ink);
            transform: translate(-1px, -2px);
            box-shadow: 4px 4px 0 var(--ink);
        }


        /* ---------------------------------------------------------
           RESET BUTTON
           --------------------------------------------------------- */

        .reset-btn {
            min-height: 44px;
            padding: 10px 18px;
            background: #fff;
            color: var(--ink);
            border: 2px solid var(--ink);
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s ease;
        }

        .reset-btn:hover {
            background: #F1F2EC;
            transform: translate(-1px, -2px);
            box-shadow: 3px 3px 0 var(--ink);
        }


        /* ---------------------------------------------------------
           SUMMARY CARDS
           --------------------------------------------------------- */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin: 20px 0;
        }

        .summary-card {
            position: relative;
            background: var(--card);
            border: 2px solid var(--ink) !important;
            border-radius: 12px;
            padding: 18px;
            min-height: 115px;
            overflow: hidden;
            box-shadow: 4px 4px 0 var(--border);
            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .summary-card::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 6px;
            background: var(--primary);
        }

        .summary-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0 var(--ink);
        }

        .summary-card h3 {
            margin: 8px 0 0;
            font-size: 30px;
            line-height: 1;
            font-weight: 700;
            color: var(--ink);
        }

        .summary-card p {
            margin: 9px 0 0;
            color: var(--text-light);
            font-size: 13px;
            font-weight: 600;
        }


        /* Different accent strips */
        .summary-card:nth-child(1)::before {
            background: var(--primary);
        }

        .summary-card:nth-child(2)::before {
            background: #F59E0B;
        }

        .summary-card:nth-child(3)::before {
            background: #22C55E;
        }

        .summary-card:nth-child(4)::before {
            background: #EF4444;
        }


        /* ---------------------------------------------------------
           TABLE INFORMATION
           --------------------------------------------------------- */

        .table-info {
            margin: 18px 0 12px;
            padding-left: 2px;
            color: var(--text-light);
            font-size: 13px;
            font-weight: 600;
        }


        /* ---------------------------------------------------------
           TABLE CARD
           --------------------------------------------------------- */

        .table-card {
            background: var(--card);
            border: 2px solid var(--ink);
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 5px 5px 0 var(--border);
        }

        .table-card table {
            width: 100%;
            min-width: 800px;
            border-collapse: collapse;
        }

        .table-card thead {
            background: var(--ink);
            color: #fff;
        }

        .table-card thead th {
            padding: 14px 15px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .3px;
            white-space: nowrap;
            border-bottom: 2px solid var(--primary);
        }

        .table-card tbody td {
            padding: 14px 15px;
            border-bottom: 1px solid #E4E6DF;
            color: var(--text);
            font-size: 13px;
            vertical-align: middle;
        }

        .table-card tbody tr:last-child td {
            border-bottom: none;
        }

        .table-card tbody tr {
            transition: background .15s ease;
        }

        .table-card tbody tr:hover {
            background: #FAFCEF;
        }


        /* ---------------------------------------------------------
           STATUS BADGES
           --------------------------------------------------------- */

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .badge-pending {
            background: #EEF1F5;
            color: #26313B;
            border-color: #CDD3DA;
        }

        .badge-review {
            background: #FFF3D5;
            color: #7A5700;
            border-color: #E8D08A;
        }

        .badge-success {
            background: #EAF8DD;
            color: #356300;
            border-color: #BBD98A;
        }

        .badge-danger {
            background: #FDEAEA;
            color: #8B2020;
            border-color: #E7BBBB;
        }


        /* ---------------------------------------------------------
           VIEW ACTION BUTTON
           --------------------------------------------------------- */

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 8px 13px;
            background: var(--ink);
            color: var(--primary);
            border: 2px solid var(--ink);
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            transition: .2s ease;
        }

        .action-btn:hover {
            background: var(--primary);
            color: var(--ink);
            transform: translate(-1px, -2px);
            box-shadow: 3px 3px 0 var(--ink);
        }


        /* ---------------------------------------------------------
           EMPTY STATE
           --------------------------------------------------------- */

        .table-card tbody td[colspan="7"] {
            padding: 45px 20px !important;
            color: var(--text-light);
            font-size: 14px;
            font-weight: 500;
        }


        /* ---------------------------------------------------------
           RESPONSIVE
           --------------------------------------------------------- */

        @media (max-width: 1000px) {

            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .filter-form input {
                min-width: 140px;
            }

        }


        @media (max-width: 700px) {

            .dashboard-content {
                padding-top: 25px;
            }

            .page-header {
                padding: 18px;
            }

            .page-header h2 {
                font-size: 21px;
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-form input,
            .filter-form select,
            .filter-form button[type="submit"],
            .reset-btn {
                width: 100%;
            }

            .summary-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .summary-card {
                padding: 15px;
            }

        }


        @media (max-width: 450px) {

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .summary-card {
                min-height: 100px;
            }

            .table-card {
                border-radius: 10px;
            }

        }
    </style>

</head>


<body>

    <!-- Common CampusDesk Navbar -->
    <?php include "../includes/navbar.php"; ?>

    <!-- Common CampusDesk Header -->
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

                    <i class="fa-solid fa-magnifying-glass"></i>
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
                <div class="summary-card">

                    <h3>
                        <?= $newCount ?>
                    </h3>

                    <p>
                        New
                    </p>

                </div>


                <!-- Processing -->
                <div class="summary-card">

                    <h3>
                        <?= $processingCount ?>
                    </h3>

                    <p>
                        Processing
                    </p>

                </div>


                <!-- Approved -->
                <div class="summary-card">

                    <h3>
                        <?= $approvedCount ?>
                    </h3>

                    <p>
                        Approved
                    </p>

                </div>


                <!-- Rejected -->
                <div class="summary-card">

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

                Showing <?= $totalRows > 0 ? 1 : 0 ?>–<?= $totalRows ?>
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


    <!-- Common CampusDesk Footer -->
    <?php include "../includes/footer.php"; ?>


</body>

</html>
