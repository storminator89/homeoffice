// Toast Konfiguration
const toastOptions = {
    displayLength: 8000, // Erhöht auf 8 Sekunden
    classes: 'rounded',
    inDuration: 300,
    outDuration: 300
};

function showToast(message, type = 'success') {
    const classes = type === 'success' ? 'rounded green' : 'rounded red';
    M.toast({
        html: message,
        ...toastOptions,
        classes
    });
}

function toggleTheme() {
    const body = document.body;
    const currentTheme = body.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    body.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
}

document.addEventListener('DOMContentLoaded', function() {
    // Theme toggling functionality
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.body.setAttribute('data-theme', savedTheme);

    // Initialize MaterializeCSS components
    var collapsibles = document.querySelectorAll('.collapsible');
    M.Collapsible.init(collapsibles, {
        onOpenStart: (el) => {
            const icon = el.querySelector('.material-icons:last-child');
            if (icon) icon.textContent = 'expand_less';
        },
        onCloseStart: (el) => {
            const icon = el.querySelector('.material-icons:last-child');
            if (icon) icon.textContent = 'expand_more';
        }
    });

    // Initialize tabs if they exist
    var tabs = document.querySelectorAll('.tabs');
    if (tabs.length > 0) {
        M.Tabs.init(tabs);
    }

    // Pulsierender Speichern-Button nur nach Änderung
    const form = document.querySelector('form');
    const saveButton = document.querySelector('.save-button');
    if (form && saveButton) {
        const inputs = form.querySelectorAll('input[type="radio"]');
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                saveButton.classList.add('pulse');
            });
        });
        
        form.addEventListener('submit', () => {
            saveButton.classList.remove('pulse');
        });
    }

    // Schnellaktionen: ganze Woche setzen
    const weekButtons = document.querySelectorAll('[data-week-action]');
    if (weekButtons.length && form && saveButton) {
        const labelFor = (v) => ({
            none: 'Keine Angabe',
            homeoffice: 'Homeoffice',
            office: 'Büro',
            vacation: 'Urlaub',
            sick: 'Krank',
            training: 'Schulung'
        })[v] || v;

        const setWeek = (targetValue) => {
            // Gruppiere Radiobuttons nach Name (ein Name pro Tag)
            const radios = Array.from(form.querySelectorAll('input[type="radio"][name^="location["]'));
            const names = [...new Set(radios.map(r => r.name))];
            names.forEach(name => {
                const group = radios.filter(r => r.name === name);
                const valueToSet = targetValue === 'none' ? '' : targetValue;
                const target = group.find(r => r.value === valueToSet);
                if (target) target.checked = true;
            });
            saveButton.classList.add('pulse');
            showToast('Woche gesetzt: ' + labelFor(targetValue));
        };

        weekButtons.forEach(btn => {
            btn.addEventListener('click', () => setWeek(btn.getAttribute('data-week-action')));
        });
    }

    // Toast Nachrichten - nur einmal ausführen
    if (window.serverMessages && !window.toastsShown) {
        window.toastsShown = true;
        const messages = window.serverMessages;
        const toastOptions = {
            displayLength: 4000,
            classes: 'rounded'
        };

        if (messages.success) {
            messages.success.forEach(msg => 
                M.toast({...toastOptions, html: msg, classes: 'rounded green'})
            );
        }
        if (messages.error) {
            messages.error.forEach(msg => 
                M.toast({...toastOptions, html: msg, classes: 'rounded red'})
            );
        }
    }

    // Initialize Materialize components
    M.AutoInit();
});
