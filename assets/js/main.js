// ============================================================
// ARTEC Madagascar — Scripts généraux
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
    // Auto-fermeture des alertes après 5s
    document.querySelectorAll('.alert-dismissible').forEach(el => {
        setTimeout(() => {
            const alert = bootstrap.Alert.getOrCreateInstance(el);
            alert.close();
        }, 5000);
    });

    // Active le lien de nav correspondant à l'URL
    const path = window.location.pathname;
    document.querySelectorAll('.artec-navbar .nav-link').forEach(link => {
        if (link.getAttribute('href') && path.endsWith(link.getAttribute('href').split('/').pop())) {
            link.classList.add('active');
        }
    });
});