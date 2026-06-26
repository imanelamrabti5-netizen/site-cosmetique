// main.js — Site Maison Lumière
document.addEventListener('DOMContentLoaded', function () {
    // Auto-fermeture des alertes flash après 4 secondes
    const alertes = document.querySelectorAll('.alert.alert-dismissible');
    alertes.forEach(function (alerte) {
        setTimeout(function () {
            const btnClose = alerte.querySelector('.btn-close');
            if (btnClose) btnClose.click();
        }, 4000);
    });
});
