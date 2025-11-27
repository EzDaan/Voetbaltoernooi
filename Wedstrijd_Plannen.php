<?php
require_once 'functions.php';

$feedback = '';
$teams = getAlleTeams();
$wedstrijden = getWedstrijden('Gepland'); // Toon alleen geplande wedstrijden

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_wedstrijd'])) {
    $thuisId = (int)$_POST['team1'];
    $uitId = (int)$_POST['team2'];
    $tijdstip = $_POST['datum'] . ' ' . $_POST['tijd'] . ':00';
    $locatie = $_POST['locatie'];

    if ($thuisId === $uitId) {
        $feedback = "Fout: Een team kan niet tegen zichzelf spelen!";
    } elseif (planWedstrijd($thuisId, $uitId, $tijdstip, $locatie)) {
        $feedback = "Wedstrijd succesvol gepland!";
        $wedstrijden = getWedstrijden('Gepland'); // Refresh lijst
    } else {
        $feedback = "Fout bij het plannen van de wedstrijd.";
    }
}
?>

<h1>Wedstrijd plannen</h1>
<p><?php echo $feedback; ?></p>
<form method="POST">
    <select name="team1"><?php foreach ($teams as $t) echo "<option value='{$t['TeamID']}'>{$t['Naam']}</option>"; ?></select>
    <select name="team2"><?php foreach ($teams as $t) echo "<option value='{$t['TeamID']}'>{$t['Naam']}</option>"; ?></select>
    <input type="datetime-local" name="tijdstip" required> <input type="text" name="locatie" placeholder="Locatie">
    <button name="plan_wedstrijd">Wedstrijd toevoegen</button>
</form>

<h2>Wedstrijd planning</h2>
<?php if (!empty($wedstrijden)): ?>
<table>
    <thead><tr><th>Thuis</th><th>Uit</th><th>Tijdstip</th><th>Locatie</th></tr></thead>
    <tbody>
        <?php foreach ($wedstrijden as $w): ?>
        <tr>
            <td><?php echo $w['TeamThuisNaam']; ?></td>
            <td><?php echo $w['TeamUitNaam']; ?></td>
            <td><?php echo date('d-m-Y H:i', strtotime($w['Tijdstip'])); ?></td>
            <td><?php echo $w['Locatie']; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>