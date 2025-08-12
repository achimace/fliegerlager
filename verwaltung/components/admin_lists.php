<?php // verwaltung/components/admin_lists.php ?>
<div class="section">
    <h3>Teilnehmer (<?php echo count($teilnehmer); ?>)</h3>
	<div class="section-actions">
                    <a href="print_teilnehmer.php?id=<?php echo $lager_id; ?>" target="_blank"><i class="fa-solid fa-print"></i> Drucken</a>
                    <a href="export_teilnehmer.php?id=<?php echo $lager_id; ?>"><i class="fa-solid fa-file-csv"></i> Exportieren (CSV)</a>
                </div>
    <table class="styled-table">
        <thead><tr><th>Name</th><th>Rolle</th></tr></thead>
        <tbody><?php foreach ($teilnehmer as $person): ?><tr><td><?php echo htmlspecialchars($person['vorname'] . ' ' . $person['nachname']); ?></td><td><?php echo htmlspecialchars($person['rolle']); ?></td></tr><?php endforeach; ?></tbody>
    </table>
</div>
<div class="section">
    <h3>Flugzeuge (<?php echo count($flugzeuge); ?>)</h3>
	 <div class="section-actions">
                    <a href="print_flugzeuge.php?id=<?php echo $lager_id; ?>" target="_blank"><i class="fa-solid fa-print"></i> Drucken</a>
                    <a href="export_flugzeuge.php?id=<?php echo $lager_id; ?>"><i class="fa-solid fa-file-csv"></i> Exportieren (CSV)</a>
                </div>
    <table class="styled-table">
        <thead><tr><th>Kennzeichen</th><th>Typ</th></tr></thead>
        <tbody><?php foreach ($flugzeuge as $flugzeug): ?><tr><td><?php echo htmlspecialchars($flugzeug['kennzeichen']); ?></td><td><?php echo htmlspecialchars($flugzeug['typ']); ?></td></tr><?php endforeach; ?></tbody>
    </table>
</div>