<h1>Documenti — <?= e($ws['name']) ?></h1>
<p>
    <?php if ($canWrite): ?><a class="btn" href="/documents/create">Nuovo documento</a><?php endif; ?>
    <a href="/search">Ricerca</a> · <a href="/tags">Tag</a> · <a href="/favorites">Preferiti</a>
    <?php if ($canWrite): ?>· <a href="/trash">Cestino</a><?php endif; ?>
</p>

<?php if ($documents === []): ?>
    <p class="muted">Nessun documento in questo workspace.</p>
<?php else: ?>
<table class="card">
    <tr><th>Titolo</th><th>Raccolta</th><th>Genitore</th><th>Aggiornato</th><th>Azioni</th></tr>
    <?php foreach ($documents as $d): ?>
    <tr>
        <td><a href="/documents/<?= (int) $d['id'] ?>"><?= e($d['title']) ?></a><br>
            <span class="muted">/<?= e($d['slug']) ?></span></td>
        <td><?= e($d['collection_name'] ?? '—') ?></td>
        <td><?= e($d['parent_title'] ?? '—') ?></td>
        <td class="muted nowrap"><?= e((string) $d['updated_at']) ?></td>
        <td class="actions">
            <?php if ($canWrite): ?>
            <a class="btn-sm" href="/documents/<?= (int) $d['id'] ?>/edit">Modifica</a>
            <form method="post" action="/documents/<?= (int) $d['id'] ?>/delete" class="inline-form">
                <?= \Archium\Support\Csrf::field() ?>
                <button type="submit" class="btn-sm danger">Elimina</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>