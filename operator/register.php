<?php
/**
 * Création de compte opérateur.
 * Seuls les comptes opérateur peuvent être créés ici.
 * Les comptes ARTEC et admin sont créés par l'administrateur.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect(BASE_URL . 'visitor/index.php');
}

$errors = [];
$success = false;

// Liste des opérateurs disponibles
$operateurs = db()->query("SELECT id, nom FROM operateurs WHERE actif = 1 ORDER BY nom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expirée. Rechargez la page.';
    } else {
        $nom         = trim($_POST['nom'] ?? '');
        $email       = trim(strtolower($_POST['email'] ?? ''));
        $telephone   = trim($_POST['telephone'] ?? '');
        $operateurId = (int) ($_POST['operateur_id'] ?? 0);
        $password    = $_POST['password'] ?? '';
        $confirm     = $_POST['password_confirm'] ?? '';

        // Validations
        if ($nom === '') $errors[] = 'Le nom est obligatoire.';
        if (mb_strlen($nom) < 3) $errors[] = 'Le nom doit faire au moins 3 caractères.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
        if ($operateurId <= 0) $errors[] = 'Veuillez choisir votre opérateur.';
        if (strlen($password) < 8) $errors[] = 'Le mot de passe doit faire au moins 8 caractères.';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Le mot de passe doit contenir une majuscule.';
        if (!preg_match('/[a-z]/', $password)) $errors[] = 'Le mot de passe doit contenir une minuscule.';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'Le mot de passe doit contenir un chiffre.';
        if ($password !== $confirm) $errors[] = 'Les mots de passe ne correspondent pas.';

        // Email unique ?
        if (empty($errors)) {
            $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Cet email est déjà utilisé.';
            }
        }

        // Vérifier que l'opérateur existe
        if (empty($errors)) {
            $stmt = db()->prepare("SELECT id FROM operateurs WHERE id = ? AND actif = 1");
            $stmt->execute([$operateurId]);
            if (!$stmt->fetch()) {
                $errors[] = 'Opérateur invalide.';
            }
        }

        // Création
        if (empty($errors)) {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = db()->prepare("
                    INSERT INTO users (nom, email, password, role, operateur_id, telephone, actif)
                    VALUES (?, ?, ?, 'operateur', ?, ?, 1)
                ");
                $stmt->execute([$nom, $email, $hash, $operateurId, $telephone ?: null]);

                log_action('register', 'users', (int) db()->lastInsertId(), "Nouveau compte opérateur : $email");

                $success = true;
            } catch (Throwable $e) {
                error_log('register error: ' . $e->getMessage());
                $errors[] = 'Erreur technique. Réessayez.';
            }
        }
    }
}

$pageTitle = 'Créer un compte';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center py-4">
    <div class="col-md-6 col-lg-5">
        <div class="card card-artec">
            <div class="card-header text-center py-3">
                <i class="bi bi-person-plus fs-2"></i>
                <h4 class="mb-0 mt-2">Créer un compte opérateur</h4>
            </div>
            <div class="card-body p-4">

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <strong>Compte créé avec succès !</strong><br>
                        Vous pouvez maintenant vous connecter.
                    </div>
                    <a href="<?= e(BASE_URL) ?>operator/login.php" class="btn btn-artec w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Aller à la connexion
                    </a>
                <?php else: ?>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle"></i>
                        Ce formulaire crée un compte <strong>opérateur</strong>.
                        Les comptes ARTEC et administrateur sont créés par l'administrateur de la plateforme.
                    </p>

                    <form method="post" autocomplete="off">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom complet *</label>
                            <input type="text" name="nom" class="form-control" required minlength="3"
                                   value="<?= e($_POST['nom'] ?? '') ?>"
                                   placeholder="Ex: Rakoto Andry">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Email professionnel *</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?= e($_POST['email'] ?? '') ?>"
                                   placeholder="vous@operateur.mg">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Téléphone</label>
                            <input type="text" name="telephone" class="form-control"
                                   value="<?= e($_POST['telephone'] ?? '') ?>"
                                   placeholder="+261 34 00 000 00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Opérateur *</label>
                            <select name="operateur_id" class="form-select" required>
                                <option value="">— Choisir votre opérateur —</option>
                                <?php foreach ($operateurs as $op): ?>
                                    <option value="<?= (int) $op['id'] ?>"
                                        <?= (int) ($_POST['operateur_id'] ?? 0) === (int) $op['id'] ? 'selected' : '' ?>>
                                        <?= e($op['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Mot de passe *</label>
                            <input type="password" name="password" id="password" class="form-control" required minlength="8">
                            <div class="form-text">
                                Min. 8 caractères, dont 1 majuscule, 1 minuscule et 1 chiffre.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Confirmer le mot de passe *</label>
                            <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
                            <div class="form-text text-danger d-none" id="matchError">
                                Les mots de passe ne correspondent pas.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-artec w-100">
                            <i class="bi bi-person-check"></i> Créer mon compte
                        </button>
                    </form>

                    <hr class="my-4">

                    <div class="text-center small">
                        Vous avez déjà un compte ?
                        <a href="<?= e(BASE_URL) ?>operator/login.php" class="fw-bold">Se connecter</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Vérification live de la correspondance des mots de passe
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