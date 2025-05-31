function formatDuration(seconds) {
    if (!seconds) return "0 sa 0 dk";
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return `${hours} sa ${minutes} dk`;
}

function formatDate(dateString) {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleDateString('tr-TR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function apiUrl(endpoint) {
    return `api/${endpoint}`;
}

document.addEventListener('DOMContentLoaded', function() {
    const state = {
        currentPage: 'dashboard',
        user: null,
        studyData: [],
        healthData: [],
        stats: {},
        badges: []
    };

    const appContainer = document.getElementById('app');
    
    initApp();
    
    function initApp() {
        checkAuth();
        setupEventListeners();
    }
    
    function checkAuth() {
        fetch(apiUrl('user.php') + '?action=check')
            .then(response => response.json())
            .then(data => {
                if (data.loggedIn) {
                    state.user = data.user;
                    loadUserData();
                    loadPage();
                } else {
                    navigateTo('login');
                }
            })
            .catch(error => {
                console.error('Kimlik doğrulama hatası:', error);
                appContainer.innerHTML = `<div class="card"><p>Sunucu bağlantı hatası. Lütfen daha sonra tekrar deneyin.</p></div>`;
            });
    }
    
    function loadUserData() {
        fetch(apiUrl('study.php') + '?action=get')
            .then(response => response.json())
            .then(data => {
                state.studyData = data;
                renderPage();
            });
        
        fetch(apiUrl('health.php') + '?action=get')
            .then(response => response.json())
            .then(data => {
                state.healthData = data;
                renderPage();
            });
        
        fetch(apiUrl('stats.php'))
            .then(response => response.json())
            .then(data => {
                state.stats = data;
                renderPage();
            });
        
        fetch(apiUrl('user.php') + '?action=badges')
            .then(response => response.json())
            .then(data => {
                state.badges = data;
                renderPage();
            });
    }
    
    function loadPage() {
        const urlParams = new URLSearchParams(window.location.search);
        const page = urlParams.get('page') || 'dashboard';
        state.currentPage = page;
        renderPage();
    }
    
    function navigateTo(page) {
        window.history.pushState({}, '', `?page=${page}`);
        state.currentPage = page;
        loadPage();
    }
    
    function setupEventListeners() {
        window.addEventListener('popstate', loadPage);
    }
    
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        
        if (state.user) {
            fetch(apiUrl('user.php') + '?action=updateTheme', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({theme})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    state.user.theme = theme;
                }
            });
        } else {
            document.cookie = `theme=${theme}; max-age=31536000; path=/`;
        }
    }
    
    function renderPage() {
        switch(state.currentPage) {
            case 'login':
                renderLogin();
                break;
            case 'register':
                renderRegister();
                break;
            case 'profile':
                renderProfile();
                break;
            case 'settings':
                renderSettings();
                break;
            case 'dashboard':
            default:
                renderDashboard();
        }
    }
    
    function renderDashboard() {
        appContainer.innerHTML = `
            <header>
                <div class="container">
                    <nav class="navbar">
                        <a href="#" class="logo">AkademikTakip</a>
                        <div class="nav-links">
                            <a href="#" data-nav="dashboard" class="${state.currentPage === 'dashboard' ? 'active' : ''}">Panel</a>
                            <a href="#" data-nav="profile" class="${state.currentPage === 'profile' ? 'active' : ''}">Profil</a>
                            <a href="#" data-nav="settings">Ayarlar</a>
                            <a href="#" id="logout">Çıkış Yap</a>
                        </div>
                        <div class="theme-selector">
                            <button class="theme-btn ${state.user?.theme === 'light' ? 'active' : ''}" data-theme="light" title="Açık Tema">☀️</button>
                            <button class="theme-btn ${state.user?.theme === 'dark' ? 'active' : ''}" data-theme="dark" title="Koyu Tema">🌙</button>
                            <button class="theme-btn ${state.user?.theme === 'system' ? 'active' : ''}" data-theme="system" title="Sistem Varsayılanı">⚙️</button>
                        </div>
                    </nav>
                </div>
            </header>
            
            <main class="main-content">
                <div class="container">
                    <h1 class="section-title">Çalışma Paneli</h1>
                    
                    <div class="dashboard-grid">
                        <div class="card">
                            <h2>Yeni Çalışma Başlat</h2>
                            <form id="start-study-form">
                                <div class="form-group">
                                    <label class="form-label" for="study-category">Kategori</label>
                                    <input type="text" class="form-control" id="study-category" placeholder="Örn: Matematik, Fizik" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="study-notes">Notlar</label>
                                    <textarea class="form-control" id="study-notes" rows="3" placeholder="Çalışma notları..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">📚 Çalışmayı Başlat</button>
                            </form>
                        </div>
                        
                        <div class="card">
                            <h2>Son Çalışmalar</h2>
                            <div id="recent-studies">
                                ${state.studyData.length ? 
                                    state.studyData.slice(0, 5).map(study => `
                                        <div class="study-item">
                                            <h3>${study.category}</h3>
                                            <p><strong>Süre:</strong> ${formatDuration(study.duration)}</p>
                                            <p><strong>Başlangıç:</strong> ${formatDate(study.start_time)}</p>
                                            ${study.notes ? `<p><strong>Notlar:</strong> ${study.notes}</p>` : ''}
                                        </div>
                                    `).join('') : 
                                    '<p>Henüz çalışma kaydı bulunmamaktadır.</p>'
                                }
                            </div>
                        </div>
                        
                        <div class="card">
                            <h2>Sağlık Verileri</h2>
                            <div id="health-data">
                                ${state.healthData.length ? `
                                    <div class="health-stat">
                                        <p><strong>Son Adım Sayısı:</strong> ${state.healthData[0].steps}</p>
                                    </div>
                                    <div class="health-stat">
                                        <p><strong>Son Kan Şekeri:</strong> ${state.healthData[0].blood_sugar || 'Ölçülmedi'}</p>
                                    </div>
                                ` : '<p>Henüz sağlık verisi bulunmamaktadır.</p>'}
                            </div>
                            <div class="form-group" style="margin-top: 1rem;">
                                <button class="btn btn-primary" id="sync-health">🔄 Sağlık Verilerini Senkronize Et</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-container">
                        <div class="card stat-card">
                            <div class="stat-label">Toplam Çalışma Süresi</div>
                            <div class="stat-value">${formatDuration(state.stats.total_study_time || 0)}</div>
                        </div>
                        
                        <div class="card stat-card">
                            <div class="stat-label">Günlük Ortalama</div>
                            <div class="stat-value">${formatDuration(state.stats.daily_average || 0)}</div>
                        </div>
                        
                        <div class="card stat-card">
                            <div class="stat-label">Bu Hafta Adımlar</div>
                            <div class="stat-value">${state.stats.weekly_steps || 0}</div>
                        </div>
                        
                        <div class="card stat-card">
                            <div class="stat-label">Rozetler</div>
                            <div class="stat-value">${state.badges.length || 0}</div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h2>Kazandığınız Rozetler</h2>
                        <div class="badges-container">
                            ${state.badges.length ? 
                                state.badges.map(badge => `
                                    <div class="badge">
                                        <div class="badge-icon">🏆</div>
                                        <div class="badge-content">
                                            <div class="badge-title">${badge.name}</div>
                                            <div class="badge-description">${badge.description}</div>
                                        </div>
                                    </div>
                                `).join('') : 
                                '<p>Henüz rozet kazanmadınız. Çalışmaya devam edin!</p>'
                            }
                        </div>
                    </div>
                </div>
            </main>
            
            <footer>
                <div class="container">
                    <p>Akademik Çalışma Yönetim Sistemi &copy; ${new Date().getFullYear()}</p>
                </div>
            </footer>
        `;
        
        setupDashboardEvents();
    }
    
    function setupDashboardEvents() {
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const theme = this.dataset.theme;
                setTheme(theme);
            });
        });
        
        document.querySelectorAll('[data-nav]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                navigateTo(this.dataset.nav);
            });
        });
        
        document.getElementById('logout').addEventListener('click', function(e) {
            e.preventDefault();
            fetch(apiUrl('user.php') + '?action=logout')
                .then(() => {
                    state.user = null;
                    navigateTo('login');
                });
        });
        
        document.getElementById('start-study-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const category = document.getElementById('study-category').value;
            const notes = document.getElementById('study-notes').value;
            
            if (!category) {
                alert('Lütfen bir kategori girin!');
                return;
            }
            
            fetch(apiUrl('study.php') + '?action=start', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({category, notes})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Çalışma başarıyla başlatıldı!');
                    document.getElementById('study-category').value = '';
                    document.getElementById('study-notes').value = '';
                    loadUserData();
                } else {
                    alert('Hata: ' + (data.message || 'Bilinmeyen bir hata oluştu'));
                }
            })
            .catch(error => {
                alert('Sunucu hatası: ' + error.message);
            });
        });
        
        document.getElementById('sync-health').addEventListener('click', function() {
            alert('Sağlık verileri senkronize ediliyor...');
            // Burada Samsung Health/Google Fit entegrasyonu yapılacak
            // Şimdilik mock data ekleyelim
            const mockSteps = Math.floor(Math.random() * 5000) + 3000;
            const mockBloodSugar = Math.floor(Math.random() * 50) + 70;
            
            fetch(apiUrl('health.php') + '?action=add', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    steps: mockSteps,
                    blood_sugar: mockBloodSugar
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Sağlık verileri senkronize edildi!\nAdım: ${mockSteps}\nKan Şekeri: ${mockBloodSugar}`);
                    loadUserData();
                }
            });
        });
    }
    
    function renderLogin() {
        appContainer.innerHTML = `
            <header>
                <div class="container">
                    <nav class="navbar">
                        <a href="#" class="logo">AkademikTakip</a>
                        <div class="theme-selector">
                            <button class="theme-btn" data-theme="light" title="Açık Tema">☀️</button>
                            <button class="theme-btn" data-theme="dark" title="Koyu Tema">🌙</button>
                            <button class="theme-btn" data-theme="system" title="Sistem Varsayılanı">⚙️</button>
                        </div>
                    </nav>
                </div>
            </header>
            
            <main class="main-content">
                <div class="container login-container">
                    <div class="card">
                        <h2 class="section-title" style="text-align: center;">Giriş Yap</h2>
                        <form id="login-form">
                            <div class="form-group">
                                <label class="form-label" for="login-email">E-posta</label>
                                <input type="email" class="form-control" id="login-email" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="login-password">Şifre</label>
                                <input type="password" class="form-control" id="login-password" required>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">🔑 Giriş Yap</button>
                        </form>
                        <p style="text-align: center; margin-top: 1rem;">
                            Hesabınız yok mu? <a href="#" data-nav="register">Kayıt Ol</a>
                        </p>
                    </div>
                </div>
            </main>
            
            <footer>
                <div class="container">
                    <p>Akademik Çalışma Yönetim Sistemi &copy; ${new Date().getFullYear()}</p>
                </div>
            </footer>
        `;
        
        setupLoginEvents();
    }
    
    function setupLoginEvents() {
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const theme = this.dataset.theme;
                setTheme(theme);
            });
        });
        
        document.querySelector('[data-nav="register"]').addEventListener('click', function(e) {
            e.preventDefault();
            navigateTo('register');
        });
        
        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            
            fetch(apiUrl('user.php') + '?action=login', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ email, password })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    state.user = data.user;
                    navigateTo('dashboard');
                } else {
                    alert(data.message || 'Giriş başarısız. Lütfen bilgilerinizi kontrol edin.');
                }
            })
            .catch(error => {
                alert('Sunucu hatası: ' + error.message);
            });
        });
    }
    
    function renderRegister() {
        appContainer.innerHTML = `
            <header>
                <div class="container">
                    <nav class="navbar">
                        <a href="#" class="logo">AkademikTakip</a>
                        <div class="theme-selector">
                            <button class="theme-btn" data-theme="light" title="Açık Tema">☀️</button>
                            <button class="theme-btn" data-theme="dark" title="Koyu Tema">🌙</button>
                            <button class="theme-btn" data-theme="system" title="Sistem Varsayılanı">⚙️</button>
                        </div>
                    </nav>
                </div>
            </header>
            
            <main class="main-content">
                <div class="container login-container">
                    <div class="card">
                        <h2 class="section-title" style="text-align: center;">Kayıt Ol</h2>
                        <form id="register-form">
                            <div class="form-group">
                                <label class="form-label" for="register-username">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="register-username" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="register-email">E-posta</label>
                                <input type="email" class="form-control" id="register-email" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="register-password">Şifre</label>
                                <input type="password" class="form-control" id="register-password" required>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">📝 Kayıt Ol</button>
                        </form>
                        <p style="text-align: center; margin-top: 1rem;">
                            Zaten hesabınız var mı? <a href="#" data-nav="login">Giriş Yap</a>
                        </p>
                    </div>
                </div>
            </main>
            
            <footer>
                <div class="container">
                    <p>Akademik Çalışma Yönetim Sistemi &copy; ${new Date().getFullYear()}</p>
                </div>
            </footer>
        `;
        
        setupRegisterEvents();
    }
    
    function setupRegisterEvents() {
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const theme = this.dataset.theme;
                setTheme(theme);
            });
        });
        
        document.querySelector('[data-nav="login"]').addEventListener('click', function(e) {
            e.preventDefault();
            navigateTo('login');
        });
        
        document.getElementById('register-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const username = document.getElementById('register-username').value;
            const email = document.getElementById('register-email').value;
            const password = document.getElementById('register-password').value;
            
            fetch(apiUrl('user.php') + '?action=register', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ username, email, password })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    state.user = data.user;
                    navigateTo('dashboard');
                } else {
                    alert(data.message || 'Kayıt sırasında bir hata oluştu.');
                }
            })
            .catch(error => {
                alert('Sunucu hatası: ' + error.message);
            });
        });
    }
    
    function renderProfile() {
        appContainer.innerHTML = `
            <header>
                <div class="container">
                    <nav class="navbar">
                        <a href="#" class="logo">AkademikTakip</a>
                        <div class="nav-links">
                            <a href="#" data-nav="dashboard">Panel</a>
                            <a href="#" data-nav="profile" class="active">Profil</a>
                            <a href="#" data-nav="settings">Ayarlar</a>
                            <a href="#" id="logout">Çıkış Yap</a>
                        </div>
                        <div class="theme-selector">
                            <button class="theme-btn ${state.user?.theme === 'light' ? 'active' : ''}" data-theme="light" title="Açık Tema">☀️</button>
                            <button class="theme-btn ${state.user?.theme === 'dark' ? 'active' : ''}" data-theme="dark" title="Koyu Tema">🌙</button>
                            <button class="theme-btn ${state.user?.theme === 'system' ? 'active' : ''}" data-theme="system" title="Sistem Varsayılanı">⚙️</button>
                        </div>
                    </nav>
                </div>
            </header>
            
            <main class="main-content">
                <div class="container">
                    <h1 class="section-title">Profil</h1>
                    <div class="card">
                        <h2>Profil Bilgileri</h2>
                        <div class="form-group">
                            <label class="form-label">Kullanıcı Adı</label>
                            <p><strong>${state.user.username}</strong></p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">E-posta</label>
                            <p><strong>${state.user.email}</strong></p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Üyelik Tarihi</label>
                            <p><strong>${new Date(state.user.created_at).toLocaleDateString('tr-TR')}</strong></p>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h2>Profil Fotoğrafı</h2>
                        <div style="display: flex; align-items: center; gap: 1.5rem; margin-top: 1rem;">
                            <div style="width: 120px; height: 120px; border-radius: 50%; background-color: var(--primary-color); display: flex; align-items: center; justify-content: center; font-size: 3rem; color: white;">
                                ${state.user.username.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <button class="btn btn-primary">🖼️ Fotoğraf Yükle</button>
                                <p style="margin-top: 0.5rem; font-size: 0.9rem;">Maksimum dosya boyutu: 2MB</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h2>Başarım Rozetleri</h2>
                        <div class="badges-container">
                            ${state.badges.length ? 
                                state.badges.slice(0, 4).map(badge => `
                                    <div class="badge">
                                        <div class="badge-icon">🏆</div>
                                        <div class="badge-content">
                                            <div class="badge-title">${badge.name}</div>
                                            <div class="badge-description">${badge.description}</div>
                                        </div>
                                    </div>
                                `).join('') : 
                                '<p>Henüz rozet kazanmadınız. Çalışmaya devam edin!</p>'
                            }
                        </div>
                        ${state.badges.length > 4 ? `<div style="text-align: center; margin-top: 1rem;">
                            <button class="btn btn-primary" data-nav="dashboard">Tüm Rozetleri Görüntüle</button>
                        </div>` : ''}
                    </div>
                </div>
            </main>
            
            <footer>
                <div class="container">
                    <p>Akademik Çalışma Yönetim Sistemi &copy; ${new Date().getFullYear()}</p>
                </div>
            </footer>
        `;
        
        setupCommonEvents();
    }
    
    function renderSettings() {
        appContainer.innerHTML = `
            <header>
                <div class="container">
                    <nav class="navbar">
                        <a href="#" class="logo">AkademikTakip</a>
                        <div class="nav-links">
                            <a href="#" data-nav="dashboard">Panel</a>
                            <a href="#" data-nav="profile">Profil</a>
                            <a href="#" data-nav="settings" class="active">Ayarlar</a>
                            <a href="#" id="logout">Çıkış Yap</a>
                        </div>
                        <div class="theme-selector">
                            <button class="theme-btn ${state.user?.theme === 'light' ? 'active' : ''}" data-theme="light" title="Açık Tema">☀️</button>
                            <button class="theme-btn ${state.user?.theme === 'dark' ? 'active' : ''}" data-theme="dark" title="Koyu Tema">🌙</button>
                            <button class="theme-btn ${state.user?.theme === 'system' ? 'active' : ''}" data-theme="system" title="Sistem Varsayılanı">⚙️</button>
                        </div>
                    </nav>
                </div>
            </header>
            
            <main class="main-content">
                <div class="container">
                    <h1 class="section-title">Ayarlar</h1>
                    <div class="card">
                        <h2>Tema Ayarları</h2>
                        <div class="theme-selector" style="margin-top: 1rem; gap: 1rem;">
                            <button class="theme-btn ${state.user?.theme === 'light' ? 'active' : ''}" data-theme="light" title="Açık Tema">
                                <div>☀️</div>
                                <div>Açık Tema</div>
                            </button>
                            <button class="theme-btn ${state.user?.theme === 'dark' ? 'active' : ''}" data-theme="dark" title="Koyu Tema">
                                <div>🌙</div>
                                <div>Koyu Tema</div>
                            </button>
                            <button class="theme-btn ${state.user?.theme === 'system' ? 'active' : ''}" data-theme="system" title="Sistem Varsayılanı">
                                <div>⚙️</div>
                                <div>Sistem Varsayılanı</div>
                            </button>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h2>Entegrasyonlar</h2>
                        <div class="integration-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-top: 1rem;">
                            <div class="integration-card">
                                <h3>Forest App</h3>
                                <p>Çalışma sürelerinizi otomatik olarak aktarın</p>
                                <button class="btn btn-primary" style="margin-top: 0.5rem;">🔗 Bağlan</button>
                            </div>
                            <div class="integration-card">
                                <h3>Samsung Health</h3>
                                <p>Sağlık verilerinizi otomatik olarak aktarın</p>
                                <button class="btn btn-primary" style="margin-top: 0.5rem;">🔗 Bağlan</button>
                            </div>
                            <div class="integration-card">
                                <h3>Google Fit</h3>
                                <p>Sağlık verilerinizi otomatik olarak aktarın</p>
                                <button class="btn btn-primary" style="margin-top: 0.5rem;">🔗 Bağlan</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h2>Hesap Ayarları</h2>
                        <div class="form-group">
                            <button class="btn btn-primary">✏️ Kullanıcı Adını Değiştir</button>
                        </div>
                        <div class="form-group">
                            <button class="btn btn-primary">🔐 Şifreyi Değiştir</button>
                        </div>
                        <div class="form-group">
                            <button class="btn btn-danger" style="background-color: var(--danger-color);">🗑️ Hesabı Sil</button>
                        </div>
                    </div>
                </div>
            </main>
            
            <footer>
                <div class="container">
                    <p>Akademik Çalışma Yönetim Sistemi &copy; ${new Date().getFullYear()}</p>
                </div>
            </footer>
        `;
        
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const theme = this.dataset.theme;
                setTheme(theme);
            });
        });
        
        setupCommonEvents();
    }
    
    function setupCommonEvents() {
        document.querySelectorAll('[data-nav]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                navigateTo(this.dataset.nav);
            });
        });
        
        document.getElementById('logout').addEventListener('click', function(e) {
            e.preventDefault();
            fetch(apiUrl('user.php') + '?action=logout')
                .then(() => {
                    state.user = null;
                    navigateTo('login');
                });
        });
    }
});