<?php require_once 'functions.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
        .topbar { position: relative; padding: 12px 16px; }
        .logout-button { position: absolute; right: 16px; top: 12px; background:#e11d48; color:#fff; padding:8px 12px; text-decoration:none; border-radius:6px; font-weight:600; }
        .logout-button:hover { background:#be123c; }
        .card-grid { margin-top: 60px; }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>Dashboard</h1>
        <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
            <a href="logout.php" class="logout-button">Uitloggen</a>
        <?php endif; ?>
    </div>
    <div class="card-grid">
        <a href="Team_inschrijven.php">Team inschrijven</a>
        <a href="Teams_bekijken.php">Teams bekijken</a>
        <a href="Wedstrijd_Plannen.php">Wedstrijd plannen</a>
        <a href="Uitslag_Invoeren.php">Uitslag invoeren</a>
        <a href="Klassement.php">Klassement</a>
    </div>
</body>
</html>