<?php
require_once __DIR__ . '/../includes/login_helper.php';

if (is_logged_in() && has_role('admin')) {
    redirect(BASE_URL . 'admin/dashboard.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Session expiree.';
    } else {
        $res = attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '', 'admin');
        if ($res['success']) redirect(BASE_URL . 'admin/dashboard.php');
        $error = $res['message'];
    }
}

$hasAdmin = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND actif = 1")->fetchColumn();

$pageTitle = 'Connexion Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center align-items-center py-4" style="min-height: 75vh;">
    <div class="col-md-6 col-lg-5 col-xl-4">
        <div class="card card-artec fade-in">
            <div class="card-header text-center py-4" style="background: linear-gradient(135deg, #0f172a 0%, #334155 100%);">
                <div class="mb-2" style="font-size: 2.5rem;"><i class="bi bi-gear"></i></div>
                <h4 class="mb-0">Administration</h4>
                <p class="mb-0 small opacity-75">Acces technique restreint</p>
            </div>
            <div class="card-body p-4">

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$hasAdmin): ?>
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle"></i>
                        Aucun administrateur n'existe encore.
                        <a href="<?= e(BASE_URL) ?>admin/register.php" class="alert-link">Creez le premier compte</a>.
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" required autofocus
                                   value="<?= e($_POST['email'] ?? '') ?>"
                                   placeholder="admin@artec.mg">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" required placeholder="........">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 mb-3">
                        <i class="bi bi-shield-lock"></i> Se connecter
                    </button>
                </form>

                <div class="d-flex justify-content-between small mt-3">
                    <a href="<?= e(BASE_URL) ?>admin/register.php" class="fw-bold">
                        <i class="bi bi-person-plus"></i> Creer un compte
                    </a>
                    <a href="<?= e(BASE_URL) ?>operator/forgot_password.php">
                        <i class="bi bi-key"></i> Mot de passe oublie ?
                    </a>
                </div>
            </div>
        </div>

        <p class="text-center text-muted small mt-3">
            <a href="<?= e(BASE_URL) ?>visitor/index.php">
                <i class="bi bi-arrow-left"></i> Retour au site
            </a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>