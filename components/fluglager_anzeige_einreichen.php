<div class="section">
    <h3>Fluglager zur Prüfung einreichen</h3>
    <?php
    $has_contact = !empty($lager['ansprechpartner_vorname']) && !empty($lager['ansprechpartner_nachname']);
    $has_participants = count($teilnehmer) > 0;
    $has_aircraft = count($flugzeuge) > 0;
    $can_submit = $has_contact && $has_participants && $has_aircraft;
    
    if ($can_submit):
    ?>
        <div class="submission-requirements">
            <ul><li><i class="fa-solid fa-check-circle icon-green"></i> Alle Voraussetzungen sind erfüllt. Du kannst das Fluglager zur Prüfung an uns senden.</li></ul>
        </div><br>
        <form action="handle_submit_fluglager.php" method="post" onsubmit="return confirm('Möchten Sie dieses Fluglager wirklich einreichen? Danach sind keine Änderungen mehr möglich.');">
            <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
            <button type="submit" class="btn" style="background-color: #28a745;">Jetzt Einreichen</button>
        </form>
    <?php else: ?>
        <div class="submission-requirements">
            <p><strong>Das Fluglager kann noch nicht eingereicht werden.</strong> Bitte erfülle folgende Voraussetzungen:</p>
            <ul>
                <li><i class="fa-solid <?php echo $has_contact ? 'fa-check-circle icon-green' : 'fa-times-circle icon-red'; ?>"></i> <a href="#Ansprechpartner">Ansprechpartner ist hinterlegt</a></li>
                <li><i class="fa-solid <?php echo $has_participants ? 'fa-check-circle icon-green' : 'fa-times-circle icon-red'; ?>"></i><a href="#Teilnehmer"> Ein  Teilnehmer ist hinzugefügt</a></li>
                <li><i class="fa-solid <?php echo $has_aircraft ? 'fa-check-circle icon-green' : 'fa-times-circle icon-red'; ?>"></i>  <a href="#Flugzeug">Ein Flugzeug ist hinzugefügt</a></li>
            </ul>
        </div>
    <?php endif; ?>
</div>