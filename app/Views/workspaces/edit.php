<h1>Modifica workspace</h1>
<form method="post" action="/workspaces/<?= (int) $ws['id'] ?>/update" class="card form-card">
    <?= \Archium\Support\Csrf::field() ?>

    <label for="name">Nome</label>
    <input id="name" name="name" value="<?= e($ws['name']) ?>" required minlength="2" maxlength="150">
    <?php if (!empty($errors['name'])): ?><p class="field-error"><?= e($errors['name'][0]) ?></p><?php endif; ?>

    <label for="description">Descrizione (opzionale)</label>
    <input id="description" name="description" value="<?= e($ws['description'] ?? '') ?>" maxlength="500">

    <button type="submit">Salva modifiche</button>
    <p class="muted"><a href="/workspaces">Annulla</a></p>
</form>