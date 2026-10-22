</main>

<footer class="artec-footer mt-5">
    <div class="container py-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <h5 class="fw-bold">ARTEC Madagascar</h5>
                <p class="small text-muted mb-0">Autorité de Régulation des Technologies de Communication.<br>Plateforme officielle de régulation des télécommunications.</p>
            </div>
            <div class="col-md-4 mb-3">
                <h6 class="fw-bold">Liens rapides</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= e(BASE_URL) ?>visitor/index.php">Publications</a></li>
                    <li><a href="<?= e(BASE_URL) ?>visitor/statistiques.php">Statistiques</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-3">
                <h6 class="fw-bold">Contact</h6>
                <p class="small mb-0">
                    <i class="bi bi-envelope"></i> contact@artec.mg<br>
                    <i class="bi bi-telephone"></i> +261 20 22 000 00<br>
                    <i class="bi bi-geo-alt"></i> Antananarivo, Madagascar
                </p>
            </div>
        </div>
        <hr>
        <p class="text-center small mb-0">&copy; <?= date('Y') ?> ARTEC Madagascar — Tous droits réservés.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= e(ASSETS_URL) ?>js/main.js"></script>
</body>
</html>