<?php
/**
 * Parcial de paginación. Espera en scope: $page (actual), $totalPages.
 * Preserva los demás parámetros de la URL actual (filtros, tab, etc.),
 * solo cambia "page".
 */
if (($totalPages ?? 1) > 1):
    $qsAnterior  = http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)]));
    $qsSiguiente = http_build_query(array_merge($_GET, ['page' => min($totalPages, $page + 1)]));
?>
<div style="display:flex; align-items:center; justify-content:center; gap:1rem; padding:1.25rem; border-top:1px solid var(--slate-border);">
    <a href="?<?= $qsAnterior ?>" class="btn btn-ghost btn-sm <?= $page <= 1 ? 'disabled' : '' ?>" style="<?= $page <= 1 ? 'pointer-events:none;opacity:0.4;' : '' ?>">‹ Anterior</a>
    <span style="font-size:0.8rem; color:var(--slate-medium);">Página <?= $page ?> de <?= $totalPages ?></span>
    <a href="?<?= $qsSiguiente ?>" class="btn btn-ghost btn-sm <?= $page >= $totalPages ? 'disabled' : '' ?>" style="<?= $page >= $totalPages ? 'pointer-events:none;opacity:0.4;' : '' ?>">Siguiente ›</a>
</div>
<?php endif; ?>
