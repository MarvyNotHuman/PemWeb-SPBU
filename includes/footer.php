</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php foreach (($skrip ?? []) as $s): ?><script src="assets/<?= e($s) ?>"></script><?php endforeach; ?>
</body></html>
