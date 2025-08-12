<?php // verwaltung/components/admin_actions.php ?>
<?php if ($lager['status'] !== 'abrechnung_gesendet' && $lager['status'] !== 'fertig_abgerechnet'): ?>
    <div class="section">
        <h3>Aktion durchführen</h3>
        <form action="handle_decision.php" method="post">
            </form>
    </div>
    <div class="section">
        <h3>Zahlungsstatus Anzahlung</h3>
        <?php if ($lager['anzahlung_bezahlt']): ?>
            <?php else: ?>
            <form action="handle_payment_status.php" method="post">
                </form>
        <?php endif; ?>
    </div>
<?php endif; ?>