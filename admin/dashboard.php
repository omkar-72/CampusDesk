<?php
/*
|--------------------------------------------------------------------------
| CampusDesk - Admin Dashboard
|--------------------------------------------------------------------------
*/

require_once "admin_auth.php";
requireAdmin();

require_once "../config/database.php";
require_once "../includes/functions.php";


/* =========================================================
   HELPER FUNCTIONS
========================================================= */

function getDashboardCount($conn, $query)
{
    $result = pg_query($conn, $query);

    if (!$result) {
        return 0;
    }

    $row = pg_fetch_assoc($result);

    return isset($row["count"]) ? (int)$row["count"] : 0;
}


function formatDashboardDate($datetime)
{
    if (empty($datetime)) {
        return "-";
    }

    $timestamp = strtotime($datetime);

    if ($timestamp === false) {
        return "-";
    }

    return date("d M Y, h:i A", $timestamp);
}


function formatDashboardId($prefix, $id)
{
    return $prefix . "-" . str_pad(
        (string)$id,
        5,
        "0",
        STR_PAD_LEFT
    );
}


function getStatusClass($status)
{
    $status = strtolower(trim((string)$status));

    switch ($status) {

        case "resolved":
        case "implemented":
        case "approved":
        case "accepted":
            return "status-success";

        case "rejected":
            return "status-danger";

        case "under review":
        case "in progress":
        case "processing":
            return "status-warning";

        case "new":
        default:
            return "status-info";
    }
}


function getModuleClass($module)
{
    switch (strtoupper((string)$module)) {

        case "GRIEVANCE":
            return "module-grievance";

        case "SUGGESTION":
            return "module-suggestion";

        case "APPLICATION":
            return "module-application";

        default:
            return "module-general";
    }
}


function getActionClass($action)
{
    $action = strtolower(trim((string)$action));

    if (
        strpos($action, "delete") !== false ||
        strpos($action, "reject") !== false ||
        strpos($action, "deactivate") !== false
    ) {
        return "action-danger";
    }

    if (
        strpos($action, "create") !== false ||
        strpos($action, "add") !== false ||
        strpos($action, "approve") !== false ||
        strpos($action, "resolve") !== false
    ) {
        return "action-success";
    }

    if (
        strpos($action, "update") !== false ||
        strpos($action, "edit") !== false
    ) {
        return "action-warning";
    }

    return "action-info";
}


/* =========================================================
   USER OVERVIEW
========================================================= */

$totalUsers = getDashboardCount(
    $conn,
    "SELECT COUNT(*) AS count FROM users"
);

$totalStudents = getDashboardCount(
    $conn,
    "SELECT COUNT(*) AS count FROM students"
);


/*
|--------------------------------------------------------------------------
| Authority count
|--------------------------------------------------------------------------
| Admin is also represented in authorities, but the Admin dashboard
| shows active authority records only.
|--------------------------------------------------------------------------
*/

$totalAuthorities = getDashboardCount(
    $conn,
    "SELECT COUNT(*) AS count
     FROM users u
     INNER JOIN roles r
         ON u.role_id = r.role_id
     WHERE r.role_name = 'AUTHORITY'
       AND u.account_status = TRUE"
);


$totalDepartments = getDashboardCount(
    $conn,
    "SELECT COUNT(*) AS count
     FROM departments
     WHERE status = TRUE"
);


/* =========================================================
   SERVICE OVERVIEW
========================================================= */

$totalGrievances = getDashboardCount(
    $conn,
    "SELECT COUNT(*) AS count
     FROM grievances"
);

$totalSuggestions = getDashboardCount(
    $conn,
    "SELECT COUNT(*) AS count
     FROM suggestions"
);

$totalApplications = getDashboardCount(
    $conn,
    "SELECT COUNT(*) AS count
     FROM applications"
);


/* =========================================================
   COMPLETED SERVICE COUNTS
========================================================= */

$resolvedGrievances = getDashboardCount(
    $conn,
    "SELECT COUNT(*) AS count
     FROM grievances g
     INNER JOIN statuses s
         ON g.status_id = s.status_id
     WHERE s.module_type = 'GRIEVANCE'
       AND s.status_name = 'Resolved'"
);

