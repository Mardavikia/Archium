<h1>Imposta una nuova password</h1>
<?php if (($token ?? '') === '' || ($email ?? '') === ''): ?>
    <p class="field-error">Link incompleto. Richiedi un nuovo link da <a href="/forgot-password">qui</a>.</p>
<?php else: ?>
<form method="post" action="/reset-password" class="card form-card">
    <?= \Archium\Support\Csrf::field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <input type="hidden" name="email" value="<?= e($email) ?>">

    <label for="password">Nuova password (min. 8 caratteri)</label>
    <input id="password" type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password">

    <label for="password_confirmation">Conferma password</label>
    <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" maxlength="72" autocomplete="new-password">
    <?php if (!empty($errors['password'])): ?><p class="field-error"><?= e($errors['password'][0]) ?></p><?php endif; ?>

    <button type="submit">Aggiorna password</button>
</form>
<?php endif; ?>
<?php if (!empty($errors['token'])): ?><p class="field-error"><?= e($errors['token'][0]) ?></p><?php endif; ?>