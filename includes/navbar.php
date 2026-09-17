<?php

$role = $_SESSION["role_name"] ?? "";
$currentPage = basename($_SERVER["PHP_SELF"]);

?>

<nav class="navbar">

    <div class="navbar-title">
        <h2>CampusDesk</h2>
    </div>

    <ul class="navbar-menu">

        <?php if($role=="STUDENT"){ ?>

            <li class="<?php if($currentPage=="dashboard.php") echo "active"; ?>">
                <a href="../student/dashboard.php">Dashboard</a>
            </li>

            <li class="<?php if($currentPage=="grievances.php") echo "active"; ?>">
                <a href="../student/grievances.php">Grievances</a>
            </li>

            <li class="<?php if($currentPage=="suggestions.php") echo "active"; ?>">
                <a href="../student/suggestions.php">Suggestions</a>
            </li>

            <li class="<?php if($currentPage=="applications.php") echo "active"; ?>">
                <a href="../student/applications.php">Applications</a>
            </li>

            <li class="<?php if($currentPage=="profile.php") echo "active"; ?>">
                <a href="../student/profile.php">Profile</a>
            </li>

        <?php } ?>

        <?php if($role=="AUTHORITY"){ ?>

            <li class="<?php if($currentPage=="dashboard.php") echo "active"; ?>">
                <a href="../authority/dashboard.php">Dashboard</a>
            </li>

            <li class="<?php if($currentPage=="grievances.php") echo "active"; ?>">
                <a href="../authority/grievances.php">Grievances</a>
            </li>

            <li class="<?php if($currentPage=="suggestions.php") echo "active"; ?>">
                <a href="../authority/suggestions.php">Suggestions</a>
            </li>

            <li class="<?php if($currentPage=="applications.php") echo "active"; ?>">
                <a href="../authority/applications.php">Applications</a>
            </li>

            <li class="<?php if($currentPage=="profile.php") echo "active"; ?>">
                <a href="../authority/profile.php">Profile</a>
            </li>

        <?php } ?>

        <?php if($role=="ADMIN"){ ?>

            <li class="<?php if($currentPage=="dashboard.php") echo "active"; ?>">
                <a href="../admin/dashboard.php">Dashboard</a>
            </li>

            <li class="<?php if($currentPage=="students.php") echo "active"; ?>">
                <a href="../admin/students.php">Students</a>
            </li>

            <li class="<?php if($currentPage=="authorities.php") echo "active"; ?>">
                <a href="../admin/authorities.php">Authorities</a>
            </li>

            <li class="<?php if($currentPage=="departments.php") echo "active"; ?>">
                <a href="../admin/departments.php">Departments</a>
            </li>

            <li class="<?php if($currentPage=="grievances.php") echo "active"; ?>">
                <a href="../admin/grievances.php">Grievances</a>
            </li>

            <li class="<?php if($currentPage=="suggestions.php") echo "active"; ?>">
                <a href="../admin/suggestions.php">Suggestions</a>
            </li>

            <li class="<?php if($currentPage=="applications.php") echo "active"; ?>">
                <a href="../admin/applications.php">Applications</a>
            </li>

            <li class="<?php if($currentPage=="profile.php") echo "active"; ?>">
                <a href="../admin/profile.php">Profile</a>
            </li>

        <?php } ?>

        <li>
            <a href="../logout.php">Logout</a>
        </li>

    </ul>

</nav>