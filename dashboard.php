<?php
session_start();
require_once 'functions.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* Eenvoudige topbar stijl voor logoutknop */
        .topbar {
            position: relative;
            padding: 14px 16px;
            background: #f8fafc00;
        }
        .logout-btn {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: #ef4444;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }
        .logout-btn:hover { background: #dc2626; }
        .welcome-text { color: #111827; font-weight: 600; }
        .card-grid a { display:block; margin:6px 0; }
    </style>
</head>
<body>
    <div class="topbar">
        <?php if (isset($_SESSION['user_email'])): ?>
            <span class="welcome-text">Ingelogd als: <?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
            <a class="logout-btn" href="logout.php">Uitloggen</a>
        <?php endif; ?>
    </div>

    <h1>Dashboard</h1>
    <div class="card-grid">
        <a href="Team_inschrijven.php">Team inschrijven</a>
        <a href="Teams_bekijken.php">Teams bekijken</a>
        <a href="Wedstrijd_Plannen.php">Wedstrijd plannen</a>
        <a href="Uitslag_Invoeren.php">Uitslag invoeren</a>
        <a href="Klassement.php">Klassement</a>
    </div>
</body>
</html>