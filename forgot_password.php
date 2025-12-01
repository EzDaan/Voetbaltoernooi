<?php
session_start();
include 'config.php';

if (!defined('DEBUG')) {
    define('DEBUG', true);
}

// Functie om willekeurige tokens te genereren
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

$message = '';
$message_type = '';

// Als gebruiker al ingelogd is, doorsturen naar de juiste pagina
if (isset($_SESSION['user_role'])) {
    header("Location: " . ($_SESSION['user_role'] === ROLE_ADMIN ? ADMIN_PAGE : HOME_PAGE));
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (!empty($email)) {
        try {
            // Zoek gebruiker in 'spelers' tabel
            $stmt = $pdo->prepare("SELECT SpelerID AS id, Email AS email FROM spelers WHERE Email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Zorg dat table password_resets bestaat (lichtere aanpak dan spelers wijzigen)
                $createSql = "CREATE TABLE IF NOT EXISTS password_resets (  
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL,
                    token VARCHAR(128) NOT NULL,
                    expiry DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $pdo->exec($createSql);

                // Genereer token en insert
                $token = generate_token(16);
                $expiry = date("Y-m-d H:i:s", time() + 3600);
                $ins = $pdo->prepare("INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?)");
                $ins->execute([$user['email'], $token, $expiry]);

                $reset_link = "reset_password.php?token=" . $token;
                $message = "Als dit e-mailadres bestaat, is er een link om je wachtwoord te herstellen naar je e-mail verzonden. (Debug Link: <a href=\"{$reset_link}\" class='reset-link'>{$token}</a>)";
                $message_type = "success";
            } else {
                // Altijd dezelfde melding om geen info te lekken
                $message = "Als dit e-mailadres bestaat, is er een link om je wachtwoord te herstellen naar je e-mail verzonden.";
                $message_type = "success";
            }
        } catch (PDOException $e) {
            $message = "Er is een fout opgetreden bij het verwerken van de aanvraag.";
            $message_type = "error";
            if (defined('DEBUG') && DEBUG) {
                $message .= " (" . htmlspecialchars($e->getMessage()) . ")";
            }
        }
    } else {
        $message = "Vul je e-mailadres in.";
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wachtwoord vergeten</title>
    <!-- Gebruik dezelfde mooie stijl als login.php -->
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
        p {
            color: #94a3b8;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #475569;
            border-radius: 8px;
            background: #1e293b;
            color: #f8fafc;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        input[type="email"]:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
        }
        input::placeholder {
            color: #94a3b8;
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
        .form-switch a, .reset-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .form-switch a:hover, .reset-link:hover {
            color: #60a5fa;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <h2>Wachtwoord vergeten</h2>
            <p>Vul je e-mailadres in en we sturen je een link om je wachtwoord te resetten.</p>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; // Let op: $message bevat nu de reset-link, dus geen htmlspecialchars() ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="email" name="email" placeholder="E-mailadres" required>
                <button type="submit">Reset link versturen</button>
            </form>
            
            <div class="form-switch">
                Terug naar <a href="login.php">Inloggen</a>
            </div>
        </div>
    </div>
</body>
</html>