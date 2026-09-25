<h1>Nuovo workspace</h1>
<form method="post" action="/workspaces" class="card form-card">
    <?= \Archium\Support\Csrf::field() ?>

    <label for="name">Nome</label>
    <input id="name" name="name" value="<?= e($old['name'] ?? '') ?>" required minlength="2" maxlength="150">
    <?php if (!empty($errors['name'])): ?><p class="field-error"><?= e($errors['name'][0]) ?></p><?php endif; ?>

    <label for="description">Descrizione (opzionale)</label>
    <input id="description" name="description" value="<?= e($old['description'] ?? '') ?>" maxlength="500">
    <?php if (!empty($errors['description'])): ?><p class="field-error"><?= e($errors['description'][0]) ?></p><?php endif; ?>

    <button type="submit">Crea workspace</button>
</form>