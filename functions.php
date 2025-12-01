<?php
// Bestand: functions.php
require_once 'config.php'; // Zorg ervoor dat $pdo beschikbaar is

// Hulp: laatste DB-fout opslaan voor debug-doeleinden
$lastDbError = null;

function getLastDbError() {
    global $lastDbError;
    return $lastDbError;
}

function voegTeamToe(string $naam) {
    global $pdo;
    $sql = "INSERT INTO Teams (Naam) VALUES (?)";
    try {
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$naam])) {
            return $pdo->lastInsertId();
        }
        return false;
    } catch (\PDOException $e) {
        // Bijv. unieke naam constraint overtreden
        global $lastDbError;
        $lastDbError = $e->getMessage();
        return false;
    }
}

function voegSpelerToe(array $data) {
    global $pdo;
    // Probeer eerst te zien of een speler met hetzelfde e-mailadres al bestaat
    try {
        $check = $pdo->prepare("SELECT SpelerID FROM Spelers WHERE Email = ? LIMIT 1");
        $check->execute([$data['Email']]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Bestaande speler: update TeamID en overige velden zodat speler aan dit team gekoppeld wordt
            $sqlUpdate = "UPDATE Spelers SET TeamID = :team_id, Voornaam = :voornaam, Achternaam = :achternaam, Telefoonnummer = :telefoon WHERE SpelerID = :id";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $ok = $stmtUpdate->execute([
                'team_id' => $data['TeamID'],
                'voornaam' => $data['Voornaam'],
                'achternaam' => $data['Achternaam'],
                'telefoon' => $data['Telefoonnummer'],
                'id' => $existing['SpelerID']
            ]);
            return $ok ? (int)$existing['SpelerID'] : false;
        } else {
            $sql = "INSERT INTO Spelers (TeamID, Voornaam, Achternaam, Telefoonnummer, Email) VALUES (:team_id, :voornaam, :achternaam, :telefoon, :email)";
            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                'team_id' => $data['TeamID'],
                'voornaam' => $data['Voornaam'],
                'achternaam' => $data['Achternaam'],
                'telefoon' => $data['Telefoonnummer'],
                'email' => $data['Email']
            ]);
            if ($ok) {
                return (int)$pdo->lastInsertId();
            }
            return false;
        }
    } catch (\PDOException $e) {
        global $lastDbError;
        $lastDbError = $e->getMessage();
        return false;
    }
}

