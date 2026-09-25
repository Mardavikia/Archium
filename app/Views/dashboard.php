<h1>Dashboard</h1>
<p>Ciao <strong><?= e($currentUser['name']) ?></strong> — ruolo globale:
   <span class="badge ok"><?= e($currentUser['role_label'] ?? '—') ?></span></p>

<p>Workspace attivo: <strong><?= e($ws['name']) ?></strong>
   <span class="muted">(<?= e($ws['my_role']) ?>)</span>
   — <a href="/workspaces">cambia o crea un altro workspace</a></p>

<table class="card">
    <tr><td>Documenti nel workspace</td><td><?= (int) $totalDocs ?></td></tr>
    <tr><td>Raccolte nel workspace</td><td><?= (int) $totalCols ?></td></tr>
</table>

<p>
    <a class="btn" href="/documents/create">Nuovo documento</a>
    <a class="btn secondary" href="/collections/create">Nuova raccolta</a>
</p>