<h1>I tuoi preferiti</h1>
<?php if ($favorites === []): ?>
    <p class="muted">Nessun preferito. Apri un documento e usa il pulsante "Preferito".</p>
<?php else: ?>
<table class="card">
    <tr><th>Documento</th><th>Workspace</th><th>Aggiunto il</th></tr>
    <?php foreach ($favorites as $f): ?>
    <tr>
        <td><a href="/documents/<?= (int) $f['id'] ?>"><?= e($f['title']) ?></a></td>
        <td><?= e($f['workspace_name']) ?></td>
        <td class="muted nowrap"><?= e((string) $f['fav_at']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>