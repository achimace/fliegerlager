<?php // verwaltung/components/admin_summary.php ?>
<?php if ($lager['status'] === 'abrechnung_gesendet' || $lager['status'] === 'fertig_abgerechnet'): ?>
<div class="section" id="summary-section">
    <h3>Zusammenfassung der Abrechnung</h3>
    <p>Die folgenden Daten wurden vom Kunden übermittelt.</p>
    
    <?php if (!empty($summary_data['teilnehmer'])): ?>
    <h4>Teilnehmer</h4>
    <table class="styled-table">
        </table>
    <?php endif; ?>
    
    <?php if (!empty($summary_data['flugzeuge'])): ?>
    <h4 style="margin-top: 20px;">Flugzeuge</h4>
    <table class="styled-table">
        </table>
    <?php endif; ?>
    
    <h3 style="text-align: right; margin-top: 20px;">Gesamtsumme: <?php echo number_format($gesamtsumme, 2, ',', '.'); ?> €</h3>
    <div class="status-form">
        <h4>Abrechnung bearbeiten & abschließen</h4>
        </div>
</div>
<?php endif; ?>