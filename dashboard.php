<?php
require_once 'functions.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'header.php';
?>

<div class="card-grid container">
    <h1>Dashboard</h1>
    <div class="card-grid-links">
        <a href="Team_inschrijven.php">Team inschrijven</a>
        <a href="Teams_bekijken.php">Teams bekijken</a>
        <a href="Wedstrijd_Planning.php">Wedstrijd plannen</a>
        <a href="Uitslag_Invoeren.php">Uitslag invoeren</a>
        <a href="Klassement.php">Klassement</a>
    </div>
</div>