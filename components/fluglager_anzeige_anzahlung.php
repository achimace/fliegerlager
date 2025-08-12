<div class="section">
    <?php if ($lager['anzahlung_bezahlt'] == 0): ?>
        <h3>Nächster Schritt: Anzahlung</h3>
        <p class="message-info">Ihre Anfrage wurde bestätigt! Um die Buchung final abzuschließen, überweisen Sie bitte die fällige Anzahlung.</p>
        <div class="payment-details">
            <p>Wir bitten um eine Anzahlung von <strong><?php echo number_format($einstellungen['preis_anzahlung'], 2, ',', '.'); ?> €</strong> pro teilnehmendem Piloten oder Flugschüler.</p>
            <p>Aktuell fälliger Betrag: <strong><?php echo number_format($deposit_amount, 2, ',', '.'); ?> €</strong></p><hr>
            <p><strong>Konto:</strong> <?php echo htmlspecialchars($einstellungen['kontonummer_anzahlung']); ?></p>
            <p><strong>Verwendungszweck:</strong> Anzahlung Fluglager <?php echo htmlspecialchars($club_name); ?></p>
        </div>
    <?php else: ?>
        <h3>Buchung bestätigt</h3>
        <p class="message-success">Vielen Dank, Ihre Anzahlung wurde verbucht. Ihr Fluglager ist jetzt final bestätigt.</p>
    <?php endif; ?>
</div>