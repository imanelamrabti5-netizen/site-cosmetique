<?php
// ============================================================
//  includes/footer.php
//  À inclure en bas de chaque page, après le contenu.
//  Ferme le <main> ouvert par header.php.
// ============================================================
?>
</main><!-- /main -->

<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
        <div class="row gy-4">

            <!-- Colonne 1 : Identité -->
            <div class="col-md-4">
                <h5 class="fw-bold mb-3"><?= h(SITE_NOM) ?></h5>
                <p class="text-secondary small mb-0"><?= h(SITE_SLOGAN) ?></p>
                <p class="text-secondary small mt-2">
                    <i class="bi bi-envelope me-1"></i>
                    <a href="mailto:<?= h(SITE_EMAIL) ?>" class="text-secondary text-decoration-none">
                        <?= h(SITE_EMAIL) ?>
                    </a>
                </p>
            </div>

            <!-- Colonne 2 : Navigation rapide -->
            <div class="col-md-4">
                <h6 class="text-uppercase fw-semibold mb-3 small tracking-wide">Navigation</h6>
                <ul class="list-unstyled small">
                    <li class="mb-1">
                        <a href="<?= URL_BASE ?>/index.php" class="text-secondary text-decoration-none">
                            Accueil
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?= URL_BASE ?>/produits/liste.php" class="text-secondary text-decoration-none">
                            Tous les produits
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?= URL_BASE ?>/produits/recherche.php" class="text-secondary text-decoration-none">
                            Recherche
                        </a>
                    </li>
                    <?php if (!estConnecte()): ?>
                    <li class="mb-1">
                        <a href="<?= URL_BASE ?>/auth/inscription.php" class="text-secondary text-decoration-none">
                            Créer un compte
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Colonne 3 : Mon compte -->
            <div class="col-md-4">
                <h6 class="text-uppercase fw-semibold mb-3 small">Mon compte</h6>
                <ul class="list-unstyled small">
                    <?php if (estConnecte() && !estAdmin()): ?>
                    <li class="mb-1">
                        <a href="<?= URL_BASE ?>/client/profil.php" class="text-secondary text-decoration-none">
                            Mon profil
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?= URL_BASE ?>/commandes/historique.php" class="text-secondary text-decoration-none">
                            Mes commandes
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?= URL_BASE ?>/favoris/favoris.php" class="text-secondary text-decoration-none">
                            Mes favoris
                        </a>
                    </li>
                    <?php elseif (estAdmin()): ?>
                    <li class="mb-1">
                        <a href="<?= URL_BASE ?>/admin/dashboard.php" class="text-secondary text-decoration-none">
                            Tableau de bord admin
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="mb-1">
                        <a href="<?= URL_BASE ?>/auth/connexion.php" class="text-secondary text-decoration-none">
                            Se connecter
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="<?= URL_BASE ?>/auth/inscription.php" class="text-secondary text-decoration-none">
                            S'inscrire
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

        </div><!-- /.row -->

        <hr class="border-secondary my-4">

        <div class="row align-items-center">
            <div class="col-md-6 small text-secondary">
                &copy; <?= date('Y') ?> <?= h(SITE_NOM) ?>. Tous droits réservés.
            </div>
            <div class="col-md-6 text-md-end small text-secondary">
                Paiement sécurisé
                <i class="bi bi-shield-lock ms-1"></i>
            </div>
        </div>

    </div><!-- /.container -->
</footer>

<!-- Bootstrap 5 JS (bundle avec Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmMBjWDygkk9S8qFgMbhZ57gjjmI"
        crossorigin="anonymous"></script>

<!-- JS personnalisé -->
<script src="<?= URL_JS ?>/main.js"></script>

</body>
</html>
