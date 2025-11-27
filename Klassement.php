<?php
require_once 'functions.php';

$klassement = getKlassement();
?>

<h1>Klassement</h1>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Teamnaam</th>
            <th>W/G/V</th>
            <th>V/T</th>
            <th>Score (Ptn)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($klassement as $i => $team): ?>
        <tr>
            <td><?php echo $i + 1; ?></td>
            <td><?php echo htmlspecialchars($team['Teamnaam']); ?></td>
            <td><?php echo "{$team['Winsten']}/{$team['Gelijkspelen']}/{$team['Verliezen']}"; ?></td>
            <td><?php echo "{$team['DoelpuntenVoor']}/{$team['DoelpuntenTegen']}"; ?></td>
            <td><?php echo $team['Score']; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>