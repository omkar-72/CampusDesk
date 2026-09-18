<?php
session_start();

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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/authority-dashboard.css">
    <link rel="stylesheet" href="../css/authority-table.css">

    <style>
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin: 22px 0;
        }

        .summary-card {
            background: #fff;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
            border-top: 4px solid #2563EB;
            transition: .3s;
        }

        .summary-card:hover {
            transform: translateY(-4px);
        }

        .summary-card h3 {
            margin: 0;
            font-size: 30px;
        }

        .summary-card p {
            margin-top: 8px;
            color: #64748B;
        }

        .filter-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .filter-form input,
        .filter-form select {
            padding: 10px 14px;
            border: 1px solid #CBD5E1;
            border-radius: 10px;
            background: white;
        }

        .search-btn {
            background: #2563EB;
            color: white;
            padding: 10px 18px;
            border-radius: 10px;
        }

        .reset-btn {
            background: #64748B;
            color: white;
            padding: 10px 18px;
            border-radius: 10px;
        }

        .table-info {
            margin: 16px 0;
            font-weight: 600;
            color: #334155;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: #2563EB;
            color: white;
            border-radius: 8px;
            text-decoration: none;
        }

        .action-btn:hover {
            background: #1D4ED8;
        }

        .badge-review {
            background: #F59E0B;
            color: white;
        }

        .badge-success {
            background: #16A34A;
            color: white;
        }

        .badge-danger {
            background: #DC2626;
            color: white;
        }

        .badge-pending {
            background: #2563EB;
            color: white;
        }
    </style>

</head>

<body>

    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/header.php'; ?>

    <div class="dashboard-layout">

        <main class="dashboard-content">

            <div class="page-header">

                <div>

                    <h2>Grievance Management</h2>

                    <p>Review and manage student grievances.</p>

                </div>

            </div>

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

                    <option value="today" <?= $dateFilter == 'today' ? 'selected' : '' ?>>

                        Today

                    </option>

                </select>

                <button type="submit" class="search-btn">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    Search

                </button>

                <a href="grievances.php">

                    <button type="button" class="reset-btn">

                        Reset

                    </button>

                </a>

            </form>

            <div class="summary-grid">

                <div class="summary-card" style="border-color:#2563EB;">

                    <h3><?= $newCount ?></h3>

                    <p>New</p>

                </div>

                <div class="summary-card" style="border-color:#F59E0B;">

                    <h3><?= $reviewCount ?></h3>

                    <p>Review</p>

                </div>

                <div class="summary-card" style="border-color:#16A34A;">

                    <h3><?= $resolvedCount ?></h3>

                    <p>Resolved</p>

                </div>

                <div class="summary-card" style="border-color:#DC2626;">

                    <h3><?= $rejectedCount ?></h3>

                    <p>Rejected</p>

                </div>

            </div>

            <div class="table-info">

                Showing <?= $totalRows > 0 ? 1 : 0 ?>–<?= $totalRows ?> of <?= $totalRows ?>

            </div>

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

                                        <?= htmlspecialchars($row['full_name']) ?>

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
                                        } elseif ($status == 'Under Review' || $status == 'In Progress') {
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

                                <td colspan="7" style="text-align:center;padding:35px;">

                                    No grievances found.

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </main>

    </div>

    <?php include '../includes/footer.php'; ?>

</body>

</html>