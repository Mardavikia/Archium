<h1>Installazione — stato dei servizi</h1>
<p class="muted">Ambiente: <?= e($env) ?> · PHP <?= e($phpVer) ?></p>
<table class="card">
    <tr>
        <td>Applicazione</td>
        <td><span class="badge ok">attiva</span></td>
    </tr>
    <tr>
        <td>Connessione database</td>
        <td>
            <?php if ($dbOk): ?>
                <span class="badge ok">OK</span>
            <?php else: ?>
                <span class="badge ko">NON DISPONIBILE</span>
            <?php endif; ?>
        </td>
    </tr>
</table>
<p>
    <a href="/setup">Setup primo amministratore</a> ·
    <a href="/register">Registrati</a> ·
    <a href="/login">Accedi</a> ·
    <a href="/dashboard">Dashboard</a>
</p>