<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Comptes utilisateurs', 'Créez des comptes et contrôlez les accès à la plateforme.', 'users.php', 'admin');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? null)) {
    if (($_POST['action'] ?? '') === 'create') {
        $name = trim((string) ($_POST['name'] ?? '')); $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $role = in_array($_POST['role'] ?? '', ['operator','artec','admin'], true) ? $_POST['role'] : '';
        $operator = in_array($_POST['operator_code'] ?? '', ['orange','yas','airtel'], true) ? $_POST['operator_code'] : null;
        $password = (string) ($_POST['password'] ?? '');
        if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || !$role || ($role === 'operator' && !$operator) || ($role !== 'operator' && $operator)) $error = 'Vérifiez les informations. Le mot de passe doit contenir au moins 12 caractères.';
        else { try { db()->prepare('INSERT INTO users (name,email,password_hash,role,operator_code) VALUES (?,?,?,?,?)')->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role,$operator]); log_activity('user_created',current_user()['id'],['email'=>$email,'role'=>$role]); flash('success','Compte créé.'); header('Location: ' . url('admin/users.php')); exit; } catch (PDOException $exception) { $error = 'Cette adresse e-mail existe déjà.'; } }
    } elseif (($_POST['action'] ?? '') === 'toggle') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id && $id !== (int) current_user()['id']) db()->prepare('UPDATE users SET is_active=1-is_active WHERE id=?')->execute([$id]);
        flash('success','Statut du compte mis à jour.'); header('Location: ' . url('admin/users.php')); exit;
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id && $id !== (int) current_user()['id']) {
            db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            log_activity('user_deleted', current_user()['id'], ['deleted_user_id' => $id]);
            flash('success','Compte supprimé.');
        }
        header('Location: ' . url('admin/users.php')); exit;
    }
}
$rows = db()->query('SELECT id,name,email,role,operator_code,is_active,last_login_at,created_at FROM users ORDER BY created_at DESC')->fetchAll();
?>
<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?><form method="post" class="panel"><h2 style="font-size:20px">Créer un compte</h2><?= csrf_field() ?><input type="hidden" name="action" value="create"><div class="form-grid"><div class="field"><label>Nom</label><input name="name" required maxlength="140"></div><div class="field"><label>E-mail</label><input type="email" name="email" required></div><div class="field"><label>Rôle</label><select name="role" required><option value="operator">Opérateur</option><option value="artec">ARTEC</option><option value="admin">Administrateur</option></select></div><div class="field"><label>Code opérateur (si opérateur)</label><select name="operator_code"><option value="">Sans opérateur</option><option value="orange">Orange</option><option value="yas">Yas</option><option value="airtel">Airtel</option></select></div><div class="field full"><label>Mot de passe temporaire (12 caractères minimum)</label><input type="password" name="password" minlength="12" required autocomplete="new-password"></div></div><button class="button" type="submit">Créer le compte</button></form>
<div class="toolbar"><h2 style="font-size:19px;margin:0">Comptes existants</h2></div><div class="panel table-wrap"><table><thead><tr><th>Utilisateur</th><th>Rôle</th><th>Statut</th><th>Dernière connexion</th><th>Action</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><strong><?= e($row['name']) ?></strong><br><?= e($row['email']) ?></td><td><?= e($row['role']) ?><?= $row['operator_code'] ? ' · ' . e($row['operator_code']) : '' ?></td><td><span class="badge <?= $row['is_active'] ? 'success' : 'danger' ?>"><?= $row['is_active'] ? 'Actif' : 'Désactivé' ?></span></td><td><?= $row['last_login_at'] ? e(date('d/m/Y H:i', strtotime($row['last_login_at']))) : 'Jamais' ?></td><td><?php if ((int) $row['id'] !== (int) current_user()['id']): ?><div style="display:flex;gap:6px"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><button class="button secondary" type="submit"><?= $row['is_active'] ? 'Désactiver' : 'Réactiver' ?></button></form><form method="post" data-confirm="Supprimer définitivement ce compte ? Les décisions et journaux resteront conservés."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><button class="button danger" type="submit">Supprimer</button></form></div><?php else: ?><small>Compte actuel</small><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php workspace_end(); ?>