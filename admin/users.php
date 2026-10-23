<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$user = current_user();
$errors = [];
$action = $_POST['action'] ?? '';
$editId = (int) ($_GET['edit'] ?? 0);

// --- Actions POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    if ($action === 'create') {
        $nom    = trim($_POST['nom'] ?? '');
        $email  = trim(strtolower($_POST['email'] ?? ''));
        $pass   = $_POST['password'] ?? '';
        $role   = $_POST['role'] ?? 'operateur';
        $opId   = !empty($_POST['operateur_id']) ? (int) $_POST['operateur_id'] : null;
        $tel    = trim($_POST['telephone'] ?? '');

        if ($nom === '') $errors[] = 'Nom requis.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
        if (strlen($pass) < 6) $errors[] = 'Mot de passe ≥ 6 caractères.';
        if (!in_array($role, ['admin', 'artec', 'operateur'], true)) $errors[] = 'Rôle invalide.';
        if ($role === 'operateur' && !$opId) $errors[] = 'Opérateur requis pour un compte opérateur.';

        // Email unique ?
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'Cet email est déjà utilisé.';

        if (empty($errors)) {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = db()->prepare("INSERT INTO users (nom, email, password, role, operateur_id, telephone) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $email, $hash, $role, $role === 'operateur' ? $opId : null, $tel]);
            log_action('create_user', 'users', (int) db()->lastInsertId(), "Créé $email ($role)");
            flash('success', 'Utilisateur créé.', 'success');
            redirect(BASE_URL . 'admin/users.php');
        }
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && $id !== (int) $user['id']) {
            db()->prepare("UPDATE users SET actif = NOT actif WHERE id = ?")->execute([$id]);
            flash('success', 'Statut modifié.', 'success');
        } else {
            flash('error', 'Action impossible sur votre propre compte.', 'warning');
        }
        redirect(BASE_URL . 'admin/users.php');
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && $id !== (int) $user['id']) {
            db()->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            log_action('delete_user', 'users', $id, 'Suppression');
            flash('success', 'Utilisateur supprimé.', 'success');
        } else {
            flash('error', 'Impossible de supprimer votre compte.', 'danger');
        }
        redirect(BASE_URL . 'admin/users.php');
    } elseif ($action === 'update' && $editId > 0) {
        $nom  = trim($_POST['nom'] ?? '');
        $role = $_POST['role'] ?? 'operateur';
        $opId = !empty($_POST['operateur_id']) ? (int) $_POST['operateur_id'] : null;
        $tel  = trim($_POST['telephone'] ?? '');
        $pass = $_POST['password'] ?? '';

        if ($nom === '') $errors[] = 'Nom requis.';

        if (empty($errors)) {
            if ($pass !== '') {
                if (strlen($pass) < 6) $errors[] = 'Mot de passe ≥ 6 caractères.';
                else {
                    $hash = password_hash($pass, PASSWORD_BCRYPT);
                    db()->prepare("UPDATE users SET nom=?, role=?, operateur_id=?, telephone=?, password=? WHERE id=?")
                        ->execute([$nom, $role, $role === 'operateur' ? $opId : null, $tel, $hash, $editId]);
                }
            }
            if (empty($errors)) {
                db()->prepare("UPDATE users SET nom=?, role=?, operateur_id=?, telephone=? WHERE id=?")
                    ->execute([$nom, $role, $role === 'operateur' ? $opId : null, $tel, $editId]);
                log_action('update_user', 'users', $editId, 'Modification');
                flash('success', 'Utilisateur modifié.', 'success');
                redirect(BASE_URL . 'admin/users.php');
            }
        }
    }
}

