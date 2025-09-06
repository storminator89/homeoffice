<?php
require_once 'database.php';

$db = new Database();
$messages = ['success' => [], 'error' => []];

// Verarbeite POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['homeoffice_quota'])) {
        $quota = filter_var($_POST['homeoffice_quota'], FILTER_VALIDATE_INT);
        if ($quota !== false && $quota >= 0 && $quota <= 100) {
            $db->setSetting('homeoffice_quota', $quota);
            $messages['success'][] = "Homeoffice-Quote wurde erfolgreich auf {$quota}% gesetzt.";
        } else {
            $messages['error'][] = "Bitte geben Sie einen gültigen Prozentwert zwischen 0 und 100 ein.";
        }
    }
}

// Lade aktuelle Einstellungen
$currentQuota = $db->getSetting('homeoffice_quota');

include 'templates/header.php';
?>

<div class="row">
    <div class="col s12">
        <?php if (!empty($messages)) { ?>
            <script>window.serverMessages = <?php echo json_encode($messages); ?>;</script>
        <?php } ?>
        
        <div class="card">
            <div class="card-content">
                <div class="card-header blue darken-1 white-text" style="margin: -20px -20px 20px -20px; padding: 20px;">
                    <span class="card-title" style="font-size: 2rem; display: flex; align-items: center;">
                        <i class="material-icons" style="margin-right: 10px;">settings</i>
                        Einstellungen
                    </span>
                </div>

                <div class="section">
                    <form method="post" id="settingsForm">
                        <div class="row">
                            <div class="col s12 m8 offset-m2 l6 offset-l3">
                                <div class="card-panel z-depth-2 hoverable" style="border-radius: 12px;">
                                    <div class="input-field">
                                        <i class="material-icons prefix blue-text">home</i>
                                        <input type="number" 
                                               id="homeoffice_quota" 
                                               name="homeoffice_quota" 
                                               value="<?php echo htmlspecialchars($currentQuota); ?>" 
                                               min="0" 
                                               max="100" 
                                               required
                                               class="validate"
                                               style="font-size: 1.2rem;">
                                        <label for="homeoffice_quota">Homeoffice-Quote (%)</label>
                                        <span class="helper-text">Zwischen 0% und 100%</span>
                                    </div>

                                    <div class="range-field" style="margin: 3rem 0;">
                                        <input type="range" 
                                               min="0" 
                                               max="100" 
                                               value="<?php echo htmlspecialchars($currentQuota); ?>"
                                               oninput="updateQuota(this.value)"
                                               class="blue">
                                    </div>

                                    <div class="quota-indicator center-align">
                                        <div class="chip" id="quotaChip">
                                            <i class="material-icons left tiny">info</i>
                                            <span id="quotaText"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col s12 center-align">
                                <button class="btn-large waves-effect waves-light blue darken-1 hoverable pulse" 
                                        type="submit"
                                        style="border-radius: 25px; padding: 0 2rem; margin-top: 1rem;">
                                    <i class="material-icons left">save</i>
                                    Einstellungen speichern
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.input-field input[type=number]:focus {
    border-bottom: 1px solid #1976D2 !important;
    box-shadow: 0 1px 0 0 #1976D2 !important;
}

.input-field input[type=number]:focus + label {
    color: #1976D2 !important;
}

.range-field input[type=range]::-webkit-slider-thumb {
    background-color: #1976D2 !important;
}

.range-field input[type=range]::-moz-range-thumb {
    background-color: #1976D2 !important;
}

.range-field input[type=range]::-ms-thumb {
    background-color: #1976D2 !important;
}

.card {
    border-radius: 12px;
}

.card-panel {
    transition: all 0.3s ease-in-out;
}

.card-panel:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 17px 2px rgba(0,0,0,0.14), 
                0 3px 14px 2px rgba(0,0,0,0.12), 
                0 5px 5px -3px rgba(0,0,0,0.2);
}

.chip {
    font-size: 1rem;
    height: auto;
    line-height: 2;
    padding: 8px 12px;
    border-radius: 16px;
}

.quota-indicator {
    margin-top: 1rem;
    min-height: 50px;
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    updateQuota(document.getElementById('homeoffice_quota').value);
    
    // Formular-Änderungen überwachen
    const form = document.getElementById('settingsForm');
    const originalValues = new FormData(form);
    
    form.addEventListener('input', function() {
        const currentValues = new FormData(form);
        let hasChanges = false;
        
        for(let pair of currentValues.entries()) {
            if(originalValues.get(pair[0]) !== pair[1]) {
                hasChanges = true;
                break;
            }
        }
        
        const saveButton = form.querySelector('button[type="submit"]');
        if(hasChanges) {
            saveButton.classList.add('pulse');
        } else {
            saveButton.classList.remove('pulse');
        }
    });
});

function updateQuota(value) {
    const chip = document.getElementById('quotaChip');
    const quotaText = document.getElementById('quotaText');
    document.getElementById('homeoffice_quota').value = value;
    
    // Update text and color based on value
    if(value <= 50) {
        chip.className = 'chip green white-text';
        quotaText.textContent = `${value}% - Innerhalb der empfohlenen Grenze`;
    } else if(value <= 75) {
        chip.className = 'chip orange white-text';
        quotaText.textContent = `${value}% - Über der empfohlenen 50% Grenze`;
    } else {
        chip.className = 'chip red white-text';
        quotaText.textContent = `${value}% - Sehr hoher Homeoffice-Anteil`;
    }
}
</script>

<?php include 'templates/footer.php'; ?>