<h1>Tag — <?= e($ws['name']) ?></h1>
<?php if ($tags === []): ?>
    <p class="muted">Nessun tag in questo workspace. Aggiungili dalla pagina di un documento.</p>
<?php else: ?>
<table class="card">
    <tr><th>Tag</th><th>Documenti</th></tr>
    <?php foreach ($tags as $t): ?>
    <tr>
        <td><a href="/tags/<?= (int) $t['id'] ?>"><span class="chip"><?= e($t['name']) ?></span></a></td>
        <td><?= (int) $t['doc_count'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>