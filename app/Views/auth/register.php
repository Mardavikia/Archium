<h1>Crea un account</h1>
<form method="post" action="/register" class="card form-card">
    <?= \Archium\Support\Csrf::field() ?>

    <label for="name">Nome</label>
    <input id="name" name="name" value="<?= e($old['name'] ?? '') ?>" required minlength="2" maxlength="120" autocomplete="name">
    <?php if (!empty($errors['name'])): ?><p class="field-error"><?= e($errors['name'][0]) ?></p><?php endif; ?>

    <label for="email">Email</label>
    <input id="email" type="email" name="email" value="<?= e($old['email'] ?? '') ?>" required maxlength="190" autocomplete="email">
    <?php if (!empty($errors['email'])): ?><p class="field-error"><?= e($errors['email'][0]) ?></p><?php endif; ?>

    <label for="password">Password (min. 8 caratteri)</label>
    <input id="password" type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password">

    <label for="password_confirmation">Conferma password</label>
    <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" maxlength="72" autocomplete="new-password">
    <?php if (!empty($errors['password'])): ?><p class="field-error"><?= e($errors['password'][0]) ?></p><?php endif; ?>

    <button type="submit">Registrati</button>
    <p class="muted">Hai gia' un account? <a href="/login">Accedi</a></p>
</form>