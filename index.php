<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusDesk</title>

    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/home.css">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
</head>

<body>

    <!-- ========== TOP BAR ========== -->
    <header class="navbar">

        <div class="brand">
            <span class="brand-logo">C</span>
            <span class="brand-name">CampusDesk</span>
        </div>

        <div class="nav-right">

            <a href="about.php" class="about-link">
                <i class="fa-solid fa-circle-info"></i>
                About Us
            </a>


            <a href="contact.php" class="about-link">
                <i class="fa-solid fa-circle-info"></i>
                Contact Us
            </a>

            <!-- Admin Login -->
            <a href="admin/admin_login.php" class="admin-link">
                <i class="ti ti-user-shield"></i> Admin Login
            </a>

            <div class="status">
                <span class="dot"></span>
                Secure Portal &bull; Online
            </div>

        </div>

    </header>

    <!-- ========== MAIN PART ========== -->
    <main class="main">

        <!-- Dotted decorations -->
        <div class="dots dots-left"></div>
        <div class="dots dots-right"></div>

        <!-- Green waves at the bottom -->
        <svg class="waves" viewBox="0 0 1440 320" preserveAspectRatio="none">
            <path d="M0,200 C240,100 480,280 720,210 C960,140 1200,100 1440,180 L1440,320 L0,320 Z"></path>
            <path d="M0,250 C300,170 560,300 840,240 C1100,185 1300,170 1440,230 L1440,320 L0,320 Z"></path>
        </svg>

        <div class="content">

            <h1 class="title rise">CampusDesk</h1>
            <p class="subtitle rise" style="--d: 0.1s;">Student Grievance Management System</p>

            <div class="divider"></div>

            <h2 class="heading rise" style="--d: 0.2s;">Select Your Role</h2>

            <!-- Role cards -->
            <div class="roles">

                <!-- Student -->
                <div class="role role-student rise" style="--d: 0.3s;">
                    <i class="ti ti-user role-icon"></i>
                    <h3 class="role-name">Student</h3>
                    <p class="role-text">Register and Login</p>
                    <a href="login.php?role=Student" class="btn btn-student">
                        Continue <i class="ti ti-arrow-right"></i>
                    </a>
                </div>

                <!-- Authority -->
                <div class="role role-authority rise" style="--d: 0.42s;">
                    <i class="ti ti-shield-check role-icon"></i>
                    <h3 class="role-name">Authority</h3>
                    <p class="role-text">Login Only</p>
                    <a href="login.php?role=Authority" class="btn btn-authority">
                        Continue <i class="ti ti-arrow-right"></i>
                    </a>
                </div>

            </div>

            <!-- Info cards -->
            <div class="info-grid">

                <div class="info rise" style="--d: 0.6s;">
                    <div class="info-icon"><i class="ti ti-file-plus"></i></div>
                    <div>
                        <h4 class="info-title">Submit</h4>
                        <p class="info-text">Raise grievances, suggestions and applications</p>
                    </div>
                </div>

                <div class="info rise" style="--d: 0.72s;">
                    <div class="info-icon"><i class="ti ti-search"></i></div>
                    <div>
                        <h4 class="info-title">Track</h4>
                        <p class="info-text">View status and receive updates</p>
                    </div>
                </div>

                <div class="info rise" style="--d: 0.84s;">
                    <div class="info-icon"><i class="ti ti-settings"></i></div>
                    <div>
                        <h4 class="info-title">Resolve</h4>
                        <p class="info-text">Get official replies and resolution documents</p>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <!-- ========== FOOTER ========== -->
    <footer class="footer">

        <div class="footer-left">
            <strong>CampusDesk</strong>
            <span class="footer-line"></span>
            <span>Student Grievance Management System</span>
        </div>

        <div class="footer-right">
            <i class="ti ti-lock"></i>
            Protected &bull; Confidential
        </div>

    </footer>

    <script src="js/global.js"></script>
    <script src="js/home.js"></script>

</body>

</html>
