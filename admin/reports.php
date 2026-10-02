<?php

session_start();

require_once "admin_auth.php";
requireAdmin();

require_once "../config/database.php";


/* =========================================================
   HELPER FUNCTIONS
========================================================= */

function reportStatusClass($status)
{
    $status = strtolower(trim($status));

    switch ($status) {

        case "resolved":
        case "approved":
        case "implemented":
        case "accepted":
            return "status-success";

        case "rejected":
            return "status-danger";

        case "in progress":
        case "processing":
        case "under review":
            return "status-warning";

        case "new":
        default:
            return "status-info";
    }
}


function reportNumber($value)
{
    return number_format((int)$value);
}


/* =========================================================
   MODULE
========================================================= */

$module = isset($_GET["module"])
    ? strtoupper(trim($_GET["module"]))
    : "GRIEVANCE";

$allowedModules = [
    "GRIEVANCE",
    "SUGGESTION",
    "APPLICATION"
];

if (!in_array($module, $allowedModules, true)) {
    $module = "GRIEVANCE";
}


/* =========================================================
   DATE FILTER
========================================================= */

$dateFrom = isset($_GET["date_from"])
    ? trim($_GET["date_from"])
    : "";

$dateTo = isset($_GET["date_to"])
    ? trim($_GET["date_to"])
    : "";


/* =========================================================
   COMMON FILTER CONDITIONS
========================================================= */

$dateConditions = [];
$dateParams = [];

if ($dateFrom !== "") {

    $dateConditions[] = "submission_date >= $" .
        (count($dateParams) + 1) .
        "::date";

    $dateParams[] = $dateFrom;
}

if ($dateTo !== "") {

    $dateConditions[] = "submission_date < $" .
        (count($dateParams) + 1) .
        "::date + INTERVAL '1 day'";

    $dateParams[] = $dateTo;
}


$whereClause = "";

if (!empty($dateConditions)) {

    $whereClause =
        " WHERE " .
        implode(" AND ", $dateConditions);
}


/* =========================================================
   DEFAULT VALUES
========================================================= */

$totalRecords = 0;
$newRecords = 0;
$middleRecords = 0;
$successRecords = 0;
$rejectedRecords = 0;

$statusData = [];
$categoryData = [];
$monthlyData = [];

$recentRecords = [];


/* =========================================================
   MODULE-SPECIFIC CONFIGURATION
========================================================= */

if ($module === "GRIEVANCE") {

    $moduleTitle = "Grievances";
    $moduleDescription =
        "Analyze grievance submissions, statuses, categories and monthly activity.";

    $middleLabel = "In Progress";
    $successLabel = "Resolved";
} elseif ($module === "SUGGESTION") {

    $moduleTitle = "Suggestions";
    $moduleDescription =
        "Analyze suggestions, review status, categories and monthly activity.";

    $middleLabel = "Under Review";
    $successLabel = "Implemented";
} else {

    $moduleTitle = "Applications";
    $moduleDescription =
        "Analyze applications, processing status, application types and monthly activity.";

    $middleLabel = "Processing";
    $successLabel = "Approved";
}


/* =========================================================
   TOTAL / STATUS SUMMARY
========================================================= */

