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
    
    // Add a nice visual feedback
    showToast(document.documentElement.classList.contains('dark') ? 'Dark Mode aktiviert' : 'Light Mode aktiviert', 'success');
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
    const bgColor = type === 'success' ? 'bg-gradient-to-r from-green-500 to-emerald-500' : 
                    type === 'error' ? 'bg-gradient-to-r from-red-500 to-rose-500' :
                    'bg-gradient-to-r from-blue-500 to-indigo-500';
    const icon = type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info';
    
    toast.className = `${bgColor} text-white px-5 py-3 rounded-xl shadow-lg flex items-center gap-3 transform transition-all duration-300 translate-y-10 opacity-0 mb-3 z-50 backdrop-blur`;
    toast.innerHTML = `
        <i class="material-icons">${icon}</i>
        <span class="font-medium text-sm">${message}</span>
    `;

    container.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-10', 'opacity-0');
    });

    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-x-full');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

// Keyboard shortcuts
function initKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
        // Only trigger if not in input/textarea
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        // Ctrl/Cmd + D = Toggle dark mode
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            toggleTheme();
        }
        
        // Ctrl/Cmd + H = Go home
        if ((e.ctrlKey || e.metaKey) && e.key === 'h') {
            e.preventDefault();
            window.location.href = 'index.php';
        }
        
        // Ctrl/Cmd + B = Go to booking
        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
            e.preventDefault();
            window.location.href = 'booking.php';
        }
    });
}

// Animate elements on scroll
function initScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });
}

// Form submission feedback
function initFormFeedback() {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                const originalContent = btn.innerHTML;
                btn.innerHTML = '<i class="material-icons animate-spin">sync</i> Speichern...';
                btn.disabled = true;
                
                // Re-enable after a short delay in case form doesn't navigate
                setTimeout(() => {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }, 3000);
            }
        });
    });
}

// Add spin animation class
const style = document.createElement('style');
style.textContent = `
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .animate-spin { animation: spin 1s linear infinite; }
    .animate-in { animation: fadeInUp 0.4s ease-out forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
`;
document.head.appendChild(style);

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initKeyboardShortcuts();
    initScrollAnimations();
    initFormFeedback();
    
    // Check for server messages
    if (window.serverMessages) {
        if (window.serverMessages.success) {
            window.serverMessages.success.forEach(msg => showToast(msg, 'success'));
        }
        if (window.serverMessages.error) {
            window.serverMessages.error.forEach(msg => showToast(msg, 'error'));
        }
    }
    
    // Add subtle hover effects to cards
    document.querySelectorAll('.bg-white, .dark\\:bg-gray-800').forEach(card => {
        if (card.classList.contains('rounded-xl') || card.classList.contains('rounded-2xl')) {
            card.classList.add('transition-shadow', 'duration-200');
        }
    });
});
