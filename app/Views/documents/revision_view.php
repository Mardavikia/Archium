<h1>Revisione #<?= (int) $rev['revision_number'] ?> — <?= e($rev['title']) ?></h1>
<p class="muted">
    Salvata da <?= e($rev['author_name'] ?? '—') ?> il <?= e((string) $rev['created_at']) ?>
    · <a href="/documents/<?= (int) $doc['id'] ?>/revisions">Tutte le revisioni</a>
</p>

<div class="card doc-content"><?= $html ?></div>

<?php if ($canWrite): ?>
<form method="post" action="/documents/<?= (int) $doc['id'] ?>/revisions/<?= (int) $rev['revision_number'] ?>/restore" class="inline-form">
    <?= \Archium\Support\Csrf::field() ?>
    <button type="submit">Ripristina questa revisione</button>
</form>
<p class="muted">Lo stato attuale verra' salvato come nuova revisione: il ripristino e' annullabile.</p>
<?php endif; ?>