<?php
session_start();
include 'config.php';

if (!defined('DEBUG')) {
    define('DEBUG', true);
}

$message = '';
$message_type = '';

// Get token from GET or POST
$token = isset($_GET['token']) ? $_GET['token'] : (isset($_POST['token']) ? $_POST['token'] : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['token']) ? $_POST['token'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (empty($token)) {
        $message = "Ongeldige reset-token.";
        $message_type = "error";
    } elseif (empty($password) || empty($confirm)) {
        $message = "Vul beide wachtwoordvelden in.";
        $message_type = "error";
    } elseif ($password !== $confirm) {
        $message = "Wachtwoorden komen niet overeen.";
        $message_type = "error";
    } elseif (strlen($password) < 6) {
        $message = "Wachtwoord moet minimaal 6 tekens lang zijn.";
        $message_type = "error";
    } else {
        try {
            // Zoek token
            $stmt = $pdo->prepare("SELECT email, expiry FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $message = "Ongeldige of verlopen token.";
                $message_type = "error";
            } else {
                $expiry = strtotime($row['expiry']);
                if ($expiry < time()) {
                    $message = "Token is verlopen.";
                    $message_type = "error";
                } else {
                    $email = $row['email'];
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    // Update spelers tabel
                    $up = $pdo->prepare("UPDATE spelers SET Wachtwoord = ? WHERE Email = ?");
                    $up->execute([$new_hash, $email]);
                    // Verwijder gebruikte token
                    $del = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                    $del->execute([$token]);
                    $message = "Wachtwoord succesvol gereset. Je kunt nu inloggen.";
                    $message_type = "success";
                }
            }
        } catch (PDOException $e) {
            $message = "Er is een fout opgetreden bij het resetten van het wachtwoord.";
            $message_type = "error";
            if (defined('DEBUG') && DEBUG) {
                $message .= " (" . htmlspecialchars($e->getMessage()) . ")";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wachtwoord resetten</title>
    <style>
        *{box-sizing:border-box;font-family:Inter, sans-serif}
        body{background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;margin:0}
        .card{background:rgba(255,255,255,0.05);padding:24px;border-radius:10px;width:360px;color:#f8fafc}
        input{width:100%;padding:10px;border-radius:8px;border:1px solid #475569;background:#1e293b;color:#f8fafc;margin-bottom:12px}
        button{width:100%;padding:10px;border-radius:8px;border:none;background:#3b82f6;color:#fff}
        .message{padding:10px;border-radius:6px;margin-bottom:12px}
        .message.success{background:#16a34a;color:#fff}
        .message.error{background:#dc2626;color:#fff}
        a{color:#60a5fa}
    </style>
</head>
<body>
<div class="card">
    <h2>Reset wachtwoord</h2>
    <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if (empty($message_type) || $message_type === 'error'): ?>
    <form method="POST" action="">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <input type="password" name="password" placeholder="Nieuw wachtwoord" required>
        <input type="password" name="confirm_password" placeholder="Bevestig wachtwoord" required>
        <button type="submit">Wachtwoord resetten</button>
    </form>
    <?php else: ?>
        <p><a href="inloggen.php">Inloggen</a></p>
    <?php endif; ?>
</div>
</body>
</html>