if ($module === "GRIEVANCE") {

    $summaryQuery = "
        SELECT
            COUNT(*) AS total,
            COUNT(*) FILTER (
                WHERE s.status_name = 'New'
            ) AS new_count,
            COUNT(*) FILTER (
                WHERE s.status_name IN ('In Progress', 'Under Review')
            ) AS middle_count,
            COUNT(*) FILTER (
                WHERE s.status_name = 'Resolved'
            ) AS success_count,
            COUNT(*) FILTER (
                WHERE s.status_name = 'Rejected'
            ) AS rejected_count
        FROM grievances g
        LEFT JOIN statuses s
            ON g.status_id = s.status_id
        {$whereClause}
    ";
} elseif ($module === "SUGGESTION") {

    $summaryQuery = "
        SELECT
            COUNT(*) AS total,
            COUNT(*) FILTER (
                WHERE s.status_name = 'New'
            ) AS new_count,
            COUNT(*) FILTER (
                WHERE s.status_name = 'Under Review'
            ) AS middle_count,
            COUNT(*) FILTER (
                WHERE s.status_name IN ('Accepted', 'Implemented')
            ) AS success_count,
            COUNT(*) FILTER (
                WHERE s.status_name = 'Rejected'
            ) AS rejected_count
        FROM suggestions sgt
        LEFT JOIN statuses s
            ON sgt.status_id = s.status_id
        {$whereClause}
    ";
} else {

    $summaryQuery = "
        SELECT
            COUNT(*) AS total,
            COUNT(*) FILTER (
                WHERE s.status_name = 'New'
            ) AS new_count,
            COUNT(*) FILTER (
                WHERE s.status_name IN ('Processing', 'Under Review')
            ) AS middle_count,
            COUNT(*) FILTER (
                WHERE s.status_name = 'Approved'
            ) AS success_count,
            COUNT(*) FILTER (
                WHERE s.status_name = 'Rejected'
            ) AS rejected_count
        FROM applications a
        LEFT JOIN statuses s
            ON a.status_id = s.status_id
        {$whereClause}
    ";
}


if (!empty($dateParams)) {

    $summaryResult = pg_query_params(
        $conn,
        $summaryQuery,
        $dateParams
    );
} else {

    $summaryResult = pg_query(
        $conn,
        $summaryQuery
    );
}


if ($summaryResult) {

    $summary = pg_fetch_assoc($summaryResult);

    if ($summary) {

        $totalRecords =
            (int)$summary["total"];

        $newRecords =
            (int)$summary["new_count"];

        $middleRecords =
            (int)$summary["middle_count"];

        $successRecords =
            (int)$summary["success_count"];

        $rejectedRecords =
            (int)$summary["rejected_count"];
    }
}


/* =========================================================
   STATUS DISTRIBUTION
========================================================= */

/*
 * IMPORTANT:
 * The statuses table contains statuses for all modules.
 * Therefore, each report must restrict statuses.module_type
 * to the currently selected module.
 *
 * The existing date filters are then appended using AND.
 */

$statusWhereClause = "";

if ($module === "GRIEVANCE") {

    $statusWhereClause =
        " WHERE s.module_type = 'GRIEVANCE'";

    if (!empty($dateConditions)) {

        $statusWhereClause .=
            " AND " .
            implode(" AND ", $dateConditions);
    }

    $statusQuery = "
        SELECT
            s.status_name,
            COUNT(g.grievance_id) AS total
        FROM statuses s
        LEFT JOIN grievances g
            ON g.status_id = s.status_id
        {$statusWhereClause}
        GROUP BY
            s.status_id,
            s.status_name
        ORDER BY
            s.status_id
    ";
} elseif ($module === "SUGGESTION") {

    $statusWhereClause =
        " WHERE s.module_type = 'SUGGESTION'";

    if (!empty($dateConditions)) {

        $statusWhereClause .=
            " AND " .
            implode(" AND ", $dateConditions);
    }

    $statusQuery = "
        SELECT
            s.status_name,
            COUNT(sgt.suggestion_id) AS total
        FROM statuses s
        LEFT JOIN suggestions sgt
            ON sgt.status_id = s.status_id
        {$statusWhereClause}
        GROUP BY
            s.status_id,
            s.status_name
        ORDER BY
            s.status_id
    ";
} else {

    $statusWhereClause =
        " WHERE s.module_type = 'APPLICATION'";

    if (!empty($dateConditions)) {

        $statusWhereClause .=
            " AND " .
            implode(" AND ", $dateConditions);
    }

    $statusQuery = "
        SELECT
            s.status_name,
            COUNT(a.application_id) AS total
        FROM statuses s
        LEFT JOIN applications a
            ON a.status_id = s.status_id
        {$statusWhereClause}
        GROUP BY
            s.status_id,
            s.status_name
        ORDER BY
            s.status_id
    ";
}


if (!empty($dateParams)) {

    $statusResult = pg_query_params(
        $conn,
        $statusQuery,
        $dateParams
    );
} else {

    $statusResult = pg_query(
        $conn,
        $statusQuery
    );
}


