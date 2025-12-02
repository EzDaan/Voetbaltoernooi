<?php
session_start();
require_once __DIR__ . '/../functions.php';
// Gebruik beheer-header voor admin navigatie
include __DIR__ . '/beheer_header.php';

// Alleen voor admins
// Zorg dat ROLE_ADMIN beschikbaar is (definitie kan in index.php staan)
if (!defined('ROLE_ADMIN')) {
    define('ROLE_ADMIN', 'admin');
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== ROLE_ADMIN) {
    header('Location: ../index.php');
    exit();
}

$feedback = '';
// Haal wedstrijden op die gepland zijn
$openWedstrijden = getWedstrijden('Gepland');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uitslag_toevoegen'])) {
    $wedstrijdId = isset($_POST['wedstrijd_id']) ? (int)$_POST['wedstrijd_id'] : 0;
    $scoreThuis = isset($_POST['score_thuis']) ? (int)$_POST['score_thuis'] : null;
    $scoreUit = isset($_POST['score_uit']) ? (int)$_POST['score_uit'] : null;

    if ($wedstrijdId <= 0 || $scoreThuis === null || $scoreUit === null) {
        $feedback = 'Fout: controleer geselecteerde wedstrijd en scores.';
    } else {
        // Zoek wedstrijd (type-veilig vergelijken)
        $found = null;
        foreach ($openWedstrijden as $w) {
            if ((int)$w['WedstrijdID'] === $wedstrijdId) {
                $found = $w;
                break;
            }
        }

        if ($found) {
            $ok = voerUitslagIn($wedstrijdId, $scoreThuis, $scoreUit, (int)$found['TeamThuisID'], (int)$found['TeamUitID']);
            if ($ok) {
                $feedback = 'Uitslag succesvol ingevoerd! Het klassement is bijgewerkt.';
                // Refresh lijst van open wedstrijden
                $openWedstrijden = getWedstrijden('Gepland');
            } else {
                $feedback = 'Fout bij het invoeren van de uitslag.';
            }
        } else {
            $feedback = 'Geselecteerde wedstrijd niet gevonden.';
        }
    }
}
?>

<h1>Uitslagen invoeren (beheerder)</h1>
<p><?php echo htmlspecialchars($feedback); ?></p>
<form method="POST">
    <label for="wedstrijd_id">Wedstrijd:</label>
    <select name="wedstrijd_id" id="wedstrijd_id" required>
        <option value="">-- Kies Wedstrijd --</option>
        <?php foreach ($openWedstrijden as $w): ?>
        <option value="<?php echo (int)$w['WedstrijdID']; ?>"><?php echo htmlspecialchars($w['TeamThuisNaam'] . ' vs ' . $w['TeamUitNaam'] . ' (' . date('d-m-Y H:i', strtotime($w['Tijdstip'])) . ')'); ?></option>
        <?php endforeach; ?>
    </select>
    <label for="score_thuis">Score Thuis:</label>
    <input type="number" name="score_thuis" id="score_thuis" placeholder="Score Thuis" required min="0">
    <label for="score_uit">Score Uit:</label>
    <input type="number" name="score_uit" id="score_uit" placeholder="Score Uit" required min="0">
    <button name="uitslag_toevoegen">Toevoegen</button>
</form>

<h2>Nog te spelen wedstrijden</h2>
<?php if (!empty($openWedstrijden)): ?>
<table border="1" cellpadding="6" style="border-collapse:collapse;">
    <thead><tr><th>Thuis</th><th>Uit</th><th>Tijdstip</th><th>Locatie</th></tr></thead>
    <tbody>
    <?php foreach ($openWedstrijden as $w): ?>
        <tr>
            <td><?php echo htmlspecialchars($w['TeamThuisNaam']); ?></td>
            <td><?php echo htmlspecialchars($w['TeamUitNaam']); ?></td>
            <td><?php echo date('d-m-Y H:i', strtotime($w['Tijdstip'])); ?></td>
            <td><?php echo htmlspecialchars($w['Locatie']); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p>Geen geplande wedstrijden gevonden.</p>
<?php endif; ?>