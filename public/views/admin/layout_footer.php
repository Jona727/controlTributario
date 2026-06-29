    </div><!-- /.page-content -->
</div><!-- /.main-content -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script src="<?= $_ENV['APP_BASE_PATH'] ?? '/tasas_municipales/public' ?>/assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.searchable-select').forEach(function(el) {
        new TomSelect(el, {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });
    });
});
</script>
</body>
</html>
