<div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <span data-feather="home"></span>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="profile.php">
                    <span data-feather="user"></span>
                    Profil
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="activities.php">
                    <span data-feather="activity"></span>
                    Aktiviteler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="badges.php">
                    <span data-feather="award"></span>
                    Rozetler
                    <?php if (isLoggedIn()): ?>
                        <?php $badge_count = count(getUserBadges($_SESSION['user_id'])); ?>
                        <?php if ($badge_count > 0): ?>
                            <span class="badge bg-primary rounded-pill float-end"><?php echo $badge_count; ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
        
        <?php if (isLoggedIn()): ?>
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                <span>Hesap</span>
            </h6>
            <ul class="nav flex-column mb-2">
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <span data-feather="settings"></span>
                        Ayarlar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <span data-feather="log-out"></span>
                        Çıkış Yap
                    </a>
                </li>
            </ul>
        <?php endif; ?>
    </div>
</div>