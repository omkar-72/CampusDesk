<?php
session_start();

if (!isset($_SESSION["role_name"])) {
    $_SESSION["role_name"] = "AUTHORITY";
}

$name = $_SESSION["user_name"] ?? "Authority";
$email = $_SESSION["email"] ?? "authority@campusdesk.com";
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusDesk | Authority Profile</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/authority-dashboard.css">
    <link rel="stylesheet" href="../css/authority-profile.css">

</head>

<body>

<?php include "../includes/navbar.php"; ?>
<?php include "../includes/header.php"; ?>

<div class="dashboard-layout">

<main class="dashboard-content">

    <div class="profile-container">

        <!-- Profile Banner -->

        <div class="profile-banner">

            <div class="profile-avatar-large">
                <?php echo strtoupper(substr($name,0,1)); ?>
            </div>

            <div class="profile-details">

                <h2><?php echo htmlspecialchars($name); ?></h2>

                <p><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($email); ?></p>

                <span class="role-badge">AUTHORITY</span>

            </div>

        </div>

        <!-- Profile Information -->

        <div class="profile-grid">

            <div class="profile-card">

                <h3>Personal Information</h3>

                <div class="info-row">
                    <span>Full Name</span>
                    <strong><?php echo htmlspecialchars($name); ?></strong>
                </div>

                <div class="info-row">
                    <span>Email</span>
                    <strong><?php echo htmlspecialchars($email); ?></strong>
                </div>

                <div class="info-row">
                    <span>Role</span>
                    <strong>Authority</strong>
                </div>

                <div class="info-row">
                    <span>Department</span>
                    <strong>Computer Engineering</strong>
                </div>

            </div>

            <div class="profile-card">

                <h3>Account Overview</h3>

                <div class="stat-mini">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div>
                        <strong>156</strong>
                        <span>Grievances Managed</span>
                    </div>
                </div>

                <div class="stat-mini">
                    <i class="fa-solid fa-lightbulb"></i>
                    <div>
                        <strong>48</strong>
                        <span>Suggestions Reviewed</span>
                    </div>
                </div>

                <div class="stat-mini">
                    <i class="fa-solid fa-file-lines"></i>
                    <div>
                        <strong>72</strong>
                        <span>Applications Processed</span>
                    </div>
                </div>

            </div>

        </div>

        <!-- Edit Profile -->

        <div class="profile-card edit-card">

            <h3>Edit Profile</h3>

            <form>

                <div class="form-grid">

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($name); ?>">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($email); ?>">
                    </div>

                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" value="Computer Engineering">
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" placeholder="+91 9876543210">
                    </div>

                </div>

                <button type="submit" class="save-btn">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Changes
                </button>

            </form>

        </div>

    </div>

</main>

</div>

<?php include "../includes/footer.php"; ?>

</body>
</html>