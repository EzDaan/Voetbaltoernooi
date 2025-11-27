<?php
require_once 'functions.php';

$feedback = '';
// Haal wedstrijden op die nog niet gespeeld zijn OF wel, maar nog geen score hebben
$openWedstrijden = getWedstrijden('Gepland'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uitslag_toevoegen'])) {
    $wedstrijdId = (int)$_POST['wedstrijd_id'];
    $scoreThuis = (int)$_POST['score_thuis'];
    $scoreUit = (int)$_POST['score_uit'];
    
    // Zoek de team ID's op van de geselecteerde wedstrijd
    $wedstrijfDetails = array_filter($openWedstrijden, function($w) use ($wedstrijdId) {
        return $w['WedstrijdID'] === $wedstrijdId;
    });
    $w = reset($wedstrijfDetails); // Pak het eerste (en enige) resultaat

    if ($w && voerUitslagIn($wedstrijdId, $scoreThuis, $scoreUit, $w['TeamThuisID'], $w['TeamUitID'])) {
        $feedback = "Uitslag succesvol ingevoerd! Het klassement is bijgewerkt.";
        $openWedstrijden = getWedstrijden('Gepland'); // Refresh lijst
    } else {
        $feedback = "Fout bij het invoeren van de uitslag.";
    }
}
?>

<h1>Uitslagen invoeren</h1>
<p><?php echo $feedback; ?></p>
<form method="POST">
    <select name="wedstrijd_id" required>
        <option value="">-- Kies Wedstrijd --</option>
        <?php foreach ($openWedstrijden as $w): ?>
        <option value="<?php echo $w['WedstrijdID']; ?>">
            <?php echo "{$w['TeamThuisNaam']} vs {$w['TeamUitNaam']} ({$w['Tijdstip']})"; ?>
        </option>
        <?php endforeach; ?>
    </select>
    <input type="number" name="score_thuis" placeholder="Score Thuis" required min="0">
    <input type="number" name="score_uit" placeholder="Score Uit" required min="0">
    <button name="uitslag_toevoegen">Toevoegen</button>
</form>

<h2>Uitslagen recente wedstrijden</h2>