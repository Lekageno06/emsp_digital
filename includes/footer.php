<?php
// Aligne TP CRUD V2 - Scripts Bootstrap, jQuery & DataTables centralises
?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>
    <script>
        $(function () {
            $('.datatable-fr').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/fr-FR.json'
                },
                lengthMenu: [5, 10, 25, 50],
                pagingType: 'simple_numbers',
                responsive: true
            });
        });
    </script>
</body>
</html>
