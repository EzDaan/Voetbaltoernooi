<?php
session_start();
include 'db_connect.php';

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

            // Doorsturen op basis van de rol: normale gebruikers gaan naar Team_inschrijven.php
            if ($user['role'] === ROLE_ADMIN) {
                header("Location: " . ADMIN_PAGE);
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
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen</title>
    <!-- De CSS is in dit bestand ingebed voor de demo -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .auth-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        .auth-form {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        h2 {
            color: #f8fafc;
            margin-bottom: 25px;
            font-size: 1.75rem;
            font-weight: 600;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #475569;
            border-radius: 8px;
            background: #1e293b;
            color: #f8fafc;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
        }
        input::placeholder {
            color: #94a3b8;
        }
        label {
            display: flex;
            align-items: center;
            color: #94a3b8;
            font-size: 0.9rem;
            cursor: pointer;
            margin-bottom: 5px;
        }
        label input[type="checkbox"] {
            margin-right: 8px;
            width: auto;
        }
        button {
            padding: 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background 0.3s, transform 0.1s;
        }
        button:hover {
            background: #2563eb;
        }
        button:active {
            transform: scale(0.99);
        }
        .reset-pass {
            display: block;
            margin-top: 15px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        .reset-pass:hover {
            color: #3b82f6;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .message.success {
            background-color: #16a34a; /* Groen */
            color: white;
        }
        .message.error {
            background-color: #dc2626; /* Rood */
            color: white;
        }
        .form-switch {
            margin-top: 25px;
            color: #94a3b8;
            font-size: 0.9rem;
        }
        .form-switch a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .form-switch a:hover {
            color: #60a5fa;
            text-decoration: underline;
        }
    </style>
</head>
<body>
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
</body>
</html>
