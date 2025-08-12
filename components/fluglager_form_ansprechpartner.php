<div class="section">
    <h3  id="Ansprechpartner">Ansprechpartner / Organisator</h3>
    <div id="contact-display">
        <?php if (!empty($lager['ansprechpartner_vorname']) || !empty($lager['ansprechpartner_nachname'])): ?>
            <table class="styled-table">
                <tbody>
                    <tr>
                        <td style="width: 20%; text-align: right; font-weight: bold;">Name:</td>
                        <td style="text-align: left;"><?php echo htmlspecialchars($lager['ansprechpartner_vorname'] . ' ' . $lager['ansprechpartner_nachname']); ?></td>
                    </tr>
                    <tr>
                        <td style="text-align: right; font-weight: bold;">E-Mail:</td>
                        <td style="text-align: left;"><?php echo htmlspecialchars($lager['ansprechpartner_email']); ?><br>
 <span class="hinweistext" style="font-size: 0.8em;ccolor: #666; margin-top: 4px; display: block; font-style: italic;  ">Hinweis: An diese E-Mail Adresse senden wir die Buchungsbestätigung und nutzen diese für die Kommunikation</span></td>
                    </tr>
                    <tr>
                        <td style="text-align: right; font-weight: bold;">Telefon:</td>
                        <td style="text-align: left;"><?php echo htmlspecialchars($lager['ansprechpartner_telefon']); ?></td>
                    </tr>
                </tbody>
            </table>
        <?php else: ?>
            <p>Es wurden noch keine Kontaktdaten für den Ansprechpartner hinterlegt.</p>
        <?php endif; ?>
        
        <?php if ($can_edit_core_data): ?>
        <button type="button" onclick="showContactEditForm()" class="btn btn-edit" style="margin-top: 15px;">Bearbeiten</button>
        <?php endif; ?>
    </div>
    <div id="contact-edit-form" style="display:none;">
        <form action="handle_edit_camp_contact.php" method="post">
            <input type="hidden" name="lager_id" value="<?php echo $lager['id']; ?>">
            <table class="styled-table">
                <tbody>
                    <tr>
                        <td style="width: 20%; text-align: right; font-weight: bold;"><label for="ap_vorname">Vorname:</label></td>
                        <td style="text-align: left;"><input type="text" id="ap_vorname" name="ansprechpartner_vorname" value="<?php echo htmlspecialchars($lager['ansprechpartner_vorname']); ?>" placeholder="Max"></td>
                    </tr>
                    <tr>
                        <td style="text-align: right; font-weight: bold;"><label for="ap_nachname">Nachname:</label></td>
                        <td style="text-align: left;"><input type="text" id="ap_nachname" name="ansprechpartner_nachname" value="<?php echo htmlspecialchars($lager['ansprechpartner_nachname']); ?>" placeholder="Mustermann"></td>
                    </tr>
                    <tr>
                        <td style="text-align: right; font-weight: bold;"><label for="ap_email">E-Mail:</label></td>
                        <td style="text-align: left;"><input type="email" id="ap_email" name="ansprechpartner_email" value="<?php echo htmlspecialchars($lager['ansprechpartner_email']); ?>" placeholder="max@musterverein.de"> An diese E-Mail Adresse senden wir die Buchungsbestätigung und nutzen diese für die Kommunikation</td>
                    </tr>
                    <tr>
                        <td style="text-align: right; font-weight: bold;"><label for="ap_telefon">Telefon:</label></td>
                        <td style="text-align: left;"><input type="tel" id="ap_telefon" name="ansprechpartner_telefon" value="<?php echo htmlspecialchars($lager['ansprechpartner_telefon']); ?>" placeholder="0123 456789"></td>
                    </tr>
                </tbody>
            </table>
            <div style="margin-top: 15px;">
                <button type="submit" class="btn btn-edit">Speichern</button>
                <button type="button" onclick="hideContactEditForm()" class="btn btn-grey" style="background-color:#777;">Abbrechen</button>
            </div>
        </form>
    </div>
</div>