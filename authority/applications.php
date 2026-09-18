<?php
session_start();

if (!isset($_SESSION["role_name"])) {
    $_SESSION["role_name"] = "AUTHORITY";
}

require_once "../config/database.php";

/* ---------------- Handle Approve / Reject ---------------- */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $applicationId = $_POST["application_id"];

    if ($_POST["action"] == "approve") {

        pg_query_params($conn, "
            UPDATE applications
            SET status_id = (
                SELECT status_id
                FROM statuses
                WHERE module_type='APPLICATION'
                AND status_name='Approved'
            ),
            review_date = CURRENT_DATE
            WHERE application_id=$1
        ", [$applicationId]);
    }

    if ($_POST["action"] == "reject") {

        pg_query_params($conn, "
            UPDATE applications
            SET status_id = (
                SELECT status_id
                FROM statuses
                WHERE module_type='APPLICATION'
                AND status_name='Rejected'
            ),
            review_date = CURRENT_DATE
            WHERE application_id=$1
        ", [$applicationId]);
    }

    header("Location: applications.php");
    exit();
}

/* ---------------- Filters ---------------- */

$searchId = $_GET["application_id"] ?? "";
$search = $_GET["search"] ?? "";
$type = $_GET["type"] ?? "";
$statusFilter = $_GET["status"] ?? "";
$dateFilter = $_GET["date"] ?? "";

/* ---------------- Summary Cards ---------------- */

$newCount = pg_fetch_result(pg_query($conn, "
SELECT COUNT(*)
FROM applications a
JOIN statuses s ON a.status_id=s.status_id
WHERE s.status_name='New'
"), 0, 0);

$processingCount = pg_fetch_result(pg_query($conn, "
SELECT COUNT(*)
FROM applications a
JOIN statuses s ON a.status_id=s.status_id
WHERE s.status_name IN ('Processing','Under Review')
"), 0, 0);

$approvedCount = pg_fetch_result(pg_query($conn, "
SELECT COUNT(*)
FROM applications a
JOIN statuses s ON a.status_id=s.status_id
WHERE s.status_name='Approved'
"), 0, 0);

$rejectedCount = pg_fetch_result(pg_query($conn, "
SELECT COUNT(*)
FROM applications a
JOIN statuses s ON a.status_id=s.status_id
WHERE s.status_name='Rejected'
"), 0, 0);

/* ---------------- Application Types ---------------- */

$types = pg_query($conn, "
SELECT application_type_id,type_name
FROM application_types
ORDER BY type_name
");

/* ---------------- Main Query ---------------- */
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

$params = [];
$count = 1;

if ($searchId != "") {
    $query .= " AND a.application_id=$" . $count++;
    $params[] = $searchId;
}

if ($search != "") {
    $query .= " AND a.subject ILIKE $" . $count++;
    $params[] = "%" . $search . "%";
}

if ($type != "") {
    $query .= " AND a.application_type_id=$" . $count++;
    $params[] = $type;
}

if ($statusFilter != "") {
    $query .= " AND s.status_name=$" . $count++;
    $params[] = $statusFilter;
}

if ($dateFilter == "today") {
    $query .= " AND DATE(a.submission_date)=CURRENT_DATE";
}

$query .= " ORDER BY a.submission_date DESC";

$result = pg_query_params($conn, $query, $params);

$totalRows = $result ? pg_num_rows($result) : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusDesk | Authority Applications</title>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/authority-dashboard.css">
    <link rel="stylesheet" href="../css/authority-table.css">

    <style>
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin: 20px 0;
        }

        .summary-card {
            background: #fff;
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .08);
            border-top: 4px solid #2563EB;
        }

        .summary-card h3 {
            margin: 0;
            font-size: 28px;
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
            border-radius: 8px;
        }

        .filter-form button {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            background: #2563EB;
            color: white;
            cursor: pointer;
        }

        .reset-btn {
            background: #64748B;
        }

        .table-info {
            margin: 16px 0;
            font-weight: 600;
            color: #334155;
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

        .action-group {
            display: flex;
            gap: 6px;
        }

        .accept-btn {
            background: #16A34A;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
        }

        .reject-btn {
            background: #DC2626;
            color: white;
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
        }
    </style>

</head>

<body>

    <?php include "../includes/navbar.php"; ?>
    <?php include "../includes/header.php"; ?>

    <div class="dashboard-layout">

        <main class="dashboard-content">

            <div class="page-header">

                <div>
                    <h2>Application Management</h2>
                    <p>Review and approve student applications.</p>
                </div>

            </div>

            <!-- Filters -->

            <form method="GET" class="filter-form">

                <input
                    type="number"
                    name="application_id"
                    placeholder="Application ID"
                    value="<?= htmlspecialchars($searchId) ?>">

                <input
                    type="text"
                    name="search"
                    placeholder="Search subject..."
                    value="<?= htmlspecialchars($search) ?>">

                <select name="type">

                    <option value="">Application Type</option>

                    <?php while ($t = pg_fetch_assoc($types)): ?>

                        <option
                            value="<?= $t["application_type_id"] ?>"
                            <?= $type == $t["application_type_id"] ? "selected" : "" ?>>

                            <?= htmlspecialchars($t["type_name"]) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

                <select name="status">

                    <option value="">All Status</option>

                    <option value="New">New</option>
                    <option value="Processing">Processing</option>
                    <option value="Under Review">Under Review</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>

                </select>

                <select name="date">

                    <option value="">Date</option>

                    <option value="today" <?= $dateFilter == "today" ? "selected" : "" ?>>
                        Today
                    </option>

                </select>

                <button type="submit">Search</button>

                <a href="applications.php">
                    <button type="button" class="reset-btn">Reset</button>
                </a>

            </form>

            <!-- Summary Cards -->

            <div class="summary-grid">

                <div class="summary-card" style="border-color:#2563EB;">
                    <h3><?= $newCount ?></h3>
                    <p>New</p>
                </div>

                <div class="summary-card" style="border-color:#F59E0B;">
                    <h3><?= $processingCount ?></h3>
                    <p>Processing</p>
                </div>

                <div class="summary-card" style="border-color:#16A34A;">
                    <h3><?= $approvedCount ?></h3>
                    <p>Approved</p>
                </div>

                <div class="summary-card" style="border-color:#DC2626;">
                    <h3><?= $rejectedCount ?></h3>
                    <p>Rejected</p>
                </div>

            </div>

            <div class="table-info">

                Showing 1–<?= $totalRows ?> of <?= $totalRows ?>

            </div>

            <!-- Table -->

            <div class="table-card">

                <table>

                    <thead>

                        <tr>

                            <th>ID</th>
                            <th>Student</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($result && pg_num_rows($result) > 0): ?>

                            <?php while ($row = pg_fetch_assoc($result)): ?>

                                <tr>

                                    <td>AP<?= str_pad($row["application_id"], 3, "0", STR_PAD_LEFT) ?></td>

                                    <td><?= htmlspecialchars($row["full_name"] ?? "Unknown Student") ?></td>

                                    <td><?= htmlspecialchars($row["type_name"]) ?></td>

                                    <td><?= htmlspecialchars($row["subject"]) ?></td>

                                    <td><?= date("d M Y", strtotime($row["submission_date"])) ?></td>

                                    <td>

                                        <?php

                                        $status = $row["status_name"];

                                        $class = "badge-pending";

                                        if ($status == "Approved") $class = "badge-success";
                                        elseif ($status == "Rejected") $class = "badge-danger";
                                        elseif ($status == "Processing" || $status == "Under Review") $class = "badge-review";

                                        ?>

                                        <span class="badge <?= $class ?>">
                                            <?= htmlspecialchars($status) ?>
                                        </span>

                                    </td>

                                    <td>

                                        <?php if (in_array($status, ["New", "Processing", "Under Review"])): ?>

                                            <form method="POST" class="action-group">

                                                <input
                                                    type="hidden"
                                                    name="application_id"
                                                    value="<?= $row["application_id"] ?>">

                                                <button name="action" value="approve" class="accept-btn">

                                                    <i class="fa-solid fa-check"></i>

                                                </button>

                                                <button name="action" value="reject" class="reject-btn">

                                                    <i class="fa-solid fa-xmark"></i>

                                                </button>

                                                <button type="button" class="action-btn">

                                                    <i class="fa-solid fa-eye"></i>

                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <a href="view_application.php?id=<?= $row['application_id'] ?>" class="action-btn">
                                                <i class="fa-solid fa-eye"></i>
                                                View
                                            </a>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="7" style="text-align:center;padding:30px;">
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
