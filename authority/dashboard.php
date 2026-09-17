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

    <section class="welcome-banner">

        <div>
            <h2>Welcome, Authority 👋</h2>
            <p>Manage grievances, suggestions and applications from one place.</p>
        </div>

    </section>

    <section class="dashboard-cards">

        <div class="dashboard-card blue">
            <div class="card-icon">📢</div>
            <h3>Pending Grievances</h3>
            <p>12</p>
        </div>

        <div class="dashboard-card green">
            <div class="card-icon">💡</div>
            <h3>Suggestions</h3>
            <p>8</p>
        </div>

        <div class="dashboard-card orange">
            <div class="card-icon">📄</div>
            <h3>Applications</h3>
            <p>15</p>
        </div>

        <div class="dashboard-card red">
            <div class="card-icon">✔️</div>
            <h3>Resolved</h3>
            <p>56</p>
        </div>

    </section>

    <section class="bottom-grid">

        <div class="panel">

            <h3>Recent Activity</h3>

            <div class="activity-item">
                <span class="activity-dot"></span>
                <span>New grievance submitted.</span>
            </div>

            <div class="activity-item">
                <span class="activity-dot"></span>
                <span>Suggestion received.</span>
            </div>

            <div class="activity-item">
                <span class="activity-dot"></span>
                <span>Application waiting for approval.</span>
            </div>

        </div>

        <div class="panel">

            <h3>Quick Actions</h3>

            <div class="quick-actions">

                <button>View Grievances</button>
                <button>Review Suggestions</button>
                <button>Check Applications</button>

            </div>

        </div>

    </section>

</main>
    </div>

    <?php include "../includes/footer.php"; ?>

    <script src="../js/global.js"></script>
    <script src="../js/authority-dashboard.js"></script>

</body>

</html>