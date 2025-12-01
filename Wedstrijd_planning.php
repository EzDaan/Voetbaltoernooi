<?php
require_once 'functions.php';
include 'header.php';

$wedstrijden = getWedstrijden('Gepland');
?>
<h1>Wedstrijd planning</h1>
<?php if (!empty($wedstrijden)): ?>
<table border="1" cellpadding="6" style="border-collapse:collapse; min-width:600px;">
    <thead>
        <tr>
            <th>Thuis</th>
            <th>Uit</th>
            <th>Tijdstip</th>
            <th>Locatie</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($wedstrijden as $w): ?>
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
<p>Er zijn nog geen wedstrijden gepland.</p>
<?php endif; ?>