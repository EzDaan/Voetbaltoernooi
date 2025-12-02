<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define admin-specific nav and include the shared header
$navLinks = [
    ['href' => "beheerder_pagina's/dashboard.php", 'label' => 'Dashboard'],
    ['href' => "beheerder_pagina's/Wedstrijd_Plannen.php", 'label' => 'Wedstrijdplanning'],
    ['href' => "beheerder_pagina's/Uitslag_Invoeren.php", 'label' => 'Uitslagen invoeren'],
];

include __DIR__ . '/../header.php';
?>
