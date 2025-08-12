<div class="section">
    <h3>Grunddaten</h3>
    <p>Hier kannst Du angeben, in welchem Zeitraum ihr euer Fluglager plant. Auf der Rechten Seite siehst Du unsere aktuelle Belegung. Bitte such Dir ein freies Datum aus. An- und Abreisetag dürfen sich überschneiden.</p>
    <form action="handle_edit_camp_details.php" method="post">
        <input type="hidden" name="lager_id" value="<?php echo $lager['id']; ?>">
        <div class="form-group">
            <label><b>Anreise</b></label>
            <input type="date" name="startdatum" value="<?php echo htmlspecialchars($lager['startdatum']); ?>" <?php if (!$can_edit_core_data) echo 'disabled'; ?> required>
        </div>
        <div class="form-group">
            <label><b>Abreise</b></label>
            <input type="date" name="enddatum" value="<?php echo htmlspecialchars($lager['enddatum']); ?>" <?php if (!$can_edit_core_data) echo 'disabled'; ?> required>
        </div>
        <div class="form-group">
            <br><b>Exklusiv Buchen</b><br>
						Wenn ihr eine sehr große Gruppe seid oder den Platz ganz für euch alleine reservieren möchtet, könnt ihr eine Exklusivbuchung vornehmen. Dafür wird die Anzahlung verdoppelt, diese wird jedoch bei der endgültigen Abrechnung vollständig angerechnet. Bitte beachtet, dass die Anzahlung im Falle einer Absage des Fluglagers dann nicht erstattet wird.    <br><br>
            <input type="checkbox" id="exklusiv" name="exklusiv" value="1" <?php if ($lager['exklusiv']) echo 'checked'; ?> <?php if (!$can_edit_core_data) echo 'disabled'; ?> style="width: auto; margin-right: 10px;">
            <label for="exklusiv" style="display: inline;">Wir möchten den Platz exklusiv buchen.</label>
        </div> <br>
        <?php if ($can_edit_core_data): ?>
        <button type="submit" class="btn btn-edit">Grunddaten Speichern</button>
        <?php endif; ?>
    </form>
</div>