<h1>Verifica la tua email</h1>
<p>Ti abbiamo inviato un link di verifica. Aprilo per attivare l'account, poi potrai accedere.</p>
<?php if (!empty($devUrl)): ?>
    <p class="dev-note">Solo sviluppo — link di verifica: <a href="<?= e($devUrl) ?>"><?= e($devUrl) ?></a></p>
<?php endif; ?>
<p class="muted"><a href="/login">Vai al login</a></p>