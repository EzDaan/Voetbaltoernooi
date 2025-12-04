<?php
require_once 'functions.php';
include 'header.php';

$teams = getAlleTeams();

function getContactPersoonById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT Voornaam, Achternaam, Email FROM Spelers WHERE SpelerID = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<div class="page">
    <h1 style="text-align:center; margin-bottom:18px;">Teams overzicht</h1>
    <div class="team-section">
    <?php foreach ($teams as $team): ?>
        <div class="team-card">
            <h3><?php echo htmlspecialchars($team['Naam']); ?></h3>
            <?php
                $spelers = getTeamDetailsMetSpelers($team['TeamID']);
            ?>
            <ul class="player-list">
            <?php if (!empty($spelers)) {
                foreach ($spelers as $speler) {
                    echo '<li>' . htmlspecialchars(trim($speler['Voornaam'] . ' ' . $speler['Achternaam']));
                    if (!empty($speler['Email'])) {
                        echo ' &mdash; <a href="mailto:' . htmlspecialchars($speler['Email']) . '">' . htmlspecialchars($speler['Email']) . '</a>';
                    }
                    echo '</li>';
                }
            } else {
                echo '<li><em>Geen spelers</em></li>';
            }
            ?>
            </ul>
            <div class="contact">
                <?php
                $contact = null;
                if (!empty($team['ContactPersoonID'])) {
                    $contact = getContactPersoonById($team['ContactPersoonID']);
                }
                if ($contact) {
                    echo htmlspecialchars($contact['Voornaam'] . ' ' . $contact['Achternaam']) . '<br>';
                    echo '<a href="mailto:' . htmlspecialchars($contact['Email']) . '">' . htmlspecialchars($contact['Email']) . '</a>';
                } else {
                    echo '<em>Contact niet ingesteld</em>';
                }
                ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>