if ($statusResult) {

    while ($row = pg_fetch_assoc($statusResult)) {

        $statusData[] = [
            "label" => $row["status_name"],
            "value" => (int)$row["total"]
        ];
    }
}


/* =========================================================
   CATEGORY / TYPE DISTRIBUTION
========================================================= */

if ($module === "GRIEVANCE") {

    $categoryQuery = "
        SELECT
            gc.category_name AS label,
            COUNT(g.grievance_id) AS total
        FROM grievance_categories gc
        LEFT JOIN grievances g
            ON g.category_id = gc.category_id
        {$whereClause}
        GROUP BY
            gc.category_id,
            gc.category_name
        ORDER BY
            total DESC,
            gc.category_name
    ";

    $categoryChartTitle =
        "Grievances by Category";
} elseif ($module === "SUGGESTION") {

    $categoryQuery = "
        SELECT
            sc.category_name AS label,
            COUNT(sgt.suggestion_id) AS total
        FROM suggestion_categories sc
        LEFT JOIN suggestions sgt
            ON sgt.category_id = sc.category_id
        {$whereClause}
        GROUP BY
            sc.category_id,
            sc.category_name
        ORDER BY
            total DESC,
            sc.category_name
    ";

    $categoryChartTitle =
        "Suggestions by Category";
} else {

    $categoryQuery = "
        SELECT
            at.type_name AS label,
            COUNT(a.application_id) AS total
        FROM application_types at
        LEFT JOIN applications a
            ON a.application_type_id = at.application_type_id
        {$whereClause}
        GROUP BY
            at.application_type_id,
            at.type_name
        ORDER BY
            total DESC,
            at.type_name
    ";

    $categoryChartTitle =
        "Applications by Type";
}


if (!empty($dateParams)) {

    $categoryResult = pg_query_params(
        $conn,
        $categoryQuery,
        $dateParams
    );
} else {

    $categoryResult = pg_query(
        $conn,
        $categoryQuery
    );
}


if ($categoryResult) {

    while ($row = pg_fetch_assoc($categoryResult)) {

        $categoryData[] = [
            "label" => $row["label"],
            "value" => (int)$row["total"]
        ];
    }
}


/* =========================================================
   MONTHLY TREND
========================================================= */

if ($module === "GRIEVANCE") {

    $monthlyQuery = "
        SELECT
            TO_CHAR(
                DATE_TRUNC('month', g.submission_date),
                'Mon YYYY'
            ) AS month_label,
            DATE_TRUNC(
                'month',
                g.submission_date
            ) AS month_date,
            COUNT(*) AS total
        FROM grievances g
        {$whereClause}
        GROUP BY
            month_date
        ORDER BY
            month_date
    ";
} elseif ($module === "SUGGESTION") {

    $monthlyQuery = "
        SELECT
            TO_CHAR(
                DATE_TRUNC('month', sgt.submission_date),
                'Mon YYYY'
            ) AS month_label,
            DATE_TRUNC(
                'month',
                sgt.submission_date
            ) AS month_date,
            COUNT(*) AS total
        FROM suggestions sgt
        {$whereClause}
        GROUP BY
            month_date
        ORDER BY
            month_date
    ";
} else {

    $monthlyQuery = "
        SELECT
            TO_CHAR(
                DATE_TRUNC('month', a.submission_date),
                'Mon YYYY'
            ) AS month_label,
            DATE_TRUNC(
                'month',
                a.submission_date
            ) AS month_date,
            COUNT(*) AS total
        FROM applications a
        {$whereClause}
        GROUP BY
            month_date
        ORDER BY
            month_date
    ";
}


if (!empty($dateParams)) {

    $monthlyResult = pg_query_params(
        $conn,
        $monthlyQuery,
        $dateParams
    );
} else {

    $monthlyResult = pg_query(
        $conn,
        $monthlyQuery
    );
}


if ($monthlyResult) {

    while ($row = pg_fetch_assoc($monthlyResult)) {

        $monthlyData[] = [
            "label" => $row["month_label"],
            "value" => (int)$row["total"]
        ];
    }
}


