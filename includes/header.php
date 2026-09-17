<header class="top-header">

    <div class="header-left">
        <h2>CampusDesk</h2>
    </div>

    <div class="header-center">
        <input type="text" placeholder="Search">
    </div>

    <div class="header-right">
        <span>Notifications</span>
        <span><?php echo $_SESSION["role_name"] ?? "User"; ?></span>
    </div>

</header>