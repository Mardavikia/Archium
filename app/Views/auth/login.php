<h1>Accedi</h1>
<form method="post" action="/login" class="card form-card">
    <?= \Archium\Support\Csrf::field() ?>

    <label for="email">Email</label>
    <input id="email" type="email" name="email" value="<?= e($old['email'] ?? '') ?>" required maxlength="190" autocomplete="email">
    <?php if (!empty($errors['email'])): ?><p class="field-error"><?= e($errors['email'][0]) ?></p><?php endif; ?>

    <label for="password">Password</label>
    <input id="password" type="password" name="password" required maxlength="72" autocomplete="current-password">
    <?php if (!empty($errors['password'])): ?><p class="field-error"><?= e($errors['password'][0]) ?></p><?php endif; ?>

    <button type="submit">Accedi</button>
    <p class="muted"><a href="/forgot-password">Password dimenticata?</a> · <a href="/register">Crea un account</a></p>
</form>