/* =========================================================
   RECENT RECORDS
========================================================= */

if ($module === "GRIEVANCE") {

    $recentQuery = "
        SELECT
            g.grievance_id AS id,
            g.title,
            g.submission_date,
            s.status_name,
            gc.category_name AS category
        FROM grievances g
        LEFT JOIN statuses s
            ON g.status_id = s.status_id
        LEFT JOIN grievance_categories gc
            ON g.category_id = gc.category_id
        {$whereClause}
        ORDER BY
            g.submission_date DESC
        LIMIT 10
    ";
} elseif ($module === "SUGGESTION") {

    $recentQuery = "
        SELECT
            sgt.suggestion_id AS id,
            sgt.title,
            sgt.submission_date,
            s.status_name,
            sc.category_name AS category
        FROM suggestions sgt
        LEFT JOIN statuses s
            ON sgt.status_id = s.status_id
        LEFT JOIN suggestion_categories sc
            ON sgt.category_id = sc.category_id
        {$whereClause}
        ORDER BY
            sgt.submission_date DESC
        LIMIT 10
    ";
} else {

    $recentQuery = "
        SELECT
            a.application_id AS id,
            a.subject AS title,
            a.submission_date,
            s.status_name,
            at.type_name AS category
        FROM applications a
        LEFT JOIN statuses s
            ON a.status_id = s.status_id
        LEFT JOIN application_types at
            ON a.application_type_id = at.application_type_id
        {$whereClause}
        ORDER BY
            a.submission_date DESC
        LIMIT 10
    ";
}


if (!empty($dateParams)) {

    $recentResult = pg_query_params(
        $conn,
        $recentQuery,
        $dateParams
    );
} else {

    $recentResult = pg_query(
        $conn,
        $recentQuery
    );
}


if ($recentResult) {

    while ($row = pg_fetch_assoc($recentResult)) {

        $recentRecords[] = $row;
    }
}


/* =========================================================
   JSON DATA FOR CHART.JS
========================================================= */

$statusChartLabels = [];
$statusChartValues = [];

foreach ($statusData as $row) {

    $statusChartLabels[] =
        $row["label"];

    $statusChartValues[] =
        $row["value"];
}


$categoryChartLabels = [];
$categoryChartValues = [];

foreach ($categoryData as $row) {

    $categoryChartLabels[] =
        $row["label"];

    $categoryChartValues[] =
        $row["value"];
}


$monthlyChartLabels = [];
$monthlyChartValues = [];

foreach ($monthlyData as $row) {

    $monthlyChartLabels[] =
        $row["label"];

    $monthlyChartValues[] =
        $row["value"];
}


/* =========================================================
   PAGE TITLE
========================================================= */

