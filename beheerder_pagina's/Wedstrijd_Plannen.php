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
<?php include 'beheer_header.php'; ?>
<div class="page container">
        <h1>Wedstrijd Plannen</h1>
        
        <?php if (!empty($feedback)): ?>
            <div class="feedback <?php echo strpos($feedback, 'Fout') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($feedback); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="form-card">
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
            <section class="match-grid">
                <?php foreach ($wedstrijden as $w): ?>
                    <article class="match-card">
                        <div class="match-teams">
                            <div class="team home"><div class="team-name"><?php echo htmlspecialchars($w['TeamThuisNaam']); ?></div></div>
                            <div class="versus">vs</div>
                            <div class="team away"><div class="team-name"><?php echo htmlspecialchars($w['TeamUitNaam']); ?></div></div>
                        </div>
                        <div class="match-meta">
                            <div class="time"><?php echo date('d-m-Y H:i', strtotime($w['Tijdstip'])); ?></div>
                            <div class="location"><?php echo htmlspecialchars($w['Locatie'] ?? '-'); ?></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php else: ?>
            <p>Geen geplande wedstrijden gevonden.</p>
        <?php endif; ?>
    </div>
    </body>
    </html>