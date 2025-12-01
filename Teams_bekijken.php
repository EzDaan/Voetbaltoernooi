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
<div style="padding:18px;">
    <h1>Teams overzicht</h1>
    <table style="border-collapse:collapse; width:800px;" border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Teamnaam</th>
                <th>Contactpersoon</th>
                <th>Spelers</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($teams as $team): ?>
            <tr>
                <td><?php echo htmlspecialchars($team['Naam']); ?></td>
                <td>
                    <?php
                    $contact = null;
                    if (!empty($team['ContactPersoonID'])) {
                        $contact = getContactPersoonById($team['ContactPersoonID']);
                    }
                    if ($contact) {
                        echo htmlspecialchars($contact['Voornaam'] . ' ' . $contact['Achternaam']) . '<br>';
                        echo '<a href="mailto:' . htmlspecialchars($contact['Email']) . '">' . htmlspecialchars($contact['Email']) . '</a>';
                    } else {
                        echo '<em>Niet ingesteld</em>';
                    }
                    ?>
                </td>
                <td>
                    <ul style="margin:0; padding-left:18px;">
                    <?php
                    $spelers = getTeamDetailsMetSpelers($team['TeamID']);
                    if (!empty($spelers)) {
                        foreach ($spelers as $speler) {
                            echo '<li>' . htmlspecialchars(trim($speler['Voornaam'] . ' ' . $speler['Achternaam'])) . ' (' . htmlspecialchars($speler['Email']) . ')</li>';
                        }
                    } else {
                        echo '<li><em>Geen spelers</em></li>';
                    }
                    ?>
                    </ul>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>