<?php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/functions.php';
}

// 1. Chargement du ML
require_once __DIR__ . '/auto_ml.php';
ml_auto_run();

// 2. Chargement des alertes (le fichier ne doit contenir QUE la fonction)
require_once __DIR__ . '/alertes_functions.php';
if (is_logged_in()) {
    verifier_et_alerter();
}

$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="<?= e(ASSETS_URL) ?>css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg artec-navbar">
    <div class="container-fluid px-3">

        <a class="navbar-brand" href="<?= e(BASE_URL) ?>visitor/index.php">
            <span class="brand-logo">ARTEC</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">

            <ul class="navbar-nav me-auto">
                <?php if ($user && $user['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>admin/dashboard.php">
                            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>admin/users.php">
                            <i class="bi bi-people"></i> <span>Utilisateurs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>admin/logs.php">
                            <i class="bi bi-list-check"></i> <span>Logs</span>
                        </a>
                    </li>
                <?php elseif ($user && $user['role'] === 'artec'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>artec/dashboard.php">
                            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>artec/operateurs.php">
                            <i class="bi bi-broadcast"></i> <span>Operateurs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>artec/avis_moderation.php">
                            <i class="bi bi-flag"></i> <span>Moderation</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>artec/decisions.php">
                            <i class="bi bi-shield-exclamation"></i> <span>Decisions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>artec/predictions.php">
                            <i class="bi bi-cpu"></i> <span>Predictions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>artec/alertes.php">
                            <i class="bi bi-bell"></i> <span>Alertes</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>artec/social.php">
                            <i class="bi bi-share"></i> <span>Social</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>artec/rapports.php">
                            <i class="bi bi-file-earmark-bar-graph"></i> <span>Rapports</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>artec/messages.php">
                            <i class="bi bi-chat-dots"></i> <span>Messages</span>
                        </a>
                    </li>
                <?php elseif ($user && $user['role'] === 'operateur'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>operator/dashboard.php">
                            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>operator/publications.php">
                            <i class="bi bi-megaphone"></i> <span>Publications</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>operator/avis.php">
                            <i class="bi bi-star"></i> <span>Avis</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>operator/statistiques.php">
                            <i class="bi bi-graph-up"></i> <span>Statistiques</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>operator/reclamations.php">
                            <i class="bi bi-exclamation-circle"></i> <span>Reclamations</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>operator/decisions.php">
                            <i class="bi bi-shield-exclamation"></i> <span>Decisions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>operator/messages.php">
                            <i class="bi bi-chat-dots"></i> <span>Messages</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>visitor/index.php">
                            <i class="bi bi-house-door"></i> <span>Accueil</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>visitor/index.php#publications">
                            <i class="bi bi-grid"></i> <span>Publications</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>visitor/statistiques.php">
                            <i class="bi bi-bar-chart-line"></i> <span>Statistiques</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php if ($user): ?>
                    <?php if ($user['role'] === 'operateur' && $user['operateur_slug']): ?>
                        <li class="nav-item me-1">
                            <span class="badge-operateur badge-<?= e($user['operateur_slug']) ?> badge-sm">
                                <?= e($user['operateur_nom']) ?>
                            </span>
                        </li>
                    <?php elseif ($user['role'] === 'artec'): ?>
                        <li class="nav-item me-1">
                            <span class="badge bg-warning text-dark badge-sm">ARTEC</span>
                        </li>
                    <?php elseif ($user['role'] === 'admin'): ?>
                        <li class="nav-item me-1">
                            <span class="badge bg-dark badge-sm">Admin</span>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item me-1">
                        <span class="nav-link user-name" style="cursor: default;">
                            <i class="bi bi-person-circle"></i>
                            <span class="user-name-text"><?= e($user['nom']) ?></span>
                        </span>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link nav-link-logout" href="<?= e(BASE_URL) ?><?= e($user['role'] === 'admin' ? 'admin' : ($user['role'] === 'artec' ? 'artec' : 'operator')) ?>/logout.php" title="Deconnexion">
                            <i class="bi bi-box-arrow-right"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person"></i> Connexion
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>operator/login.php"><i class="bi bi-briefcase"></i> Espace Operateur</a></li>
                            <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>artec/login.php"><i class="bi bi-shield-check"></i> Espace ARTEC</a></li>
                            <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>admin/login.php"><i class="bi bi-gear"></i> Espace Admin</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?= render_flash() ?>