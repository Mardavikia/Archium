<h1>Nuovo documento</h1>
<form method="post" action="/documents" class="card form-card">
    <?= \Archium\Support\Csrf::field() ?>

    <label for="title">Titolo</label>
    <input id="title" name="title" value="<?= e($old['title'] ?? '') ?>" required minlength="2" maxlength="255">
    <?php if (!empty($errors['title'])): ?><p class="field-error"><?= e($errors['title'][0]) ?></p><?php endif; ?>

    <label for="collection_id">Raccolta (opzionale)</label>
    <select id="collection_id" name="collection_id">
        <option value="">— nessuna —</option>
        <?php foreach ($collections as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (string)($old['collection_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (!empty($errors['collection_id'])): ?><p class="field-error"><?= e($errors['collection_id'][0]) ?></p><?php endif; ?>

    <label for="parent_id">Documento genitore (opzionale)</label>
    <select id="parent_id" name="parent_id">
        <option value="">— radice —</option>
        <?php foreach ($parents as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= (string)($old['parent_id'] ?? '') === (string)$p['id'] ? 'selected' : '' ?>>
                <?= e($p['title']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (!empty($errors['parent_id'])): ?><p class="field-error"><?= e($errors['parent_id'][0]) ?></p><?php endif; ?>

    <label for="tags">Tag (separati da virgola, max 10)</label>
    <input id="tags" name="tags" list="tag-suggestions" value="<?= e($old['tags'] ?? '') ?>" maxlength="520" placeholder="es. rete, vpn, howto">
    <datalist id="tag-suggestions">
        <?php foreach ($availableTags as $t): ?>
            <option value="<?= e($t['name']) ?>"></option>
        <?php endforeach; ?>
    </datalist>
    <?php if (!empty($errors['tags'])): ?><p class="field-error"><?= e($errors['tags'][0]) ?></p><?php endif; ?>

    <label for="content_markdown">Contenuto (Markdown)</label>
    <div class="editor-grid">
        <textarea id="content_markdown" name="content_markdown" rows="16"><?= e($old['content_markdown'] ?? '') ?></textarea>
        <div id="md-preview" class="md-preview"></div>
    </div>

    <button type="submit">Crea documento</button>
    <p class="muted">Per allegare file: salva prima il documento, poi usa la pagina di modifica.</p>
</form>