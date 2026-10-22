<?php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/functions.php';
}
$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>
    <meta name="description" content="Plateforme de régulation des télécommunications à Madagascar - ARTEC">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(ASSETS_URL) ?>css/style.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= e(ASSETS_URL) ?>img/artec.png">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark artec-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= e(BASE_URL) ?>visitor/index.php">
            <span class="brand-logo">ARTEC</span>
            <span class="d-none d-md-inline">Madagascar</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(BASE_URL) ?>visitor/index.php"><i class="bi bi-house-door"></i> Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(BASE_URL) ?>visitor/statistiques.php"><i class="bi bi-bar-chart"></i> Statistiques</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if ($user): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?= e($user['nom']) ?>
                            <span class="badge bg-warning text-dark ms-1 text-uppercase"><?= e($user['role']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($user['role'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>admin/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard Admin</a></li>
                            <?php elseif ($user['role'] === 'artec'): ?>
                                <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>artec/dashboard.php"><i class="bi bi-shield-check"></i> Dashboard ARTEC</a></li>
                            <?php elseif ($user['role'] === 'operateur'): ?>
                                <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>operator/dashboard.php"><i class="bi bi-briefcase"></i> Dashboard Opérateur</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= e(BASE_URL) ?><?= e($user['role'] === 'admin' ? 'admin' : ($user['role'] === 'artec' ? 'artec' : 'operator')) ?>/logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-box-arrow-in-right"></i> Se connecter</a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>operator/login.php">Espace Opérateur</a></li>
                            <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>artec/login.php">Espace ARTEC</a></li>
                            <li><a class="dropdown-item" href="<?= e(BASE_URL) ?>admin/login.php">Espace Admin</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?= render_flash() ?>