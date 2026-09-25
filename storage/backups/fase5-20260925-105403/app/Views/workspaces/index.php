<h1>I tuoi workspace</h1>
<p><a class="btn" href="/workspaces/create">Nuovo workspace</a></p>

<?php if ($workspaces === []): ?>
    <p class="muted">Non fai ancora parte di nessun workspace (ne verra' creato uno automaticamente alla prossima pagina).</p>
<?php else: ?>
<table class="card">
    <tr><th>Nome</th><th>Il tuo ruolo</th><th>Stato</th><th>Azioni</th></tr>
    <?php foreach ($workspaces as $w): ?>
    <tr>
        <td><?= e($w['name']) ?><br><span class="muted"><?= e($w['description'] ?? '') ?></span></td>
        <td><span class="badge ok"><?= e($w['my_role'] ?? '—') ?></span></td>
        <td><?= (int) $w['id'] === $currentId ? '<span class="badge ok">attivo</span>' : '' ?></td>
        <td class="actions">
            <form method="post" action="/workspaces/<?= (int) $w['id'] ?>/select" class="inline-form">
                <?= \Archium\Support\Csrf::field() ?>
                <button type="submit" class="btn-sm">Usa</button>
            </form>
            <?php if (($w['my_role'] ?? '') === 'owner'): ?>
                <a class="btn-sm" href="/workspaces/<?= (int) $w['id'] ?>/edit">Modifica</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>