<script src="https://unpkg.com/popper.js@1"></script>
<script src="https://unpkg.com/tippy.js@5"></script>

<style>
    #calendar-component { height: 600px; }
    .tippy-box { font-size: 0.9em; text-align: left; background-color: #333; }
    .tippy-box strong { color: #a1d8ff; }
    .tippy-arrow { color: #333; }

    /* Spezielle Farbe für das aktuell bearbeitete Event */
    .fc-event.event-current {
        background-color: #ffc107 !important;
        border-color: #d39e00 !important;
        color: #000 !important; /* Besser lesbarer Text auf gelbem Grund */
    }
</style>

<div id='calendar-component'></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar-component');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'de',
        firstDay: 1,
        initialDate: '<?php echo htmlspecialchars($lager['startdatum'] ?? ''); ?>',
        headerToolbar: {
            left: 'prev',
            center: 'title',
            right: 'next'
        },
        
        // Übergibt die ID des aktuellen Lagers, um es hervorzuheben
        events: 'get_events.php?_cache=' + new Date().getTime() + '&current_id=<?php echo $lager_id; ?>',

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
                
                tippy(info.el, { content: tooltipContent, allowHTML: true, });
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