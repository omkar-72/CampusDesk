<?php
$role = $_SESSION["role_name"] ?? "";
$currentPage = basename($_SERVER["PHP_SELF"]);
?>

<nav class="navbar">

    <div class="navbar-logo">
        <div class="logo-circle">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>

        <div class="logo-text">
            <h2>CampusDesk</h2>
            <span>College ERP</span>
        </div>
    </div>

    <ul class="navbar-menu">

        <?php if($role=="AUTHORITY"){ ?>

            <li class="<?php if($currentPage=="dashboard.php") echo "active"; ?>">
                <a href="../authority/dashboard.php">
                    <i class="fa-solid fa-house"></i>
                    Dashboard
                </a>
            </li>

            <li class="<?php if($currentPage=="grievances.php") echo "active"; ?>">
                <a href="../authority/grievances.php">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    Grievances
                </a>
            </li>

            <li class="<?php if($currentPage=="suggestions.php") echo "active"; ?>">
                <a href="../authority/suggestions.php">
                    <i class="fa-solid fa-lightbulb"></i>
                    Suggestions
                </a>
            </li>

            <li class="<?php if($currentPage=="applications.php") echo "active"; ?>">
                <a href="../authority/applications.php">
                    <i class="fa-solid fa-file-lines"></i>
                    Applications
                </a>
            </li>

            <li class="<?php if($currentPage=="profile.php") echo "active"; ?>">
                <a href="../authority/profile.php">
                    <i class="fa-solid fa-user"></i>
                    Profile
                </a>
            </li>

        <?php } ?>

        <?php if($role=="STUDENT"){ ?>

            <li class="<?php if($currentPage=="dashboard.php") echo "active"; ?>">
                <a href="../student/dashboard.php">
                    <i class="fa-solid fa-house"></i>
                    Dashboard
                </a>
            </li>

            <li class="<?php if($currentPage=="grievances.php") echo "active"; ?>">
                <a href="../student/grievances.php">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    Grievances
                </a>
            </li>

            <li class="<?php if($currentPage=="suggestions.php") echo "active"; ?>">
                <a href="../student/suggestions.php">
                    <i class="fa-solid fa-lightbulb"></i>
                    Suggestions
                </a>
            </li>

            <li class="<?php if($currentPage=="applications.php") echo "active"; ?>">
                <a href="../student/applications.php">
                    <i class="fa-solid fa-file-lines"></i>
                    Applications
                </a>
            </li>

            <li class="<?php if($currentPage=="profile.php") echo "active"; ?>">
                <a href="../student/profile.php">
                    <i class="fa-solid fa-user"></i>
                    Profile
                </a>
            </li>

        <?php } ?>

        <?php if($role=="ADMIN"){ ?>

            <li class="<?php if($currentPage=="dashboard.php") echo "active"; ?>">
                <a href="../admin/dashboard.php">
                    <i class="fa-solid fa-house"></i>
                    Dashboard
                </a>
            </li>

            <li class="<?php if($currentPage=="students.php") echo "active"; ?>">
                <a href="../admin/students.php">
                    <i class="fa-solid fa-users"></i>
                    Students
                </a>
            </li>

            <li class="<?php if($currentPage=="authorities.php") echo "active"; ?>">
                <a href="../admin/authorities.php">
                    <i class="fa-solid fa-user-tie"></i>
                    Authorities
                </a>
            </li>

            <li class="<?php if($currentPage=="departments.php") echo "active"; ?>">
                <a href="../admin/departments.php">
                    <i class="fa-solid fa-building"></i>
                    Departments
                </a>
            </li>

        <?php } ?>

        <li class="logout">
            <a href="../logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>
        </li>

    </ul>

</nav>