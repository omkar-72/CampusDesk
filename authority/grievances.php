<?php
session_start();

/*
|--------------------------------------------------------------------------
| Authority Grievances Page
|--------------------------------------------------------------------------
| UI has been updated to match the CampusDesk Friend Login/Register theme.
| Backend logic, database queries, filters and functionality are unchanged.
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['role_name'])) {
    $_SESSION['role_name'] = 'AUTHORITY';
}

require_once '../config/database.php';

/* ---------------- Filters ---------------- */

$searchId   = $_GET['grievance_id'] ?? '';
$search     = $_GET['search'] ?? '';
$category   = $_GET['category'] ?? '';
$dateFilter = $_GET['date'] ?? '';

/* ---------------- Summary Cards ---------------- */

$newCount = pg_fetch_result(pg_query($conn, "
SELECT COUNT(*)
FROM grievances g
JOIN statuses s ON g.status_id=s.status_id
WHERE s.status_name='New'
"), 0, 0);

$reviewCount = pg_fetch_result(pg_query($conn, "
SELECT COUNT(*)
FROM grievances g
JOIN statuses s ON g.status_id=s.status_id
WHERE s.status_name IN ('Under Review','In Progress')
"), 0, 0);

$resolvedCount = pg_fetch_result(pg_query($conn, "
SELECT COUNT(*)
FROM grievances g
JOIN statuses s ON g.status_id=s.status_id
WHERE s.status_name='Resolved'
"), 0, 0);

$rejectedCount = pg_fetch_result(pg_query($conn, "
SELECT COUNT(*)
FROM grievances g
JOIN statuses s ON g.status_id=s.status_id
WHERE s.status_name='Rejected'
"), 0, 0);

/* ---------------- Categories ---------------- */

$categories = pg_query($conn, "
SELECT category_id, category_name
FROM grievance_categories
ORDER BY category_name
");

/* ---------------- Main Query ---------------- */

$query = "
SELECT
    g.grievance_id,
    g.student_id,
    st.full_name,
    g.anonymous_status,
    g.title,
    g.description,
    g.submission_date,
    gc.category_name,
    s.status_name

FROM grievances g

LEFT JOIN students st
    ON g.student_id = st.student_id

LEFT JOIN grievance_categories gc
    ON g.category_id = gc.category_id

LEFT JOIN statuses s
    ON g.status_id = s.status_id

WHERE 1=1
";

$params = [];
$count = 1;

if ($searchId != '') {
    $query .= " AND g.grievance_id=$" . $count++;
    $params[] = $searchId;
}

if ($search != '') {
    $query .= " AND g.title ILIKE $" . $count++;
    $params[] = "%" . $search . "%";
}

if ($category != '') {
    $query .= " AND g.category_id=$" . $count++;
    $params[] = $category;
}

if ($dateFilter == 'today') {
    $query .= " AND DATE(g.submission_date)=CURRENT_DATE";
}

$query .= " ORDER BY g.submission_date DESC";

$result = pg_query_params($conn, $query, $params);
$totalRows = $result ? pg_num_rows($result) : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusDesk | Authority Grievances</title>

    <!-- Font Awesome -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Common CampusDesk styles -->
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/authority-dashboard.css">
    <link rel="stylesheet" href="../css/authority-table.css">

    <!-- Friend Login/Register theme for this page -->
    <style>
        /* =========================================================
           AUTHORITY GRIEVANCES
           FRIEND LOGIN / REGISTER UI THEME
           ========================================================= */

        .dashboard-content {
            padding-top: 100px;
            padding-bottom: 40px;
        }

        /* ---------------- Page Header ---------------- */

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

        /* ---------------- Filter Form ---------------- */

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

        /* ---------------- Search Button ---------------- */

        .search-btn {
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

        .search-btn:hover {
            background: var(--primary);
            color: var(--ink);
            transform: translate(-1px, -2px);
            box-shadow: 4px 4px 0 var(--ink);
        }

        /* ---------------- Reset Button ---------------- */

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

        /* ---------------- Summary Cards ---------------- */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin: 20px 0;
        }

        .summary-card {
            position: relative;
            background: var(--card);
            border: 2px solid var(--ink);
            border-radius: 12px;
            padding: 18px;
            min-height: 115px;
            overflow: hidden;
            box-shadow: 4px 4px 0 var(--border);
            transition: transform .2s ease, box-shadow .2s ease;
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

        /* Different visual accent for each summary card */
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

        /* ---------------- Table Information ---------------- */

        .table-info {
            margin: 18px 0 12px;
            padding-left: 2px;
            color: var(--text-light);
            font-size: 13px;
            font-weight: 600;
        }

        /* ---------------- Table Card ---------------- */

        .table-card {
            background: var(--card);
            border: 2px solid var(--ink);
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 5px 5px 0 var(--border);
        }

        .table-card table {
            width: 100%;
            min-width: 760px;
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

        /* ---------------- Status Badges ---------------- */

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

        /* ---------------- Action Button ---------------- */

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

        /* ---------------- Empty State ---------------- */

        .table-card tbody td[colspan="7"] {
            padding: 45px 20px !important;
            color: var(--text-light);
            font-size: 14px;
            font-weight: 500;
        }

        /* ---------------- Responsive ---------------- */

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
            .search-btn,
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
    <?php include '../includes/navbar.php'; ?>

    <!-- Common CampusDesk Header -->
    <?php include '../includes/header.php'; ?>

    <div class="dashboard-layout">

        <main class="dashboard-content">

            <!-- Page Header -->
            <div class="page-header">

                <div>
                    <h2>Grievance Management</h2>
                    <p>Review and manage student grievances.</p>
                </div>

            </div>

            <!-- Grievance Filters -->
            <form method="GET" class="filter-form">

                <input
                    type="number"
                    name="grievance_id"
                    placeholder="Grievance ID"
                    value="<?= htmlspecialchars($searchId) ?>">

                <input
                    type="text"
                    name="search"
                    placeholder="Search title..."
                    value="<?= htmlspecialchars($search) ?>">

                <select name="category">

                    <option value="">All Categories</option>

                    <?php while ($cat = pg_fetch_assoc($categories)): ?>

                        <option
                            value="<?= $cat['category_id'] ?>"
                            <?= ($category == $cat['category_id']) ? 'selected' : '' ?>>

                            <?= htmlspecialchars($cat['category_name']) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

                <select name="date">

                    <option value="">All Dates</option>

                    <option
                        value="today"
                        <?= $dateFilter == 'today' ? 'selected' : '' ?>>

                        Today

                    </option>

                </select>

                <!-- Search -->
                <button type="submit" class="search-btn">

                    <i class="fa-solid fa-magnifying-glass"></i>
                    Search

                </button>

                <!-- Reset -->
                <a href="grievances.php">

                    <button type="button" class="reset-btn">
                        Reset
                    </button>

                </a>

            </form>

            <!-- Summary Cards -->
            <div class="summary-grid">

                <div class="summary-card">
                    <h3><?= $newCount ?></h3>
                    <p>New</p>
                </div>

                <div class="summary-card">
                    <h3><?= $reviewCount ?></h3>
                    <p>Review</p>
                </div>

                <div class="summary-card">
                    <h3><?= $resolvedCount ?></h3>
                    <p>Resolved</p>
                </div>

                <div class="summary-card">
                    <h3><?= $rejectedCount ?></h3>
                    <p>Rejected</p>
                </div>

            </div>

            <!-- Table Result Count -->
            <div class="table-info">

                Showing <?= $totalRows > 0 ? 1 : 0 ?>–<?= $totalRows ?>
                of <?= $totalRows ?>

            </div>

            <!-- Grievance Table -->
            <div class="table-card">

                <table>

                    <thead>

                        <tr>

                            <th>ID</th>
                            <th>Student</th>
                            <th>Category</th>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($result && pg_num_rows($result) > 0): ?>

                            <?php while ($row = pg_fetch_assoc($result)): ?>

                                <tr>

                                    <td>
                                        GRV<?= str_pad($row['grievance_id'], 3, '0', STR_PAD_LEFT) ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($row['anonymous_status'])): ?>
                                            Anonymous
                                        <?php else: ?>
                                            <?= htmlspecialchars($row['full_name']) ?>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($row['category_name']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($row['title']) ?>
                                    </td>

                                    <td>
                                        <?= date('d M Y', strtotime($row['submission_date'])) ?>
                                    </td>

                                    <td>

                                        <?php

                                        $status = $row['status_name'];
                                        $class = 'badge-pending';

                                        if ($status == 'Resolved') {

                                            $class = 'badge-success';
                                        } elseif ($status == 'Rejected') {

                                            $class = 'badge-danger';
                                        } elseif (
                                            $status == 'Under Review' ||
                                            $status == 'In Progress'
                                        ) {

                                            $class = 'badge-review';
                                        }

                                        ?>

                                        <span class="badge <?= $class ?>">
                                            <?= htmlspecialchars($status) ?>
                                        </span>

                                    </td>

                                    <td>

                                        <a
                                            href="view_grievance.php?id=<?= $row['grievance_id'] ?>"
                                            class="action-btn">

                                            <i class="fa-solid fa-eye"></i>
                                            View

                                        </a>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="7">

                                    No grievances found.

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </main>

    </div>

    <!-- Common CampusDesk Footer -->
    <?php include '../includes/footer.php'; ?>

</body>

</html>
