<?php
include "config/database.php";

// Load departments
$departmentQuery = "SELECT department_id, department_name
                    FROM departments
                    WHERE status = TRUE
                    ORDER BY department_name";

$departments = pg_query($conn, $departmentQuery);

$message = "";

if (isset($_GET["error"])) {
    $message = "Registration failed. Please try again.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusDesk | Student Register</title>

    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/register.css">

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

    <!-- Dotted decorations -->
    <div class="dots dots-left"></div>
    <div class="dots dots-right"></div>

    <!-- Green waves at the bottom -->
    <svg class="waves" viewBox="0 0 1440 320" preserveAspectRatio="none">
        <path d="M0,200 C240,100 480,280 720,210 C960,140 1200,100 1440,180 L1440,320 L0,320 Z"></path>
        <path d="M0,250 C300,170 560,300 840,240 C1100,185 1300,170 1440,230 L1440,320 L0,320 Z"></path>
    </svg>

    <!-- ========== MAIN PART ========== -->
    <main class="main">

        <div class="register-container">

            <div class="register-box rise">

                <div class="register-header">

                    <div class="register-icon">
                        <i class="ti ti-user-plus"></i>
                    </div>

                    <h1>CampusDesk</h1>
                    <p>Student Registration</p>

                </div>

                <hr>

                <?php if ($message != "") { ?>
                    <div class="register-message">
                        <i class="ti ti-alert-circle"></i>
                        <?php echo $message; ?>
                    </div>
                <?php } ?>

                <form action="actions/register.php" method="POST" onsubmit="return validateRegister()">

                    <div class="register-field">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
                    </div>

                    <div class="register-row">

                        <div class="register-field">
                            <label for="prn">PRN</label>
                            <input type="text" id="prn" name="prn" placeholder="Enter your PRN" required>
                        </div>

                        <div class="register-field">
                            <label for="roll_no">Roll Number</label>
                            <input type="text" id="roll_no" name="roll_no" placeholder="Enter your roll number">
                        </div>

                    </div>

                    <div class="register-row">

                        <div class="register-field">
                            <label for="email">College Email</label>
                            <input type="email" id="email" name="email" placeholder="name@college.com" required>
                        </div>

                        <div class="register-field">
                            <label for="mobile">Mobile Number</label>
                            <input type="text" id="mobile" name="mobile" placeholder="Enter mobile number">
                        </div>

                    </div>

                    <div class="register-field">

                        <label for="department">Department</label>

                        <select id="department" name="department_id" required>

                            <option value="">Select Department</option>

                            <?php while ($row = pg_fetch_assoc($departments)) { ?>

                                <option value="<?php echo $row["department_id"]; ?>">
                                    <?php echo htmlspecialchars($row["department_name"]); ?>
                                </option>

                            <?php } ?>

                        </select>

                    </div>

                    <div class="register-row">

                        <div class="register-field">
                            <label for="course">Course</label>
                            <input type="text" id="course" name="course" placeholder="Example: B.Sc. Computer Science">
                        </div>

                        <div class="register-field">
                            <label for="division">Division</label>
                            <input type="text" id="division" name="division" placeholder="Example: A">
                        </div>

                    </div>

                    <div class="register-row">

                        <div class="register-field">
                            <label for="year">Year</label>
                            <input type="number" id="year" name="year" min="1" max="4" placeholder="1 to 4">
                        </div>

                        <div class="register-field">
                            <label for="semester">Semester</label>
                            <input type="number" id="semester" name="semester" min="1" max="8" placeholder="1 to 8">
                        </div>

                    </div>

                    <div class="register-field">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3" placeholder="Enter your address"></textarea>
                    </div>

                    <div class="register-row">

                        <div class="register-field">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Minimum 6 characters" required>
                        </div>

                        <div class="register-field">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                        </div>

                    </div>

                    <div class="register-checkbox">
                        <input type="checkbox" id="terms" required>
                        <label for="terms">I agree to the Terms &amp; Conditions.</label>
                    </div>

                    <button type="submit" class="register-btn">
                        Register <i class="ti ti-arrow-right"></i>
                    </button>

                </form>

                <div class="register-links">
                    <a href="login.php?role=Student">
                        <i class="ti ti-arrow-left"></i> Back to Login
                    </a>
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
    <script src="js/register.js"></script>

</body>

</html>
