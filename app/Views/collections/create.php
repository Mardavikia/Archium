<h1>Nuova raccolta</h1>
<form method="post" action="/collections" class="card form-card">
    <?= \Archium\Support\Csrf::field() ?>

    <label for="name">Nome</label>
    <input id="name" name="name" value="<?= e($old['name'] ?? '') ?>" required minlength="2" maxlength="150">
    <?php if (!empty($errors['name'])): ?><p class="field-error"><?= e($errors['name'][0]) ?></p><?php endif; ?>

    <label for="parent_id">Raccolta genitore (opzionale)</label>
    <select id="parent_id" name="parent_id">
        <option value="">— radice —</option>
        <?php foreach ($collections as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (string)($old['parent_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (!empty($errors['parent_id'])): ?><p class="field-error"><?= e($errors['parent_id'][0]) ?></p><?php endif; ?>

    <label for="description">Descrizione (opzionale)</label>
    <input id="description" name="description" value="<?= e($old['description'] ?? '') ?>" maxlength="500">

    <button type="submit">Crea raccolta</button>
</form>