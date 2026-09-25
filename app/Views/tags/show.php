<h1><span class="chip"><?= e($tag['name']) ?></span></h1>
<p class="muted"><a href="/tags">Tutti i tag</a></p>
<?php if ($documents === []): ?>
    <p class="muted">Nessun documento con questo tag.</p>
<?php else: ?>
<table class="card">
    <tr><th>Documento</th><th>Aggiornato</th></tr>
    <?php foreach ($documents as $d): ?>
    <tr>
        <td><a href="/documents/<?= (int) $d['id'] ?>"><?= e($d['title']) ?></a></td>
        <td class="muted nowrap"><?= e((string) $d['updated_at']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>