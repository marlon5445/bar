/**
 * BAR MANAGER - GLOBAL JAVASCRIPT
 * Gestor de Temas (Oscuro 🌙 / Claro ☀️), Menú Responsive y Gráficos
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. GESTIÓN DE TEMA GLOBAL (OSCURO / CLARO) CON PERSISTENCIA EN LOCALSTORAGE
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeIcon = document.getElementById('themeIcon');
    const themeText = document.getElementById('themeText');

    // Cargar tema guardado en localStorage o predeterminado 'light'
    const currentTheme = localStorage.getItem('bar_manager_theme') || 'light';
    applyTheme(currentTheme);

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function () {
            const activeTheme = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            applyTheme(activeTheme);
            localStorage.setItem('bar_manager_theme', activeTheme);
            
            // Disparar evento para actualizar el gráfico si existe
            window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: activeTheme } }));
        });
    }

    function applyTheme(theme) {
        if (theme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
            if (themeIcon) themeIcon.textContent = '☀️';
            if (themeText) themeText.textContent = 'Tema Claro';
        } else {
            document.documentElement.removeAttribute('data-theme');
            if (themeIcon) themeIcon.textContent = '🌙';
            if (themeText) themeText.textContent = 'Tema Oscuro';
        }
    }

    // 2. CONTROL DE SIDEBAR RESPONSIVE EN MÓVILES
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar && sidebarOverlay) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        });

        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }

    // 3. MÓDULOS EN DESARROLLO (TOAST / ALERTA PARA ENLACES NO DESARROLLADOS)
    const unbuiltLinks = document.querySelectorAll('.module-unbuilt');
    unbuiltLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            showNotice('El módulo "' + this.innerText.trim() + '" está preparado para la siguiente etapa.');
        });
    });

    function showNotice(msg) {
        let toast = document.createElement('div');
        toast.style.position = 'fixed';
        toast.style.bottom = '20px';
        toast.style.right = '20px';
        toast.style.backgroundColor = 'var(--bg-card)';
        toast.style.color = 'var(--accent)';
        toast.style.border = '1px solid var(--accent)';
        toast.style.padding = '12px 20px';
        toast.style.borderRadius = '12px';
        toast.style.boxShadow = '0 10px 30px rgba(0,0,0,0.5)';
        toast.style.zIndex = '9999';
        toast.style.fontWeight = '600';
        toast.style.fontSize = '0.9rem';
        toast.style.transition = 'all 0.3s ease';
        toast.innerHTML = '⚙️ ' + msg;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});
