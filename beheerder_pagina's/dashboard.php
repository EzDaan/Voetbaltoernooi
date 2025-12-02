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

// Haal data op voor het dashboard
$teams = getAlleTeams();
$wedstrijden = getWedstrijden('Alle');
$klassement = getKlassement();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Beheerder Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .topbar { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; background:#f3f4f6; }
        .topbar .nav { display:flex; gap:12px; align-items:center; }
        .logout-button { background:#e11d48; color:#fff; padding:6px 10px; border-radius:6px; text-decoration:none; font-weight:600; }
        .logout-button:hover { background:#be123c; }
        .dashboard { padding:20px; max-width:1200px; margin:0 auto; }
        .dashboard h1 { color:#1e293b; margin-bottom:10px; }
        .dashboard p { color:#64748b; margin-bottom:20px; }
        .stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:30px; }
        .stat-card { background:#f1f5f9; padding:15px; border-radius:8px; border-left:4px solid #3b82f6; }
        .stat-card h3 { margin:0 0 5px 0; font-size:0.9rem; color:#64748b; }
        .stat-card .number { font-size:2rem; font-weight:bold; color:#1e293b; }
        .section { margin-bottom:30px; }
        .section h2 { color:#1e293b; border-bottom:2px solid #e2e8f0; padding-bottom:10px; margin-bottom:15px; }
        table { width:100%; border-collapse:collapse; }
        table th { background:#f1f5f9; padding:10px; text-align:left; border:1px solid #e2e8f0; }
        table td { padding:10px; border:1px solid #e2e8f0; }
        table tr:hover { background:#f8fafc; }
        .action-links { display:flex; gap:10px; }
        .action-links a { padding:6px 12px; background:#3b82f6; color:#fff; border-radius:4px; text-decoration:none; font-size:0.85rem; }
        .action-links a:hover { background:#2563eb; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="nav">
            <a href="dashboard.php"><strong>Dashboard</strong></a>
            <a href="Wedstrijd_Plannen.php">Wedstrijdplanning</a>
            <a href="#klassement">Klassement</a>
        </div>
        <div>
            <a href="../logout.php" class="logout-button">Uitloggen</a>
        </div>
    </div>

    <div class="dashboard">
        <h1>Beheerder Dashboard</h1>
        <p>Welkom <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>

        <!-- Statistieken -->
        <div class="stats">
            <div class="stat-card">
                <h3>Totaal Teams</h3>
                <div class="number"><?php echo count($teams); ?></div>
            </div>
            <div class="stat-card">
                <h3>Geplande Wedstrijden</h3>
                <div class="number"><?php echo count(array_filter($wedstrijden, function($w) { return $w['Status'] === 'Gepland'; })); ?></div>
            </div>
            <div class="stat-card">
                <h3>Gespeelde Wedstrijden</h3>
                <div class="number"><?php echo count(array_filter($wedstrijden, function($w) { return $w['Status'] === 'Gespeeld'; })); ?></div>
            </div>
        </div>

        <!-- Teams Sectie -->
        <div class="section">
            <h2>Teams</h2>
            <?php if (!empty($teams)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Team ID</th>
                        <th>Teamnaam</th>
                        <th>Acties</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teams as $team): ?>
                    <tr>
                        <td><?php echo $team['TeamID']; ?></td>
                        <td><?php echo htmlspecialchars($team['Naam']); ?></td>
                        <td>
                            <div class="action-links">
                                <a href="#">Details</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>Geen teams gevonden.</p>
            <?php endif; ?>
        </div>

        <!-- Wedstrijden Sectie -->
        <div class="section">
            <h2>Recente Wedstrijden</h2>
            <?php if (!empty($wedstrijden)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Thuis</th>
                        <th>Uit</th>
                        <th>Tijdstip</th>
                        <th>Locatie</th>
                        <th>Status</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($wedstrijden, 0, 5) as $w): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($w['TeamThuisNaam']); ?></td>
                        <td><?php echo htmlspecialchars($w['TeamUitNaam']); ?></td>
                        <td><?php echo date('d-m-Y H:i', strtotime($w['Tijdstip'])); ?></td>
                        <td><?php echo htmlspecialchars($w['Locatie'] ?? '-'); ?></td>
                        <td><?php echo $w['Status']; ?></td>
                        <td><?php echo ($w['ScoreThuis'] !== null ? $w['ScoreThuis'] . ' - ' . $w['ScoreUit'] : '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>Geen wedstrijden gevonden.</p>
            <?php endif; ?>
        </div>

        <!-- Klassement Sectie -->
        <div class="section" id="klassement">
            <h2>Klassement</h2>
            <?php if (!empty($klassement)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Positie</th>
                        <th>Team</th>
                        <th>Gespeeld</th>
                        <th>Winsten</th>
                        <th>Gelijkspelen</th>
                        <th>Verliezen</th>
                        <th>Punten</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $pos = 1; foreach ($klassement as $team): ?>
                    <tr>
                        <td><strong><?php echo $pos++; ?></strong></td>
                        <td><?php echo htmlspecialchars($team['Teamnaam']); ?></td>
                        <td><?php echo $team['Gespeeld'] ?? 0; ?></td>
                        <td><?php echo $team['Winsten'] ?? 0; ?></td>
                        <td><?php echo $team['Gelijkspelen'] ?? 0; ?></td>
                        <td><?php echo $team['Verliezen'] ?? 0; ?></td>
                        <td><strong><?php echo $team['Score'] ?? 0; ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>Geen klassement beschikbaar.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
