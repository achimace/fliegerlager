<div class="section">
    <h3 id="Teilnehmer">Teilnehmer (<?php echo count($teilnehmer)." / ".($einstellungen['max_teilnehmer'] ?? 40); ?>)</h3>
    <div style="overflow-x: auto;">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Geburtsdatum</th>
                    <th>E-Mail</th>
                    <th>VF-Nr.</th>
                    <th>Aufenthalt</th>
                    <th>Camping</th>
                    <th>Funktion</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php $rollen = ['Pilot', 'Flugschüler', 'Begleitperson']; ?>
                <?php foreach ($teilnehmer as $person): ?>
                    <tr id="participant-row-<?php echo $person['id']; ?>">
                        <td data-label="Name"><?php echo htmlspecialchars($person['vorname'] . ' ' . $person['nachname']); ?></td>
                        <td data-label="GebDatum"><?php echo $person['geburtsdatum'] ? date('d.m.Y', strtotime($person['geburtsdatum'])) : ''; ?></td>
                        <td data-label="E-Mail"><?php echo htmlspecialchars($person['email']); ?></td>
                        <td data-label="VNr"><?php echo htmlspecialchars($person['vereinsflieger_nr']); ?></td>
                        <td data-label="Von"><?php echo $person['aufenthalt_von'] ? date('d.m', strtotime($person['aufenthalt_von'])) . ' - ' . date('d.m', strtotime($person['aufenthalt_bis'])) : 'Lagerzeitraum'; ?></td>
                        <td data-label="Camping"><?php echo $person['camping'] ? 'Ja' : 'Nein'; ?></td>
                        <td data-label="Funktion"><?php echo htmlspecialchars($person['rolle']); ?></td>
                        <td data-label="Edit">
                            <?php if ($can_edit_lists): ?>
                                <button type="button" onclick="showParticipantEditForm(<?php echo $person['id']; ?>)" class="btn btn-edit">Bearbeiten</button>
                                <form action="handle_delete_participant.php" method="post" style="display:inline;" onsubmit="return confirm('Teilnehmer wirklich löschen?');">
                                    <input type="hidden" name="participant_id" value="<?php echo $person['id']; ?>">
                                    <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                                    <button type="submit" class="btn btn-danger btn-icon" title="Löschen"><i class="fa-solid fa-trash-can"></i></button>
                                </form>
                            <?php else: ?>
                                <span>Gesperrt</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($can_edit_lists): ?>
                    <tr id="edit-participant-form-<?php echo $person['id']; ?>" style="display:none; background-color: #eef;">
                        <td colspan="8">
                            <form action="handle_edit_participant.php" method="post">
                                <input type="hidden" name="participant_id" value="<?php echo $person['id']; ?>">
                                <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                                <div style="display: grid; grid-template-columns: 2fr 1fr 1.5fr 1fr 1.5fr 0.5fr 1fr 1.5fr; gap: 10px; align-items: center; padding: 10px;">
                                    <div>
                                        <input type="text" name="vorname" value="<?php echo htmlspecialchars($person['vorname']); ?>" placeholder="Vorname" required><br>
                                        <input type="text" name="nachname" value="<?php echo htmlspecialchars($person['nachname']); ?>" placeholder="Nachname" required>
                                    </div>
                                    <input type="date" name="geburtsdatum" value="<?php echo htmlspecialchars($person['geburtsdatum']); ?>">
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($person['email']); ?>" placeholder="E-Mail">
                                    <input type="text" name="vereinsflieger_nr" value="<?php echo htmlspecialchars($person['vereinsflieger_nr']); ?>" placeholder="VF-Nr.">
                                    <div>
                                        <input type="date" name="aufenthalt_von" value="<?php echo htmlspecialchars($person['aufenthalt_von']); ?>" title="Anreise"><br>
                                        <input type="date" name="aufenthalt_bis" value="<?php echo htmlspecialchars($person['aufenthalt_bis']); ?>" title="Abreise">
                                    </div>
                                    <input type="checkbox" name="camping" value="1" <?php if ($person['camping']) echo 'checked'; ?>>
                                    <select name="rolle">
                                        <option value="">-- Funktion --</option>
                                        <?php foreach($rollen as $rolle): ?>
                                            <option value="<?php echo $rolle; ?>" <?php if($person['rolle'] == $rolle) echo 'selected'; ?>><?php echo $rolle; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div>
                                        <button type="submit" class="btn btn-edit">OK</button>
                                        <button type="button" onclick="hideParticipantEditForm(<?php echo $person['id']; ?>)" class="btn btn-grey">X</button>
                                    </div>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
            <?php if ($can_edit_lists): ?>
            <tfoot>
                <tr id="addParticipantForm" style="display:none; background-color: #f3f3f3;">
                    <td colspan="8" data-label="Funktion">
                        <form action="handle_add_participant.php" method="post">
                            <input type="hidden" name="lager_id" value="<?php echo $lager_id; ?>">
                            <div style="display: grid; grid-template-columns: 2fr 1fr 1.5fr 1fr 1.5fr 0.5fr 1fr 1.5fr; gap: 10px; align-items: center; padding: 10px;">
                                <div>
                                    <input type="text" name="vorname" placeholder="Vorname" required><br>
                                    <input type="text" name="nachname" placeholder="Nachname" required>
                                </div>
                                <input type="date" name="geburtsdatum" title="Geburtsdatum">
                                <input type="email" name="email" placeholder="E-Mail">
                                <input type="text" name="vereinsflieger_nr" placeholder="VF-Nr.">
                                <div>
                                    <input type="date" name="aufenthalt_von" title="Anreise"><br>
                                    <input type="date" name="aufenthalt_bis" title="Abreise">
                                </div>
                                <input type="checkbox" name="camping" value="1">
                                <select name="rolle">
                                    <option value="">-- Funktion --</option>
                                    <?php foreach($rollen as $rolle): ?>
                                        <option value="<?php echo $rolle; ?>"><?php echo $rolle; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-edit">Speichern</button>
                            </div>
                        </form>
                    </td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
    <?php if ($can_edit_lists): ?>
    <button type="button" onclick="toggleForm('addParticipantForm')" class="btn btn-edit" style="margin-top: 20px;">+ Neuen Teilnehmer hinzufügen</button>
    <?php endif; ?>
</div>