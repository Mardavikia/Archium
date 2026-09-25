<h1>Membri — <?= e($workspace['name']) ?></h1>
<p class="muted">Solo gli owner possono invitare, modificare ruoli o rimuovere membri. Deve rimanere almeno un owner.</p>

<form method="post" action="/workspaces/<?= (int) $workspace['id'] ?>/members/add" class="card form-card">
    <?= \Archium\Support\Csrf::field() ?>
    <h2>Aggiungi membro</h2>
    <label for="email">Email utente già registrato e attivo</label>
    <input id="email" type="email" name="email" required maxlength="190">
    <label for="role">Ruolo nel workspace</label>
    <select id="role" name="role"><option value="viewer">Viewer</option><option value="editor">Editor</option><option value="owner">Owner</option></select>
    <button type="submit">Aggiungi / aggiorna</button>
</form>

<table class="card"><tr><th>Utente</th><th>Email</th><th>Ruolo workspace</th><th>Stato</th><th>Azioni</th></tr>
<?php foreach ($members as $member): ?>
<tr><td><?= e($member['name']) ?></td><td><?= e($member['email']) ?></td><td>
<form method="post" action="/workspaces/<?= (int)$workspace['id'] ?>/members/<?= (int)$member['id'] ?>/update" class="inline-form"><?= \Archium\Support\Csrf::field() ?><select name="role"><?php foreach (['owner','editor','viewer'] as $role): ?><option value="<?= $role ?>" <?= $member['role']===$role?'selected':'' ?>><?= e($role) ?></option><?php endforeach; ?></select><button class="btn-sm" type="submit">Salva</button></form>
</td><td><?= e($member['status']) ?></td><td><form method="post" action="/workspaces/<?= (int)$workspace['id'] ?>/members/<?= (int)$member['id'] ?>/remove" class="inline-form"><?= \Archium\Support\Csrf::field() ?><button type="submit" class="btn-sm danger">Rimuovi</button></form></td></tr>
<?php endforeach; ?></table>
<p><a href="/workspaces">Torna ai workspace</a></p>