<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

if (is_logged_in()) redirect(BASE_URL . 'visitor/index.php');

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Session expiree.';
    } else {
        $email = trim(strtolower($_POST['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalide.';
        } else {
            $stmt = db()->prepare("SELECT id, nom FROM users WHERE email = ? AND actif = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            $message = 'Si un compte existe avec cet email, vous recevrez un lien de reinitialisation.';

            if ($user) {
                $stmt = db()->prepare("SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at > (NOW() - INTERVAL 1 HOUR)");
                $stmt->execute([$user['id']]);
                if ((int) $stmt->fetchColumn() < 3) {
                    $token = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

                    db()->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0")
                        ->execute([$user['id']]);

                    $stmt = db()->prepare("INSERT INTO password_resets (user_id, token, expires_at, ip_hash) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user['id'], $token, $expiresAt, hashIp()]);

                    $link = BASE_URL . 'operator/reset_password.php?token=' . urlencode($token);

                    $body = "
                        <p>Bonjour " . e($user['nom']) . ",</p>
                        <p>Vous avez demande a reinitialiser votre mot de passe sur la plateforme ARTEC Madagascar.</p>
                        <p>Cliquez sur le lien ci-dessous (valable 1 heure) :</p>
                        <p><a href=\"$link\">$link</a></p>
                        <p>Si vous n'etes pas a l'origine de cette demande, ignorez cet email.</p>
                        <p>Cordialement,<br>L'equipe ARTEC Madagascar</p>
                    ";
                    send_mail($email, 'Reinitialisation de mot de passe - ARTEC', $body);
                    log_action('forgot_password', 'users', (int) $user['id'], "Demande pour $email");
                }
            }
        }
    }
}

$pageTitle = 'Mot de passe oublie';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center align-items-center py-4" style="min-height: 70vh;">
    <div class="col-md-6 col-lg-5 col-xl-4">
        <div class="card card-artec fade-in">
            <div class="card-header text-center py-4">
                <div class="mb-2" style="font-size: 2.5rem;"><i class="bi bi-key"></i></div>
                <h4 class="mb-0">Mot de passe oublie</h4>
            </div>
            <div class="card-body p-4">

                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <?= e($message) ?>
                    </div>
                    <?php if (APP_ENV === 'local'): ?>
                        <div class="alert alert-info small">
                            <strong>Mode local (WAMP)</strong><br>
                            Aucun email n'est reellement envoye.<br>
                            Le lien est ecrit dans <code>logs/mails.log</code>.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if (!$message): ?>
                    <p class="text-muted small mb-3">
                        Entrez votre adresse email. Vous recevrez un lien pour definir un nouveau mot de passe.
                    </p>

                    <form method="post" autocomplete="off">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control" required autofocus
                                       value="<?= e($_POST['email'] ?? '') ?>"
                                       placeholder="vous@operateur.mg">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-artec w-100">
                            <i class="bi bi-send"></i> Envoyer le lien
                        </button>
                    </form>
                <?php endif; ?>

                <hr class="my-4">

                <div class="text-center small">
                    <a href="<?= e(BASE_URL) ?>operator/login.php">
                        <i class="bi bi-arrow-left"></i> Retour a la connexion
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>