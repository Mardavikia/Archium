<h1>Modifica documento</h1>
<form method="post" action="/documents/<?= (int) $doc['id'] ?>/update" class="card form-card">
    <?= \Archium\Support\Csrf::field() ?>

    <label for="title">Titolo</label>
    <input id="title" name="title" value="<?= e($doc['title']) ?>" required minlength="2" maxlength="255">
    <?php if (!empty($errors['title'])): ?><p class="field-error"><?= e($errors['title'][0]) ?></p><?php endif; ?>

    <label for="collection_id">Raccolta (opzionale)</label>
    <select id="collection_id" name="collection_id">
        <option value="">— nessuna —</option>
        <?php foreach ($collections as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (string)($doc['collection_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (!empty($errors['collection_id'])): ?><p class="field-error"><?= e($errors['collection_id'][0]) ?></p><?php endif; ?>

    <label for="parent_id">Documento genitore (opzionale)</label>
    <select id="parent_id" name="parent_id">
        <option value="">— radice —</option>
        <?php foreach ($parents as $p): ?>
            <?php if ((int) $p['id'] === (int) $doc['id']) { continue; } ?>
            <option value="<?= (int) $p['id'] ?>" <?= (string)($doc['parent_id'] ?? '') === (string)$p['id'] ? 'selected' : '' ?>>
                <?= e($p['title']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (!empty($errors['parent_id'])): ?><p class="field-error"><?= e($errors['parent_id'][0]) ?></p><?php endif; ?>

    <label for="tags">Tag (separati da virgola, max 10)</label>
    <input id="tags" name="tags" list="tag-suggestions" value="<?= e($docTagsCsv ?? '') ?>" maxlength="520" placeholder="es. rete, vpn, howto">
    <datalist id="tag-suggestions">
        <?php foreach ($availableTags as $t): ?>
            <option value="<?= e($t['name']) ?>"></option>
        <?php endforeach; ?>
    </datalist>
    <?php if (!empty($errors['tags'])): ?><p class="field-error"><?= e($errors['tags'][0]) ?></p><?php endif; ?>

    <label for="content_markdown">Contenuto (Markdown)</label>
    <div class="editor-grid">
        <textarea id="content_markdown" name="content_markdown" rows="16"><?= e($doc['content_markdown'] ?? '') ?></textarea>
        <div id="md-preview" class="md-preview"></div>
    </div>

    <button type="submit">Salva modifiche</button>
    <p class="muted">
        <a href="/documents/<?= (int) $doc['id'] ?>">Annulla</a> ·
        <a href="/documents/<?= (int) $doc['id'] ?>/revisions">Cronologia revisioni</a> ·
        Slug: <code>/<?= e($doc['slug']) ?></code>
    </p>
</form>

<h2>Allegati</h2>
<?php if ($attachments === []): ?>
    <p class="muted">Nessun allegato.</p>
<?php else: ?>
<table class="card">
    <tr><th>File</th><th>Dimensione</th><th>Caricato da</th><th>Azioni</th></tr>
    <?php foreach ($attachments as $a): ?>
    <tr>
        <td><?= e($a['original_name']) ?><br>
            <span class="muted">inserisci nel testo: </span><code>![<?= e($a['original_name']) ?>](/attachments/<?= (int) $a['id'] ?>)</code></td>
        <td class="nowrap"><?= number_format(((int) $a['size_bytes']) / 1024, 0, ',', '.') ?> KB</td>
        <td><?= e($a['uploader_name'] ?? '—') ?></td>
        <td class="actions">
            <a class="btn-sm" href="/attachments/<?= (int) $a['id'] ?>">Apri</a>
            <a class="btn-sm" href="/attachments/<?= (int) $a['id'] ?>/download">Scarica</a>
            <form method="post" action="/attachments/<?= (int) $a['id'] ?>/delete" class="inline-form">
                <?= \Archium\Support\Csrf::field() ?>
                <button type="submit" class="btn-sm danger">Elimina</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<form method="post" action="/documents/<?= (int) $doc['id'] ?>/attachments" enctype="multipart/form-data" class="card form-card">
    <?= \Archium\Support\Csrf::field() ?>
    <label for="attachment">Carica allegato o immagine (png, jpg, gif, webp, pdf, txt, md, zip, docx, xlsx — max 20 MB)</label>
    <input id="attachment" type="file" name="attachment" required>
    <button type="submit">Carica</button>
</form>