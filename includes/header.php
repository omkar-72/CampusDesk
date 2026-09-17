<header class="top-header">

    <div class="header-left">
        <h2>CampusDesk</h2>
    </div>

    <div class="header-center">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Search...">
        </div>
    </div>

    <div class="header-right">

        <button class="notification-btn">
            <i class="fa-regular fa-bell"></i>
            <span class="notification-dot"></span>
        </button>

        <div class="user-box">

            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION["role_name"] ?? "U",0,1)); ?>
            </div>

            <div class="user-info">
                <small>Logged in as</small>
                <span><?php echo $_SESSION["role_name"] ?? "User"; ?></span>
            </div>

        </div>

    </div>

</header>