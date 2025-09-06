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

    // Cache primary form + save button early (fix ReferenceError)
    const form = document.querySelector('form');
    const saveButton = document.querySelector('.save-button');

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

    // Floating action save button
    const fabSave = document.getElementById('fab-save');
    if (fabSave && form) {
        fabSave.addEventListener('click', () => { form.submit(); });
    }

    // Pulsierender Speichern-Button nur nach Änderung
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
            updateWeekSummary();
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

    // Update week summary counts on change
    function updateWeekSummary() {
        if (!form) return;
        const radios = Array.from(form.querySelectorAll('input[type="radio"][name^="location["]'));
        const names = [...new Set(radios.map(r => r.name))];
        const counts = {homeoffice:0, office:0, vacation:0, sick:0, training:0, none:0};
        names.forEach(name => {
            const checked = radios.find(r => r.name === name && r.checked);
            const val = checked ? checked.value : '';
            if (!val) counts.none++; else if (counts.hasOwnProperty(val)) counts[val]++;
        });
        const map = {
            homeoffice: 'week-count-homeoffice',
            office: 'week-count-office',
            vacation: 'week-count-vacation',
            sick: 'week-count-sick',
            training: 'week-count-training',
            none: 'week-count-none'
        };
        Object.entries(map).forEach(([key, id]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = counts[key];
        });
    }

    // Hook into radio changes to refresh summary
    document.querySelectorAll('input[type="radio"][name^="location["]').forEach(r => {
        r.addEventListener('change', updateWeekSummary);
    });
    updateWeekSummary();
});
