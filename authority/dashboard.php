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

    <!-- Top Welcome Section -->

    <section class="welcome-banner">

        <div class="welcome-text">
            <h2>Good Morning, Authority 👋</h2>
            <p>Here's what's happening in your department today.</p>
        </div>

        <div class="welcome-date">
            <span><?php echo date("d M Y"); ?></span>
        </div>

    </section>

    <!-- Statistics Cards -->

    <section class="dashboard-cards">

        <div class="dashboard-card blue">
            <div class="card-top">
                <span class="card-icon">📢</span>
                <span class="card-number">12</span>
            </div>
            <h3>Pending Grievances</h3>
            <p>Needs immediate review</p>
        </div>

        <div class="dashboard-card green">
            <div class="card-top">
                <span class="card-icon">💡</span>
                <span class="card-number">8</span>
            </div>
            <h3>Suggestions</h3>
            <p>Waiting for decision</p>
        </div>

        <div class="dashboard-card orange">
            <div class="card-top">
                <span class="card-icon">📄</span>
                <span class="card-number">15</span>
            </div>
            <h3>Applications</h3>
            <p>Pending approval</p>
        </div>

        <div class="dashboard-card red">
            <div class="card-top">
                <span class="card-icon">✔</span>
                <span class="card-number">56</span>
            </div>
            <h3>Resolved Cases</h3>
            <p>Completed successfully</p>
        </div>

    </section>

    <!-- Bottom Section -->

    <section class="bottom-grid">

        <div class="panel">

            <div class="panel-header">
                <h3>Recent Activity</h3>
                <a href="#">View All</a>
            </div>

            <div class="activity-item">
                <div class="activity-icon">📢</div>
                <div>
                    <strong>New Grievance</strong>
                    <p>Library Issue • 2 min ago</p>
                </div>
            </div>

            <div class="activity-item">
                <div class="activity-icon">💡</div>
                <div>
                    <strong>Suggestion Received</strong>
                    <p>Wi-Fi Improvement • 15 min ago</p>
                </div>
            </div>

            <div class="activity-item">
                <div class="activity-icon">📄</div>
                <div>
                    <strong>Application Submitted</strong>
                    <p>Bonafide Certificate • 1 hour ago</p>
                </div>
            </div>

        </div>

        <div class="panel">

            <div class="panel-header">
                <h3>Quick Actions</h3>
            </div>

            <div class="quick-actions">

                <a href="grievances.php" class="quick-btn">
                    📢 Review Grievances
                </a>

                <a href="suggestions.php" class="quick-btn">
                    💡 Review Suggestions
                </a>

                <a href="applications.php" class="quick-btn">
                    📄 Check Applications
                </a>

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