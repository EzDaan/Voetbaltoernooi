<?php
require_once 'functions.php';
include 'header.php';

$feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registreer'])) {
    $teamNaam = $_POST['teamnaam'];
    $teamID = voegTeamToe($teamNaam);

    if ($teamID) {
        $spelerData = [
            'TeamID' => $teamID,
            'Voornaam' => $_POST['voornaam'],
            'Achternaam' => $_POST['achternaam'],
            'Telefoonnummer' => $_POST['telefoonnummer'],
            'Email' => $_POST['email']
        ];
        
        if (voegSpelerToe($spelerData)) {
            $feedback = "Team '{$teamNaam}' succesvol ingeschreven inclusief speler!";
        } else {
            // Team is aangemaakt, maar speler niet (bijv. e-mail bestaat al)
            $feedback = "Team '{$teamNaam}' aangemaakt, maar spelerregistratie mislukt.";
        }
    } else {
        $feedback = "Fout: Team kon niet worden ingeschreven (bestaat de naam al?).";
    }
}
?>

<h1>Team inschrijven</h1>
<p><?php echo $feedback; ?></p>
<form method="POST">
    <input type="text" name="teamnaam" placeholder="Teamnaam" required>
    
    <input type="text" name="voornaam" placeholder="Voornaam" required>
    <input type="text" name="achternaam" placeholder="Achternaam" required>
    <input type="tel" name="telefoonnummer" placeholder="Telefoonnummer">
    <input type="email" name="email" placeholder="E-mail" required>
    
    <button name="registreer">Toevoegen</button>
</form>