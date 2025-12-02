<?php
session_start();
include 'config.php';

// Default application constants (define if not already defined)
if (!defined('ROLE_ADMIN')) {
    define('ROLE_ADMIN', 'admin');
}
if (!defined('ADMIN_PAGE')) {
    define('ADMIN_PAGE', 'admin.php');
}
if (!defined('HOME_PAGE')) {
    define('HOME_PAGE', 'home.php');
}

$login_message = '';
$login_message_type = '';

// Als gebruiker al ingelogd is, doorsturen naar de juiste pagina
if (isset($_SESSION['user_role'])) {
    header("Location: " . ($_SESSION['user_role'] === ROLE_ADMIN ? ADMIN_PAGE : HOME_PAGE));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);
    
    if (!empty($email) && !empty($password)) {
        try {
            // Probeer standaard 'users' tabel
            $stmt = $pdo->prepare("SELECT id, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $user = false;
            if ($e->getCode() === '42S02') {
                // users bestaat niet — probeer 'spelers' tabel
                try {
                    $stmt = $pdo->prepare("SELECT SpelerID AS id, Email AS email, Wachtwoord AS password, Rol AS role FROM spelers WHERE Email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e2) {
                    $login_message = "Er is een fout opgetreden bij het inloggen: " . ($e2->getMessage());
                    $login_message_type = "error";
                }
            } else {
                $login_message = "Er is een fout opgetreden bij het inloggen. Probeer het later opnieuw.";
                $login_message_type = "error";
            }
        }

        // Verifieer wachtwoord: ondersteund zowel gehashte waarden als bestaande platte Wachtwoord-velden
        $password_ok = false;
        if ($user) {
            $stored = isset($user['password']) ? $user['password'] : '';
            // Detecteer of het opgeslagen wachtwoord een password_hash-resultaat is
            $is_hash = is_string($stored) && (strpos($stored, '$2y$') === 0 || strpos($stored, '$2a$') === 0 || strpos($stored, '$2b$') === 0 || strpos($stored, '$argon2') === 0);
            if ($is_hash) {
                $password_ok = password_verify($password, $stored);
            } else {
                // Vergelijk als plaintext (oude DB) — gebruik hash_equals voor timing-safe vergelijking
                $password_ok = hash_equals((string)$stored, (string)$password);
            }
            // Als login succesvol is en het opgeslagen wachtwoord geen hash was, migreer naar een gehashte waarde
            if ($password_ok && !$is_hash) {
                try {
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    // Probeer beide mogelijke tabellen te updaten; als tabel ontbreekt faalt dit in try/catch zonder gevolgen
                    $up1 = $pdo->prepare("UPDATE spelers SET Wachtwoord = ? WHERE Email = ?");
                    $up1->execute([$new_hash, $user['email']]);
                } catch (PDOException $e) {
                    // negeren — migratie is optioneel
                }
            }
        }

        if ($password_ok) {
            // Inloggen succesvol
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role']; // Sla de rol op in de sessie

            if ($remember_me) {
                // Cookie instellen voor 30 dagen
                setcookie('user_email', $email, time() + (30 * 24 * 60 * 60), "/"); 
            } else {
                // Cookie verwijderen als 'onthoud mij' niet is aangevinkt
                setcookie('user_email', '', time() - 3600, "/");
            }

            // Doorsturen op basis van de rol: admins gaan naar beheerder pagina's
            if ($user['role'] === ROLE_ADMIN) {
                header("Location: beheerder_pagina's/dashboard.php");
            } else {
                header("Location: Team_inschrijven.php");
            }
            exit();
        } else {
            if (empty($login_message)) {
                $login_message = "Onjuist e-mailadres of wachtwoord.";
                $login_message_type = "error";
            }
        }
    } else {
        $login_message = "Vul e-mailadres en wachtwoord in.";
        $login_message_type = "error";
    }
}

include 'header.php';
?>

<script>/* small helper to add auth background class to body */
document.addEventListener('DOMContentLoaded', function(){ document.body.classList.add('auth-bg'); });
</script>

    <div class="auth-container">
        <div class="auth-form">
            <h2>Inloggen</h2>
            
            <?php if (!empty($login_message)): ?>
                <div class="message <?php echo $login_message_type; ?>">
                    <?php echo htmlspecialchars($login_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="email" name="email" placeholder="E-mailadres" 
                       value="<?php echo isset($_COOKIE['user_email']) ? htmlspecialchars($_COOKIE['user_email']) : ''; ?>" required>
                <input type="password" name="password" placeholder="Wachtwoord" required>
                
                <label>
                    <input type="checkbox" name="remember_me" id="remember-me" 
                           <?php echo isset($_COOKIE['user_email']) ? 'checked' : ''; ?>> Onthoud mij
                </label>
                
                <button type="submit">Inloggen</button>
            </form>
            
            <a href="forgot_password.php" class="reset-pass">Wachtwoord vergeten?</a>
            
            <div class="form-switch">
                Nog geen account? <a href="register.php">Aanmelden</a>
            </div>
        </div>
    </div>