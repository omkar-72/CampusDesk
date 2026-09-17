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
    <title>CampusDesk | Applications</title>

    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/authority-dashboard.css">
    <link rel="stylesheet" href="../css/authority-table.css">
</head>

<body>

    <?php include "../includes/header.php"; ?>

    <div class="dashboard-layout">

        <?php include "../includes/navbar.php"; ?>

        <main class="dashboard-content">

            <h2>Applications</h2>

            <div class="table-container">

                <table>

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <tr>
                            <td>AP001</td>
                            <td>Omkar Gavane</td>
                            <td>Bonafide Certificate</td>
                            <td>Pending</td>
                            <td>12-09-2026</td>
                            <td><button>View</button></td>
                        </tr>

                        <tr>
                            <td>AP002</td>
                            <td>Aditi Sharma</td>
                            <td>Leave Application</td>
                            <td>Under Review</td>
                            <td>11-09-2026</td>
                            <td><button>View</button></td>
                        </tr>

                        <tr>
                            <td>AP003</td>
                            <td>Rahul Patil</td>
                            <td>Transfer Certificate</td>
                            <td>Approved</td>
                            <td>10-09-2026</td>
                            <td><button>View</button></td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </main>

    </div>

    <?php include "../includes/footer.php"; ?>

</body>

</html>