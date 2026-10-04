<?php

session_start();

$role = $_GET["role"] ?? "Student";

$error = "";

if (isset($_GET["error"])) {
    $error = "Invalid Email or Password.";
}

// Safe version of the role, used when printing on the page
$roleSafe = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');

// Icon changes with the role
$roleIcon = ($role == "Student") ? "ti-school" : "ti-shield-check";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusDesk | <?php echo $roleSafe; ?> Login</title>

    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/login.css">

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

        <div class="status">
            <span class="dot"></span>
            Secure Portal &bull; Online
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

        <div class="login-container">

            <div class="login-box rise">

                <div class="login-icon">
                    <i class="ti <?php echo $roleIcon; ?>"></i>
                </div>

                <h1>CampusDesk</h1>

                <p><?php echo $roleSafe; ?> Login</p>

                <hr>

                <?php if ($error != "") { ?>

                    <div class="login-message">
                        <i class="ti ti-alert-circle"></i>
                        <?php echo $error; ?>
                    </div>

                <?php } ?>

                <form action="actions/login.php" method="POST">

                    <input
                        type="hidden"
                        name="role"
                        value="<?php echo $roleSafe; ?>">

                    <div class="login-field">

                        <label for="email">Email</label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            required>

                    </div>

                    <div class="login-field">

                        <label for="password">Password</label>

                        <div class="login-password-box">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required>

                            <button
                                type="button"
                                onclick="togglePassword()">

                                Show

                            </button>

                        </div>

                    </div>

                    <button class="login-btn" type="submit">

                        Login <i class="ti ti-arrow-right"></i>

                    </button>

                </form>

                <div class="login-links">

                    <a href="index.php">
                        <i class="ti ti-arrow-left"></i> Back
                    </a>

                    <?php if ($role == "Student") { ?>

                        <span>|</span>

                        <a href="register.php">Register</a>

                    <?php } ?>

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
    <script src="js/login.js"></script>

</body>

</html>
