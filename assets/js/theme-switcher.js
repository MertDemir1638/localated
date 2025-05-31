document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    
    // Temayı kontrol et ve uygula
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        
        // İkon güncelleme
        themeIcon.className = theme === 'dark' ? 'fas fa-moon' : 
                             theme === 'light' ? 'fas fa-sun' : 
                             'fas fa-adjust';
    }
    
    // Sistem temasını algıla
    function getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    
    // Tema değiştirme butonu
    themeToggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'system';
        let newTheme;
        
        if (currentTheme === 'system') {
            newTheme = getSystemTheme() === 'dark' ? 'light' : 'dark';
        } else if (currentTheme === 'light') {
            newTheme = 'dark';
        } else {
            newTheme = 'system';
        }
        
        applyTheme(newTheme);
    });
    
    // İlk yüklemede temayı ayarla
    const savedTheme = localStorage.getItem('theme');
    const systemTheme = getSystemTheme();
    
    if (savedTheme) {
        applyTheme(savedTheme);
    } else {
        applyTheme('system');
        document.documentElement.setAttribute('data-theme', 'system');
    }
});