$implementedSuggestions = getDashboardCount(
    $conn,
    "SELECT COUNT(*) AS count
     FROM suggestions sg
     INNER JOIN statuses s
         ON sg.status_id = s.status_id
     WHERE s.module_type = 'SUGGESTION'
       AND s.status_name = 'Implemented'"
);

$approvedApplications = getDashboardCount(
    $conn,
    "SELECT COUNT(*) AS count
     FROM applications a
     INNER JOIN statuses s
         ON a.status_id = s.status_id
     WHERE s.module_type = 'APPLICATION'
       AND s.status_name = 'Approved'"
);


/* =========================================================
   SYSTEM SUMMARY
========================================================= */

$totalAuditLogs = getDashboardCount(
    $conn,
    "SELECT COUNT(*) AS count
     FROM audit_logs"
);

$totalAttachments = getDashboardCount(
    $conn,
    "SELECT COUNT(*) AS count
     FROM attachments"
);

$inactiveUsers = getDashboardCount(
    $conn,
    "SELECT COUNT(*) AS count
     FROM users
     WHERE account_status = FALSE"
);


/* =========================================================
   RECENT GRIEVANCES
========================================================= */

$recentGrievances = [];

$query = "
    SELECT
        g.grievance_id,
        g.title,
        g.submission_date,
        s.status_name,
        gc.category_name
    FROM grievances g
    LEFT JOIN statuses s
        ON g.status_id = s.status_id
    LEFT JOIN grievance_categories gc
        ON g.category_id = gc.category_id
    ORDER BY g.grievance_id DESC
    LIMIT 3
";

$result = pg_query($conn, $query);

if ($result) {

    while ($row = pg_fetch_assoc($result)) {
        $recentGrievances[] = $row;
    }
}


/* =========================================================
   RECENT SUGGESTIONS
========================================================= */

$recentSuggestions = [];

$query = "
    SELECT
        sg.suggestion_id,
        sg.title,
        sg.submission_date,
        s.status_name,
        sc.category_name
    FROM suggestions sg
    LEFT JOIN statuses s
        ON sg.status_id = s.status_id
    LEFT JOIN suggestion_categories sc
        ON sg.category_id = sc.category_id
    ORDER BY sg.suggestion_id DESC
    LIMIT 3
";

$result = pg_query($conn, $query);

if ($result) {

    while ($row = pg_fetch_assoc($result)) {
        $recentSuggestions[] = $row;
    }
}


/* =========================================================
   RECENT APPLICATIONS
========================================================= */

$recentApplications = [];

$query = "
    SELECT
        a.application_id,
        a.subject,
        a.submission_date,
        s.status_name,
        at.type_name
    FROM applications a
    LEFT JOIN statuses s
        ON a.status_id = s.status_id
    LEFT JOIN application_types at
        ON a.application_type_id = at.application_type_id
    ORDER BY a.application_id DESC
    LIMIT 3
";

$result = pg_query($conn, $query);

if ($result) {

    while ($row = pg_fetch_assoc($result)) {
        $recentApplications[] = $row;
    }
}


/* =========================================================
   RECENT AUDIT LOGS
========================================================= */

$recentAuditLogs = [];

$query = "
    SELECT
        al.audit_id,
        al.user_id,
        al.module_type,
        al.reference_id,
        al.action,
        al.ip_address,
        al.created_at,
        u.email,
        r.role_name
    FROM audit_logs al
    LEFT JOIN users u
        ON al.user_id = u.user_id
    LEFT JOIN roles r
        ON u.role_id = r.role_id
    ORDER BY al.audit_id DESC
    LIMIT 6
";

$result = pg_query($conn, $query);

if ($result) {

    while ($row = pg_fetch_assoc($result)) {
        $recentAuditLogs[] = $row;
    }
}


/* =========================================================
   ADMIN INFORMATION
========================================================= */

$adminEmail = getAdminEmail();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="CampusDesk Administration Dashboard">

    <title>Admin Dashboard - CampusDesk</title>


    <!-- =====================================================
         COMMON ADMIN CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/admin-common.css">


    <!-- =====================================================
         ADMIN NAVBAR CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/admin-navbar.css">


    <!-- =====================================================
         ADMIN DASHBOARD CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/admin-dashboard.css">


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>


    <!-- =========================================================
     ADMIN NAVBAR
