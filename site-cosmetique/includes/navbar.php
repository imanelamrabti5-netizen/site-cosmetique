<?php
// ============================================================
//  includes/navbar.php
//  Navigation principale — 3 états :
//    • Visiteur non connecté
//    • Client connecté
//    • Administrateur connecté
// ============================================================

$nbPanier  = nombreArticlesPanier();
$nbFavoris = nombreFavoris();
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
    <div class="container">

        <!-- Logo / Nom du site -->
        <a class="navbar-brand fw-bold" href="<?= URL_BASE ?>/index.php">
            <span class="text-dark"><?= h(SITE_NOM) ?></span>
        </a>

        <!-- Bouton burger (mobile) -->
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false"
                aria-label="Ouvrir le menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">

            <!-- Liens principaux (toujours visibles) -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?= URL_BASE ?>/produits/liste.php">
                        Produits
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= URL_BASE ?>/produits/recherche.php">
                        <i class="bi bi-search"></i> Recherche
                    </a>
                </li>
            </ul>

            <!-- Barre de recherche rapide -->
            <form class="d-flex me-3" method="GET"
                  action="<?= URL_BASE ?>/produits/recherche.php">
                <input class="form-control form-control-sm me-2"
                       type="search" name="q"
                       placeholder="Rechercher un produit…"
                       aria-label="Rechercher"
                       value="<?= h($_GET['q'] ?? '') ?>">
                <button class="btn btn-outline-secondary btn-sm" type="submit">OK</button>
            </form>

            <!-- Zone droite : panier, favoris, compte -->
            <ul class="navbar-nav align-items-center gap-1">

                <?php if (estConnecte() && !estAdmin()): ?>
                    <!-- CLIENT CONNECTÉ -->

                    <!-- Favoris -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?= URL_BASE ?>/favoris/favoris.php"
                           title="Mes favoris">
                            <i class="bi bi-heart fs-5"></i>
                            <?php if ($nbFavoris > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $nbFavoris ?>
                                    <span class="visually-hidden">favoris</span>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <!-- Panier -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?= URL_BASE ?>/panier/panier.php"
                           title="Mon panier">
                            <i class="bi bi-bag fs-5"></i>
                            <?php if ($nbPanier > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">
                                    <?= $nbPanier ?>
                                    <span class="visually-hidden">articles dans le panier</span>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <!-- Menu compte client -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#"
                           id="dropdownCompte" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5"></i>
                            <?= h($_SESSION['user_prenom'] ?? 'Mon compte') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownCompte">
                            <li>
                                <a class="dropdown-item" href="<?= URL_BASE ?>/client/profil.php">
                                    <i class="bi bi-person me-2"></i>Mon profil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= URL_BASE ?>/commandes/historique.php">
                                    <i class="bi bi-clock-history me-2"></i>Mes commandes
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= URL_BASE ?>/auth/deconnexion.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Se déconnecter
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Bouton Déconnexion visible -->
                    <li class="nav-item">
                        <a class="btn btn-danger btn-sm ms-1" href="<?= URL_BASE ?>/auth/deconnexion.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
                        </a>
                    </li>

                <?php elseif (estAdmin()): ?>
                    <!-- ADMINISTRATEUR CONNECTÉ -->

                    <li class="nav-item">
                        <a class="nav-link text-primary fw-semibold"
                           href="<?= URL_BASE ?>/admin/dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>Back-office
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#"
                           id="dropdownAdmin" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shield-check fs-5"></i>
                            <?= h($_SESSION['user_prenom'] ?? 'Admin') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownAdmin">
                            <li>
                                <a class="dropdown-item" href="<?= URL_BASE ?>/admin/dashboard.php">
                                    <i class="bi bi-house me-2"></i>Tableau de bord
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= URL_BASE ?>/admin/produits.php">
                                    <i class="bi bi-box me-2"></i>Produits
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= URL_BASE ?>/admin/commandes.php">
                                    <i class="bi bi-receipt me-2"></i>Commandes
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= URL_BASE ?>/auth/deconnexion.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Se déconnecter
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Bouton Déconnexion visible (Admin) -->
                    <li class="nav-item">
                        <a class="btn btn-danger btn-sm ms-1" href="<?= URL_BASE ?>/auth/deconnexion.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
                        </a>
                    </li>

                <?php else: ?>
                    <!-- VISITEUR NON CONNECTÉ -->

                    <li class="nav-item">
                        <a class="nav-link" href="<?= URL_BASE ?>/auth/connexion.php">
                            <i class="bi bi-person me-1"></i>Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-dark btn-sm ms-2"
                           href="<?= URL_BASE ?>/auth/inscription.php">
                            S'inscrire
                        </a>
                    </li>

                <?php endif; ?>

            </ul><!-- /.navbar-nav droite -->
        </div><!-- /.collapse -->
    </div><!-- /.container -->
</nav>
