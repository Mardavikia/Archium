<h1>Esito migrazioni</h1>
<ul>
    <?php foreach ($results as $r): ?>
        <li class="<?= $r['ok'] ? 'ok-text' : 'ko-text' ?>"><?= e($r['msg']) ?></li>
    <?php endforeach; ?>
</ul>
<p><a href="/migrate?token=<?= e((string) $token) ?>">Torna all'elenco</a></p>