========================================================== -->

    <?php include "includes/navbar.php"; ?>


    <!-- =========================================================
     MAIN CONTENT
========================================================== -->

    <main class="admin-main">

        <div class="admin-container">


            <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

            <div class="dashboard-header">

                <div class="page-header-content">

                    <div>

                        <h1 class="page-title">
                            Administration Dashboard
                        </h1>

                        <p class="page-subtitle">
                            Overview of CampusDesk users,
                            services and system activity.
                        </p>

                    </div>


                    <div class="dashboard-header-user">

                        <span class="dashboard-welcome">
                            Welcome, Admin
                        </span>

                        <?php if (!empty($adminEmail)): ?>

                            <span class="dashboard-admin-email">
                                <?= htmlspecialchars($adminEmail) ?>
                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>


            <!-- =====================================================
             USER OVERVIEW
        ====================================================== -->

            <section class="dashboard-section">

                <div class="section-heading">

                    <div>

                        <h2>
                            <i class="fa-solid fa-users"></i>
                            User Overview
                        </h2>

                        <p>
                            Current CampusDesk account statistics.
                        </p>

                    </div>

                </div>


                <div class="statistics-grid">


                    <!-- Total Users -->

                    <div class="stat-card">

                        <div class="stat-icon stat-icon-users">

                            <i class="fa-solid fa-users"></i>

                        </div>

                        <div class="stat-content">

                            <span class="stat-label">
                                Total Users
                            </span>

                            <strong class="stat-value">
                                <?= number_format($totalUsers) ?>
                            </strong>

                        </div>

                        <a
                            href="users.php"
                            class="stat-link">
                            View Users
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>


                    <!-- Total Students -->

                    <div class="stat-card">

                        <div class="stat-icon stat-icon-students">

                            <i class="fa-solid fa-user-graduate"></i>

                        </div>

                        <div class="stat-content">

                            <span class="stat-label">
                                Total Students
                            </span>

                            <strong class="stat-value">
                                <?= number_format($totalStudents) ?>
                            </strong>

                        </div>

                        <a
                            href="users.php?role=STUDENT"
                            class="stat-link">
                            View Students
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>


                    <!-- Total Authorities -->

                    <div class="stat-card">

                        <div class="stat-icon stat-icon-authorities">

                            <i class="fa-solid fa-user-tie"></i>

                        </div>

                        <div class="stat-content">

                            <span class="stat-label">
                                Total Authorities
                            </span>

                            <strong class="stat-value">
                                <?= number_format($totalAuthorities) ?>
                            </strong>

                        </div>

                        <a
                            href="users.php?role=AUTHORITY"
                            class="stat-link">
                            View Authorities
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>


                    <!-- Active Departments -->

                    <div class="stat-card">

                        <div class="stat-icon stat-icon-departments">

                            <i class="fa-solid fa-building"></i>

                        </div>

                        <div class="stat-content">

                            <span class="stat-label">
                                Active Departments
                            </span>

                            <strong class="stat-value">
                                <?= number_format($totalDepartments) ?>
                            </strong>

                        </div>

                        <a href="departments.php" class="stat-link">
                            Manage Departments
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>

                    </div>

                </div>

            </section>


            <!-- =====================================================
             SERVICE OVERVIEW
        ====================================================== -->

            <section class="dashboard-section">

                <div class="section-heading">

                    <div>

                        <h2>
                            <i class="fa-solid fa-layer-group"></i>
                            Service Overview
                        </h2>

                        <p>
                            Overall activity across CampusDesk services.
                        </p>

                    </div>

                </div>


                <div class="service-summary-grid">


                    <!-- Grievances -->

                    <div class="service-summary-card">

                        <div class="service-summary-header">

                            <div class="service-summary-icon grievance-icon">

                                <i class="fa-solid fa-triangle-exclamation"></i>

                            </div>

                            <div>

                                <h3>
                                    Grievances
                                </h3>

                                <span>
                                    Total submissions
                                </span>

                            </div>

                        </div>


                        <div class="service-summary-number">

                            <?= number_format($totalGrievances) ?>

                        </div>


                        <div class="service-summary-footer">

                            <span class="completed-count">

                                <?= number_format($resolvedGrievances) ?>

                                resolved

                            </span>

                        </div>

                    </div>


                    <!-- Suggestions -->

                    <div class="service-summary-card">

                        <div class="service-summary-header">

                            <div class="service-summary-icon suggestion-icon">

                                <i class="fa-solid fa-lightbulb"></i>

                            </div>

                            <div>

                                <h3>
                                    Suggestions
                                </h3>

                                <span>
                                    Total submissions
                                </span>

                            </div>

                        </div>


                        <div class="service-summary-number">

                            <?= number_format($totalSuggestions) ?>

                        </div>


                        <div class="service-summary-footer">

                            <span class="completed-count">

                                <?= number_format($implementedSuggestions) ?>

                                implemented

                            </span>

                        </div>

                    </div>


                    <!-- Applications -->

                    <div class="service-summary-card">

                        <div class="service-summary-header">

                            <div class="service-summary-icon application-icon">

                                <i class="fa-solid fa-file-lines"></i>

                            </div>

                            <div>

                                <h3>
                                    Applications
                                </h3>

                                <span>
                                    Total submissions
                                </span>

                            </div>

                        </div>


                        <div class="service-summary-number">

                            <?= number_format($totalApplications) ?>

                        </div>


                        <div class="service-summary-footer">

                            <span class="completed-count">

                                <?= number_format($approvedApplications) ?>

                                approved

                            </span>

                        </div>

                    </div>

                </div>

            </section>


            <!-- =====================================================
             QUICK ACTIONS
        ====================================================== -->

            <section class="dashboard-section">

                <div class="dashboard-card">

                    <div class="card-header">

                        <div>

                            <h2>
                                <i class="fa-solid fa-bolt"></i>
                                Quick Actions
                            </h2>

                            <p>
                                Frequently used administration actions.
                            </p>

                        </div>

                    </div>


                    <div class="quick-actions">


                        <!-- Users -->

                        <a
                            href="users.php"
                            class="quick-action">

                            <span class="quick-action-icon">

                                <i class="fa-solid fa-users"></i>

                            </span>

                            <span class="quick-action-content">

                                <strong>
                                    Manage Users
                                </strong>

                                <small>
                                    Add, edit and manage user accounts
                                </small>

                            </span>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>


                        <!-- Categories -->

                        <a
                            href="categories.php"
                            class="quick-action">

                            <span class="quick-action-icon">

                                <i class="fa-solid fa-layer-group"></i>

                            </span>

                            <span class="quick-action-content">

                                <strong>
                                    Manage Categories
                                </strong>

                                <small>
                                    Manage categories and application types
                                </small>

                            </span>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>


                        <!-- Reports -->

                        <a
                            href="reports.php"
                            class="quick-action">

                            <span class="quick-action-icon">

                                <i class="fa-solid fa-chart-column"></i>

                            </span>

                            <span class="quick-action-content">

                                <strong>
                                    Reports & Analysis
                                </strong>

                                <small>
                                    View service statistics and analysis
                                </small>

                            </span>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>


                        <!-- Audit Logs -->

                        <a
                            href="audit_logs.php"
                            class="quick-action">

                            <span class="quick-action-icon">

                                <i class="fa-solid fa-clock-rotate-left"></i>

                            </span>

                            <span class="quick-action-content">

                                <strong>
                                    Audit Logs
                                </strong>

                                <small>
                                    Review recorded system activities
                                </small>

                            </span>

                            <i class="fa-solid fa-chevron-right"></i>

                        </a>

                    </div>

                </div>

            </section>


            <!-- =====================================================
             RECENT SERVICE ACTIVITY
        ====================================================== -->

            <section class="dashboard-section">

                <div class="dashboard-card">

                    <div class="card-header">

                        <div>

                            <h2>
                                <i class="fa-solid fa-clock"></i>
                                Recent Service Activity
                            </h2>

                            <p>
                                Latest submitted grievances,
                                suggestions and applications.
                            </p>

                        </div>

                    </div>


                    <div class="recent-activity-list">


                        <!-- Recent Grievances -->

                        <?php foreach ($recentGrievances as $item): ?>

                            <div class="recent-activity-item">

                                <div class="activity-icon grievance-icon">

                                    <i class="fa-solid fa-triangle-exclamation"></i>

                                </div>


                                <div class="activity-content">

                                    <div class="activity-main">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $item["title"] ?? "Grievance"
                                            ) ?>

                                        </strong>

                                        <span class="activity-module">

                                            <?= htmlspecialchars(
                                                $item["category_name"] ?? "Grievance"
                                            ) ?>

                                        </span>

                                    </div>


                                    <span class="activity-meta">

                                        <?= formatDashboardId(
                                            "GRV",
                                            $item["grievance_id"]
                                        ) ?>

                                        &nbsp;•&nbsp;

                                        <?= formatDashboardDate(
                                            $item["submission_date"]
                                        ) ?>

                                    </span>

                                </div>


                                <span
                                    class="status-badge <?= getStatusClass(
                                                            $item["status_name"] ?? ""
                                                        ) ?>">

                                    <?= htmlspecialchars(
                                        $item["status_name"] ?? "Unknown"
                                    ) ?>

                                </span>

                            </div>

                        <?php endforeach; ?>


                        <!-- Recent Suggestions -->

                        <?php foreach ($recentSuggestions as $item): ?>

                            <div class="recent-activity-item">

                                <div class="activity-icon suggestion-icon">

                                    <i class="fa-solid fa-lightbulb"></i>

                                </div>


                                <div class="activity-content">

                                    <div class="activity-main">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $item["title"] ?? "Suggestion"
                                            ) ?>

                                        </strong>

                                        <span class="activity-module">

                                            <?= htmlspecialchars(
                                                $item["category_name"] ?? "Suggestion"
                                            ) ?>

                                        </span>

                                    </div>


                                    <span class="activity-meta">

                                        <?= formatDashboardId(
                                            "SGT",
                                            $item["suggestion_id"]
                                        ) ?>

                                        &nbsp;•&nbsp;

                                        <?= formatDashboardDate(
                                            $item["submission_date"]
                                        ) ?>

                                    </span>

                                </div>


                                <span
                                    class="status-badge <?= getStatusClass(
                                                            $item["status_name"] ?? ""
                                                        ) ?>">

                                    <?= htmlspecialchars(
                                        $item["status_name"] ?? "Unknown"
                                    ) ?>

                                </span>

                            </div>

                        <?php endforeach; ?>


                        <!-- Recent Applications -->

                        <?php foreach ($recentApplications as $item): ?>

                            <div class="recent-activity-item">

                                <div class="activity-icon application-icon">

                                    <i class="fa-solid fa-file-lines"></i>

                                </div>


                                <div class="activity-content">

                                    <div class="activity-main">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $item["subject"] ?? "Application"
                                            ) ?>

                                        </strong>

                                        <span class="activity-module">

                                            <?= htmlspecialchars(
                                                $item["type_name"] ?? "Application"
                                            ) ?>

                                        </span>

                                    </div>


                                    <span class="activity-meta">

                                        <?= formatDashboardId(
                                            "APP",
                                            $item["application_id"]
                                        ) ?>

                                        &nbsp;•&nbsp;

                                        <?= formatDashboardDate(
                                            $item["submission_date"]
                                        ) ?>

                                    </span>

                                </div>


                                <span
                                    class="status-badge <?= getStatusClass(
                                                            $item["status_name"] ?? ""
                                                        ) ?>">

                                    <?= htmlspecialchars(
                                        $item["status_name"] ?? "Unknown"
                                    ) ?>

                                </span>

                            </div>

                        <?php endforeach; ?>


                        <?php if (
                            empty($recentGrievances) &&
                            empty($recentSuggestions) &&
                            empty($recentApplications)
                        ): ?>

                            <div class="empty-state">

                                <div class="empty-state-icon">

                                    <i class="fa-solid fa-inbox"></i>

                                </div>

                                <h3>
                                    No Recent Activity
                                </h3>

                                <p>
                                    No service records are available yet.
                                </p>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </section>


            <!-- =====================================================
             RECENT AUDIT ACTIVITY
        ====================================================== -->

            <section class="dashboard-section">

                <div class="dashboard-card">

                    <div class="card-header">

                        <div>

                            <h2>
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                Recent Audit Activity
                            </h2>

                            <p>
                                Latest actions recorded in CampusDesk.
                            </p>

                        </div>


                        <a
                            href="audit_logs.php"
                            class="btn btn-secondary">
                            View All Logs
                        </a>

                    </div>


                    <!-- Audit Summary -->

                    <div class="audit-summary">


                        <div class="audit-summary-stat">

                            <span class="audit-summary-icon">

                                <i class="fa-solid fa-list-check"></i>

                            </span>

                            <div>

                                <strong>
                                    <?= number_format($totalAuditLogs) ?>
                                </strong>

                                <span>
                                    Total Audit Logs
                                </span>

                            </div>

                        </div>


                        <div class="audit-summary-stat">

                            <span class="audit-summary-icon">

                                <i class="fa-solid fa-paperclip"></i>

                            </span>

                            <div>

                                <strong>
                                    <?= number_format($totalAttachments) ?>
                                </strong>

                                <span>
                                    Attachments
                                </span>

                            </div>

                        </div>


                        <div class="audit-summary-stat">

                            <span class="audit-summary-icon">

                                <i class="fa-solid fa-user-slash"></i>

                            </span>

                            <div>

                                <strong>
                                    <?= number_format($inactiveUsers) ?>
                                </strong>

                                <span>
                                    Inactive Accounts
                                </span>

                            </div>

                        </div>

                    </div>


                    <?php if (!empty($recentAuditLogs)): ?>

                        <div class="table-responsive">

                            <table class="admin-table">

                                <thead>

                                    <tr>

                                        <th>
                                            ID
                                        </th>

                                        <th>
                                            User
                                        </th>

                                        <th>
                                            Role
                                        </th>

                                        <th>
                                            Module
                                        </th>

                                        <th>
                                            Reference
                                        </th>

                                        <th>
                                            Action
                                        </th>

                                        <th>
                                            Date & Time
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach ($recentAuditLogs as $log): ?>

                                        <tr>

                                            <td>

                                                <span class="record-id">

                                                    #<?= (int)$log["audit_id"] ?>

                                                </span>

                                            </td>


                                            <td>

                                                <div class="user-info-cell">

                                                    <span class="user-avatar-small">

                                                        <i class="fa-solid fa-user"></i>

                                                    </span>

                                                    <div>

                                                        <strong>

                                                            <?= htmlspecialchars(
                                                                $log["email"] ?? "Unknown"
                                                            ) ?>

                                                        </strong>

                                                        <small>

                                                            User #<?= (int)$log["user_id"] ?>

                                                        </small>

                                                    </div>

                                                </div>

                                            </td>


                                            <td>

                                                <span class="role-badge">

                                                    <?= htmlspecialchars(
                                                        $log["role_name"] ?? "Unknown"
                                                    ) ?>

                                                </span>

                                            </td>


                                            <td>

                                                <span
                                                    class="module-badge <?= getModuleClass(
                                                                            $log["module_type"] ?? ""
                                                                        ) ?>">

                                                    <?= htmlspecialchars(
                                                        $log["module_type"] ?? "-"
                                                    ) ?>

                                                </span>

                                            </td>


                                            <td>

                                                <span class="record-id">

                                                    #<?= (int)$log["reference_id"] ?>

                                                </span>

                                            </td>


                                            <td>

                                                <span
                                                    class="action-badge <?= getActionClass(
                                                                            $log["action"] ?? ""
                                                                        ) ?>">

                                                    <?= htmlspecialchars(
                                                        $log["action"] ?? "-"
                                                    ) ?>

                                                </span>

                                            </td>


                                            <td>

                                                <span class="table-date">

                                                    <?= formatDashboardDate(
                                                        $log["created_at"]
                                                    ) ?>

                                                </span>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="empty-state">

                            <div class="empty-state-icon">

                                <i class="fa-solid fa-clock-rotate-left"></i>

                            </div>

                            <h3>
                                No Audit Activity
                            </h3>

                            <p>
                                No audit records are available yet.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </section>


        </div>

    </main>


    <!-- =========================================================
     ADMIN COMMON JAVASCRIPT
========================================================== -->

    <script src="js/admin-script.js"></script>


</body>

</html>
