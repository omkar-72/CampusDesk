<?php
include "config/database.php";

// Load departments
$departmentQuery = "SELECT department_id, department_name
                    FROM departments
                    WHERE status = TRUE
                    ORDER BY department_name";

$departments = pg_query($conn, $departmentQuery);

$message = "";

if(isset($_GET["error"])){
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
</head>

<body>

<div class="register-container">

    <div class="register-box">

        <div class="register-header">
            <h1>CampusDesk</h1>
            <p>Student Registration</p>
        </div>

        <hr>

        <?php if($message!=""){ ?>
            <div class="register-message"><?php echo $message; ?></div>
        <?php } ?>

        <form action="actions/register.php" method="POST" onsubmit="return validateRegister()">

            <div class="register-field">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>

            <div class="register-row">

                <div class="register-field">
                    <label for="prn">PRN</label>
                    <input type="text" id="prn" name="prn" required>
                </div>

                <div class="register-field">
                    <label for="roll_no">Roll Number</label>
                    <input type="text" id="roll_no" name="roll_no">
                </div>

            </div>

            <div class="register-field">
                <label for="email">College Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="register-field">
                <label for="mobile">Mobile Number</label>
                <input type="text" id="mobile" name="mobile">
            </div>

            <div class="register-field">

                <label for="department">Department</label>

                <select id="department" name="department_id" required>

                    <option value="">Select Department</option>

                    <?php while($row = pg_fetch_assoc($departments)){ ?>

                    <option value="<?php echo $row["department_id"]; ?>">
                        <?php echo $row["department_name"]; ?>
                    </option>

                    <?php } ?>

                </select>

            </div>

            <div class="register-row">

                <div class="register-field">
                    <label for="course">Course</label>
                    <input type="text" id="course" name="course">
                </div>

                <div class="register-field">
                    <label for="division">Division</label>
                    <input type="text" id="division" name="division">
                </div>

            </div>

            <div class="register-row">

                <div class="register-field">
                    <label for="year">Year</label>
                    <input type="number" id="year" name="year" min="1" max="4">
                </div>

                <div class="register-field">
                    <label for="semester">Semester</label>
                    <input type="number" id="semester" name="semester" min="1" max="8">
                </div>

            </div>

            <div class="register-field">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"></textarea>
            </div>

            <div class="register-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="register-field">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <div class="register-checkbox">
                <input type="checkbox" id="terms" required>
                <label for="terms">I agree to the Terms & Conditions.</label>
            </div>

            <button type="submit" class="register-btn">
                Register
            </button>

        </form>

        <div class="register-links">
            <a href="login.php?role=Student">Back to Login</a>
        </div>

    </div>

</div>

<script src="js/global.js"></script>
<script src="js/register.js"></script>

</body>
</html>