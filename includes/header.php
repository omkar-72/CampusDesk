<header class="top-header">

    <div class="header-left">
        <button class="menu-btn">
            <i class="fa-solid fa-bars"></i>
        </button>

        <h2>CampusDesk</h2>
    </div>

    <div class="header-right">

        <button class="icon-btn">
            <i class="fa-regular fa-bell"></i>
        </button>

        <div class="profile-box">

            <div class="profile-avatar">
                <?php echo strtoupper(substr($_SESSION["role_name"] ?? "U", 0, 1)); ?>
            </div>

            <div class="profile-text">
                <small>Role</small>
                <span><?php echo $_SESSION["role_name"] ?? "User"; ?></span>
            </div>

        </div>

    </div>

</header>