function getAlleTeams() {
    global $pdo;
    $sql = "SELECT TeamID, Naam FROM Teams ORDER BY Naam";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function getTeamDetailsMetSpelers(int $teamID) {
    global $pdo;
    $sql = "SELECT Voornaam, Achternaam, Telefoonnummer, Email FROM Spelers WHERE TeamID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$teamID]);
    return $stmt->fetchAll();
}


// Bestand: functions.php (vervolg)

function planWedstrijd(int $thuisId, int $uitId, string $tijdstip, string $locatie) {
    global $pdo;
    $sql = "INSERT INTO Wedstrijden (TeamThuisID, TeamUitID, Tijdstip, Locatie) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$thuisId, $uitId, $tijdstip, $locatie]);
}

function getWedstrijden(string $status = 'Alle') {
    global $pdo;
    $statusFilter = $status === 'Alle' ? "" : "WHERE w.Status = :status";
    
    $sql = "
        SELECT
            w.WedstrijdID, w.Tijdstip, w.Locatie, w.Status,
            t1.Naam AS TeamThuisNaam, t1.TeamID AS TeamThuisID,
            t2.Naam AS TeamUitNaam, t2.TeamID AS TeamUitID,
            u.ScoreThuis, u.ScoreUit
        FROM Wedstrijden w
        INNER JOIN Teams t1 ON w.TeamThuisID = t1.TeamID
        INNER JOIN Teams t2 ON w.TeamUitID = t2.TeamID
        LEFT JOIN Uitslagen u ON w.WedstrijdID = u.WedstrijdID
        {$statusFilter}
        ORDER BY w.Tijdstip ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    if ($status !== 'Alle') {
        $stmt->bindParam(':status', $status);
    }
    $stmt->execute();
    return $stmt->fetchAll();
}

function voerUitslagIn(int $wedstrijdId, int $scoreThuis, int $scoreUit, int $teamThuisId, int $teamUitId) {
    global $pdo;
    
    $gewonnenTeamID = NULL;
    $isGelijkspel = 0;
    if ($scoreThuis > $scoreUit) {
        $gewonnenTeamID = $teamThuisId;
    } elseif ($scoreUit > $scoreThuis) {
        $gewonnenTeamID = $teamUitId;
    } else {
        $isGelijkspel = 1;
    }

    // Begin transactie voor atomaire update
    $pdo->beginTransaction();
    try {
        // 1. Uitslag invoeren/bijwerken
        $sql_uitslag = "
            INSERT INTO Uitslagen (WedstrijdID, ScoreThuis, ScoreUit, GewonnenTeamID, Gelijkspel)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                ScoreThuis = VALUES(ScoreThuis), ScoreUit = VALUES(ScoreUit), 
                GewonnenTeamID = VALUES(GewonnenTeamID), Gelijkspel = VALUES(Gelijkspel)
        ";
        $stmt = $pdo->prepare($sql_uitslag);
        $stmt->execute([$wedstrijdId, $scoreThuis, $scoreUit, $gewonnenTeamID, $isGelijkspel]);
        
        // 2. Wedstrijdstatus bijwerken
        $sql_status = "UPDATE Wedstrijden SET Status = 'Gespeeld' WHERE WedstrijdID = ?";
        $stmt = $pdo->prepare($sql_status);
        $stmt->execute([$wedstrijdId]);
        
        $pdo->commit();
        return true;
    } catch (\PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

function getKlassement() {
    global $pdo;
    $sql = "
        SELECT
            t.Naam AS Teamnaam,
            COUNT(u.WedstrijdID) AS Gespeeld,
            SUM(CASE 
                WHEN (u.ScoreThuis > u.ScoreUit AND t.TeamID = w.TeamThuisID) OR (u.ScoreUit > u.ScoreThuis AND t.TeamID = w.TeamUitID) THEN 1 
                ELSE 0 END) AS Winsten,
            SUM(CASE WHEN u.Gelijkspel = TRUE THEN 1 ELSE 0 END) AS Gelijkspelen,
            SUM(CASE 
                WHEN (u.ScoreThuis < u.ScoreUit AND t.TeamID = w.TeamThuisID) OR (u.ScoreUit < u.ScoreThuis AND t.TeamID = w.TeamUitID) THEN 1 
                ELSE 0 END) AS Verliezen,
            SUM(CASE WHEN t.TeamID = w.TeamThuisID THEN u.ScoreThuis ELSE u.ScoreUit END) AS DoelpuntenVoor,
            SUM(CASE WHEN t.TeamID = w.TeamThuisID THEN u.ScoreUit ELSE u.ScoreThuis END) AS DoelpuntenTegen,
            SUM(CASE WHEN u.Gelijkspel = TRUE THEN 1 ELSE 
                CASE WHEN (u.ScoreThuis > u.ScoreUit AND t.TeamID = w.TeamThuisID) OR (u.ScoreUit > u.ScoreThuis AND t.TeamID = w.TeamUitID) THEN 3 ELSE 0 END 
            END) AS Score
        FROM
            Teams t
        LEFT JOIN Wedstrijden w ON t.TeamID = w.TeamThuisID OR t.TeamID = w.TeamUitID
        LEFT JOIN Uitslagen u ON w.WedstrijdID = u.WedstrijdID
        GROUP BY
            t.TeamID, t.Naam
        ORDER BY
            Score DESC, (DoelpuntenVoor - DoelpuntenTegen) DESC, DoelpuntenVoor DESC, t.Naam ASC
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// Sessie-hulpfunctie: controleer of gebruiker ingelogd is
function isLoggedIn(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['user_id']);
}

// Log de huidige gebruiker uit: vernietig sessie en verwijder cookie
function logout(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Maak alle sessie-variabelen leeg
    $_SESSION = [];

    // Verwijder session cookie indien aanwezig
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }

    // Vernietig de sessie
    session_destroy();

    // Verwijder eventueel 'remember me' cookie
    setcookie('user_email', '', time() - 3600, '/');

    // Stuur terug naar inlogpagina
    header('Location: index.php');
    exit();
}