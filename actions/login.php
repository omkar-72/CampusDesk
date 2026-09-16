<?php
session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../index.php");
    exit();
}

$email = trim($_POST["email"]);
$password = $_POST["password"];
$selectedRole = strtoupper(trim($_POST["role"])); // Student → STUDENT

$query = "
SELECT
    u.user_id,
    u.email,
    u.password,
    r.role_name
FROM users u
JOIN roles r ON u.role_id = r.role_id
WHERE u.email = $1
AND u.account_status = TRUE
";

$result = pg_query_params($conn, $query, array($email));

if ($result && pg_num_rows($result) == 1) {

    $user = pg_fetch_assoc($result);

    if (password_verify($password, $user["password"])) {

        if ($user["role_name"] !== $selectedRole) {
            header("Location: ../login.php?role=" . urlencode($_POST["role"]) . "&error=1");
            exit();
        }

        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["role_name"] = $user["role_name"];

        pg_query_params(
            $conn,
            "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = $1",
            array($user["user_id"])
        );

        switch ($user["role_name"]) {

            case "STUDENT":
                header("Location: ../student/dashboard.php");
                break;

            case "AUTHORITY":
                header("Location: ../authority/dashboard.php");
                break;

            case "ADMIN":
                header("Location: ../admin/dashboard.php");
                break;
        }

        exit();
    }
}

header("Location: ../login.php?role=" . urlencode($_POST["role"]) . "&error=1");
exit();
?>