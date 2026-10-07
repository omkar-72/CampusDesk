<?php

session_start();

/*
|--------------------------------------------------------------------------
| CampusDesk - Admin Login
|--------------------------------------------------------------------------
| Uses the same visual structure as the normal CampusDesk login page.
| Admin authentication is still handled separately.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Already Logged-In Admin
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION["user_id"]) &&
    isset($_SESSION["role_name"]) &&
    $_SESSION["role_name"] === "ADMIN"
) {
    header("Location: dashboard.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| Login Error
|--------------------------------------------------------------------------
*/

$error = $_SESSION["admin_login_error"] ?? "";
unset($_SESSION["admin_login_error"]);


/*
|--------------------------------------------------------------------------
| Preserve Email
|--------------------------------------------------------------------------
*/

$email = $_SESSION["admin_login_email"] ?? "";
unset($_SESSION["admin_login_email"]);


/*
|--------------------------------------------------------------------------
| Safe Email
|--------------------------------------------------------------------------
*/

$emailSafe = htmlspecialchars($email, ENT_QUOTES, "UTF-8");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="CampusDesk Administration Login">

    <title>CampusDesk | Admin Login</title>

    <!-- Same CampusDesk login CSS -->
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/login.css">

    <!-- Tabler Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

</head>

<body>


    <!-- =========================================================
         TOP BAR
    ========================================================== -->

    <header class="navbar">

        <div class="brand">

            <span class="brand-logo">C</span>

            <span class="brand-name">
                CampusDesk
            </span>

        </div>


        <div class="status">

            <span class="dot"></span>

            Secure Portal &bull; Online

        </div>

    </header>



    <!-- =========================================================
         MAIN PART
    ========================================================== -->

    <main class="main">


        <!-- Dotted decorations -->

        <div class="dots dots-left"></div>

        <div class="dots dots-right"></div>


        <!-- Green waves at bottom -->

        <svg
            class="waves"
            viewBox="0 0 1440 320"
            preserveAspectRatio="none">

            <path
                d="M0,200 C240,100 480,280 720,210 C960,140 1200,100 1440,180 L1440,320 L0,320 Z">
            </path>

            <path
                d="M0,250 C300,170 560,300 840,240 C1100,185 1300,170 1440,230 L1440,320 L0,320 Z">
            </path>

        </svg>



        <!-- =====================================================
             LOGIN CONTAINER
        ====================================================== -->

        <div class="login-container">


            <div class="login-box rise">


                <!-- Admin Icon -->

                <div class="login-icon">

                    <i class="ti ti-shield-check"></i>

                </div>


                <!-- CampusDesk -->

                <h1>CampusDesk</h1>


                <!-- Login Type -->

                <p>Admin Login</p>


                <hr>



                <!-- =================================================
                     ERROR MESSAGE
                ================================================== -->

                <?php if ($error != "") { ?>

                    <div class="login-message">

                        <i class="ti ti-alert-circle"></i>

                        <?php echo htmlspecialchars($error); ?>

                    </div>

                <?php } ?>



                <!-- =================================================
                     LOGIN FORM
                ================================================== -->

                <form
                    action="../admin_actions/admin_login.php"
                    method="POST"
                    id="adminLoginForm">


                    <!-- Email -->

                    <div class="login-field">

                        <label for="email">
                            Email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo $emailSafe; ?>"
                            placeholder="Enter your email"
                            autocomplete="username"
                            required
                            autofocus>

                    </div>



                    <!-- Password -->

                    <div class="login-field">

                        <label for="password">
                            Password
                        </label>


                        <div class="login-password-box">

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required>


                            <button
                                type="button"
                                id="passwordToggle"
                                onclick="toggleAdminPassword()">

                                Show

                            </button>

                        </div>

                    </div>


                    <!--
                     Remember Me

                    <div class="admin-remember-wrapper">

                        <label class="admin-remember">

                            <input
                                type="checkbox"
                                name="remember"
                                value="1">

                            <span>Remember me</span>

                        </label>

                    </div>
                    -->


                    <!-- Login Button -->

                    <button
                        class="login-btn"
                        type="submit"
                        id="adminLoginButton">

                        Login

                        <i class="ti ti-arrow-right"></i>

                    </button>


                </form>



                <!-- =================================================
                     SECURITY INFORMATION
                ================================================== -->

                <div class="admin-security-note">

                    <i class="ti ti-shield-lock"></i>

                    <span>
                        Administration access is restricted to
                        authorized CampusDesk administrators.
                    </span>

                </div>



                <!-- =================================================
                     BACK
                ================================================== -->

                <div class="login-links">

                    <a href="../index.php">

                        <i class="ti ti-arrow-left"></i>

                        Back

                    </a>

                </div>


            </div>

        </div>

    </main>



    <!-- =========================================================
         FOOTER
    ========================================================== -->

    <footer class="footer">


        <div class="footer-left">

            <strong>CampusDesk</strong>

            <span class="footer-line"></span>

            <span>
                Student Grievance Management System
            </span>

        </div>


        <div class="footer-right">

            <i class="ti ti-lock"></i>

            Protected &bull; Confidential

        </div>


    </footer>



    <!-- =========================================================
         JAVASCRIPT
    ========================================================== -->

    <script src="../js/global.js"></script>

    <script>
        /*
        |--------------------------------------------------------------------------
        | Toggle Password
        |--------------------------------------------------------------------------
        */

        function toggleAdminPassword() {

            const password =
                document.getElementById("password");

            const button =
                document.getElementById("passwordToggle");


            if (!password || !button) {
                return;
            }


            if (password.type === "password") {

                password.type = "text";

                button.textContent = "Hide";

                button.setAttribute(
                    "aria-label",
                    "Hide password"
                );

            } else {

                password.type = "password";

                button.textContent = "Show";

                button.setAttribute(
                    "aria-label",
                    "Show password"
                );

            }

        }



        /*
        |--------------------------------------------------------------------------
        | Prevent Double Login Submission
        |--------------------------------------------------------------------------
        */

        const adminLoginForm =
            document.getElementById("adminLoginForm");

        const adminLoginButton =
            document.getElementById("adminLoginButton");


        if (adminLoginForm && adminLoginButton) {

            adminLoginForm.addEventListener(
                "submit",
                function(event) {

                    if (
                        adminLoginButton.dataset.submitted === "true"
                    ) {

                        event.preventDefault();

                        return;

                    }


                    adminLoginButton.dataset.submitted = "true";

                    adminLoginButton.disabled = true;

                    adminLoginButton.innerHTML =
                        '<i class="ti ti-loader-2"></i>' +
                        '<span>Signing in...</span>';

                }
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Remove Error Message When User Starts Typing
        |--------------------------------------------------------------------------
        */

        const emailInput =
            document.getElementById("email");

        const passwordInput =
            document.getElementById("password");


        function clearLoginError() {

            const alert =
                document.querySelector(".login-message");


            if (!alert) {
                return;
            }


            alert.style.transition =
                "opacity 0.25s ease";

            alert.style.opacity = "0";


            setTimeout(function() {

                if (alert.parentNode) {

                    alert.parentNode.removeChild(alert);

                }

            }, 250);

        }


        if (emailInput) {

            emailInput.addEventListener(
                "input",
                clearLoginError
            );

        }


        if (passwordInput) {

            passwordInput.addEventListener(
                "input",
                clearLoginError
            );

        }
    </script>


</body>

</html>
