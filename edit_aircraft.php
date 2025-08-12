<?php
// edit_aircraft.php
session_start();
require_once 'Database.php';

// Sicherheits- und Berechtigungsprüfungen
if (!isset($_SESSION['loggedin']) || !isset($_GET['id'])) {
    header('Location: login_customer.php');
    exit;
}

$aircraft_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$db = new Database();
$conn = $db->getConnection();

// Autorisierungsprüfung und Daten abrufen
$stmt = $conn->prepare("
    SELECT p.*, f.id as lager_id FROM flugzeuge p JOIN fluglager f ON p.fluglager_id = f.id WHERE p.id = ? AND f.user_id = ?");
$stmt->bind_param('ii', $aircraft_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header('Location: dashboard.php?error=Zugriff verweigert');
    exit;
}
$aircraft = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Flugzeug bearbeiten</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Flugzeug bearbeiten: <?php echo htmlspecialchars($aircraft['kennzeichen']); ?></h1>
            <a href="edit_fluglager.php?id=<?php echo $aircraft['lager_id']; ?>" class="btn">Zurück zum Fluglager</a>
        </header>

        <main style="margin-top: 20px;">
            <form action="handle_edit_aircraft.php" method="post">
                <input type="hidden" name="aircraft_id" value="<?php echo $aircraft['id']; ?>">
                <input type="hidden" name="lager_id" value="<?php echo $aircraft['lager_id']; ?>">
                
                <div class="form-group half-width">
                    <label>Kennzeichen</label>
                    <input type="text" name="kennzeichen" value="<?php echo htmlspecialchars($aircraft['kennzeichen']); ?>" required>
                </div>
                <div class="form-group half-width">
                    <label>Typ</label>
                    <input type="text" name="typ" value="<?php echo htmlspecialchars($aircraft['typ']); ?>" required>
                </div>
                 <div class="form-group half-width">
                    <label>Flarm-ID</label>
                    <input type="text" name="flarm_id" value="<?php echo htmlspecialchars($aircraft['flarm_id']); ?>">
                </div>
                <div class="form-group half-width">
                    <label>SPOT</label>
                    <input type="text" name="spot" value="<?php echo htmlspecialchars($aircraft['spot']); ?>">
                </div>
                
                <button type="submit">Änderungen speichern</button>
            </form>
        </main>
    </div>
</body>
</html>