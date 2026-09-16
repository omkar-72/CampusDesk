<?php
include "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../register.php");
    exit();
}

// Get form data
$full_name = trim($_POST["full_name"]);
$prn = trim($_POST["prn"]);
$roll_no = trim($_POST["roll_no"]);
$email = trim($_POST["email"]);
$mobile = trim($_POST["mobile"]);
$department_id = $_POST["department_id"];
$course = trim($_POST["course"]);
$year = $_POST["year"];
$semester = $_POST["semester"];
$division = trim($_POST["division"]);
$address = trim($_POST["address"]);
$password = $_POST["password"];
$confirm_password = $_POST["confirm_password"];

// Check password
if ($password !== $confirm_password) {
    die("Passwords do not match.");
}

// Check duplicate email
$checkEmail = pg_query_params(
    $conn,
    "SELECT user_id FROM users WHERE email = $1",
    array($email)
);

if (pg_num_rows($checkEmail) > 0) {
    die("Email already exists.");
}

// Check duplicate PRN
$checkPrn = pg_query_params(
    $conn,
    "SELECT student_id FROM students WHERE prn = $1",
    array($prn)
);

if (pg_num_rows($checkPrn) > 0) {
    die("PRN already exists.");
}

// Get STUDENT role
$roleResult = pg_query_params(
    $conn,
    "SELECT role_id FROM roles WHERE role_name = $1 LIMIT 1",
    array("STUDENT")
);

if (!$roleResult || pg_num_rows($roleResult) == 0) {
    die("STUDENT role not found.");
}

$role = pg_fetch_assoc($roleResult);
$role_id = $role["role_id"];

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Start transaction
pg_query($conn, "BEGIN");

// Insert into users
$userQuery = "
INSERT INTO users
(role_id, email, password, mobile_number)
VALUES
($1, $2, $3, $4)
RETURNING user_id";

$userResult = pg_query_params(
    $conn,
    $userQuery,
    array(
        $role_id,
        $email,
        $hashedPassword,
        $mobile
    )
);

if (!$userResult) {
    pg_query($conn, "ROLLBACK");
    die("User Insert Error: " . pg_last_error($conn));
}

$user = pg_fetch_assoc($userResult);
$user_id = $user["user_id"];

// Insert into students
$studentQuery = "
INSERT INTO students
(
user_id,
department_id,
full_name,
prn,
roll_no,
course,
year,
semester,
division,
address
)
VALUES
($1,$2,$3,$4,$5,$6,$7,$8,$9,$10)";

$studentResult = pg_query_params(
    $conn,
    $studentQuery,
    array(
        $user_id,
        $department_id,
        $full_name,
        $prn,
        $roll_no,
        $course,
        $year,
        $semester,
        $division,
        $address
    )
);

if (!$studentResult) {
    pg_query($conn, "ROLLBACK");
    die("Student Insert Error: " . pg_last_error($conn));
}

// Commit transaction
pg_query($conn, "COMMIT");

// Success
header("Location: ../login.php?role=Student&registered=1");
exit();
?>