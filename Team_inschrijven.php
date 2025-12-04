<?php
require_once 'functions.php';
include 'header.php';

$feedback = '';

// Prefill with logged-in gebruiker indien beschikbaar
$prefill = [
    'Voornaam' => '',
    'Achternaam' => '',
    'Telefoonnummer' => '',
    'Email' => ''
];
if (function_exists('isLoggedIn') && isLoggedIn() && !empty($_SESSION['user_email'])) {
    try {
        $stmt = $pdo->prepare("SELECT Voornaam, Achternaam, Telefoonnummer, Email FROM Spelers WHERE Email = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_email']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $prefill = $row;
        } else {
            // Als gebruiker niet in spelers-tabel staat, probeer e-mail uit sessie vullen
            $prefill['Email'] = $_SESSION['user_email'];
        }
    } catch (PDOException $e) {
        // negeren; prefill blijft leeg
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registreer'])) {
    $teamNaam = trim($_POST['teamnaam'] ?? '');
    if ($teamNaam === '') {
        $feedback = "Vul een geldige teamnaam in.";
    } else {
        $teamID = voegTeamToe($teamNaam);
        if ($teamID) {
            // Verwacht arrays: voornaam[], achternaam[], telefoonnummer[], email[]
            $voornamen = $_POST['voornaam'] ?? [];
            $achternamen = $_POST['achternaam'] ?? [];
            $telefoons = $_POST['telefoonnummer'] ?? [];
            $emails = $_POST['email'] ?? [];

            $added = 0;
            $failed = 0;
            $errors = [];
            $contactSpelerID = null;

            // Loop over entries by index
            $count = max(count($voornamen), count($emails), count($achternamen), count($telefoons));
            for ($i = 0; $i < $count; $i++) {
                $v = trim($voornamen[$i] ?? '');
                $a = trim($achternamen[$i] ?? '');
                $t = trim($telefoons[$i] ?? '');
                $e = trim($emails[$i] ?? '');

                // Sla lege rijen over
                if ($v === '' && $a === '' && $e === '') {
                    continue;
                }

                // E-mail is verplicht voor registratie van speler
                if ($e === '') {
                    $failed++;
                    $errors[] = "Speler op regel " . ($i+1) . " mist een e-mailadres.";
                    continue;
                }

                $spelerData = [
                    'TeamID' => $teamID,
                    'Voornaam' => $v,
                    'Achternaam' => $a,
                    'Telefoonnummer' => $t,
                    'Email' => $e
                ];

                $spelerId = voegSpelerToe($spelerData);
                if ($spelerId !== false && $spelerId !== null) {
                    $added++;
                    // Als dit de eerste rij is (aanmelder), onthoud als contactpersoon
                    if ($i === 0) {
                        $contactSpelerID = $spelerId;
                    }
                } else {
                    $failed++;
                    $errMsg = '';
                    if (defined('DEBUG') && DEBUG) {
                        $errMsg = ' (' . getLastDbError() . ')';
                    }
                    $errors[] = "Kon speler '{$e}' niet toevoegen (mogelijk bestaat e-mailadres al)." . $errMsg;
                }
            }

            $feedback = "Team '" . htmlspecialchars($teamNaam) . "' succesvol aangemaakt. ";
            $feedback .= ($added > 0) ? "{$added} spelers toegevoegd. " : "Geen spelers toegevoegd. ";
            if ($failed > 0) {
                $feedback .= "{$failed} spelers konden niet worden toegevoegd.";
            }
            if (!empty($errors) && defined('DEBUG') && DEBUG) {
                $feedback .= ' Fouten: ' . implode(' | ', $errors);
            }
            // Als we een contactpersoon-ID hebben gekregen, werk die dan in de Teams tabel bij
            if ($contactSpelerID !== null) {
                try {
                    $up = $pdo->prepare("UPDATE Teams SET ContactPersoonID = ? WHERE TeamID = ?");
                    $up->execute([$contactSpelerID, $teamID]);
                } catch (PDOException $e) {
                    if (defined('DEBUG') && DEBUG) {
                        $feedback .= ' (Kon contactpersoon niet instellen: ' . htmlspecialchars($e->getMessage()) . ')';
                    }
                }
            }
        } else {
            $feedback = "Fout: Team kon niet worden ingeschreven (bestaat de naam al?).";
        }
    }
}
?>

<div class="page">
    <h1 style="text-align:center;">Team inschrijven</h1>
    <?php if (!empty($feedback)): ?>
        <div class="message <?php echo (strpos($feedback, 'Fout') === 0) ? 'error' : 'success'; ?> spaced"><?php echo htmlspecialchars($feedback); ?></div>
    <?php endif; ?>

    <form method="POST" class="form-card">
        <div class="form-row">
            <div class="col"><input type="text" name="teamnaam" placeholder="Teamnaam" required></div>
        </div>

        <div id="spelers-container">
            <!-- Eén initiële speler-rij -->
            <div class="speler-row form-row">
                <input type="text" name="voornaam[]" placeholder="Voornaam" required value="<?php echo htmlspecialchars($prefill['Voornaam'] ?? ''); ?>">
                <input type="text" name="achternaam[]" placeholder="Achternaam" value="<?php echo htmlspecialchars($prefill['Achternaam'] ?? ''); ?>">
            </div>
            <div class="speler-row form-row">
                <input type="tel" name="telefoonnummer[]" placeholder="Telefoonnummer" value="<?php echo htmlspecialchars($prefill['Telefoonnummer'] ?? ''); ?>">
                <input type="email" name="email[]" placeholder="E-mail" required value="<?php echo htmlspecialchars($prefill['Email'] ?? ''); ?>">
                <button type="button" class="remove-player btn secondary" onclick="removePlayer(this)">Verwijder</button>
            </div>
        </div>

        <div class="form-row">
            <button type="button" id="add-player" class="btn ghost">Voeg speler toe</button>
            <button name="registreer" class="btn">Team inschrijven</button>
        </div>

        <!-- Contactpersoon velden (voor backend / notificaties) -->
        <input type="hidden" name="contact_voornaam" value="<?php echo htmlspecialchars($prefill['Voornaam'] ?? ''); ?>">
        <input type="hidden" name="contact_achternaam" value="<?php echo htmlspecialchars($prefill['Achternaam'] ?? ''); ?>">
        <input type="hidden" name="contact_telefoon" value="<?php echo htmlspecialchars($prefill['Telefoonnummer'] ?? ''); ?>">
        <input type="hidden" name="contact_email" value="<?php echo htmlspecialchars($prefill['Email'] ?? ''); ?>">
    </form>
</div>

<script>
// Eenvoudige JS om meerdere spelers toe te voegen/verwijderen
function removePlayer(btn) {
    const row = btn.closest('.speler-row');
    const container = document.getElementById('spelers-container');
    if (!row) return;
    // Laat minimaal één rij over
    if (container.querySelectorAll('.speler-row').length > 1) {
        row.remove();
    } else {
        // Clear inputs
        row.querySelectorAll('input').forEach(i => i.value = '');
    }
}

document.getElementById('add-player').addEventListener('click', function() {
    const container = document.getElementById('spelers-container');
    const template = document.createElement('div');
    template.className = 'speler-row';
    template.innerHTML = `
        <input type="text" name="voornaam[]" placeholder="Voornaam">
        <input type="text" name="achternaam[]" placeholder="Achternaam">
        <input type="tel" name="telefoonnummer[]" placeholder="Telefoonnummer">
        <input type="email" name="email[]" placeholder="E-mail">
        <button type="button" class="remove-player" onclick="removePlayer(this)">Verwijder</button>
    `;
    container.appendChild(template);
});
</script>