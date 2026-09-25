<h1>Password dimenticata</h1>
<form method="post" action="/forgot-password" class="card form-card">
    <?= \Archium\Support\Csrf::field() ?>

    <label for="email">Email del tuo account</label>
    <input id="email" type="email" name="email" required maxlength="190" autocomplete="email">
    <?php if (!empty($errors['email'])): ?><p class="field-error"><?= e($errors['email'][0]) ?></p><?php endif; ?>

    <button type="submit">Invia link di reset</button>
    <p class="muted"><a href="/login">Torna al login</a></p>
</form>
<?php if (!empty($devUrl)): ?>
    <p class="dev-note">Solo sviluppo — link di reset: <a href="<?= e($devUrl) ?>"><?= e($devUrl) ?></a></p>
<?php endif; ?>