$pageTitle = "Reports & Analysis";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($pageTitle) ?>
        - CampusDesk Administration
    </title>


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <!-- Admin CSS -->

    <link
        rel="stylesheet"
        href="css/admin-common.css">

    <link
        rel="stylesheet"
        href="css/admin-navbar.css">

    <link
        rel="stylesheet"
        href="css/admin-reports.css">


    <!-- Chart.js -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>


    <?php include "includes/navbar.php"; ?>


    <main class="admin-main">

        <div class="admin-container">


            <!-- =================================================
             PAGE HEADER
        ================================================== -->

            <div class="page-header">

                <div class="page-header-content">

                    <h1>

                        <i class="fa-solid fa-chart-column"></i>

                        Reports & Analysis

                    </h1>

                    <p>

                        View service statistics, trends and
                        detailed analysis of CampusDesk records.

                    </p>

                </div>

            </div>


            <!-- =================================================
             MODULE TABS
        ================================================== -->

            <section class="report-tabs">

                <a
                    href="reports.php?module=GRIEVANCE<?= $dateFrom !== "" ? "&date_from=" . urlencode($dateFrom) : "" ?><?= $dateTo !== "" ? "&date_to=" . urlencode($dateTo) : "" ?>"
                    class="report-tab <?= $module === "GRIEVANCE" ? "active" : "" ?>">

                    <span class="report-tab-icon">

                        <i class="fa-solid fa-triangle-exclamation"></i>

                    </span>

                    <span class="report-tab-content">

                        <strong>
                            Grievances
                        </strong>

                        <small>
                            Complaints and issues
                        </small>

                    </span>

                </a>


                <a
                    href="reports.php?module=SUGGESTION<?= $dateFrom !== "" ? "&date_from=" . urlencode($dateFrom) : "" ?><?= $dateTo !== "" ? "&date_to=" . urlencode($dateTo) : "" ?>"
                    class="report-tab <?= $module === "SUGGESTION" ? "active" : "" ?>">

                    <span class="report-tab-icon">

                        <i class="fa-solid fa-lightbulb"></i>

                    </span>

                    <span class="report-tab-content">

                        <strong>
                            Suggestions
                        </strong>

                        <small>
                            Student suggestions
                        </small>

                    </span>

                </a>


                <a
                    href="reports.php?module=APPLICATION<?= $dateFrom !== "" ? "&date_from=" . urlencode($dateFrom) : "" ?><?= $dateTo !== "" ? "&date_to=" . urlencode($dateTo) : "" ?>"
                    class="report-tab <?= $module === "APPLICATION" ? "active" : "" ?>">

                    <span class="report-tab-icon">

                        <i class="fa-solid fa-file-lines"></i>

                    </span>

                    <span class="report-tab-content">

                        <strong>
                            Applications
                        </strong>

                        <small>
                            Student applications
                        </small>

                    </span>

                </a>

            </section>


            <!-- =================================================
             FILTERS
        ================================================== -->

            <section class="report-filter-card">

                <div class="report-filter-header">

                    <div>

                        <h2>

                            <i class="fa-solid fa-filter"></i>

                            Report Filters

                        </h2>

                        <p>
                            Select a date range to filter the report.
                        </p>

                    </div>

                </div>


                <form
                    method="GET"
                    action="reports.php"
                    class="report-filter-form">

                    <input
                        type="hidden"
                        name="module"
                        value="<?= htmlspecialchars($module) ?>">


                    <div class="form-group">

                        <label for="dateFrom">
                            From Date
                        </label>

                        <input
                            type="date"
                            id="dateFrom"
                            name="date_from"
                            class="form-control"
                            value="<?= htmlspecialchars($dateFrom) ?>">

                    </div>


                    <div class="form-group">

                        <label for="dateTo">
                            To Date
                        </label>

                        <input
                            type="date"
                            id="dateTo"
                            name="date_to"
                            class="form-control"
                            value="<?= htmlspecialchars($dateTo) ?>">

                    </div>


                    <div class="report-filter-actions">

                        <button
                            type="submit"
                            class="btn btn-primary">

                            <i class="fa-solid fa-filter"></i>

                            Apply Filter

                        </button>


                        <a
                            href="reports.php?module=<?= urlencode($module) ?>"
                            class="btn btn-secondary">

                            <i class="fa-solid fa-rotate-left"></i>

                            Reset

                        </a>

                    </div>

                </form>

            </section>


            <!-- =================================================
             MODULE INTRODUCTION
        ================================================== -->

            <div class="report-section-heading">

                <div>

                    <h2>
                        <?= htmlspecialchars($moduleTitle) ?>
                    </h2>

                    <p>
                        <?= htmlspecialchars($moduleDescription) ?>
                    </p>

                </div>

            </div>


            <!-- =================================================
             SUMMARY CARDS
        ================================================== -->

            <section class="report-stats-grid">


                <!-- Total -->

                <div class="report-stat-card">

                    <div class="report-stat-icon">

                        <i class="fa-solid fa-layer-group"></i>

                    </div>

                    <div class="report-stat-content">

                        <span>
                            Total
                        </span>

                        <strong>
                            <?= reportNumber($totalRecords) ?>
                        </strong>

                        <small>
                            Total submissions
                        </small>

                    </div>

                </div>


                <!-- New -->

                <div class="report-stat-card">

                    <div class="report-stat-icon">

                        <i class="fa-solid fa-circle-plus"></i>

                    </div>

                    <div class="report-stat-content">

                        <span>
                            New
                        </span>

                        <strong>
                            <?= reportNumber($newRecords) ?>
                        </strong>

                        <small>
                            New submissions
                        </small>

                    </div>

                </div>


                <!-- Middle -->

                <div class="report-stat-card">

                    <div class="report-stat-icon">

                        <i class="fa-solid fa-spinner"></i>

                    </div>

                    <div class="report-stat-content">

                        <span>
                            <?= htmlspecialchars($middleLabel) ?>
                        </span>

                        <strong>
                            <?= reportNumber($middleRecords) ?>
                        </strong>

                        <small>
                            Currently being processed
                        </small>

                    </div>

                </div>


                <!-- Success -->

                <div class="report-stat-card">

                    <div class="report-stat-icon">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                    <div class="report-stat-content">

                        <span>
                            <?= htmlspecialchars($successLabel) ?>
                        </span>

                        <strong>
                            <?= reportNumber($successRecords) ?>
                        </strong>

                        <small>
                            Successfully completed
                        </small>

                    </div>

                </div>


                <!-- Rejected -->

                <div class="report-stat-card">

                    <div class="report-stat-icon">

                        <i class="fa-solid fa-circle-xmark"></i>

                    </div>

                    <div class="report-stat-content">

                        <span>
                            Rejected
                        </span>

                        <strong>
                            <?= reportNumber($rejectedRecords) ?>
                        </strong>

                        <small>
                            Rejected submissions
                        </small>

                    </div>

                </div>

            </section>


            <!-- =================================================
             CHART ROW 1
        ================================================== -->

            <section class="report-chart-grid">


                <!-- Status Chart -->

                <div class="report-chart-card">

                    <div class="report-chart-header">

                        <div>

                            <h3>
                                Status Distribution
                            </h3>

                            <p>
                                Distribution of records by current status.
                            </p>

                        </div>

                    </div>


                    <div class="report-chart-container report-doughnut-container">

                        <?php if (!empty($statusData)): ?>

                            <canvas id="statusChart"></canvas>

                        <?php else: ?>

                            <div class="chart-empty-state">

                                <i class="fa-solid fa-chart-pie"></i>

                                <p>
                                    No status data available.
                                </p>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- Category Chart -->

                <div class="report-chart-card">

                    <div class="report-chart-header">

                        <div>

                            <h3>
                                <?= htmlspecialchars($categoryChartTitle) ?>
                            </h3>

                            <p>
                                Distribution across categories or types.
                            </p>

                        </div>

                    </div>


                    <div class="report-chart-container">

                        <?php if (!empty($categoryData)): ?>

                            <canvas id="categoryChart"></canvas>

                        <?php else: ?>

                            <div class="chart-empty-state">

                                <i class="fa-solid fa-chart-bar"></i>

                                <p>
                                    No category data available.
                                </p>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </section>


            <!-- =================================================
             MONTHLY TREND
        ================================================== -->

            <section class="report-chart-card report-full-chart">

                <div class="report-chart-header">

                    <div>

                        <h3>
                            Monthly Submission Trend
                        </h3>

                        <p>
                            Number of <?= strtolower($moduleTitle) ?>
                            submitted each month.
                        </p>

                    </div>

                </div>


                <div class="report-chart-container report-line-container">

                    <?php if (!empty($monthlyData)): ?>

                        <canvas id="monthlyChart"></canvas>

                    <?php else: ?>

                        <div class="chart-empty-state">

                            <i class="fa-solid fa-chart-line"></i>

                            <p>
                                No monthly trend data available.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </section>


            <!-- =================================================
             RECENT RECORDS
        ================================================== -->

            <section class="report-table-section">

                <div class="report-table-header">

                    <div>

                        <h2>
                            Recent <?= htmlspecialchars($moduleTitle) ?>
                        </h2>

                        <p>
                            Latest 10 records for the selected module.
                        </p>

                    </div>

                </div>


                <div class="table-card">

                    <div class="table-responsive">

                        <table class="admin-table report-table">

                            <thead>

                                <tr>

                                    <th>
                                        ID
                                    </th>

                                    <th>
                                        Title / Subject
                                    </th>

                                    <th>
                                        Category / Type
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Submitted
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php if (empty($recentRecords)): ?>

                                    <tr>

                                        <td
                                            colspan="5"
                                            class="empty-table-cell">

                                            <div class="empty-state">

                                                <div class="empty-state-icon">

                                                    <i class="fa-solid fa-chart-column"></i>

                                                </div>

                                                <h3>
                                                    No Records Found
                                                </h3>

                                                <p>
                                                    No records are available for
                                                    the selected filters.
                                                </p>

                                            </div>

                                        </td>

                                    </tr>

                                <?php else: ?>

                                    <?php foreach ($recentRecords as $record): ?>

                                        <?php

                                        if ($module === "GRIEVANCE") {

                                            $formattedId =
                                                "GRV-" .
                                                str_pad(
                                                    (int)$record["id"],
                                                    5,
                                                    "0",
                                                    STR_PAD_LEFT
                                                );
                                        } elseif ($module === "SUGGESTION") {

                                            $formattedId =
                                                "SGT-" .
                                                str_pad(
                                                    (int)$record["id"],
                                                    5,
                                                    "0",
                                                    STR_PAD_LEFT
                                                );
                                        } else {

                                            $formattedId =
                                                "APP-" .
                                                str_pad(
                                                    (int)$record["id"],
                                                    5,
                                                    "0",
                                                    STR_PAD_LEFT
                                                );
                                        }

                                        ?>

                                        <tr>

                                            <td>

                                                <span class="report-record-id">

                                                    <?= htmlspecialchars($formattedId) ?>

                                                </span>

                                            </td>


                                            <td>

                                                <div class="report-record-title">

                                                    <?= htmlspecialchars(
                                                        $record["title"]
                                                    ) ?>

                                                </div>

                                            </td>


                                            <td>

                                                <span class="report-category">

                                                    <?= htmlspecialchars(
                                                        $record["category"] ?? "—"
                                                    ) ?>

                                                </span>

                                            </td>


                                            <td>

                                                <span
                                                    class="status-badge <?= htmlspecialchars(
                                                                            reportStatusClass(
                                                                                $record["status_name"] ?? ""
                                                                            )
                                                                        ) ?>">

                                                    <?= htmlspecialchars(
                                                        $record["status_name"] ?? "Unknown"
                                                    ) ?>

                                                </span>

                                            </td>


                                            <td>

                                                <?= !empty($record["submission_date"])
                                                    ? date(
                                                        "d M Y, h:i A",
                                                        strtotime(
                                                            $record["submission_date"]
                                                        )
                                                    )
                                                    : "—" ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </section>


            <!-- =================================================
             REPORT FOOTNOTE
        ================================================== -->

            <div class="report-footnote">

                <i class="fa-solid fa-circle-info"></i>

                <span>

                    This report is generated from the current
                    CampusDesk database records. Charts and
                    statistics update according to the selected
                    module and date filters.

                </span>

            </div>

        </div>

    </main>


    <!-- =========================================================
     CHART.JS
