<h1>Cestino — <?= e($ws['name']) ?></h1>

<h2>Documenti eliminati</h2>
<?php if ($documents === []): ?>
    <p class="muted">Nessun documento nel cestino.</p>
<?php else: ?>
<table class="card">
    <tr><th>Titolo</th><th>Eliminato il</th><th>Azioni</th></tr>
    <?php foreach ($documents as $d): ?>
    <tr>
        <td><?= e($d['title']) ?></td>
        <td class="muted nowrap"><?= e((string) $d['deleted_at']) ?></td>
        <td class="actions">
            <form method="post" action="/trash/documents/<?= (int) $d['id'] ?>/restore" class="inline-form">
                <?= \Archium\Support\Csrf::field() ?>
                <button type="submit" class="btn-sm">Ripristina</button>
            </form>
            <?php if ($isOwner): ?>
            <form method="post" action="/trash/documents/<?= (int) $d['id'] ?>/force-delete" class="inline-form">
                <?= \Archium\Support\Csrf::field() ?>
                <button type="submit" class="btn-sm danger">Elimina definitivamente</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<h2>Raccolte eliminate</h2>
<p class="muted">Il ripristino riattiva la raccolta; documenti e sotto-raccolte spostati alla radice durante l'eliminazione non vengono riagganciati automaticamente.</p>
<?php if ($collections === []): ?>
    <p class="muted">Nessuna raccolta nel cestino.</p>
<?php else: ?>
<table class="card">
    <tr><th>Nome</th><th>Eliminata il</th><th>Azioni</th></tr>
    <?php foreach ($collections as $c): ?>
    <tr>
        <td><?= e($c['name']) ?></td>
        <td class="muted nowrap"><?= e((string) $c['deleted_at']) ?></td>
        <td class="actions">
            <form method="post" action="/trash/collections/<?= (int) $c['id'] ?>/restore" class="inline-form">
                <?= \Archium\Support\Csrf::field() ?>
                <button type="submit" class="btn-sm">Ripristina</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>