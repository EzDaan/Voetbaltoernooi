<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../functions.php';

// Controleer of gebruiker is ingelogd en admin is
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

// Zorg ervoor dat ROLE_ADMIN is gedefinieerd
if (!defined('ROLE_ADMIN')) {
    define('ROLE_ADMIN', 'admin');
}

// Controleer of gebruiker admin is
if ($_SESSION['user_role'] !== ROLE_ADMIN) {
    header('Location: ../Team_inschrijven.php');
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
        // Zoek wedstrijd (type-veilig vergelijken.)
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
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Uitslagen Invoeren</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .container { padding:20px; max-width:1000px; margin:0 auto; }
        .container h1 { color:#1e293b; margin-bottom:15px; }
        .container h2 { color:#1e293b; margin-top:30px; margin-bottom:15px; border-bottom:2px solid #e2e8f0; padding-bottom:10px; }
        .form-group { margin-bottom:15px; }
        .form-group label { display:block; margin-bottom:5px; color:#1e293b; font-weight:500; }
        .form-group input, .form-group select { width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:1rem; }
        .form-group input:focus, .form-group select:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59, 130, 246, 0.1); }
        .button-group { display:flex; gap:10px; margin-top:20px; }
        button { padding:10px 20px; background:#3b82f6; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:600; }
        button:hover { background:#2563eb; }
        .feedback { padding:12px; border-radius:6px; margin-bottom:20px; }
        .feedback.success { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .feedback.error { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
        table { width:100%; border-collapse:collapse; margin-top:15px; }
        table th { background:#f1f5f9; padding:10px; text-align:left; border:1px solid #e2e8f0; font-weight:600; }
        table td { padding:10px; border:1px solid #e2e8f0; }
        table tr:hover { background:#f8fafc; }
    </style>
</head>
<body>
    <?php include 'beheer_header.php'; ?>
    <div class="container">
        <h1>Uitslagen Invoeren</h1>
        
        <?php if (!empty($feedback)): ?>
            <div class="feedback <?php echo strpos($feedback, 'Fout') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($feedback); ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="background:#f8fafc; padding:20px; border-radius:8px; border:1px solid #e2e8f0;">
            <div class="form-group">
                <label for="wedstrijd_id">Wedstrijd *</label>
                <select name="wedstrijd_id" id="wedstrijd_id" required>
                    <option value="">-- Selecteer wedstrijd --</option>
                    <?php foreach ($openWedstrijden as $w): ?>
                    <option value="<?php echo (int)$w['WedstrijdID']; ?>"><?php echo htmlspecialchars($w['TeamThuisNaam'] . ' vs ' . $w['TeamUitNaam'] . ' (' . date('d-m-Y H:i', strtotime($w['Tijdstip'])) . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="score_thuis">Score Thuis *</label>
                <input type="number" name="score_thuis" id="score_thuis" placeholder="Bijv. 2" required min="0">
            </div>

            <div class="form-group">
                <label for="score_uit">Score Uit *</label>
                <input type="number" name="score_uit" id="score_uit" placeholder="Bijv. 1" required min="0">
            </div>

            <div class="button-group">
                <button type="submit" name="uitslag_toevoegen">Uitslag Toevoegen</button>
            </div>
        </form>

        <h2>Nog Te Spelen Wedstrijden</h2>
        <?php if (!empty($openWedstrijden)): ?>
        <table>
            <thead>
                <tr>
                    <th>Thuis</th>
                    <th>Uit</th>
                    <th>Datum & Tijd</th>
                    <th>Locatie</th>
                </tr>
            </thead>
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
    </div>
</body>
</html>