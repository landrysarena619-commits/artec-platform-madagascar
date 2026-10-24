<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) redirect(BASE_URL . 'visitor/index.php');

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$success = false;
$user = null;

if ($token !== '') {
    $stmt = db()->prepare("
        SELECT pr.*, u.nom, u.email
        FROM password_resets pr
        INNER JOIN users u ON u.id = pr.user_id
        WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW() AND u.actif = 1
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if ($reset) {
        $user = ['id' => (int) $reset['user_id'], 'nom' => $reset['nom'], 'email' => $reset['email']];
    } else {
        $errors[] = 'Ce lien est invalide ou a expire. Demandez un nouveau lien.';
    }
} else {
    $errors[] = 'Lien invalide.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expiree.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if (strlen($password) < 8) $errors[] = 'Le mot de passe doit faire au moins 8 caracteres.';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Une majuscule est requise.';
        if (!preg_match('/[a-z]/', $password)) $errors[] = 'Une minuscule est requise.';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'Un chiffre est requis.';
        if ($password !== $confirm) $errors[] = 'Les mots de passe ne correspondent pas.';

        if (empty($errors)) {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                db()->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user['id']]);
                db()->prepare("UPDATE password_resets SET used = 1 WHERE token = ?")->execute([$token]);
                db()->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0")->execute([$user['id']]);
                log_action('reset_password', 'users', $user['id'], 'Mot de passe reinitialise');
                $success = true;
            } catch (Throwable $ex) {
                error_log('reset error: ' . $ex->getMessage());
                $errors[] = 'Erreur technique. Reessayez.';
            }
        }
    }
}

$pageTitle = 'Reinitialisation';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center align-items-center py-4" style="min-height: 70vh;">
    <div class="col-md-6 col-lg-5 col-xl-4">
        <div class="card card-artec fade-in">
            <div class="card-header text-center py-4">
                <div class="mb-2" style="font-size: 2.5rem;"><i class="bi bi-shield-lock"></i></div>
                <h4 class="mb-0">Nouveau mot de passe</h4>
            </div>
            <div class="card-body p-4">

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>
                            <strong>Mot de passe modifie.</strong><br>
                            Vous pouvez maintenant vous connecter.
                        </div>
                    </div>
                    <a href="<?= e(BASE_URL) ?>operator/login.php" class="btn btn-artec w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Se connecter
                    </a>

                <?php elseif (!$user): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $err): ?>
                            <div><?= e($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?= e(BASE_URL) ?>operator/forgot_password.php" class="btn btn-artec w-100">
                        Demander un nouveau lien
                    </a>

                <?php else: ?>
                    <p class="text-muted small mb-3">
                        Bonjour <strong><?= e($user['nom']) ?></strong>, choisissez un nouveau mot de passe.
                    </p>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" autocomplete="off">
                        <?= csrf_field() ?>
                        <input type="hidden" name="token" value="<?= e($token) ?>">

                        <div class="mb-3">
                            <label class="form-label">Nouveau mot de passe *</label>
                            <input type="password" name="password" id="password" class="form-control" required minlength="8" autofocus>
                            <div class="form-text">Min. 8 caracteres, dont 1 majuscule, 1 minuscule et 1 chiffre.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirmer *</label>
                            <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
                            <div class="form-text text-danger d-none" id="matchError">Ne correspondent pas.</div>
                        </div>

                        <button type="submit" class="btn btn-artec w-100">
                            <i class="bi bi-check-circle"></i> Enregistrer
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const p1 = document.getElementById('password');
const p2 = document.getElementById('password_confirm');
const err = document.getElementById('matchError');
if (p1 && p2 && err) {
    const check = () => {
        if (p2.value === '') { err.classList.add('d-none'); return; }
        err.classList.toggle('d-none', p1.value === p2.value);
    };
    p1.addEventListener('input', check);
    p2.addEventListener('input', check);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>