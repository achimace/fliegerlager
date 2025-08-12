<?php
// create_fluglager.php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login_customer.php');
    exit;
}
$user_vorname = $_SESSION['user_vorname'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Neues Fluglager anlegen - Flugplatz Ohlstadt</title>
    <link rel="stylesheet" href="styles.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js'></script>
    <script src="https://unpkg.com/popper.js@1"></script>
    <script src="https://unpkg.com/tippy.js@5"></script>
</head>
<body class="app-body">

    <header class="app-header">
        <div class="logo">
            <a href="dashboard.php"><img src="pics/logo.png" alt="Logo"></a>
        </div>
        <nav class="app-header-nav">
            <a href="dashboard.php">Dashboard</a>
            <span>|</span>
            <span>Willkommen, <?php echo htmlspecialchars($user_vorname); ?>!</span>
            <a href="logout.php" class="btn" style="background-color: #6c757d;">Logout</a>
        </nav>
    </header>

    <div class="app-container">
        <h1>Neues Fluglager anlegen</h1>
        <p>Bitte wähle den gewünschten Zeitraum für dein Fluglager aus. Rechts siehst du die aktuelle Belegung des Platzes.</p>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; margin-top: 20px;">
            
            <div class="section">
                <h3>1. Zeitraum festlegen</h3>
                <form action="handle_create_fluglager.php" method="post">
                    <div class="form-group">
                        <label for="startdatum">Anreise</label>
                        <input type="date" id="startdatum" name="startdatum" required>
                    </div>
                    <div class="form-group">
                        <label for="enddatum">Abreise</label>
                        <input type="date" id="enddatum" name="enddatum" required>
                    </div>
                    <div class="form-group">
                        <input type="checkbox" id="exklusiv" name="exklusiv" value="1" style="width: auto; margin-right: 10px;">
                        <label for="exklusiv" style="display: inline;">Wir möchten den Platz exklusiv buchen.</label>
                    </div>
                    <?php if (isset($_GET['error'])): ?>
                        <p style="color: red;"><?php echo htmlspecialchars($_GET['error']); ?></p>
                    <?php endif; ?>
                    <button type="submit" style="width: 100%; margin-top: 15px;">Fluglager anlegen & Details hinzufügen</button>
                </form>
            </div>

            <aside class="section">
                 <h3>2. Belegung prüfen</h3>
                 <?php require_once 'calendar_component.php'; ?>
            </aside>
        </div>
    </div>

<script>
    // --- All JavaScript functions for the page ---

    // Toggles the visibility of the "add new" forms in the table footers
    function toggleForm(rowId) {
        var row = document.getElementById(rowId);
        if (row.style.display === 'none' || row.style.display === '') {
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    }

    // --- Functions for Participant inline editing ---
    function showParticipantEditForm(id) { /* ... unchanged ... */ }
    function hideParticipantEditform(id) { /* ... unchanged ... */ }
    
    // --- Functions for Aircraft inline editing ---
    function showAircraftEditForm(id) { /* ... unchanged ... */ }
    function hideAircraftEditForm(id) { /* ... unchanged ... */ }


    // --- Logic for the Calendar Component ---
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar-component');
        if (calendarEl) {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'de',
                firstDay: 1, // Start week on Monday
                height: 'auto',

                // ### NEW: Enable date selection ###
                selectable: true,

                headerToolbar: {
                    left: 'prev',
                    center: 'title',
                    right: 'next'
                },
                events: 'get_events.php',

                // ### NEW: This function runs after a user selects a date range ###
                select: function(info) {
                    // 'info.startStr' is the selected start date in 'YYYY-MM-DD' format
                    var startDate = info.startStr;

                    // FullCalendar's end date is exclusive, so we subtract one day for the "Abreise" field
                    var endDate = new Date(info.endStr);
                    endDate.setDate(endDate.getDate() - 1);
                    
                    // Format the date correctly back to 'YYYY-MM-DD'
                    var formattedEndDate = endDate.toISOString().split('T')[0];

                    // Populate the form fields
                    document.getElementById('startdatum').value = startDate;
                    document.getElementById('enddatum').value = formattedEndDate;
                    
                    // Optional: Clear the visual selection in the calendar
                    calendar.unselect();
                },

                eventDidMount: function(info) {
                    // Tooltip logic remains unchanged
                },
            });
            calendar.render();
        }
    });
</script>

</body>
</html>