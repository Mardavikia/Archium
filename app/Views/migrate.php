<h1>Migrazioni database</h1>

<h2>In attesa (<?= count($pending) ?>)</h2>
<?php if ($pending === []): ?>
    <p class="muted">Nessuna migrazione in attesa.</p>
<?php else: ?>
    <ul>
        <?php foreach ($pending as $m): ?>
            <li><code><?= e($m) ?></code></li>
        <?php endforeach; ?>
    </ul>
    <form method="post" action="/migrate/run?token=<?= e((string) $token) ?>">
        <?= $csrfField ?>
        <button type="submit">Esegui migrazioni</button>
    </form>
<?php endif; ?>

<h2>Già applicate (<?= count($applied) ?>)</h2>
<?php if ($applied === []): ?>
    <p class="muted">Nessuna.</p>
<?php else: ?>
    <ul>
        <?php foreach ($applied as $m): ?>
            <li><code><?= e($m) ?></code></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>