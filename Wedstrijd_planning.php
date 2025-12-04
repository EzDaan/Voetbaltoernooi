<?php
require_once 'functions.php';
include 'header.php';

$wedstrijden = getWedstrijden('Gepland');
?>
<div class="page">
    <h1>Wedstrijd planning</h1>

    <?php if (!empty($wedstrijden)): ?>
        <section class="match-grid">
            <?php foreach ($wedstrijden as $w): ?>
                <article class="match-card">
                    <div class="match-teams">
                        <div class="team home">
                            <div class="team-name"><?php echo htmlspecialchars($w['TeamThuisNaam']); ?></div>
                        </div>
                        <div class="versus">vs</div>
                        <div class="team away">
                            <div class="team-name"><?php echo htmlspecialchars($w['TeamUitNaam']); ?></div>
                        </div>
                    </div>

                    <div class="match-meta">
                        <div class="time"><?php echo date('d-m-Y H:i', strtotime($w['Tijdstip'])); ?></div>
                        <div class="location"><?php echo htmlspecialchars($w['Locatie']); ?></div>
                    </div>

                    <div class="match-actions">
                        <a href="Wedstrijd_details.php?id=<?php echo urlencode($w['id'] ?? $w['WedstrijdID'] ?? ''); ?>" class="btn ghost">Details</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php else: ?>
        <p class="spaced">Er zijn nog geen wedstrijden gepland.</p>
    <?php endif; ?>
</div>