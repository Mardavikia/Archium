<h1>Raccolte — <?= e($ws['name']) ?></h1>
<?php if ($canWrite): ?>
    <p><a class="btn" href="/collections/create">Nuova raccolta</a></p>
<?php endif; ?>

<?php if ($collections === []): ?>
    <p class="muted">Nessuna raccolta in questo workspace.</p>
<?php else: ?>
<table class="card">
    <tr><th>Nome</th><th>Genitore</th><th>Documenti</th><th>Azioni</th></tr>
    <?php foreach ($collections as $c): ?>
    <tr>
        <td><?= e($c['name']) ?><br><span class="muted">/<?= e($c['slug']) ?></span></td>
        <td><?= e($c['parent_name'] ?? '—') ?></td>
        <td><?= (int) $c['doc_count'] ?></td>
        <td class="actions">
            <?php if ($canWrite): ?>
            <a class="btn-sm" href="/collections/<?= (int) $c['id'] ?>/edit">Modifica</a>
            <form method="post" action="/collections/<?= (int) $c['id'] ?>/delete" class="inline-form">
                <?= \Archium\Support\Csrf::field() ?>
                <button type="submit" class="btn-sm danger">Elimina</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>