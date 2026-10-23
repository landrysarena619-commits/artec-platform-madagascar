<?php
require_once __DIR__ . '/../includes/login_helper.php';

if (is_logged_in() && has_role('admin')) {
    redirect(BASE_URL . 'admin/dashboard.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée.';
    } else {
        $res = attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '', 'admin');
        if ($res['success']) redirect(BASE_URL . 'admin/dashboard.php');
        $error = $res['message'];
    }
}

$pageTitle = 'Connexion Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="col-md-5 col-lg-4">
        <div class="card card-artec">
            <div class="card-header text-center py-3 bg-dark">
                <i class="bi bi-gear-fill fs-2"></i>
                <h4 class="mb-0 mt-2">Administration</h4>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" required autofocus
                               value="<?= e($_POST['email'] ?? '') ?>" placeholder="admin@artec.mg">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mot de passe</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-dark w-100">
                        <i class="bi bi-shield-lock"></i> Se connecter
                    </button>
                </form>
                <hr class="my-4">
                <div class="small text-muted">
                    <strong>Compte de démo :</strong><br>
                    admin@artec.mg / Admin@2025
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>