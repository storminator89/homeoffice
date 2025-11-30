// Theme Management
function initTheme() {
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    updateThemeIcon();
}

function toggleTheme() {
    if (document.documentElement.classList.contains('dark')) {
        document.documentElement.classList.remove('dark');
        localStorage.theme = 'light';
    } else {
        document.documentElement.classList.add('dark');
        localStorage.theme = 'dark';
    }
    updateThemeIcon();
}

function updateThemeIcon() {
    const icons = document.querySelectorAll('.theme-toggle-icon');
    const isDark = document.documentElement.classList.contains('dark');
    icons.forEach(icon => {
        icon.textContent = isDark ? 'light_mode' : 'dark_mode';
    });
}

// Toast Notifications
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    const icon = type === 'success' ? 'check_circle' : 'error';
    
    toast.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 transform transition-all duration-300 translate-y-10 opacity-0 mb-3 z-50`;
    toast.innerHTML = `
        <i class="material-icons">${icon}</i>
        <span class="font-medium">${message}</span>
    `;

    container.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-10', 'opacity-0');
    });

    // Remove after 4 seconds
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-x-full');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    
    // Check for server messages
    if (window.serverMessages) {
        if (window.serverMessages.success) {
            window.serverMessages.success.forEach(msg => showToast(msg, 'success'));
        }
        if (window.serverMessages.error) {
            window.serverMessages.error.forEach(msg => showToast(msg, 'error'));
        }
    }
});
