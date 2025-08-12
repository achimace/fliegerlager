<header class="app-header">
    <div class="logo"><a href="dashboard.php"><img src="pics/logo.png" alt="Logo"></a></div>
    <nav class="app-header-nav">
        <a href="dashboard.php" class="<?php if($currentPage == 'dashboard.php') echo 'active'; ?>">Dashboard</a>
        <span>|</span>
        <a href="calendar.php" class="<?php if($currentPage == 'calendar.php') echo 'active'; ?>">Kalender</a>
        <span>|</span>
        <span>Willkommen, <?php echo htmlspecialchars($user_vorname); ?>!</span>
        <a href="logout.php" class="btn btn-grey">Logout</a>
    </nav>
</header>
