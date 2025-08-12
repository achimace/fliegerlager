<script>
    function toggleForm(rowId) {
        var row = document.getElementById(rowId);
        if (row.style.display === 'none' || row.style.display === '') {
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    }
    function showParticipantEditForm(id) {
        document.getElementById('participant-row-' + id).style.display = 'none';
        document.getElementById('edit-participant-form-' + id).style.display = 'table-row';
    }
    function hideParticipantEditForm(id) {
        document.getElementById('edit-participant-form-' + id).style.display = 'none';
        document.getElementById('participant-row-' + id).style.display = 'table-row';
    }
    function showAircraftEditForm(id) {
        document.getElementById('aircraft-row-' + id).style.display = 'none';
        document.getElementById('edit-aircraft-form-' + id).style.display = 'table-row';
    }
    function hideAircraftEditForm(id) {
        document.getElementById('edit-aircraft-form-' + id).style.display = 'none';
        document.getElementById('aircraft-row-' + id).style.display = 'table-row';
    }
    function showContactEditForm() {
        document.getElementById('contact-display').style.display = 'none';
        document.getElementById('contact-edit-form').style.display = 'block';
    }
    function hideContactEditForm() {
        document.getElementById('contact-display').style.display = 'block';
        document.getElementById('contact-edit-form').style.display = 'none';
    }
	function showNotestEditForm() {
        document.getElementById('notes-display').style.display = 'none';
        document.getElementById('notes-edit-form').style.display = 'block';
    }
    function hideNotesEditForm() {
        document.getElementById('notes-display').style.display = 'block';
        document.getElementById('notes-edit-form').style.display = 'none';
    }
</script>