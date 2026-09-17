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

    <title>CampusDesk | Authority Dashboard</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/authority-dashboard.css">

</head>

<body>

<?php include "../includes/navbar.php"; ?>
<?php include "../includes/header.php"; ?>

<div class="dashboard-layout">

    <main class="dashboard-content">

        <!-- Welcome Banner -->

        <section class="welcome-banner">

            <div class="welcome-text">
                <h2>Good Morning, Authority 👋</h2>
                <p>Here's what's happening in your department today.</p>
            </div>

            <div class="welcome-date">
                <?php echo date("d M Y"); ?>
            </div>

        </section>

        <!-- Statistics -->

        <section class="stats-grid">

            <div class="stat-box blue">
                <div class="stat-header">
                    <span>Grievances</span>
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <h2>156</h2>
            </div>

            <div class="stat-box purple">
                <div class="stat-header">
                    <span>Suggestions</span>
                    <i class="fa-solid fa-lightbulb"></i>
                </div>
                <h2>48</h2>
            </div>

            <div class="stat-box orange">
                <div class="stat-header">
                    <span>Applications</span>
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <h2>72</h2>
            </div>

            <div class="stat-box red">
                <div class="stat-header">
                    <span>Pending</span>
                    <i class="fa-solid fa-clock"></i>
                </div>
                <h2>23</h2>
            </div>

            <div class="stat-box teal">
                <div class="stat-header">
                    <span>New Grievances</span>
                    <i class="fa-solid fa-bell"></i>
                </div>
                <h2>12</h2>
            </div>

            <div class="stat-box green">
                <div class="stat-header">
                    <span>New Suggestions</span>
                    <i class="fa-solid fa-star"></i>
                </div>
                <h2>05</h2>
            </div>

            <div class="stat-box cyan">
                <div class="stat-header">
                    <span>New Applications</span>
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <h2>08</h2>
            </div>

            <div class="stat-box success">
                <div class="stat-header">
                    <span>Resolved Today</span>
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h2>07</h2>
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
                    <div class="activity-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
                    <div>
                        <strong>New Grievance</strong>
                        <p>Library Issue • 2 min ago</p>
                    </div>
                </div>

                <div class="activity-item">
                    <div class="activity-icon"><i class="fa-solid fa-lightbulb"></i></div>
                    <div>
                        <strong>Suggestion Received</strong>
                        <p>Wi-Fi Improvement • 15 min ago</p>
                    </div>
                </div>

                <div class="activity-item">
                    <div class="activity-icon"><i class="fa-solid fa-file-lines"></i></div>
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
                        <i class="fa-solid fa-circle-exclamation"></i>
                        Review Grievances
                    </a>

                    <a href="suggestions.php" class="quick-btn">
                        <i class="fa-solid fa-lightbulb"></i>
                        Review Suggestions
                    </a>

                    <a href="applications.php" class="quick-btn">
                        <i class="fa-solid fa-file-lines"></i>
                        Check Applications
                    </a>

                </div>

            </div>

        </section>

    </main>

</div>

<?php include "../includes/footer.php"; ?>

</body>

</html>