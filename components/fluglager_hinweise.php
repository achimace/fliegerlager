<?php
// components/fluglager_hinweise.php
// This component expects $lager and $can_edit_core_data to be defined in the parent file (edit_fluglager.php)
?>
<div class="section">
    <h3>Hinweise und Wünsche</h3>

    <div id="notes-display">
        <p>
            <?php echo !empty($lager['hinweise_an_admin']) ? nl2br(htmlspecialchars($lager['hinweise_an_admin'])) : '<i>Keine besonderen Hinweise hinterlegt.</i>'; ?>
        </p>
        <?php if ($can_edit_core_data): ?>
        <button type="button" onclick="showNotestEditForm()" class="btn btn-edit" style="margin-top: 15px;">Bearbeiten</button>
        <?php endif; ?>
    </div>

    <div id="notes-edit-form" style="display:none;">
        <form action="handle_edit_camp_notes.php" method="post">
            <input type="hidden" name="lager_id" value="<?php echo $lager['id']; ?>">
            <div class="form-group">
                <label for="hinweise_an_admin">Hier kannst Du besondere Wünsche oder Hinweise für unsere Gäste-Verwaltung hinterlassen</label>
                <textarea id="hinweise_an_admin" name="hinweise_an_admin" rows="5" placeholder="Wünsche oder Hinweise (optional)." style="width:600px;"><?php echo htmlspecialchars($lager['hinweise_an_admin']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-edit">Speichern</button>
            <button type="button" onclick="hideNotesEditForm()" class="btn btn-grey">Abbrechen</button>
        </form>
    </div>
</div>