</main>

<footer class="artec-footer">
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-md-4">
                <h5>ARTEC Madagascar</h5>
                <p class="small mb-0">
                    Autorite de Regulation des Technologies de Communication.<br>
                    Plateforme officielle de regulation des telecommunications a Madagascar.
                </p>
            </div>
            <div class="col-md-4">
                <h6>Navigation</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-1"><a href="<?= e(BASE_URL) ?>visitor/index.php">Accueil</a></li>
                    <li class="mb-1"><a href="<?= e(BASE_URL) ?>visitor/index.php#publications">Publications</a></li>
                    <li class="mb-1"><a href="<?= e(BASE_URL) ?>visitor/statistiques.php">Statistiques</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>Espaces professionnels</h6>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-1"><a href="<?= e(BASE_URL) ?>operator/login.php">Espace Operateur</a></li>
                    <li class="mb-1"><a href="<?= e(BASE_URL) ?>artec/login.php">Espace ARTEC</a></li>
                    <li class="mb-1"><a href="<?= e(BASE_URL) ?>admin/login.php">Espace Administrateur</a></li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6 small mb-2 mb-md-0">
                <i class="bi bi-envelope"></i> contact@artec.mg
                <span class="mx-2">|</span>
                <i class="bi bi-telephone"></i> +261 20 22 000 00
                <span class="mx-2">|</span>
                <i class="bi bi-geo-alt"></i> Antananarivo, Madagascar
            </div>
            <div class="col-md-6 text-md-end small">
                &copy; <?= date('Y') ?> ARTEC Madagascar. Tous droits reserves.
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= e(ASSETS_URL) ?>js/main.js"></script>
</body>
</html>