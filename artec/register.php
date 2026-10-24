<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) redirect(BASE_URL . 'visitor/index.php');

$errors = [];
$success = false;

$hasArtec = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'artec'")->fetchColumn();
$isFirst = $hasArtec === 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expiree. Rechargez la page.';
    } else {
        $nom      = trim($_POST['nom'] ?? '');
        $email    = trim(strtolower($_POST['email'] ?? ''));
        $telephone= trim($_POST['telephone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if (mb_strlen($nom) < 3) $errors[] = 'Le nom doit faire au moins 3 caracteres.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
        if (strlen($password) < 8) $errors[] = 'Le mot de passe doit faire au moins 8 caracteres.';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Le mot de passe doit contenir une majuscule.';
        if (!preg_match('/[a-z]/', $password)) $errors[] = 'Le mot de passe doit contenir une minuscule.';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'Le mot de passe doit contenir un chiffre.';
        if ($password !== $confirm) $errors[] = 'Les mots de passe ne correspondent pas.';

        if (empty($errors)) {
            $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) $errors[] = 'Cet email est deja utilise.';
        }

        if (empty($errors)) {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $actif = $isFirst ? 1 : 0;
                $stmt = db()->prepare("
                    INSERT INTO users (nom, email, password, role, operateur_id, telephone, actif)
                    VALUES (?, ?, ?, 'artec', NULL, ?, ?)
                ");
                $stmt->execute([$nom, $email, $hash, $telephone ?: null, $actif]);
                log_action('register', 'users', (int) db()->lastInsertId(), "Nouveau compte ARTEC : $email");
                $success = true;
            } catch (Throwable $ex) {
                error_log('register artec error: ' . $ex->getMessage());
                $errors[] = 'Erreur technique. Reessayez.';
            }
        }
    }
}

$pageTitle = 'Creer un compte ARTEC';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center py-4">
    <div class="col-md-7 col-lg-6">
        <div class="card card-artec fade-in">
            <div class="card-header text-center py-4">
                <div class="mb-2" style="font-size: 2.5rem;"><i class="bi bi-shield-check"></i></div>
                <h4 class="mb-0">Creer un compte ARTEC</h4>
                <p class="mb-0 small opacity-75"><?= $isFirst ? 'Premier compte : acces immediat' : 'Validation par un administrateur requise' ?></p>
            </div>
            <div class="card-body p-4">

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>
                            <strong>Compte cree.</strong><br>
                            <?php if ($isFirst): ?>
                                Vous pouvez maintenant vous connecter.
                            <?php else: ?>
                                Votre compte doit etre valide par un administrateur avant activation.
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="<?= e(BASE_URL) ?>artec/login.php" class="btn btn-artec w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Aller a la connexion
                    </a>

                <?php else: ?>

                    <?php if (!$isFirst): ?>
                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle"></i>
                            Un compte ARTEC existe deja. Votre nouveau compte devra etre valide par un administrateur.
                        </div>
                    <?php endif; ?>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" autocomplete="off">
                        <?= csrf_field() ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nom complet *</label>
                                <input type="text" name="nom" class="form-control" required minlength="3"
                                       value="<?= e($_POST['nom'] ?? '') ?>"
                                       placeholder="Rakoto Andry">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?= e($_POST['email'] ?? '') ?>"
                                       placeholder="vous@artec.mg">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Telephone</label>
                            <input type="text" name="telephone" class="form-control"
                                   value="<?= e($_POST['telephone'] ?? '') ?>"
                                   placeholder="+261 34 00 000 00">
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Mot de passe *</label>
                            <input type="password" name="password" id="password" class="form-control" required minlength="8">
                            <div class="form-text">Min. 8 caracteres, dont 1 majuscule, 1 minuscule et 1 chiffre.</div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Confirmer le mot de passe *</label>
                            <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
                            <div class="form-text text-danger d-none" id="matchError">Les mots de passe ne correspondent pas.</div>
                        </div>

                        <button type="submit" class="btn btn-artec w-100 mt-4">
                            <i class="bi bi-person-check"></i> Creer mon compte
                        </button>
                    </form>

                    <hr class="my-4">

                    <div class="text-center small">
                        Vous avez deja un compte ?
                        <a href="<?= e(BASE_URL) ?>artec/login.php" class="fw-bold">Se connecter</a>
                    </div>
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