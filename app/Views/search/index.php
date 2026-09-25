<h1>Ricerca</h1>
<form method="get" action="/search" class="card form-card">
    <label for="q">Cerca nei tuoi workspace (titoli e contenuti)</label>
    <input id="q" name="q" value="<?= e($q) ?>" minlength="2" maxlength="200" placeholder="Almeno 2 caratteri…">
    <button type="submit">Cerca</button>
</form>

<?php if ($searched): ?>
    <h2>Risultati (<?= count($results) ?>)</h2>
    <?php if ($results === []): ?>
        <p class="muted">Nessun documento trovato per "<?= e($q) ?>".</p>
    <?php else: ?>
        <?php foreach ($results as $r): ?>
        <div class="card result-card">
            <a href="/documents/<?= (int) $r['id'] ?>"><strong><?= e($r['title']) ?></strong></a>
            <span class="badge ok"><?= e($r['workspace_name']) ?></span>
            <?php if ($r['excerpt'] !== ''): ?>
                <p class="muted"><?= e($r['excerpt']) ?></p>
            <?php endif; ?>
            <span class="muted">Aggiornato: <?= e((string) $r['updated_at']) ?></span>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>