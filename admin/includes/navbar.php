<?php
/*
 * =========================================================
 * CampusDesk - Admin Navigation
 * =========================================================
 *
 * Admin-specific navigation.
 *
 * This file is intentionally separate from:
 * ../includes/navbar.php
 *
 * Student and Authority navigation must remain unchanged.
 * =========================================================
 */

$current_page = basename($_SERVER["PHP_SELF"]);
?>

<!-- =========================
     ADMIN SIDEBAR
========================== -->

<aside class="admin-sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">

        <h2>CampusDesk</h2>

        <span>Administration</span>

    </div>


    <!-- Navigation -->
    <nav class="sidebar-nav">

        <!-- Dashboard -->
        <a
            href="dashboard.php"
            class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <span class="nav-icon">
                <i class="fa-solid fa-house"></i>
            </span>

            <span class="nav-text">
                Dashboard
            </span>
        </a>


        <!-- Users -->
        <a
            href="users.php"
            class="nav-item <?= $current_page === 'users.php' ? 'active' : '' ?>">
            <span class="nav-icon">
                <i class="fa-solid fa-users"></i>
            </span>

            <span class="nav-text">
                Users
            </span>
        </a>


        <!-- Categories -->
        <a
            href="categories.php"
            class="nav-item <?= $current_page === 'categories.php' ? 'active' : '' ?>">
            <span class="nav-icon">
                <i class="fa-solid fa-layer-group"></i>
            </span>

            <span class="nav-text">
                Categories
            </span>
        </a>

        <!-- Departments -->
        <a
            href="departments.php"
            class="nav-item <?= $current_page === 'departments.php' ? 'active' : '' ?>">
            <span class="nav-icon">
                <i class="fa-solid fa-building"></i>
            </span>

            <span class="nav-text">
                Departments
            </span>
        </a>


        <!-- Reports -->
        <a
            href="reports.php"
            class="nav-item <?= $current_page === 'reports.php' ? 'active' : '' ?>">
            <span class="nav-icon">
                <i class="fa-solid fa-chart-column"></i>
            </span>

            <span class="nav-text">
                Reports & Analysis
            </span>
        </a>


        <!-- Audit Logs -->
        <a
            href="audit_logs.php"
            class="nav-item <?= $current_page === 'audit_logs.php' ? 'active' : '' ?>">
            <span class="nav-icon">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </span>

            <span class="nav-text">
                Audit Logs
            </span>
        </a>


        <!-- Profile -->
        <a
            href="profile.php"
            class="nav-item <?= $current_page === 'profile.php' ? 'active' : '' ?>">
            <span class="nav-icon">
                <i class="fa-solid fa-user"></i>
            </span>

            <span class="nav-text">
                Profile
            </span>
        </a>

    </nav>


    <!-- Logout -->
    <div class="sidebar-bottom">

        <a
            href="../logout.php"
            class="nav-item logout-item"
            onclick="return confirm('Are you sure you want to logout?');">

            <span class="nav-icon">
                <i class="fa-solid fa-right-from-bracket"></i>
            </span>

            <span class="nav-text">
                Logout
            </span>

        </a>

    </div>

</aside>
