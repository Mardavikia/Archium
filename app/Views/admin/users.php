<h1>Gestione utenti</h1>

<p class="muted">
    Gestisci account, ruoli globali e stato di accesso.
</p>

<details class="user-create-panel">
    <summary>
        <span class="summary-title">+ Nuovo utente</span>
        <span class="summary-help">Crea un account senza registrazione pubblica</span>
    </summary>

    <form method="post" action="/admin/users/create" class="user-create-form">
        <?= \Archium\Support\Csrf::field() ?>

        <div class="user-form-grid">
            <div class="form-field">
                <label for="name">Nome</label>
                <input
                    id="name"
                    name="name"
                    value="<?= e($createOld['name'] ?? '') ?>"
                    required
                    minlength="2"
                    maxlength="120"
                    autocomplete="name"
                >
                <?php if (!empty($createErrors['name'])): ?>
                    <p class="field-error"><?= e($createErrors['name'][0]) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="<?= e($createOld['email'] ?? '') ?>"
                    required
                    maxlength="190"
                    autocomplete="email"
                >
                <?php if (!empty($createErrors['email'])): ?>
                    <p class="field-error"><?= e($createErrors['email'][0]) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="password">Password iniziale</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    minlength="10"
                    maxlength="72"
                    autocomplete="new-password"
                >
                <?php if (!empty($createErrors['password'])): ?>
                    <p class="field-error"><?= e($createErrors['password'][0]) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="password_confirmation">Conferma password</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    minlength="10"
                    maxlength="72"
                    autocomplete="new-password"
                >
            </div>

            <div class="form-field">
                <label for="role_id">Ruolo globale</label>
                <select id="role_id" name="role_id" required>
                    <option value="">— seleziona —</option>

                    <?php foreach ($roles as $role): ?>
                        <option
                            value="<?= (int) $role['id'] ?>"
                            <?= (string) ($createOld['role_id'] ?? '') === (string) $role['id'] ? 'selected' : '' ?>
                        >
                            <?= e($role['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (!empty($createErrors['role_id'])): ?>
                    <p class="field-error"><?= e($createErrors['role_id'][0]) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="create_status">Stato iniziale</label>
                <select id="create_status" name="status" required>
                    <option
                        value="active"
                        <?= ($createOld['status'] ?? 'active') === 'active' ? 'selected' : '' ?>
                    >
                        Attivo
                    </option>

                    <option
                        value="disabled"
                        <?= ($createOld['status'] ?? '') === 'disabled' ? 'selected' : '' ?>
                    >
                        Disabilitato
                    </option>
                </select>
            </div>
        </div>

        <div class="user-form-actions">
            <button type="submit" class="btn-sm">Crea utente</button>
            <span class="muted">L’account sarà già verificato.</span>
        </div>
    </form>
</details>

<h2>Utenti esistenti</h2>

<table class="card users-table">
    <tr>
        <th>Utente</th>
        <th>Email</th>
        <th>Ruolo</th>
        <th>Stato</th>
        <th>Ultimo login</th>
    </tr>

    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= e($user['name'] ?? '') ?></td>

            <td><?= e($user['email'] ?? '') ?></td>

            <td>
                <form
                    method="post"
                    action="/admin/users/<?= (int) $user['id'] ?>/role"
                    class="inline-form admin-inline-form"
                >
                    <?= \Archium\Support\Csrf::field() ?>

                    <select name="role_id" required>
                        <?php foreach ($roles as $role): ?>
                            <option
                                value="<?= (int) $role['id'] ?>"
                                <?= isset($user['role_id']) && (int) $user['role_id'] === (int) $role['id'] ? 'selected' : '' ?>
                            >
                                <?= e($role['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn-sm">Salva</button>
                </form>
            </td>

            <td>
                <form
                    method="post"
                    action="/admin/users/<?= (int) $user['id'] ?>/status"
                    class="inline-form admin-inline-form"
                >
                    <?= \Archium\Support\Csrf::field() ?>

                    <select name="status" required>
                        <option
                            value="active"
                            <?= ($user['status'] ?? '') === 'active' ? 'selected' : '' ?>
                        >
                            Attivo
                        </option>

                        <option
                            value="disabled"
                            <?= ($user['status'] ?? '') === 'disabled' ? 'selected' : '' ?>
                        >
                            Disabilitato
                        </option>
                    </select>

                    <button type="submit" class="btn-sm">Salva</button>
                </form>
            </td>

            <td class="nowrap">
                <?= e($user['last_login_at'] ?? '—') ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>