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
$teams = getAlleTeams();
$wedstrijden = getWedstrijden('Gepland'); // Toon alleen geplande wedstrijden

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_wedstrijd'])) {
    $thuisId = (int)$_POST['team1'];
    $uitId = (int)$_POST['team2'];
    $datum = $_POST['datum'] ?? '';
    $tijd = $_POST['tijd'] ?? '';
    $tijdstip = $datum . ' ' . $tijd . ':00';
    $locatie = $_POST['locatie'] ?? '';

    if ($thuisId === $uitId) {
        $feedback = "Fout: Een team kan niet tegen zichzelf spelen!";
    } elseif (planWedstrijd($thuisId, $uitId, $tijdstip, $locatie)) {
        $feedback = "Wedstrijd succesvol gepland!";
        $wedstrijden = getWedstrijden('Gepland'); // Refresh lijst
    } else {
        $feedback = "Fout bij het plannen van de wedstrijd.";
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wedstrijden Plannen</title>
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
        <h1>Wedstrijd Plannen</h1>
        
        <?php if (!empty($feedback)): ?>
            <div class="feedback <?php echo strpos($feedback, 'Fout') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($feedback); ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="background:#f8fafc; padding:20px; border-radius:8px; border:1px solid #e2e8f0;">
            <div class="form-group">
                <label for="team1">Thuis Team *</label>
                <select name="team1" id="team1" required>
                    <option value="">-- Selecteer team --</option>
                    <?php foreach ($teams as $t): ?>
                        <option value="<?php echo $t['TeamID']; ?>"><?php echo htmlspecialchars($t['Naam']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="team2">Uit Team *</label>
                <select name="team2" id="team2" required>
                    <option value="">-- Selecteer team --</option>
                    <?php foreach ($teams as $t): ?>
                        <option value="<?php echo $t['TeamID']; ?>"><?php echo htmlspecialchars($t['Naam']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="datum">Datum *</label>
                <input type="date" name="datum" id="datum" required>
            </div>

            <div class="form-group">
                <label for="tijd">Tijd *</label>
                <input type="time" name="tijd" id="tijd" required>
            </div>

            <div class="form-group">
                <label for="locatie">Locatie *</label>
                <input type="text" name="locatie" id="locatie" placeholder="Bijv. Sportpark de Zeeuw" required>
            </div>

            <div class="button-group">
                <button type="submit" name="plan_wedstrijd">Wedstrijd Toevoegen</button>
            </div>
        </form>

        <h2>Geplande Wedstrijden</h2>
        <?php if (!empty($wedstrijden)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Thuis Team</th>
                        <th>Uit Team</th>
                        <th>Datum &amp; Tijd</th>
                        <th>Locatie</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wedstrijden as $w): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($w['TeamThuisNaam']); ?></td>
                        <td><?php echo htmlspecialchars($w['TeamUitNaam']); ?></td>
                        <td><?php echo date('d-m-Y H:i', strtotime($w['Tijdstip'])); ?></td>
                        <td><?php echo htmlspecialchars($w['Locatie'] ?? '-'); ?></td>
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