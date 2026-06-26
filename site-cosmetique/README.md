# Maison Lumière — Site e-commerce de cosmétiques

Site e-commerce développé en **PHP natif** (sans framework), **MySQL** (PDO),
**HTML5 / CSS3 / JavaScript**, **Bootstrap 5**, conçu pour un environnement
**XAMPP**.

---

## 1. Installation sous XAMPP

1. **Copier le projet**
   Copiez l'intégralité du dossier `site-cosmetique/` dans le dossier
   `htdocs/` de votre installation XAMPP, par exemple :
   `C:\xampp\htdocs\site-cosmetique` (Windows) ou
   `/Applications/XAMPP/htdocs/site-cosmetique` (macOS).

2. **Démarrer les services**
   Lancez **Apache** et **MySQL** depuis le panneau de contrôle XAMPP.

3. **Créer la base de données**
   Ouvrez **phpMyAdmin** (`http://localhost/phpmyadmin`), allez dans l'onglet
   **Importer**, puis sélectionnez le fichier `sql/schema.sql` du projet et
   validez. Cela crée automatiquement la base `cosmetique_db`, toutes les
   tables, ainsi que des données de démonstration (catégories, produits,
   utilisateurs de test, avis, favoris, promotions).

4. **Vérifier la configuration**
   Le fichier `config/db.php` est préréglé pour un XAMPP par défaut
   (`host=localhost`, utilisateur `root`, mot de passe vide). Si votre
   installation MySQL utilise un autre utilisateur/mot de passe, modifiez les
   constantes `DB_USER` et `DB_PASS` dans ce fichier.

   Le fichier `config/config.php` définit `URL_BASE` (par défaut
   `http://localhost/site-cosmetique`). Adaptez cette valeur si vous avez
   copié le projet dans un sous-dossier différent.

5. **Accéder au site**
   Rendez-vous sur `http://localhost/site-cosmetique/index.php`.

6. **Images produits (optionnel)**
   Le dossier `assets/images/produits/` est vide au départ — voir le fichier
   `LISEZMOI.txt` qu'il contient pour les noms de fichiers attendus. Tant
   qu'une image n'est pas fournie, une image de remplacement s'affiche
   automatiquement.

---

## 2. Comptes de test

| Rôle          | E-mail                       | Mot de passe |
|---------------|-------------------------------|---------------|
| Cliente       | marie.dupont@example.com      | `Client123!`  |
| Client        | julien.martin@example.com     | `Client123!`  |
| Administrateur| admin@cosmetique.fr           | `Admin123!`   |

Ces mots de passe sont hachés (bcrypt, `password_hash`) directement dans
`sql/schema.sql` — aucune étape supplémentaire n'est nécessaire après
l'import.

---

## 3. Fonctionnalités par module

### Public (sans connexion)
- **Catalogue** (`produits/liste.php`) : liste paginée, filtrage par
  catégorie, prix barré + prix promo si une promotion est active.
- **Fiche produit** (`produits/details.php`) : description, stock, note
  moyenne, avis clients, ajout au panier/favoris (clients connectés).
- **Recherche** (`produits/recherche.php`) : recherche par mot-clé.
- **Compte** (`auth/inscription.php`, `auth/connexion.php`) : création de
  compte et connexion sécurisée (mots de passe hachés, jetons CSRF,
  protection contre la fixation de session).

### Client connecté
- **Profil** (`client/profil.php`, `client/modifier_profil.php`).
- **Panier** (`panier/`) : ajout, modification de quantité, suppression,
  recalcul automatique du total.
- **Favoris** (`favoris/`) : ajout/retrait sans doublon.
- **Commande** (`commandes/passer_commande.php`) : transforme le panier en
  commande, décrémente le stock.
- **Paiement simulé** (`commandes/paiement.php`) : aucune API externe, le
  paiement est simulé côté serveur et enregistré en base.
- **Suivi** (`commandes/suivi.php`) et **historique**
  (`commandes/historique.php`) : consultation strictement limitée aux
  commandes du client connecté.
- **Avis** (`avis/ajouter_avis.php`) : un avis maximum par produit et par
  client.

### Administration (`admin/`, accès réservé au rôle `administrateur`)
- **Tableau de bord** (`dashboard.php`) : indicateurs clés (commandes en
  attente, clients, ruptures de stock).
- **Produits** (`produits.php`) et **catégories** (`categories.php`) : CRUD
  complet, upload d'image.
- **Clients** (`clients.php`) : recherche, fiche détaillée, activation /
  désactivation de compte (un compte désactivé ne peut plus se connecter).
- **Commandes** (`commandes.php`) : vue globale, changement de statut.
- **Promotions** (`promotions.php`) : CRUD complet (réduction en pourcentage
  ou montant fixe, période de validité).
- **Statistiques** (`statistiques.php`) : chiffre d'affaires, top produits,
  répartition des commandes par statut.

---

## 4. Sécurité

- Toutes les requêtes SQL utilisent des **requêtes préparées PDO**.
- Toutes les sorties de données utilisateur passent par `h()` /
  `nettoyerEntree()` (protection XSS).
- Tous les formulaires de modification utilisent un **jeton CSRF**
  (`genererToken()` / `verifierToken()`).
- Les pages réservées appellent `redirigerSiNonConnecte()` (client) ou
  `redirigerSiNonAdmin()` (administrateur) en tout début de script.
- Les pages de suivi/historique de commande vérifient que la commande
  consultée appartient bien au client connecté.

---

## 5. Identité visuelle

Palette "maison de beauté épurée" (ivoire, vert sauge, prune, rose poudré,
doré), typographie Lora (titres) + Inter (corps), cartes produit avec
étiquette "note + stock" façon flacon, plutôt que les badges génériques
habituels. Voir `assets/css/style.css`.
