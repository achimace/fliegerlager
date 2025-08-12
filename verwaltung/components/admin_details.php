<?php // verwaltung/components/admin_details.php ?>
<div class="section">
    <h3>Anfragedaten</h3>
    <p><strong>Anfragesteller:</strong> <?php echo htmlspecialchars($lager['vorname'] . ' ' . $lager['nachname']); ?> (<?php echo htmlspecialchars($lager['email']); ?>)</p>
    <p><strong>Verein:</strong> <?php echo htmlspecialchars($lager['vereinsname']); ?></p>
    <p><strong>Zeitraum:</strong> <?php echo date('d.m.Y', strtotime($lager['startdatum'])); ?> bis <?php echo date('d.m.Y', strtotime($lager['enddatum'])); ?></p>
    <p><strong>Status:</strong> <?php echo getStatusBadge($lager['status']); ?></p>
</div>
<div class="section">
    <h3>Hinweise vom Kunden</h3>
    <?php if (!empty($lager['hinweise_an_admin'])): ?>
        <p style="white-space: pre-wrap; background-color: #f8f9fa; padding: 15px; border-radius: 5px;"><?php echo htmlspecialchars($lager['hinweise_an_admin']); ?></p>
    <?php else: ?>
        <p><i>Es wurden keine besonderen Hinweise hinterlegt.</i></p>
    <?php endif; ?>
</div>