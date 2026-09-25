<h1><?= e($doc['title']) ?></h1>

<p class="muted">
    Slug: <code>/<?= e($doc['slug']) ?></code>
    · Aggiornato: <?= e((string) $doc['updated_at']) ?>
    · <a href="/documents/<?= (int) $doc['id'] ?>/revisions">Revisioni</a>

    <?php if ($canWrite): ?>
        · <a href="/documents/<?= (int) $doc['id'] ?>/edit">Modifica</a>
    <?php endif; ?>
</p>

<div class="document-toolbar">
    <form method="post"
          action="/documents/<?= (int) $doc['id'] ?>/favorite"
          class="inline-form">
        <?= \Archium\Support\Csrf::field() ?>

        <button type="submit" class="btn-sm favorite-button">
            <?= !empty($isFavorite)
                ? '★ Rimuovi dai preferiti'
                : '☆ Aggiungi ai preferiti' ?>
        </button>
    </form>
</div>

<?php if (!empty($tags)): ?>
    <section class="tag-section">
        <h2>Tag</h2>

        <p class="tag-row">
            <?php foreach ($tags as $tag): ?>
                <a
                    class="tag-chip"
                    href="/search?tag=<?= (int) $tag['id'] ?>&q="
                >
                    <?= e($tag['name']) ?>
                </a>
            <?php endforeach; ?>
        </p>
    </section>
<?php endif; ?>

<div class="card doc-content">
    <?= $html ?>
</div>

<?php if (!empty($children)): ?>
    <h2>Sotto-documenti</h2>

    <ul>
        <?php foreach ($children as $child): ?>
            <li>
                <a href="/documents/<?= (int) $child['id'] ?>">
                    <?= e($child['title']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (!empty($attachments)): ?>
    <h2>Allegati</h2>

    <ul>
        <?php foreach ($attachments as $attachment): ?>
            <li>
                <a href="/attachments/<?= (int) $attachment['id'] ?>/download">
                    <?= e($attachment['original_name']) ?>
                </a>

                <span class="muted">
                    (<?= number_format(
                        ((int) $attachment['size_bytes']) / 1024,
                        0,
                        ',',
                        '.'
                    ) ?> KB)
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($canWrite): ?>
    <form
        method="post"
        action="/documents/<?= (int) $doc['id'] ?>/delete"
        class="inline-form"
    >
        <?= \Archium\Support\Csrf::field() ?>

        <button type="submit" class="btn-sm danger">
            Sposta nel cestino
        </button>
    </form>
<?php endif; ?>