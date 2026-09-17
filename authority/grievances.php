<?php
session_start();

if (!isset($_SESSION["role_name"])) {
    $_SESSION["role_name"] = "AUTHORITY";
}
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

    <!-- CSS -->
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/authority-dashboard.css">
    <link rel="stylesheet" href="../css/authority-table.css">

</head>

<body>

<?php include "../includes/navbar.php"; ?>
<?php include "../includes/header.php"; ?>

<div class="dashboard-layout">

    <main class="dashboard-content">

        <div class="page-header">

            <div>
                <h2>Grievance Management</h2>
                <p>Review and manage student grievances.</p>
            </div>

        </div>

        <div class="filter-bar">

            <input type="text" placeholder="Search grievances...">

            <select>
                <option>All Status</option>
                <option>Pending</option>
                <option>In Progress</option>
                <option>Resolved</option>
            </select>

        </div>

        <div class="table-card">

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                    <tr>
                        <td>GR001</td>
                        <td>Omkar Gavane</td>
                        <td>Library Issue</td>
                        <td><span class="badge badge-pending">Pending</span></td>
                        <td>12 Sep 2026</td>
                        <td>
                            <button class="action-btn">View</button>
                        </td>
                    </tr>

                    <tr>
                        <td>GR002</td>
                        <td>Aditi Sharma</td>
                        <td>Wi-Fi Problem</td>
                        <td><span class="badge badge-progress">In Progress</span></td>
                        <td>11 Sep 2026</td>
                        <td>
                            <button class="action-btn">View</button>
                        </td>
                    </tr>

                    <tr>
                        <td>GR003</td>
                        <td>Rahul Patil</td>
                        <td>Classroom Cleaning</td>
                        <td><span class="badge badge-success">Resolved</span></td>
                        <td>10 Sep 2026</td>
                        <td>
                            <button class="action-btn">View</button>
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>

    </main>

</div>

<?php include "../includes/footer.php"; ?>

</body>

</html>