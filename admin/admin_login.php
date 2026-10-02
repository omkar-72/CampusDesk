<?php
/*
|--------------------------------------------------------------------------
| CampusDesk - Admin Login
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

    <title>Admin Login - CampusDesk</title>

    <!-- Admin Login CSS Only -->
    <link
        rel="stylesheet"
        href="css/admin-login.css">

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

    <main class="admin-login-page">

        <div class="admin-login-container">

            <!-- =====================================================
                 LOGIN CARD
            ====================================================== -->

            <div class="admin-login-card">

                <!-- =================================================
                     BRAND
                ================================================== -->

                <div class="admin-login-brand">

                    <div class="admin-login-logo">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>

                    <h1>CampusDesk</h1>

                    <p>Administration</p>

                </div>


                <!-- =================================================
                     HEADING
                ================================================== -->

                <div class="admin-login-heading">

                    <h2>Admin Login</h2>

                    <p>
                        Sign in to access the CampusDesk
                        administration panel.
                    </p>

                </div>


                <!-- =================================================
                     ERROR MESSAGE
                ================================================== -->

                <?php if (!empty($error)): ?>

                    <div class="admin-login-alert admin-login-alert-error">

                        <span class="alert-icon">
                            <i class="fa-solid fa-circle-exclamation"></i>
                        </span>

                        <span class="alert-message">
                            <?= htmlspecialchars($error) ?>
                        </span>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     LOGIN FORM
                ================================================== -->

                <form
                    action="../admin_actions/admin_login.php"
                    method="POST"
                    class="admin-login-form"
                    autocomplete="on">

                    <!-- Email -->

                    <div class="admin-login-form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <div class="admin-login-input-wrapper">

                            <span class="admin-login-input-icon">
                                <i class="fa-solid fa-envelope"></i>
                            </span>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($email) ?>"
                                placeholder="Enter admin email"
                                autocomplete="username"
                                required
                                autofocus>

                        </div>

                    </div>


                    <!-- Password -->

                    <div class="admin-login-form-group">

                        <label for="password">
                            Password
                        </label>

                        <div class="admin-login-input-wrapper">

                            <span class="admin-login-input-icon">
                                <i class="fa-solid fa-lock"></i>
                            </span>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required>

                            <button
                                type="button"
                                class="admin-password-toggle"
                                onclick="toggleAdminPassword()"
                                aria-label="Show password">
                                <i
                                    class="fa-solid fa-eye"
                                    id="passwordToggleIcon"></i>
                            </button>

                        </div>

                    </div>


                    <!-- Login Options -->

                    <div class="admin-login-options">

                        <label class="admin-remember">

                            <input
                                type="checkbox"
                                name="remember"
                                value="1">

                            <span>
                                Remember me
                            </span>

                        </label>

                    </div>


                    <!-- Login Button -->

                    <button
                        type="submit"
                        class="admin-login-button"
                        id="adminLoginButton">

                        <i class="fa-solid fa-right-to-bracket"></i>

                        <span>Login</span>

                    </button>

                </form>


                <!-- =================================================
                     SECURITY INFORMATION
                ================================================== -->

                <div class="admin-security-info">

                    <div class="security-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>

                    <div class="security-content">

                        <strong>Administration Access</strong>

                        <p>
                            This area is restricted to authorized
                            CampusDesk administrators.
                        </p>

                    </div>

                </div>


                <!-- =================================================
                     BACK TO HOME
                ================================================== -->

                <div class="admin-login-footer">

                    <a href="../index.php">

                        <i class="fa-solid fa-arrow-left"></i>

                        Back to CampusDesk

                    </a>

                </div>

            </div>

        </div>

    </main>


    <!-- =========================================================
         ADMIN LOGIN JAVASCRIPT
    ========================================================== -->

    <script>
        /*
        |--------------------------------------------------------------------------
        | Toggle Password Visibility
        |--------------------------------------------------------------------------
        */

        function toggleAdminPassword() {

            const password =
                document.getElementById("password");

            const icon =
                document.getElementById("passwordToggleIcon");

            const button =
                document.querySelector(".admin-password-toggle");

            if (!password || !icon) {
                return;
            }

            if (password.type === "password") {

                password.type = "text";

                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");

                if (button) {
                    button.setAttribute(
                        "aria-label",
                        "Hide password"
                    );
                }

            } else {

                password.type = "password";

                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");

                if (button) {
                    button.setAttribute(
                        "aria-label",
                        "Show password"
                    );
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent Double Login Submission
        |--------------------------------------------------------------------------
        */

        document
            .querySelector(".admin-login-form")
            .addEventListener("submit", function(event) {

                const button =
                    document.getElementById(
                        "adminLoginButton"
                    );

                if (!button) {
                    return;
                }

                if (button.dataset.submitted === "true") {

                    event.preventDefault();

                    return;
                }

                button.dataset.submitted = "true";

                button.disabled = true;

                button.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i>' +
                    '<span>Signing in...</span>';
            });


        /*
        |--------------------------------------------------------------------------
        | Remove Error Message After User Starts Typing
        |--------------------------------------------------------------------------
        */

        const emailInput =
            document.getElementById("email");

        const passwordInput =
            document.getElementById("password");

        function clearLoginError() {

            const alert =
                document.querySelector(
                    ".admin-login-alert-error"
                );

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
