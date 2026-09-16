<?php

session_start();

$role = $_GET["role"] ?? "Student";

$error = "";

if(isset($_GET["error"])){
    $error = "Invalid Email or Password.";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>CampusDesk | <?php echo $role; ?> Login</title>

<link rel="stylesheet" href="css/global.css">
<link rel="stylesheet" href="css/login.css">

</head>

<body>

<div class="login-container">

<div class="login-box">

<h1>CampusDesk</h1>

<p><?php echo $role; ?> Login</p>

<hr>

<?php if($error!=""){ ?>

<div class="login-message">
<?php echo $error; ?>
</div>

<?php } ?>

<form action="actions/login.php" method="POST">

<input
type="hidden"
name="role"
value="<?php echo $role; ?>">

<div class="login-field">

<label for="email">Email</label>

<input
type="email"
id="email"
name="email"
required>

</div>

<div class="login-field">

<label for="password">Password</label>

<div class="login-password-box">

<input
type="password"
id="password"
name="password"
required>

<button
type="button"
onclick="togglePassword()">

Show

</button>

</div>

</div>

<button class="login-btn" type="submit">

Login

</button>

</form>

<div class="login-links">

<a href="index.php">Back</a>

<?php if($role=="Student"){ ?>

<span>|</span>

<a href="register.php">Register</a>

<?php } ?>

</div>

</div>

</div>

<script src="js/global.js"></script>
<script src="js/login.js"></script>

</body>
</html>