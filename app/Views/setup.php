<h1>Setup iniziale — primo amministratore</h1>
<p class="muted">Questa pagina si disattiva automaticamente appena esiste un amministratore.</p>
<form method="post" action="/setup" class="card form-card">
    <?= \Archium\Support\Csrf::field() ?>

    <label for="name">Nome</label>
    <input id="name" name="name" value="<?= e($old['name'] ?? '') ?>" required minlength="2" maxlength="120" autocomplete="name">
    <?php if (!empty($errors['name'])): ?><p class="field-error"><?= e($errors['name'][0]) ?></p><?php endif; ?>

    <label for="email">Email</label>
    <input id="email" type="email" name="email" value="<?= e($old['email'] ?? '') ?>" required maxlength="190" autocomplete="email">
    <?php if (!empty($errors['email'])): ?><p class="field-error"><?= e($errors['email'][0]) ?></p><?php endif; ?>

    <label for="password">Password (min. 10 caratteri)</label>
    <input id="password" type="password" name="password" required minlength="10" maxlength="72" autocomplete="new-password">

    <label for="password_confirmation">Conferma password</label>
    <input id="password_confirmation" type="password" name="password_confirmation" required minlength="10" maxlength="72" autocomplete="new-password">
    <?php if (!empty($errors['password'])): ?><p class="field-error"><?= e($errors['password'][0]) ?></p><?php endif; ?>

    <button type="submit">Crea amministratore</button>
</form>