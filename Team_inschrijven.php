<?php
require_once 'functions.php';
include 'header.php';

$feedback = '';

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

                if (voegSpelerToe($spelerData)) {
                    $added++;
                } else {
                    $failed++;
                    $errors[] = "Kon speler '{$e}' niet toevoegen (mogelijk bestaat e-mailadres al).";
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
        } else {
            $feedback = "Fout: Team kon niet worden ingeschreven (bestaat de naam al?).";
        }
    }
}
?>

<h1>Team inschrijven</h1>
<p><?php echo $feedback; ?></p>
<form method="POST">
    <input type="text" name="teamnaam" placeholder="Teamnaam" required>

    <div id="spelers-container">
        <!-- Eén initiële speler-rij -->
        <div class="speler-row">
            <input type="text" name="voornaam[]" placeholder="Voornaam" required>
            <input type="text" name="achternaam[]" placeholder="Achternaam">
            <input type="tel" name="telefoonnummer[]" placeholder="Telefoonnummer">
            <input type="email" name="email[]" placeholder="E-mail" required>
            <button type="button" class="remove-player" onclick="removePlayer(this)">Verwijder</button>
        </div>
    </div>

    <p>
        <button type="button" id="add-player">Voeg speler toe</button>
    </p>

    <button name="registreer">Team en spelers inschrijven</button>
</form>

<script>
// Eenvoudige JS om meerdere spelers toe te voegen/verwijderen
function removePlayer(btn) {
    const row = btn.closest('.speler-row');
    const container = document.getElementById(' spelers-container');
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