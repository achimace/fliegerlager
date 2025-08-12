<?php
// dashboard.php
session_start();
require_once 'Database.php';

// 1. Check if user is logged in.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login_customer.php');
    exit;
}

// 2. Get user data from session
$user_id = $_SESSION['user_id'];
$user_vorname = $_SESSION['user_vorname'] ?? 'User';

// 3. Database connection
$db = new Database();
$conn = $db->getConnection();

// 4. Query for the flight camp list (with counts)
$stmt = $conn->prepare("
    SELECT 
        f.id, 
        f.startdatum, 
        f.enddatum, 
        f.status,
        (SELECT COUNT(*) FROM teilnehmer WHERE fluglager_id = f.id) AS anzahl_teilnehmer,
        (SELECT COUNT(*) FROM flugzeuge WHERE fluglager_id = f.id) AS anzahl_flugzeuge
    FROM fluglager f 
    WHERE f.user_id = ? 
    ORDER BY f.startdatum DESC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$fluglager_liste = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 5. Query for complete user data for the profile section
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Function to translate status for display
function getStatusBadge($status) {
    $farben = [
        'in_planung' => '#6F9ED4',  // Blue
        'eingereicht' => '#F68A1E', // Orange
        'bestaetigt' => 'green',   // Green
        'abgelehnt' => 'red'       // Red
    ];
    $text = ucfirst(str_replace('_', ' ', $status));
    $badge_style = 'background-color: ' . ($farben[$status] ?? '#989898') . '; color: white; padding: 5px 10px; border-radius: 5px;';
    return '<span style="' . $badge_style . '">' . htmlspecialchars($text) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Ihr Dashboard - Flugplatz Ohlstadt</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .profile-section .edit-view { display: none; }
        .profile-section.edit-mode .edit-view { display: block; }
        .profile-section.edit-mode .display-view { display: none; }
        .profile-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .data-pair-grid { display: grid; grid-template-columns: 120px 1fr; gap: 10px 15px; align-items: center; }
        .data-pair-grid strong { font-weight: bold; }
        .profile-section.edit-mode .plz-ort-wrapper { display: flex; gap: 10px; }
    </style>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="app-body">

    <header class="app-header">
        <div class="logo">
            <a href="dashboard.php"><img src="pics/logo.png" alt="Logo"></a>
        </div>
        <nav class="app-header-nav">
            <a href="calendar.php">Kalender</a>
            <span>|</span>
            <span>Willkommen, <?php echo htmlspecialchars($user_vorname); ?>!</span>
            <a href="logout.php" class="btn" style="background-color: #6c757d;">Logout</a>
        </nav>
    </header>

    <div class="app-container">
        
        <?php if (isset($_GET['message'])) echo '<p class="message-success">' . htmlspecialchars($_GET['message']) . '</p>'; ?>
        <?php if (isset($_GET['error'])) echo '<p class="message-error">' . htmlspecialchars($_GET['error']) . '</p>'; ?>

        <div id="profile-section" class="section profile-section">
            <form action="handle_edit_profile.php" method="post">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Persönliche Daten</h3>
                    <button type="button" onclick="showProfileEditForm()" class="btn btn-edit display-view">Daten bearbeiten</button>
                </div>
                <div class="profile-grid">
                    <div>
                        <h4>Persönliche Daten</h4>
                        <div class="data-pair-grid">
                            <strong>Vorname:</strong><div><span class="display-view"><?php echo htmlspecialchars($user_data['vorname']); ?></span><input class="edit-view" type="text" name="vorname" value="<?php echo htmlspecialchars($user_data['vorname']); ?>" required></div>
                            <strong>Nachname:</strong><div><span class="display-view"><?php echo htmlspecialchars($user_data['nachname']); ?></span><input class="edit-view" type="text" name="nachname" value="<?php echo htmlspecialchars($user_data['nachname']); ?>" required></div>
                            <strong>E-Mail:</strong><div><span class="display-view"><?php echo htmlspecialchars($user_data['email']); ?></span><input class="edit-view" type="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled title="E-Mail kann nicht geändert werden."></div>
                            <strong>Mobiltelefon:</strong><div><span class="display-view"><?php echo htmlspecialchars($user_data['mobiltelefon'] ?: '-'); ?></span><input class="edit-view" type="tel" name="mobiltelefon" value="<?php echo htmlspecialchars($user_data['mobiltelefon']); ?>"></div>
                        </div>
                    </div>
                    <div>
                        <h4>Vereinsdaten</h4>
                        <div class="data-pair-grid">
                            <strong>Verein:</strong><div><span class="display-view"><?php echo htmlspecialchars($user_data['vereinsname'] ?: '-'); ?></span><input class="edit-view" type="text" name="vereinsname" value="<?php echo htmlspecialchars($user_data['vereinsname']); ?>"></div>
                            <strong>Straße:</strong><div><span class="display-view"><?php echo htmlspecialchars($user_data['strasse'] ?: '-'); ?></span><input class="edit-view" type="text" name="strasse" value="<?php echo htmlspecialchars($user_data['strasse']); ?>"></div>
                            <strong>PLZ / Ort:</strong><div><span class="display-view"><?php echo htmlspecialchars(($user_data['plz'] || $user_data['ort']) ? $user_data['plz'] . ' ' . $user_data['ort'] : '-'); ?></span><div class="edit-view plz-ort-wrapper"><input type="text" name="plz" value="<?php echo htmlspecialchars($user_data['plz']); ?>" placeholder="PLZ" style="flex: 1;"><input type="text" name="ort" value="<?php echo htmlspecialchars($user_data['ort']); ?>" placeholder="Ort" style="flex: 2;"></div></div>
                        </div>
                    </div>
                </div>
                <div class="edit-view" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-edit">Änderungen speichern</button>
                    <button type="button" onclick="hideProfileEditForm()" class="btn btn-grey">Abbrechen</button>
                </div>
            </form>
            
            <hr class="display-view" style="margin: 25px 0;">

            <div class="display-view">
                <button type="button" onclick="togglePasswordForm()" class="btn">Passwort ändern</button>
                <div id="password-change-form" style="display:none; margin-top: 20px;">
                    <form action="handle_change_password.php" method="post">
                        <h4>Neues Passwort festlegen</h4>
                        <div class="form-group">
                            <label for="current_password">Aktuelles Passwort</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">Neues Passwort (min. 8 Zeichen)</label>
                            <input type="password" id="new_password" name="new_password" required minlength="8">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Neues Passwort bestätigen</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>
                        <button type="submit" class="btn btn-edit">Passwort speichern</button>
                    </form>
                </div>
            </div>
        </div>

        <main>
             <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; margin-top: 30px;">
                <h2>Ihre Fluglager</h2>
               <a href="create_fluglager.php" class="btn btn-primary">+ Neues Fluglager anlegen</a>
             </div>
            <div class="section">
                <?php if (empty($fluglager_liste)): ?>
                    <p>Sie haben noch keine Fluglager angelegt.</p>
                <?php else: ?>
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>Zeitraum</th>
                                <th>Teilnehmer</th>
                                <th>Flugzeuge</th>
                                <th>Status</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $today = new DateTime("now", new DateTimeZone('Europe/Berlin'));
                            $today->setTime(0, 0, 0);
                            foreach ($fluglager_liste as $lager): 
                                $start_date = new DateTime($lager['startdatum']);
                                $start_date->setTime(0, 0, 0);
                            ?>
                                <tr>
                                    <td><?php echo date('d.m.Y', strtotime($lager['startdatum'])) . ' - ' . date('d.m.Y', strtotime($lager['enddatum'])); ?></td>
                                    <td><?php echo $lager['anzahl_teilnehmer']; ?></td>
                                    <td><?php echo $lager['anzahl_flugzeuge']; ?></td>
                                    <td><?php echo getStatusBadge($lager['status']); ?></td>
                                    <td>
                                        <?php if ($today >= $start_date): ?>
                                            <a href="abrechnung.php?id=<?php echo $lager['id']; ?>" class="btn btn-success">Abrechnung erfassen</a>
                                        <?php else: ?>
                                            <a href="edit_fluglager.php?id=<?php echo $lager['id']; ?>" class="btn">Ansehen/Bearbeiten</a>
                                            <?php if ($lager['status'] === 'in_planung'): ?>
                                                <form action="handle_delete_camp.php" method="post" style="display:inline;" onsubmit="return confirm('Möchten Sie dieses Fluglager und alle zugehörigen Daten wirklich endgültig löschen?');">
                                                    <input type="hidden" name="lager_id" value="<?php echo $lager['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-icon" title="Löschen"><i class="fa-solid fa-trash-can"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function showProfileEditForm() {
            document.getElementById('profile-section').classList.add('edit-mode');
        }
        function hideProfileEditForm() {
            document.getElementById('profile-section').classList.remove('edit-mode');
        }
        function togglePasswordForm() {
            var form = document.getElementById('password-change-form');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
    </script>
</body>
</html>