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
    <title>CampusDesk | Authority Profile</title>

    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/authority-dashboard.css">
    <link rel="stylesheet" href="../css/authority-profile.css">

</head>

<body>

    <?php include "../includes/header.php"; ?>

    <div class="dashboard-layout">

        <?php include "../includes/navbar.php"; ?>

        <main class="dashboard-content">

            <h2>My Profile</h2>

            <div class="profile-box">

                <div class="profile-photo">
                    <div class="photo-circle">A</div>
                </div>

                <div class="profile-details">

                    <div class="detail-row">
                        <label>Name</label>
                        <p>Dr. Anil Patil</p>
                    </div>

                    <div class="detail-row">
                        <label>Email</label>
                        <p>anil.patil@campusdesk.com</p>
                    </div>

                    <div class="detail-row">
                        <label>Department</label>
                        <p>Computer Science</p>
                    </div>

                    <div class="detail-row">
                        <label>Designation</label>
                        <p>Head of Department</p>
                    </div>

                    <div class="detail-row">
                        <label>Mobile</label>
                        <p>9876543210</p>
                    </div>

                </div>

            </div>

        </main>

    </div>

    <?php include "../includes/footer.php"; ?>

</body>

</html>
