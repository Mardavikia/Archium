<h1>Permessi documento — <?= e($document['title']) ?></h1>
<p class="muted">Override documento: precedenza più alta dopo l’owner del workspace.</p>
<form method="post" action="/documents/<?= (int)$document['id'] ?>/permissions/grant" class="card form-card">
<?= \Archium\Support\Csrf::field() ?>
<label>Email membro workspace</label><input type="email" name="email" required maxlength="190">
<label>Permesso</label><select name="permission"><option value="read">Read</option><option value="write">Write</option><option value="admin">Admin</option></select>
<button type="submit">Concedi / aggiorna</button></form>
<table class="card"><tr><th>Utente</th><th>Email</th><th>Override</th><th></th></tr><?php foreach($permissions as $p): ?><tr><td><?= e($p['name']) ?></td><td><?= e($p['email']) ?></td><td><?= e($p['permission']) ?></td><td><form method="post" action="/documents/<?= (int)$document['id'] ?>/permissions/<?= (int)$p['user_id'] ?>/revoke" class="inline-form"><?= \Archium\Support\Csrf::field() ?><button class="btn-sm danger">Rimuovi</button></form></td></tr><?php endforeach; ?></table>
<p><a href="/documents/<?= (int)$document['id'] ?>">Torna al documento</a></p>