========================================================= -->

    <script>
        document.addEventListener(
            "DOMContentLoaded",
            function() {


                /* =====================================================
                   COMMON CHART OPTIONS
                ===================================================== */

                const commonLegend = {
                    position: "bottom",
                    labels: {
                        usePointStyle: true,
                        padding: 18
                    }
                };


                /* =====================================================
                   STATUS DOUGHNUT
                ===================================================== */

                const statusCanvas =
                    document.getElementById("statusChart");


                if (statusCanvas) {

                    const statusLabels =
                        <?= json_encode(
                            $statusChartLabels,
                            JSON_UNESCAPED_UNICODE
                        ) ?>;

                    const statusValues =
                        <?= json_encode(
                            $statusChartValues
                        ) ?>;


                    new Chart(
                        statusCanvas, {
                            type: "doughnut",

                            data: {
                                labels: statusLabels,

                                datasets: [{
                                    label: "Records",
                                    data: statusValues,

                                    borderWidth: 2,

                                    hoverOffset: 8
                                }]
                            },

                            options: {

                                responsive: true,

                                maintainAspectRatio: false,

                                cutout: "62%",

                                plugins: {

                                    legend: commonLegend,

                                    tooltip: {

                                        callbacks: {

                                            label: function(context) {

                                                const label =
                                                    context.label || "";

                                                const value =
                                                    context.parsed || 0;

                                                return (
                                                    label +
                                                    ": " +
                                                    value
                                                );
                                            }

                                        }

                                    }

                                }

                            }

                        }
                    );
                }


                /* =====================================================
                   CATEGORY / TYPE BAR CHART
                ===================================================== */

                const categoryCanvas =
                    document.getElementById("categoryChart");


                if (categoryCanvas) {

                    const categoryLabels =
                        <?= json_encode(
                            $categoryChartLabels,
                            JSON_UNESCAPED_UNICODE
                        ) ?>;

                    const categoryValues =
                        <?= json_encode(
                            $categoryChartValues
                        ) ?>;


                    new Chart(
                        categoryCanvas, {
                            type: "bar",

                            data: {

                                labels: categoryLabels,

                                datasets: [{
                                    label: <?= json_encode(
                                                $module === "APPLICATION"
                                                    ? "Applications"
                                                    : $moduleTitle
                                            ) ?>,

                                    data: categoryValues,

                                    borderWidth: 1,

                                    borderRadius: 6
                                }]

                            },

                            options: {

                                responsive: true,

                                maintainAspectRatio: false,

                                interaction: {
                                    mode: "index",
                                    intersect: false
                                },

                                scales: {

                                    y: {

                                        beginAtZero: true,

                                        ticks: {
                                            precision: 0
                                        },

                                        title: {
                                            display: true,
                                            text: "Number of Records"
                                        }

                                    },

                                    x: {

                                        ticks: {
                                            autoSkip: false
                                        }

                                    }

                                },

                                plugins: {

                                    legend: {
                                        display: false
                                    },

                                    tooltip: {

                                        callbacks: {

                                            label: function(context) {

                                                return (
                                                    " " +
                                                    context.parsed.y +
                                                    " records"
                                                );
                                            }

                                        }

                                    }

                                }

                            }

                        }
                    );
                }


                /* =====================================================
                   MONTHLY LINE CHART
                ===================================================== */

                const monthlyCanvas =
                    document.getElementById("monthlyChart");


                if (monthlyCanvas) {

                    const monthlyLabels =
                        <?= json_encode(
                            $monthlyChartLabels,
                            JSON_UNESCAPED_UNICODE
                        ) ?>;

                    const monthlyValues =
                        <?= json_encode(
                            $monthlyChartValues
                        ) ?>;


                    new Chart(
                        monthlyCanvas, {
                            type: "line",

                            data: {

                                labels: monthlyLabels,

                                datasets: [{
                                    label: <?= json_encode(
                                                $moduleTitle
                                            ) ?>,

                                    data: monthlyValues,

                                    fill: true,

                                    tension: 0.35,

                                    borderWidth: 3,

                                    pointRadius: 4,

                                    pointHoverRadius: 7
                                }]

                            },

                            options: {

                                responsive: true,

                                maintainAspectRatio: false,

                                interaction: {

                                    mode: "index",

                                    intersect: false

                                },

                                scales: {

                                    y: {

                                        beginAtZero: true,

                                        ticks: {
                                            precision: 0
                                        },

                                        title: {
                                            display: true,
                                            text: "Number of Records"
                                        }

                                    },

                                    x: {

                                        title: {
                                            display: true,
                                            text: "Month"
                                        }

                                    }

                                },

                                plugins: {

                                    legend: {
                                        display: true,
                                        position: "bottom"
                                    },

                                    tooltip: {

                                        callbacks: {

                                            label: function(context) {

                                                return (
                                                    " " +
                                                    context.parsed.y +
                                                    " records"
                                                );

                                            }

                                        }

                                    }

                                }

                            }

                        }
                    );
                }

            }
        );
    </script>


    <script src="js/admin-script.js"></script>

</body>

</html>
