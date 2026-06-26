<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

redirigerSiNonConnecte();

$pdo = getPDO();
$idClient = $_SESSION['user_id'];
$idCommande = (int) ($_GET['id'] ?? $_POST['idCommande'] ?? 0);

// Vérifier que la commande existe, appartient au client connecté, et est en attente de paiement
$stmt = $pdo->prepare("SELECT idCommande, montantTotal, statut FROM commandes WHERE idCommande = ? AND idClient = ?");
$stmt->execute([$idCommande, $idClient]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    flashMessage('danger', 'Commande introuvable.');
    rediriger(URL_BASE . '/commandes/historique.php');
}

if ($commande['statut'] !== 'en attente') {
    flashMessage('info', 'Cette commande a déjà été traitée.');
    rediriger(URL_BASE . '/commandes/suivi.php?id=' . $idCommande);
}

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierToken($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Requête invalide, merci de réessayer.';
    }

    $numeroCarte = preg_replace('/\s+/', '', $_POST['numeroCarte'] ?? '');
    $modePaiement = $_POST['modePaiement'] ?? '';

    if (!preg_match('/^\d{16}$/', $numeroCarte)) {
        $erreurs[] = 'Le numéro de carte doit contenir 16 chiffres (simulation).';
    }

    if (!in_array($modePaiement, ['carte bancaire', 'paypal', 'virement'], true)) {
        $erreurs[] = 'Mode de paiement invalide.';
    }

    if (empty($erreurs)) {
        try {
            $pdo->beginTransaction();

            // Insérer le paiement simulé
            $stmt = $pdo->prepare("INSERT INTO paiements (idCommande, datePaiement, montant, modePaiement, statutPaiement)
                                    VALUES (?, NOW(), ?, ?, 'validé')");
            $stmt->execute([$idCommande, $commande['montantTotal'], $modePaiement]);

            // Passer la commande au statut "validée"
            $pdo->prepare("UPDATE commandes SET statut = 'validée' WHERE idCommande = ?")->execute([$idCommande]);

            $pdo->commit();

            flashMessage('success', 'Paiement validé, merci pour votre commande !');
            rediriger(URL_BASE . '/commandes/confirmation.php?id=' . $idCommande);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $erreurs[] = 'Une erreur est survenue lors du paiement. Merci de réessayer.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h1 class="mb-4">Paiement de la commande #<?= $idCommande ?></h1>

            <?php if (!empty($erreurs)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($erreurs as $erreur): ?>
                            <li><?= h($erreur) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="alert alert-info">
                Ceci est un module de paiement <strong>simulé</strong> à des fins pédagogiques :
                aucune vraie transaction bancaire n'est effectuée.
            </div>

            <form action="<?= URL_BASE ?>/commandes/paiement.php?id=<?= $idCommande ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= h(genererToken()) ?>">
                <input type="hidden" name="idCommande" value="<?= $idCommande ?>">

                <div class="mb-3">
                    <label class="form-label">Montant à payer</label>
                    <input type="text" class="form-control" value="<?= formaterPrix($commande['montantTotal']) ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="numeroCarte">Numéro de carte (fictif)</label>
                    <input type="text" class="form-control" id="numeroCarte" name="numeroCarte"
                           placeholder="1234 5678 9012 3456" maxlength="19" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="modePaiement">Mode de paiement</label>
                    <select class="form-select" id="modePaiement" name="modePaiement" required>
                        <option value="carte bancaire">Carte bancaire</option>
                        <option value="paypal">PayPal</option>
                        <option value="virement">Virement</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">Payer maintenant</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
