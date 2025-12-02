<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../functions.php';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Voetbaltoernooi</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .topbar { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; background:#f3f4f6; }
        .topbar .nav { display:flex; gap:12px; align-items:center; }
        .logout-button { background:#e11d48; color:#fff; padding:6px 10px; border-radius:6px; text-decoration:none; font-weight:600; }
        .logout-button:hover { background:#be123c; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="Wedstrijd_Plannen.php">Wedstrijdplanning</a>
            <a href="Uitslag_Invoeren.php">Uitslagen invoeren</a>
        </div>
        <div>
            <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                <a href="../logout.php" class="logout-button">Uitloggen</a>
            <?php else: ?>
                <a href="../index.php">Inloggen</a>
            <?php endif; ?>
        </div>
    </div>
