<?php
session_start();

/* Temporary session for frontend testing */
if (!isset($_SESSION["role_name"])) {
    $_SESSION["role_name"] = "AUTHORITY";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusDesk | Authority Dashboard</title>

    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/authority-dashboard.css">

</head>

<body>

    <?php include "../includes/header.php"; ?>

    <div class="dashboard-layout">

        <?php include "../includes/navbar.php"; ?>

        <main class="dashboard-content">

            <h2>Dashboard</h2>

            <div class="dashboard-cards">

                <div class="dashboard-card">
                    <h3>Pending Grievances</h3>
                    <p>12</p>
                </div>

                <div class="dashboard-card">
                    <h3>Pending Suggestions</h3>
                    <p>8</p>
                </div>

                <div class="dashboard-card">
                    <h3>Pending Applications</h3>
                    <p>15</p>
                </div>

            </div>

            <div class="recent-section">

                <h3>Recent Activity</h3>

                <hr>

                <p>New grievance submitted.</p>
                <p>Application waiting for review.</p>
                <p>Suggestion received.</p>

            </div>

        </main>

    </div>

    <?php include "../includes/footer.php"; ?>

    <script src="../js/global.js"></script>
    <script src="../js/authority-dashboard.js"></script>

</body>

</html>