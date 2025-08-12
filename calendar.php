<?php
session_start();
require_once 'Database.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login_customer.php');
    exit;
}

$user_vorname = $_SESSION['user_vorname'] ?? 'User';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Kalender - Flugplatz Ohlstadt</title>
    
    <link rel="stylesheet" href="styles.css?v=1.6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js'></script>
    <script src="https://unpkg.com/popper.js@1"></script>
    <script src="https://unpkg.com/tippy.js@5"></script>

    <style>
        #calendar { max-width: 1100px; margin: 0 auto; padding-top: 20px; }
        .tippy-box { font-size: 0.9em; text-align: left; background-color: #333; }
        .tippy-box strong { color: #a1d8ff; }
        .tippy-arrow { color: #333; }
    </style>
</head>
<body class="app-body">

    <header class="app-header">
        <div class="logo">
            <a href="dashboard.php"><img src="pics/logo.png" alt="Logo"></a>
        </div>
        <nav class="app-header-nav">
            <a href="dashboard.php" class="<?php if($currentPage == 'dashboard.php') echo 'active'; ?>">Dashboard</a>
            <span>|</span>
            <a href="calendar.php" class="<?php if($currentPage == 'calendar.php') echo 'active'; ?>">Kalender</a>
            <span>|</span>
            <span>Willkommen, <?php echo htmlspecialchars($user_vorname); ?>!</span>
            <a href="logout.php" class="btn btn-grey">Logout</a>
        </nav>
    </header>

    <div class="app-container">
        <h1>Kalenderübersicht</h1>
        
        <div class="section">
            <div id='calendar'></div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'de',
            firstDay: 1, 
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            
            // HIER IST DIE KORREKTUR GEGEN DEN BROWSER-CACHE
            events: 'get_events.php?_cache=' + new Date().getTime(),

            eventDidMount: function(info) {
                if (info.event.extendedProps && Object.keys(info.event.extendedProps).length > 0) {
                    let props = info.event.extendedProps;
                    let tooltipContent = '';
                    
                    if (props.is_admin_block) {
                        tooltipContent = 'Dieser Zeitraum wurde vom Admin gesperrt.';
                    } else if (props.vereinsname) {
                        tooltipContent += '<strong>Verein:</strong> ' + props.vereinsname + '<br>';
                        if (props.kontakt) { 
                            tooltipContent += '<strong>Status:</strong> ' + props.status;
                        }
                    } else { return; }
                    
                    tippy(info.el, { content: tooltipContent, allowHTML: true });
                }
            },

            eventClick: function(info) {
                info.jsEvent.preventDefault(); 
                let props = info.event.extendedProps;
                let title = info.event.title;
                let details = '';

                if (props.is_admin_block) {
                    details = 'Dieser Zeitraum ist vom Admin gesperrt.\n\nGrund: ' + title.replace('Gesperrt: ', '');
                } 
                else if (props.vereinsname) {
                    details = 'Fluglager: ' + props.vereinsname + '\nStatus: ' + props.status + '\n\nKontakt: ' + props.kontakt + '\nE-Mail: ' + props.email + '\nTelefon: ' + props.telefon;
                    if (props.is_exclusive) { details += '\n(Exklusive Buchung)'; }
                } else { return; }

                alert(details);
            }
        });
        calendar.render();
    });
    </script>

</body>
</html>