$operateurs = db()->query("SELECT id, nom FROM operateurs WHERE actif = 1 ORDER BY nom")->fetchAll();
$users = db()->query("
    SELECT u.*, o.nom AS op_nom
    FROM users u
    LEFT JOIN operateurs o ON o.id = u.operateur_id
    ORDER BY u.created_at DESC
")->fetchAll();

$editUser = null;
if ($editId > 0) {
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch();
}

$pageTitle = 'Gestion utilisateurs';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="fw-bold mb-0"><i class="bi bi-people text-artec"></i> Utilisateurs</h1>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo "<li>" . e($e) . "</li>"; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <!-- ===== FORMULAIRE ===== -->
    <div class="col-lg-4">
        <div class="card card-artec">
            <div class="card-header">
                <i class="bi bi-<?= $editUser ? 'pencil-square' : 'plus-circle' ?>"></i>
                <?= $editUser ? 'Modifier' : 'Nouvel utilisateur' ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?= $editUser ? '?edit=' . (int) $editUser['id'] : '' ?>" autocomplete="off">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nom complet *</label>
                        <input type="text" name="nom" class="form-control" required
                               value="<?= e($editUser['nom'] ?? '') ?>">
                    </div>

                    <?php if (!$editUser): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" class="form-control" value="<?= e($editUser['email']) ?>" disabled>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Rôle *</label>
                        <select name="role" class="form-select" id="roleSelect" required>
                            <option value="operateur" <?= ($editUser['role'] ?? 'operateur') === 'operateur' ? 'selected' : '' ?>>Opérateur</option>
                            <option value="artec" <?= ($editUser['role'] ?? '') === 'artec' ? 'selected' : '' ?>>ARTEC</option>
                            <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                        </select>
                    </div>

                    <div class="mb-3" id="opSelectWrap">
                        <label class="form-label fw-bold">Opérateur associé</label>
                        <select name="operateur_id" class="form-select">
                            <option value="">—</option>
                            <?php foreach ($operateurs as $op): ?>
                                <option value="<?= (int) $op['id'] ?>" <?= (int) ($editUser['operateur_id'] ?? 0) === (int) $op['id'] ? 'selected' : '' ?>>
                                    <?= e($op['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Téléphone</label>
                        <input type="text" name="telephone" class="form-control"
                               value="<?= e($editUser['telephone'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Mot de passe <?= $editUser ? '(laisser vide pour ne pas changer)' : '*' ?>
                        </label>
                        <input type="password" name="password" class="form-control" <?= $editUser ? '' : 'required' ?>>
                    </div>

                    <div class="d-flex gap-2">
                        <?php if ($editUser): ?>
                            <a href="<?= e(BASE_URL) ?>admin/users.php" class="btn btn-secondary flex-grow-1">Annuler</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-artec flex-grow-1">
                            <i class="bi bi-check-circle"></i> <?= $editUser ? 'Enregistrer' : 'Créer' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== LISTE ===== -->
    <div class="col-lg-8">
        <div class="card card-artec">
            <div class="card-header"><i class="bi bi-list"></i> Tous les utilisateurs (<?= count($users) ?>)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-artec mb-0">
                        <thead>
                            <tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Opérateur</th><th>Statut</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><strong><?= e($u['nom']) ?></strong></td>
                                    <td><small><?= e($u['email']) ?></small></td>
                                    <td>
                                        <span class="badge bg-<?= $u['role'] === 'admin' ? 'dark' : ($u['role'] === 'artec' ? 'primary' : 'success') ?>">
                                            <?= e($u['role']) ?>
                                        </span>
                                    </td>
                                    <td><?= e($u['op_nom'] ?? '—') ?></td>
                                    <td>
                                        <?= $u['actif'] ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-danger">Inactif</span>' ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="?edit=<?= (int) $u['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="post" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button class="btn btn-sm btn-outline-warning" title="Activer/Désactiver">
                                                <i class="bi bi-power"></i>
                                            </button>
                                        </form>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Affiche/masque le select opérateur selon le rôle
const roleSelect = document.getElementById('roleSelect');
const opWrap = document.getElementById('opSelectWrap');
function toggleOp() {
    opWrap.style.display = (roleSelect.value === 'operateur') ? 'block' : 'none';
}
roleSelect.addEventListener('change', toggleOp);
toggleOp();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>