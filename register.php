<?php
session_start();
include 'config.php';

// Fallback definitions in case config.php doesn't define these constants
if (!defined('ROLE_BEZOEKER')) {
    define('ROLE_BEZOEKER', 'bezoeker');
}
if (!defined('ROLE_ADMIN')) {
    define('ROLE_ADMIN', 'admin');
}
if (!defined('ADMIN_PAGE')) {
    define('ADMIN_PAGE', 'admin.php');
}
if (!defined('HOME_PAGE')) {
    define('HOME_PAGE', 'index.php');
}

$signup_message = '';
$signup_message_type = '';
$email_value = ''; // Houd de ingevoerde e-mail vast

// Toggle debug to true while diagnosing registration issues
if (!defined('DEBUG')) {
    define('DEBUG', true);
}

// Als gebruiker al ingelogd is, doorsturen naar de juiste pagina
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === ROLE_ADMIN) {
        header("Location: beheerder_pagina's/dashboard.php");
    } else {
        header("Location: Team_inschrijven.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voornaam = isset($_POST['voornaam']) ? trim($_POST['voornaam']) : '';
    $achternaam = isset($_POST['achternaam']) ? trim($_POST['achternaam']) : '';
    $telefoon = isset($_POST['telefoon']) ? trim($_POST['telefoon']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    // Geen 'remember_me' op de registratiepagina (alleen bij inloggen)
    $email_value = htmlspecialchars($email); // Onthoud de ingevoerde waarde
    
    if (!empty($voornaam) && !empty($achternaam) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            if (strlen($password) >= 6) {
                // Controleer eerst of e-mail al bestaat in 'spelers'
                try {
                    $stmt = $pdo->prepare("SELECT SpelerID FROM spelers WHERE Email = ?");
                    $stmt->execute([$email]);
                    $exists_speler = ($stmt->rowCount() > 0);
                } catch (PDOException $e) {
                    $exists_speler = false;
                    if (defined('DEBUG') && DEBUG) {
                        // als spelers niet bestaat, noteer dit maar ga verder naar poging in users
                        $signup_message = "DEBUG: check spelers-fout: " . $e->getMessage();
                        $signup_message_type = "error";
                    }
                }

                if ($exists_speler) {
                    $signup_message = "Dit e-mailadres is al in gebruik.";
                    $signup_message_type = "error";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    // Probeer insert in 'spelers' (TeamID kan NULL worden gelaten)
                    try {
                        $stmt = $pdo->prepare("INSERT INTO spelers (TeamID, Voornaam, Achternaam, Telefoonnummer, Email, Wachtwoord, Rol) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        // TeamID zetten we op NULL zodat bestaande DB geen probleem geeft als kolom nullable is
                        $teamid = null;
                        $stmt->execute([$teamid, $voornaam, $achternaam, $telefoon, $email, $hashed_password, ROLE_BEZOEKER]);
                        $signup_message = "Account succesvol aangemaakt! Je kunt nu inloggen.";
                        $signup_message_type = "success";
                        $email_value = '';
                    } catch (PDOException $e) {
                        // Als spelers-tabel niet bestaat of insert faalt, probeer fallback naar 'users'
                        if ($e->getCode() === '42S02') {
                            try {
                                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                                $stmt->execute([$email]);
                                if ($stmt->rowCount() > 0) {
                                    $signup_message = "Dit e-mailadres is al in gebruik.";
                                    $signup_message_type = "error";
                                } else {
                                    $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
                                    $stmt->execute([$email, $hashed_password, ROLE_BEZOEKER]);
                                    $signup_message = "Account succesvol aangemaakt (users)! Je kunt nu inloggen.";
                                    $signup_message_type = "success";
                                    $email_value = '';
                                }
                            } catch (PDOException $e2) {
                                $signup_message_type = "error";
                                if (defined('DEBUG') && DEBUG) {
                                    $signup_message = "Er is een fout opgetreden bij het invoegen van gebruiker: " . $e2->getMessage();
                                } else {
                                    $signup_message = "Er is een fout opgetreden bij het aanmelden.";
                                }
                            }
                        } else {
                            $signup_message_type = "error";
                            if (defined('DEBUG') && DEBUG) {
                                $signup_message = "Er is een fout opgetreden bij het invoegen in spelers: " . $e->getMessage();
                            } else {
                                $signup_message = "Er is een fout opgetreden bij het aanmelden.";
                            }
                        }
                    }
                }
            } else {
                $signup_message = "Wachtwoord moet minimaal 6 tekens lang zijn.";
                $signup_message_type = "error";
            }
        } else {
            $signup_message = "Wachtwoorden komen niet overeen.";
            $signup_message_type = "error";
        }
    } else {
        $signup_message = "Vul alle velden in.";
        $signup_message_type = "error";
    }
}

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aanmelden - Voetbaltoernooi</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-bg">

    <div class="auth-container">
        <div class="auth-form">
            <h2>Aanmelden</h2>
            
            <?php if (!empty($signup_message)): ?>
                <div class="message <?php echo $signup_message_type; ?>">
                    <?php echo htmlspecialchars($signup_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="text" name="voornaam" placeholder="Voornaam" value="<?php echo htmlspecialchars($voornaam ?? ''); ?>" required>
                <input type="text" name="achternaam" placeholder="Achternaam" value="<?php echo htmlspecialchars($achternaam ?? ''); ?>" required>
                <input type="email" name="email" placeholder="E-mailadres" value="<?php echo $email_value; ?>" required>
                <input type="text" name="telefoon" placeholder="Telefoonnummer" value="<?php echo htmlspecialchars($telefoon ?? ''); ?>" required>
                <input type="password" name="password" placeholder="Wachtwoord" required>
                <input type="password" name="confirm_password" placeholder="Bevestig wachtwoord" required>
                <!-- geen 'onthoud mij' op registratiepagina -->
                <button type="submit">Aanmelden</button>
            </form>
            
            <div class="form-switch">
                Heb je al een account? <a href="index.php">Inloggen</a>
            </div>
        </div>
    </div>
</body>
</html>