<?php if ($is_submitted): ?>
<div class="message-info">
    <i class="fa-solid fa-hourglass-half"></i>
    <div>
        Dein Fluglager wurde eingereicht und wird gerade geprüft. In dieser Phase kannst du leider keine Änderungen mehr vornehmen. Falls wir Rückfragen haben, melden wir uns bei dir.<br>
        Solltet ihr selbst Fragen haben, erreichst du uns unter:<br>
        E-Mail: geschaeftsstelle@flugplatz-ohlstadt.de<br>
        Telefon: +49 (0)170 1240376
    </div>
</div>
<?php elseif ($is_confirmed): ?>
<div class="message-info">
    <i class="fa-solid fa-lock"></i>
    <div>Dein Fluglager wurde bestätigt. Der Zeitraum ist jetzt festgelegt und kann nicht mehr geändert werden. Die Teilnehmer- und Flugzeuglisten kannst du aber weiterhin bearbeiten.</div>
</div>
<?php endif; ?>