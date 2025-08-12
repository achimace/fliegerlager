<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Belegungskalender - Flugplatz Ohlstadt</title>
    <link rel="stylesheet" href="styles.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js'></script>
    <style>
        #calendar {
            max-width: 1100px;
            margin: 20px auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Belegungskalender</h1>
        </header>
        <div id='calendar'></div>
    </div>

    <script>
      // --- Dokumentation ---
      // Initialisiert FullCalendar und holt die Event-Daten vom Backend.
      document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth', // Monatsansicht als Standard
          locale: 'de', // Deutsche Sprache und Wochenstart am Montag
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek' // Ansichts-Umschalter
          },
          // Hier holt der Kalender seine Daten:
          events: 'get_events.php'
        });
        calendar.render();
      });
    </script>
</body>
</html>