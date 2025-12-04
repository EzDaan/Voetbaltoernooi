<?php
require_once 'functions.php';
include 'header.php';

$klassement = getKlassement();
?>

<div class="page container">
    <h1>Klassement</h1>

    <div class="standings">
        <div class="standings-head">
            <div class="pos">#</div>
            <div class="team">Team</div>
            <div class="wgvm">W/G/V</div>
            <div class="vt">V/T</div>
            <div class="pts">Ptn</div>
        </div>

        <div class="standings-body">
            <?php foreach ($klassement as $i => $team): ?>
                <div class="standings-row">
                    <div class="pos"><?php echo $i + 1; ?></div>
                    <div class="team"><?php echo htmlspecialchars($team['Teamnaam']); ?></div>
                    <div class="wgvm"><?php echo htmlspecialchars("{$team['Winsten']}/{$team['Gelijkspelen']}/{$team['Verliezen']}"); ?></div>
                    <div class="vt"><?php echo htmlspecialchars("{$team['DoelpuntenVoor']}/{$team['DoelpuntenTegen']}"); ?></div>
                    <div class="pts"><?php echo htmlspecialchars($team['Score']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>