<?php
require_once "../includes/auth.php";
requireAuthority();

require_once "../config/database.php";
require_once "../includes/functions.php";

$user_id = getLoggedInUserId();

$profile = getAuthorityProfile($conn, $user_id);

if (!$profile) {
    die("Authority profile not found.");
}

$profile_photo = $profile["profile_photo"] ?? null;

$profile_photo_type = "image/jpeg";
$profile_photo_data = null;

if ($profile_photo) {
    $profile_photo_data = pg_unescape_bytea($profile_photo);

    if (function_exists("finfo_open")) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo) {
            $detected = finfo_buffer($finfo, $profile_photo_data);
            finfo_close($finfo);

            if (in_array($detected, ["image/jpeg", "image/png"], true)) {
                $profile_photo_type = $detected;
            }
        }
    }
}
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

                <!-- ================= Profile Banner ================= -->

                <div class="profile-banner">

                    <div class="profile-avatar-large">

                        <?php if ($profile_photo_data): ?>

                            <img
                                src="data:<?= htmlspecialchars($profile_photo_type) ?>;base64,<?= base64_encode($profile_photo_data) ?>"
                                alt="Profile Photo"
                                class="profile-photo-large">

                        <?php else: ?>

                            <?= strtoupper(substr($profile["full_name"], 0, 1)); ?>

                        <?php endif; ?>

                    </div>

                    <div class="profile-details">

                        <h2><?= htmlspecialchars($profile["full_name"]); ?></h2>

                        <p>
                            <i class="fa-solid fa-envelope"></i>
                            <?= htmlspecialchars($profile["email"]); ?>
                        </p>

                        <span class="role-badge">AUTHORITY</span>

                    </div>

                </div>

                <!-- ================= Profile Information ================= -->

                <div class="profile-grid">

                    <div class="profile-card">

                        <h3>Personal Information</h3>

                        <div class="info-row">
                            <span>Full Name</span>
                            <strong><?= htmlspecialchars($profile["full_name"]); ?></strong>
                        </div>

                        <div class="info-row">
                            <span>Email</span>
                            <strong><?= htmlspecialchars($profile["email"]); ?></strong>
                        </div>

                        <div class="info-row">
                            <span>Department</span>
                            <strong><?= htmlspecialchars($profile["department_name"]); ?></strong>
                        </div>

                        <div class="info-row">
                            <span>Designation</span>
                            <strong><?= htmlspecialchars($profile["designation"]); ?></strong>
                        </div>

                        <div class="info-row">
                            <span>Mobile</span>
                            <strong><?= htmlspecialchars($profile["mobile_number"] ?? "-"); ?></strong>
                        </div>

                    </div>

                    <div class="profile-card">

                        <h3>Account Overview</h3>

                        <div class="stat-mini">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <div>
                                <strong><?= $profile["grievance_count"] ?? 0 ?></strong>
                                <span>Grievances Managed</span>
                            </div>
                        </div>

                        <div class="stat-mini">
                            <i class="fa-solid fa-lightbulb"></i>
                            <div>
                                <strong><?= $profile["suggestion_count"] ?? 0 ?></strong>
                                <span>Suggestions Reviewed</span>
                            </div>
                        </div>

                        <div class="stat-mini">
                            <i class="fa-solid fa-file-lines"></i>
                            <div>
                                <strong><?= $profile["application_count"] ?? 0 ?></strong>
                                <span>Applications Processed</span>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- ================= Edit Profile ================= -->

                <div class="profile-card edit-card">

                    <h3>Edit Profile</h3>

                    <form action="../actions/profile.php" method="POST">

                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-grid">

                            <div class="form-group">
                                <label>Full Name</label>
                                <input
                                    type="text"
                                    name="full_name"
                                    value="<?= htmlspecialchars($profile["full_name"]); ?>">
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input
                                    type="email"
                                    value="<?= htmlspecialchars($profile["email"]); ?>"
                                    readonly>
                            </div>

                            <div class="form-group">
                                <label>Department</label>
                                <input
                                    type="text"
                                    value="<?= htmlspecialchars($profile["department_name"]); ?>"
                                    readonly>
                            </div>

                            <div class="form-group">
                                <label>Designation</label>
                                <input
                                    type="text"
                                    value="<?= htmlspecialchars($profile["designation"]); ?>"
                                    readonly>
                            </div>

                            <div class="form-group">
                                <label>Phone</label>
                                <input
                                    type="text"
                                    name="mobile"
                                    value="<?= htmlspecialchars($profile["mobile_number"] ?? ""); ?>">
                            </div>

                            <div class="form-group">
                                <label>Address</label>
                                <input
                                    type="text"
                                    name="address"
                                    value="<?= htmlspecialchars($profile["address"] ?? ""); ?>">
                            </div>

                        </div>

                        <button type="submit" class="save-btn">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Save Changes
                        </button>

                    </form>

                </div>

                <!-- ================= Account Information ================= -->

                <div class="profile-card">

                    <h3>Account Information</h3>

                    <div class="info-row">
                        <span>Account Status</span>
                        <strong><?= $profile["account_status"] ? "Active" : "Inactive"; ?></strong>
                    </div>

                    <div class="info-row">
                        <span>Last Login</span>
                        <strong><?= formatDateTime($profile["last_login"]); ?></strong>
                    </div>

                    <div class="info-row">
                        <span>Account Created</span>
                        <strong><?= formatDateTime($profile["created_at"]); ?></strong>
                    </div>

                </div>

            </div>

        </main>

    </div>

    <?php include "../includes/footer.php"; ?>

</body>

</html>