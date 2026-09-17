<?php
$role = $_SESSION["role_name"] ?? "";
?>

<nav class="navbar">

    <div class="navbar-title">
        <h2>CampusDesk</h2>
    </div>

    <ul class="navbar-menu">

        <?php if ($role == "STUDENT") { ?>

            <li><a href="../student/dashboard.php">Dashboard</a></li>
            <li><a href="../student/grievances.php">Grievances</a></li>
            <li><a href="../student/suggestions.php">Suggestions</a></li>
            <li><a href="../student/applications.php">Applications</a></li>
            <li><a href="../student/profile.php">Profile</a></li>

        <?php } ?>

        <?php if ($role == "AUTHORITY") { ?>

            <li><a href="../authority/dashboard.php">Dashboard</a></li>
            <li><a href="../authority/grievances.php">Grievances</a></li>
            <li><a href="../authority/suggestions.php">Suggestions</a></li>
            <li><a href="../authority/applications.php">Applications</a></li>
            <li><a href="../authority/profile.php">Profile</a></li>

        <?php } ?>

        <?php if ($role == "ADMIN") { ?>

            <li><a href="../admin/dashboard.php">Dashboard</a></li>
            <li><a href="../admin/students.php">Students</a></li>
            <li><a href="../admin/authorities.php">Authorities</a></li>
            <li><a href="../admin/departments.php">Departments</a></li>
            <li><a href="../admin/grievances.php">Grievances</a></li>
            <li><a href="../admin/suggestions.php">Suggestions</a></li>
            <li><a href="../admin/applications.php">Applications</a></li>
            <li><a href="../admin/profile.php">Profile</a></li>

        <?php } ?>

        <li><a href="../logout.php">Logout</a></li>

    </ul>

</nav>