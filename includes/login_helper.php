<?php
/**
 * Helper de connexion réutilisable par les 3 espaces (operator, artec, admin).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Tente de connecter un utilisateur avec email + password + rôle attendu.
 * Retourne un tableau ['success' => bool, 'message' => string].
 */
function attempt_login(string $email, string $password, string $expectedRole): array {
    $email = trim(strtolower($email));

    if ($email === '' || $password === '') {
        return ['success' => false, 'message' => 'Email et mot de passe requis.'];
    }

    $stmt = db()->prepare("
        SELECT u.*, o.nom AS operateur_nom, o.slug AS operateur_slug
        FROM users u
        LEFT JOIN operateurs o ON o.id = u.operateur_id
        WHERE u.email = ? AND u.actif = 1
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
    }

    if ($user['role'] !== $expectedRole) {
        return ['success' => false, 'message' => 'Vous n\'avez pas accès à cet espace.'];
    }

    // --- Régénérer la session (anti-fixation) ---
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'            => (int) $user['id'],
        'nom'           => $user['nom'],
        'email'         => $user['email'],
        'role'          => $user['role'],
        'operateur_id'  => $user['operateur_id'] ? (int) $user['operateur_id'] : null,
        'operateur_nom' => $user['operateur_nom'] ?? null,
        'operateur_slug'=> $user['operateur_slug'] ?? null,
    ];
    $_SESSION['last_activity'] = time();

    // --- Mettre à jour la BDD ---
    db()->prepare("UPDATE users SET derniere_connexion = NOW(), ip_derniere_connexion = ? WHERE id = ?")
        ->execute([$_SERVER['REMOTE_ADDR'] ?? null, $user['id']]);

    log_action('login', 'users', (int) $user['id'], "Connexion {$expectedRole} réussie");

    return ['success' => true, 'message' => 'Connexion réussie.'];
}