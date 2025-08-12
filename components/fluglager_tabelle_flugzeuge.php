<div class="section">
    <h3 id="Flugzeug">Flugzeuge (<?php echo count($flugzeuge)." / ".($einstellungen['max_flugzeuge'] ?? 10); ?>)</h3>
    <div style="overflow-x: auto;">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Kennzeichen</th>
                    <th>Muster</th>
                    <th>Typ</th>
                    <th>Pilot</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($flugzeuge as $flugzeug): ?>
                <tr id="aircraft-row-<?php echo $flugzeug['id']; ?>">
                    <td><?php echo htmlspecialchars($flugzeug['kennzeichen']); ?></td>
                    <td><?php echo htmlspecialchars($flugzeug['muster']); ?></td>
                    <td><?php echo htmlspecialchars($flugzeug['typ']); ?></td>
                    <td><?php echo $flugzeug['pilot_vorname'] ? htmlspecialchars($flugzeug['pilot_vorname'] . ' ' . $flugzeug['pilot_nachname']) : '<i>Kein Pilot</i>'; ?></td>
                    <td>
                        <?php if ($can_edit_lists): ?>
                            <button type="button" onclick="showAircraftEditForm(<?php echo $flugzeug['id']; ?>)" class="btn btn-edit">Bearbeiten</button>
                            <form action="handle_delete_aircraft.php" method="post" style="display:inline;" onsubmit="return confirm('Flugzeug wirklich löschen?');">
                                <input type="hidden" name="aircraft_id" value="<?php echo $flugzeug['id']; ?>">
                                <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                                <button type="submit" class="btn btn-danger btn-icon" title="Löschen"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        <?php else: ?>
                            <span>Gesperrt</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($can_edit_lists): ?>
                <tr id="edit-aircraft-form-<?php echo $flugzeug['id']; ?>" style="display:none;">
                    <td colspan="6" style="background-color: #eef; padding: 10px;">
                        <form action="handle_edit_aircraft.php" method="post" class="form-grid-condensed">
                            <input type="hidden" name="aircraft_id" value="<?php echo $flugzeug['id']; ?>">
                            <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                            
                            <input type="text" name="kennzeichen" value="<?php echo htmlspecialchars($flugzeug['kennzeichen']); ?>" required placeholder="Kennzeichen">
                            <input type="text" name="muster" value="<?php echo htmlspecialchars($flugzeug['muster']); ?>" placeholder="Muster">
                            <select name="typ">
                                <option value="">-- Typ --</option>
                                <option value="Segler" <?php if($flugzeug['typ'] == 'Segler') echo 'selected'; ?>>Segler</option>
                                <option value="Eigenstarter" <?php if($flugzeug['typ'] == 'Eigenstarter') echo 'selected'; ?>>Eigenstarter</option>
                                <option value="TMG" <?php if($flugzeug['typ'] == 'TMG') echo 'selected'; ?>>TMG</option>
                                <option value="UL" <?php if($flugzeug['typ'] == 'UL') echo 'selected'; ?>>UL</option>
                            </select>
                            <select name="pilot_id">
                                <option value="">-- Pilot --</option>
                                <?php foreach($teilnehmer as $person): ?>
                                <option value="<?php echo $person['id']; ?>" <?php if($flugzeug['pilot_id'] == $person['id']) echo 'selected'; ?>><?php echo htmlspecialchars($person['vorname'] . ' ' . $person['nachname']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div>
                                <button type="submit" class="btn btn-edit">OK</button>
                                <button type="button" onclick="hideAircraftEditForm(<?php echo $flugzeug['id']; ?>)" class="btn btn-grey">X</button>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
            <?php if ($can_edit_lists): ?>
            <tfoot>
                <tr id="addAircraftForm" style="display:none;">
                    <td colspan="6" style="background-color: #f3f3f3; padding: 10px;">
                        <form action="handle_add_aircraft.php" method="post" class="form-grid-condensed">
                            <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                            <input type="text" name="kennzeichen" placeholder="Kennzeichen" required>
                            <input type="text" name="muster" placeholder="Muster">
                            <select name="typ" required>
                                <option value="">-- Typ wählen --</option>
                                <option value="Segler">Segler</option>
                                <option value="Eigenstarter">Eigenstarter</option>
                                <option value="TMG">TMG</option>
                                <option value="UL">UL</option>
                            </select>
                            <select name="pilot_id">
                                <option value="">-- Pilot wählen --</option>
                                <?php foreach($teilnehmer as $person): ?>
                                <option value="<?php echo $person['id']; ?>"><?php echo htmlspecialchars($person['vorname'] . ' ' . $person['nachname']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-edit">Speichern</button>
                        </form>
                    </td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
    <?php if ($can_edit_lists): ?>
    <button type="button" onclick="toggleForm('addAircraftForm')" class="btn btn-edit" style="margin-top: 20px;">+ Neues Flugzeug hinzufügen</button>
    <?php endif; ?>
</div>