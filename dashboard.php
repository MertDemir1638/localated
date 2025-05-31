<?php
require_once 'config/config.php';
require_once 'helpers.php';
require_once 'db_functions.php';

// Kullanıcı giriş yapmamışsa giriş sayfasına yönlendir
if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
$badges = getUserBadges($user['id']);
$sessions = getUserSessions($user['id']);
$activities = getUserRecentActivities($user['id']);

// Kullanıcı aktivitesini kaydet
logActivity($user['id'], 'view_dashboard', 'User viewed dashboard');

// Rozet kontrolü
checkActivityBadges($user['id']);

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Paylaş</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Yazdır</button>
                        </div>
                    </div>
                </div>
                
                <!-- Hoşgeldin mesajı -->
                <div class="alert alert-success">
                    Hoşgeldin, <?php echo htmlspecialchars($user['username']); ?>! Son giriş: <?php echo formatDate($user['last_login']); ?>
                </div>
                
                <!-- Kullanıcı bilgileri -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-header">Toplam Oturum</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo count($sessions); ?></h5>
                                <p class="card-text">aktif oturumlar</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-header">Rozetler</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo count($badges); ?></h5>
                                <p class="card-text">kazanılan rozet</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-header">Aktivite</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo getUserActivityCount($user['id']); ?></h5>
                                <p class="card-text">toplam aktivite</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Rozetler -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Rozetlerin</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($badges)): ?>
                            <p>Henüz hiç rozet kazanmadın.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($badges as $badge): ?>
                                    <div class="col-md-3 mb-3">
                                        <div class="card">
                                            <img src="<?php echo htmlspecialchars($badge['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($badge['name']); ?>">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($badge['name']); ?></h6>
                                                <p class="card-text small"><?php echo htmlspecialchars($badge['description']); ?></p>
                                                <p class="card-text small text-muted">Kazanma Tarihi: <?php echo formatDate($badge['awarded_at']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Son aktiviteler -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Son Aktiviteler</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($activities)): ?>
                            <p>Henüz hiç aktivite kaydın yok.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($activities as $activity): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo getActivityTypeName($activity['activity_type']); ?></strong>
                                            <p class="mb-0 small"><?php echo htmlspecialchars($activity['activity_data']); ?></p>
                                        </div>
                                        <span class="badge bg-secondary rounded-pill"><?php echo formatDate($activity['created_at'], true); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Aktif oturumlar -->
                <div class="card">
                    <div class="card-header">
                        <h5>Aktif Oturumlar</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($sessions)): ?>
                            <p>Hiç aktif oturumun yok.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>IP Adresi</th>
                                            <th>Cihaz</th>
                                            <th>Giriş Zamanı</th>
                                            <th>Son Aktivite</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sessions as $session): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($session['ip_address']); ?></td>
                                                <td><?php echo getDeviceName($session['user_agent']); ?></td>
                                                <td><?php echo formatDate($session['created_at']); ?></td>
                                                <td><?php echo formatDate($session['last_activity']); ?></td>
                                                <td>
                                                    <?php if ($session['id'] !== session_id()): ?>
                                                        <a href="logout.php?session_id=<?php echo $session['id']; ?>" class="btn btn-sm btn-danger">Sonlandır</a>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary">Bu oturum</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>