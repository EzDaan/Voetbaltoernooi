<?php
session_start();
require_once 'functions.php';

if (!defined('DEBUG')) {
    define('DEBUG', true);
}

function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

$message = '';
$message_type = '';

if (isset($_SESSION['user_role'])) {
    $target = defined('ROLE_ADMIN') && defined('ADMIN_PAGE') && $_SESSION['user_role'] === ROLE_ADMIN ? ADMIN_PAGE : (defined('HOME_PAGE') ? HOME_PAGE : 'index.php');
    header("Location: " . $target);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!empty($email)) {
        try {
            $stmt = $pdo->prepare("SELECT SpelerID AS id, Email AS email FROM Spelers WHERE Email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $createSql = "CREATE TABLE IF NOT EXISTS password_resets (email VARCHAR(255) NOT NULL, token VARCHAR(128) NOT NULL, expiry DATETIME NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $pdo->exec($createSql);
                $token = generate_token(16);
                $expiry = date("Y-m-d H:i:s", time() + 3600);
                $ins = $pdo->prepare("INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?)");
                $ins->execute([$user['email'], $token, $expiry]);
                $message = 'Als dit e-mailadres bestaat, is er een link om je wachtwoord te herstellen naar je e-mail verzonden.';
                $message_type = 'success';
            } else {
                $message = 'Als dit e-mailadres bestaat, is er een link om je wachtwoord te herstellen naar je e-mail verzonden.';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Er is een fout opgetreden bij het verwerken van de aanvraag.';
            $message_type = 'error';
            if (defined('DEBUG') && DEBUG) {
                $message .= ' - ' . $e->getMessage();
            }
        }
    } else {
        $message = 'Vul je e-mailadres in.';
        $message_type = 'error';
    }
}

include 'header.php';
?>

<div class="auth-container">
    <div class="auth-form">
        <h2>Wachtwoord vergeten</h2>
        <p>Vul je e-mailadres in en we sturen je een link om je wachtwoord te resetten.</p>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="email" name="email" placeholder="E-mailadres" required>
            <button type="submit">Verstuur resetlink</button>
        </form>

        <div class="form-switch" style="margin-top:12px;">
            <a href="inloggen.php">Terug naar inloggen</a>
        </div>
    </div>
</div>