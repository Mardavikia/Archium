<h1>Revisioni — <?= e($doc['title']) ?></h1>
<p class="muted"><a href="/documents/<?= (int) $doc['id'] ?>">Torna al documento</a></p>

<?php if ($revisions === []): ?>
    <p class="muted">Nessuna revisione registrata: le revisioni vengono create a ogni salvataggio successivo al primo.</p>
<?php else: ?>
<table class="card">
    <tr><th>#</th><th>Titolo</th><th>Autore</th><th>Data</th><th>Azioni</th></tr>
    <?php foreach ($revisions as $r): ?>
    <tr>
        <td><?= (int) $r['revision_number'] ?></td>
        <td><?= e($r['title']) ?></td>
        <td><?= e($r['author_name'] ?? '—') ?></td>
        <td class="muted nowrap"><?= e((string) $r['created_at']) ?></td>
        <td class="actions">
            <a class="btn-sm" href="/documents/<?= (int) $doc['id'] ?>/revisions/<?= (int) $r['revision_number'] ?>">Vedi</a>
            <?php if ($canWrite): ?>
            <form method="post" action="/documents/<?= (int) $doc['id'] ?>/revisions/<?= (int) $r['revision_number'] ?>/restore" class="inline-form">
                <?= \Archium\Support\Csrf::field() ?>
                <button type="submit" class="btn-sm">Ripristina</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>