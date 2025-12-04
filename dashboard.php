<?php
require_once 'functions.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'header.php';
?>

<div class="page container">
    <h1>Dashboard</h1>

    <section class="card-grid">
        <a class="card" href="Team_inschrijven.php">
            <h3>Team inschrijven</h3>
            <p>Registreer een nieuw team en spelers.</p>
        </a>

        <a class="card" href="Teams_bekijken.php">
            <h3>Teams bekijken</h3>
            <p>Bekijk alle geregistreerde teams en spelers.</p>
        </a>

        <a class="card" href="Wedstrijd_Planning.php">
            <h3>Wedstrijd planning</h3>
            <p>Bekijk en beheer geplande wedstrijden.</p>
        </a>

        <a class="card" href="beheerder_pagina's/Uitslag_Invoeren.php">
            <h3>Uitslag invoeren</h3>
            <p>Voer uitslagen in en werk het klassement bij.</p>
        </a>

        <a class="card" href="Klassement.php">
            <h3>Klassement</h3>
            <p>Bekijk de huidige ranglijst.</p>
        </a>
    </section>
</div>