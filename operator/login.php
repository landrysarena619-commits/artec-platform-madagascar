<?php
require_once __DIR__ . '/../includes/login_helper.php';

if (is_logged_in() && has_role('operateur')) {
    redirect(BASE_URL . 'operator/dashboard.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Session expirée. Rechargez la page.';
    } else {
        $res = attempt_login($_POST['email'] ?? '', $_POST['password'] ?? '', 'operateur');
        if ($res['success']) {
            redirect(BASE_URL . 'operator/dashboard.php');
        }
        $error = $res['message'];
    }
}

$pageTitle = 'Connexion Opérateur';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="col-md-5 col-lg-4">
        <div class="card card-artec">
            <div class="card-header text-center py-3">
                <i class="bi bi-briefcase fs-2"></i>
                <h4 class="mb-0 mt-2">Espace Opérateur</h4>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" required autofocus
                                   value="<?= e($_POST['email'] ?? '') ?>"
                                   placeholder="orange@orange.mg">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" required
                                   placeholder="••••••••">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-artec w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Se connecter
                    </button>
                </form>

                <div class="d-flex justify-content-between mt-3 small">
                    <a href="<?= e(BASE_URL) ?>operator/register.php">
                        <i class="bi bi-person-plus"></i> Créer un compte
                    </a>
                    <a href="<?= e(BASE_URL) ?>operator/forgot_password.php">
                        <i class="bi bi-key"></i> Mot de passe oublié ?
                    </a>
                </div>

                <hr class="my-4">
                <div class="small text-muted">
                    <strong>Comptes de démo :</strong><br>
                    orange@orange.mg / Orange@2025<br>
                    yas@yas.mg / Yas@2025<br>
                    airtel@airtel.mg / Airtel@2025
                </div>
            </div>
        </div>
        <p class="text-center text-muted small mt-3">
            <a href="<?= e(BASE_URL) ?>visitor/index.php"><i class="bi bi-arrow-left"></i> Retour au site</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>