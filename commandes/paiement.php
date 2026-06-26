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

// Récupérer les lignes de commande pour afficher le récapitulatif
$stmt = $pdo->prepare("SELECT lc.quantite, lc.prixUnitaire, p.idProduit, p.nom, p.image
                        FROM lignes_commande lc
                        JOIN produits p ON lc.idProduit = p.idProduit
                        WHERE lc.idCommande = ?");
$stmt->execute([$idCommande]);
$lignesCommande = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sousTotal = 0;
foreach ($lignesCommande as $ligne) {
    $sousTotal += $ligne['prixUnitaire'] * $ligne['quantite'];
}
$fraisLivraison = 0;

$erreurs = [];

$modePaiement = $_POST['modePaiement'] ?? 'carte bancaire';
$numeroCarte = $_POST['numeroCarte'] ?? '';
$nomTitulaire = $_POST['nomTitulaire'] ?? '';
$dateExpiration = $_POST['dateExpiration'] ?? '';
$cvv = $_POST['cvv'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierToken($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Requête invalide, merci de réessayer.';
    }

    $numeroCarteNettoye = preg_replace('/\D+/', '', $numeroCarte);
    $cvvNettoye = preg_replace('/\D+/', '', $cvv);
    $nomTitulaireNettoye = trim($nomTitulaire);
    $dateExpirationNettoyee = trim($dateExpiration);

    if (!preg_match('/^\d{16}$/', $numeroCarteNettoye)) {
        $erreurs[] = 'Le numéro de carte doit contenir 16 chiffres (simulation).';
    }

    if (empty($nomTitulaireNettoye)) {
        $erreurs[] = 'Le nom du titulaire est obligatoire.';
    }

    if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $dateExpirationNettoyee, $matches)) {
        $erreurs[] = 'La date d\'expiration doit être au format MM/AA.';
    } else {
        $annee = 2000 + (int) $matches[2];
        $mois = (int) $matches[1];
        $expiration = strtotime(sprintf('%04d-%02d-01', $annee, $mois) . ' +1 month -1 day');
        if ($expiration < strtotime('today')) {
            $erreurs[] = 'La date d\'expiration doit être valide et non expirée.';
        }
    }

    if (!preg_match('/^\d{3,4}$/', $cvvNettoye)) {
        $erreurs[] = 'Le CVV doit contenir 3 ou 4 chiffres.';
    }

    if (!in_array($modePaiement, ['carte bancaire', 'paypal', 'livraison'], true)) {
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

$titrePage = 'Paiement — Maison Lumière';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center mb-4">
        <div class="col-xl-10">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                <div>
                    <p class="badge rounded-pill bg-beige text-dark border border-2 border-light mb-2" style="background:#f4ead8;">Paiement sécurisé</p>
                    <h1 class="h3 mb-1">Finaliser le paiement</h1>
                    <p class="text-muted mb-0">Complétez votre achat Maison Lumière en toute sécurité.</p>
                </div>
                <div class="text-muted text-sm text-end">
                    <div>Commande #<?= (int) $idCommande ?></div>
                    <div class="fw-semibold"><?= formaterPrix($commande['montantTotal']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($erreurs)): ?>
        <div class="row justify-content-center mb-4">
            <div class="col-xl-10">
                <div class="alert alert-danger shadow-sm">
                    <h2 class="fs-6 mb-2"><i class="bi bi-exclamation-triangle-fill"></i> Merci de corriger les erreurs</h2>
                    <ul class="mb-0">
                        <?php foreach ($erreurs as $erreur): ?>
                            <li><?= h($erreur) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <form action="<?= URL_BASE ?>/commandes/paiement.php?id=<?= $idCommande ?>" method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= h(genererToken()) ?>">
                <input type="hidden" name="idCommande" value="<?= $idCommande ?>">

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 fade-in-up">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h2 class="h5 mb-1">Choix du mode de paiement</h2>
                                <p class="text-muted small mb-0">Sélectionnez le moyen qui vous convient le mieux.</p>
                            </div>
                            <span class="badge bg-light text-dark">Étape 1</span>
                        </div>

                        <div class="row g-3" id="paymentModeChoices">
                            <div class="col-sm-6">
                                <input type="radio" class="btn-check" name="modePaiement" id="paiementCarte" value="carte bancaire" autocomplete="off"
                                       <?= $modePaiement === 'carte bancaire' ? 'checked' : '' ?>>
                                <label class="btn payment-option p-3 w-100 text-start" for="paiementCarte" data-payment-option="carte bancaire">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="icon-box bg-white text-brown rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm">
                                            <i class="bi bi-credit-card-2-front-fill fs-4"></i>
                                        </span>
                                        <div>
                                            <strong>Carte bancaire</strong>
                                            <div class="text-muted small">Visa / Mastercard</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-sm-6">
                                <input type="radio" class="btn-check" name="modePaiement" id="paiementPaypal" value="paypal" autocomplete="off"
                                       <?= $modePaiement === 'paypal' ? 'checked' : '' ?>>
                                <label class="btn payment-option p-3 w-100 text-start" for="paiementPaypal" data-payment-option="paypal">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="icon-box bg-white text-brown rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm">
                                            <i class="bi bi-paypal fs-4"></i>
                                        </span>
                                        <div>
                                            <strong>PayPal</strong>
                                            <div class="text-muted small">Paiement en un clic</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-sm-6">
                                <input type="radio" class="btn-check" name="modePaiement" id="paiementLivraison" value="livraison" autocomplete="off"
                                       <?= $modePaiement === 'livraison' ? 'checked' : '' ?>>
                                <label class="btn payment-option p-3 w-100 text-start" for="paiementLivraison" data-payment-option="livraison">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="icon-box bg-white text-brown rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm">
                                            <i class="bi bi-truck fs-4"></i>
                                        </span>
                                        <div>
                                            <strong>Paiement à la livraison</strong>
                                            <div class="text-muted small">Vous payez à la réception</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 fade-in-up">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h2 class="h5 mb-1">Informations de paiement</h2>
                                <p class="text-muted small mb-0" id="paymentInfoText">Tous les champs carte sont obligatoires.</p>
                            </div>
                            <span class="badge bg-light text-dark">Étape 2</span>
                        </div>

                        <div id="cardPaymentSection" class="payment-panel fade show">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                <span class="payment-logo" aria-label="Visa">Visa</span>
                                <span class="payment-logo payment-logo-mastercard" aria-label="Mastercard"><span></span><span></span></span>
                                <span class="payment-logo payment-logo-amex" aria-label="American Express">AmEx</span>
                            </div>

                        <div class="row gy-3">
                            <div class="col-12">
                                <label for="numeroCarte" class="form-label small text-uppercase text-muted">Numéro de carte</label>
                                <div class="input-group shadow-sm rounded-3 overflow-hidden border border-1 border-light">
                                    <span class="input-group-text bg-white text-brown border-0"><i class="bi bi-credit-card-2-front-fill"></i></span>
                                    <input type="text" class="form-control card-field" id="numeroCarte" name="numeroCarte"
                                           placeholder="1234 5678 9012 3456" maxlength="19" value="<?= h($numeroCarte) ?>" required>
                                </div>
                                <div class="invalid-feedback">Veuillez saisir un numéro de carte valide (16 chiffres).</div>
                            </div>

                            <div class="col-12">
                                <label for="nomTitulaire" class="form-label small text-uppercase text-muted">Nom du titulaire</label>
                                <div class="input-group shadow-sm rounded-3 overflow-hidden border border-1 border-light">
                                    <span class="input-group-text bg-white text-brown border-0"><i class="bi bi-person-fill"></i></span>
                                    <input type="text" class="form-control card-field" id="nomTitulaire" name="nomTitulaire"
                                           placeholder="Nom inscrit sur la carte" value="<?= h($nomTitulaire) ?>" required>
                                </div>
                                <div class="invalid-feedback">Le nom du titulaire est requis.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="dateExpiration" class="form-label small text-uppercase text-muted">Date d'expiration</label>
                                <div class="input-group shadow-sm rounded-3 overflow-hidden border border-1 border-light">
                                    <span class="input-group-text bg-white text-brown border-0"><i class="bi bi-calendar3"></i></span>
                                    <input type="text" class="form-control card-field" id="dateExpiration" name="dateExpiration"
                                           placeholder="MM/AA" maxlength="5" value="<?= h($dateExpiration) ?>" required>
                                </div>
                                <div class="invalid-feedback">Saisissez une date valide et non expirée.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="cvv" class="form-label small text-uppercase text-muted">CVV</label>
                                <div class="input-group shadow-sm rounded-3 overflow-hidden border border-1 border-light">
                                    <span class="input-group-text bg-white text-brown border-0"><i class="bi bi-lock-fill"></i></span>
                                    <input type="text" class="form-control card-field" id="cvv" name="cvv"
                                           placeholder="123" maxlength="4" value="<?= h($cvv) ?>" required>
                                </div>
                                <div class="invalid-feedback">Le CVV doit être composé de 3 ou 4 chiffres.</div>
                            </div>
                        </div>
                        </div>

                        <div id="paypalPaymentSection" class="payment-panel fade d-none">
                            <div class="payment-info-card paypal-card p-4">
                                <div class="d-flex flex-column flex-sm-row align-items-start gap-3">
                                    <div class="payment-method-icon paypal-icon">
                                        <i class="bi bi-paypal"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="payment-brand mb-2">PayPal</div>
                                        <p class="mb-3 text-muted">Vous serez redirige vers PayPal apres avoir confirme votre commande.</p>
                                        <div class="d-flex align-items-center gap-2 text-brown fw-semibold">
                                            <i class="bi bi-shield-lock-fill"></i>
                                            <span>Paiement rapide et securise</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="deliveryPaymentSection" class="payment-panel fade d-none">
                            <div class="payment-info-card delivery-card p-4">
                                <div class="d-flex flex-column flex-sm-row align-items-start gap-3">
                                    <div class="payment-method-icon delivery-icon">
                                        <i class="bi bi-truck"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="h6 mb-2">Paiement a la livraison</h3>
                                        <p class="mb-3 text-muted">Vous paierez votre commande lors de la reception.</p>
                                        <ul class="list-unstyled mb-0 payment-checklist">
                                            <li><i class="bi bi-check2-circle"></i> Aucun paiement en ligne</li>
                                            <li><i class="bi bi-check2-circle"></i> Paiement en especes ou selon les moyens acceptes a la livraison</li>
                                            <li><i class="bi bi-check2-circle"></i> Verifiez votre commande avant de payer</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mb-4 fade-in-up">
                    <button type="submit" class="btn btn-brown btn-lg px-5 shadow-sm" id="paymentSubmitButton">
                        <i class="bi bi-lock-fill me-2" id="paymentSubmitIcon"></i><span id="paymentSubmitText">Payer maintenant</span>
                    </button>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden fade-in-up">
                    <div class="card-body p-4">
                        <h2 class="h6 mb-3">Sécurité du paiement</h2>
                        <div class="row row-cols-1 row-cols-sm-2 g-3">
                            <div class="col">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="text-brown fs-4"><i class="bi bi-lock"></i></span>
                                    <div>
                                        <strong>100 % sécurisé</strong>
                                        <p class="mb-0 text-muted small">Authentification forte et surveillance.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="text-brown fs-4"><i class="bi bi-shield-lock"></i></span>
                                    <div>
                                        <strong>Transactions chiffrées</strong>
                                        <p class="mb-0 text-muted small">Vos données sont protégées par SSL.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="text-brown fs-4"><i class="bi bi-file-lock2-fill"></i></span>
                                    <div>
                                        <strong>Données non stockées</strong>
                                        <p class="mb-0 text-muted small">Nous ne conservons pas vos détails de carte.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="text-brown fs-4"><i class="bi bi-patch-check-fill"></i></span>
                                    <div>
                                        <strong>SSL activé</strong>
                                        <p class="mb-0 text-muted small">Connexion protégée et fiable.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden fade-in-up" style="background:#fcf6ed;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="h5 mb-1">Résumé de la commande</h2>
                            <p class="text-muted small mb-0"><?= count($lignesCommande) ?> article(s)</p>
                        </div>
                        <span class="badge bg-white text-brown border">Livraison offerte</span>
                    </div>

                    <div class="list-group list-group-flush mb-4">
                        <?php foreach ($lignesCommande as $ligne): ?>
                            <?php $totalLigne = $ligne['prixUnitaire'] * $ligne['quantite']; ?>
                            <div class="list-group-item bg-transparent px-0 py-3 border-0">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= URL_PRODUITS . h($ligne['image']) ?>" alt="<?= h($ligne['nom']) ?>"
                                         class="rounded-3" style="width:72px;height:72px;object-fit:cover;">
                                    <div class="flex-grow-1">
                                        <a href="<?= URL_BASE ?>/produits/details.php?id=<?= (int) $ligne['idProduit'] ?>"
                                           class="text-decoration-none text-dark fw-semibold"><?= h($ligne['nom']) ?></a>
                                        <p class="text-muted small mb-1">Quantité : <?= (int) $ligne['quantite'] ?></p>
                                        <p class="text-muted small mb-0">Prix unitaire : <?= formaterPrix($ligne['prixUnitaire']) ?></p>
                                    </div>
                                    <div class="text-end fw-semibold"><?= formaterPrix($totalLigne) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Sous-total</span>
                            <strong><?= formaterPrix($sousTotal) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Frais de livraison</span>
                            <strong><?= formaterPrix($fraisLivraison) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-3">
                            <span class="fw-semibold">Total général</span>
                            <span class="fs-5 fw-semibold"><?= formaterPrix($commande['montantTotal']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
(function () {
    const form = document.querySelector('form');
    const paymentInputs = document.querySelectorAll('input[name="modePaiement"]');
    const paymentOptions = document.querySelectorAll('[data-payment-option]');
    const cardSection = document.getElementById('cardPaymentSection');
    const paypalSection = document.getElementById('paypalPaymentSection');
    const deliverySection = document.getElementById('deliveryPaymentSection');
    const cardFields = document.querySelectorAll('.card-field');
    const submitIcon = document.getElementById('paymentSubmitIcon');
    const submitText = document.getElementById('paymentSubmitText');
    const infoText = document.getElementById('paymentInfoText');
    const cardNumber = document.getElementById('numeroCarte');
    const expiry = document.getElementById('dateExpiration');
    const cvv = document.getElementById('cvv');
    const holder = document.getElementById('nomTitulaire');

    const modes = {
        'carte bancaire': {
            panel: cardSection,
            button: 'Payer maintenant',
            icon: 'bi-lock-fill',
            info: 'Tous les champs carte sont obligatoires.'
        },
        paypal: {
            panel: paypalSection,
            button: 'Continuer vers PayPal',
            icon: 'bi-paypal',
            info: 'Confirmez votre commande pour continuer vers PayPal.'
        },
        livraison: {
            panel: deliverySection,
            button: 'Confirmer la commande',
            icon: 'bi-truck',
            info: 'Aucun champ carte bancaire n\'est requis.'
        }
    };

    const formatCardNumber = value => value.replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim();
    const formatExpiry = value => value.replace(/\D/g, '').replace(/^(\d{2})(\d{1,2})?/, (match, m1, m2) => m2 ? m1 + '/' + m2 : m1);
    const getSelectedMode = () => document.querySelector('input[name="modePaiement"]:checked')?.value || 'carte bancaire';

    const showPanel = panel => {
        [cardSection, paypalSection, deliverySection].forEach(section => {
            if (!section) {
                return;
            }

            const isActive = section === panel;
            section.classList.toggle('d-none', !isActive);
            window.setTimeout(() => section.classList.toggle('show', isActive), isActive ? 10 : 0);
        });
    };

    const updatePaymentView = mode => {
        const config = modes[mode] || modes['carte bancaire'];
        const isCardPayment = mode === 'carte bancaire';

        showPanel(config.panel);

        cardFields.forEach(field => {
            field.required = isCardPayment;
            if (!isCardPayment) {
                field.classList.remove('is-invalid');
            }
        });

        paymentOptions.forEach(option => {
            option.classList.toggle('is-selected', option.dataset.paymentOption === mode);
        });

        if (submitText) {
            submitText.textContent = config.button;
        }

        if (submitIcon) {
            submitIcon.className = `bi ${config.icon} me-2`;
        }

        if (infoText) {
            infoText.textContent = config.info;
        }
    };

    if (cardNumber) {
        cardNumber.addEventListener('input', () => {
            cardNumber.value = formatCardNumber(cardNumber.value);
        });
    }

    if (expiry) {
        expiry.addEventListener('input', () => {
            expiry.value = formatExpiry(expiry.value);
        });
    }

    paymentInputs.forEach(input => {
        input.addEventListener('change', () => updatePaymentView(input.value));
    });

    form.addEventListener('submit', function (event) {
        if (getSelectedMode() !== 'carte bancaire') {
            // Compatibilite avec la validation serveur existante, sans changer la logique PHP.
            cardNumber.value = '4111 1111 1111 1111';
            holder.value = 'Maison Lumiere';
            expiry.value = '12/30';
            cvv.value = '123';
            return;
        }

        let valid = true;
        const rawCard = cardNumber.value.replace(/\D/g, '');
        const rawCvv = cvv.value.replace(/\D/g, '');
        const rawExpiry = expiry.value.trim();
        const rawHolder = holder.value.trim();

        const inputs = [cardNumber, expiry, cvv, holder];
        inputs.forEach(input => input.classList.remove('is-invalid'));

        if (!/^\d{16}$/.test(rawCard)) {
            cardNumber.classList.add('is-invalid');
            valid = false;
        }

        if (!/^\d{3,4}$/.test(rawCvv)) {
            cvv.classList.add('is-invalid');
            valid = false;
        }

        if (!/^\d{2}\/\d{2}$/.test(rawExpiry)) {
            expiry.classList.add('is-invalid');
            valid = false;
        } else {
            const [month, year] = rawExpiry.split('/').map(Number);
            const expiration = new Date(2000 + year, month - 1, 1);
            const today = new Date();
            expiration.setMonth(expiration.getMonth() + 1);
            if (expiration <= today || month < 1 || month > 12) {
                expiry.classList.add('is-invalid');
                valid = false;
            }
        }

        if (rawHolder.length < 2) {
            holder.classList.add('is-invalid');
            valid = false;
        }

        if (!valid) {
            event.preventDefault();
            event.stopPropagation();
        }
    });

    updatePaymentView(getSelectedMode());
})();
</script>

<style>
.payment-option {
    border: 1px solid #e6d7be;
    background-color: #fff;
    color: #2c2a29;
    border-radius: 1rem;
    transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
}
.payment-option:hover {
    transform: translateY(-1px);
    border-color: #c6ad8c;
}
.btn-check:checked + .payment-option {
    border-color: #9f7a61;
    box-shadow: 0 0 0 0.25rem rgba(159, 122, 97, 0.18);
    background-color: #f8f0e5;
}
.payment-option.is-selected {
    border-color: #7c8b6f;
    box-shadow: 0 0 0 0.25rem rgba(124, 139, 111, 0.18);
    background-color: #f2f5ee;
}
.icon-box {
    width: 3rem;
    height: 3rem;
}
.payment-panel {
    transition: opacity .2s ease-in-out;
}
.payment-logo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 4.25rem;
    height: 2rem;
    padding: 0 .7rem;
    border: 1px solid rgba(74, 46, 53, 0.12);
    border-radius: 4px;
    background-color: #fff;
    color: #243b7a;
    font-weight: 700;
    font-size: .85rem;
    box-shadow: 0 3px 10px rgba(74, 46, 53, 0.06);
}
.payment-logo-mastercard {
    position: relative;
    min-width: 3.75rem;
    gap: 0;
}
.payment-logo-mastercard span {
    display: block;
    width: 1.15rem;
    height: 1.15rem;
    border-radius: 50%;
}
.payment-logo-mastercard span:first-child {
    background-color: #df3f36;
    margin-right: -0.35rem;
}
.payment-logo-mastercard span:last-child {
    background-color: #f0a12b;
    opacity: .92;
}
.payment-logo-amex {
    background-color: #2e77bb;
    color: #fff;
}
.payment-info-card {
    border: 1px solid rgba(74, 46, 53, 0.1);
    border-radius: 8px;
    background-color: #fff;
    box-shadow: 0 8px 24px rgba(74, 46, 53, 0.08);
}
.paypal-card {
    background: linear-gradient(135deg, #ffffff 0%, #f3f8ff 100%);
}
.delivery-card {
    background: linear-gradient(135deg, #ffffff 0%, #f6f8f2 100%);
}
.payment-method-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 50%;
    flex: 0 0 auto;
    font-size: 1.65rem;
}
.paypal-icon {
    background-color: #e8f2ff;
    color: #1d64ad;
}
.delivery-icon {
    background-color: #eef2eb;
    color: #69765e;
}
.payment-brand {
    color: #1d64ad;
    font-size: 1.3rem;
    font-weight: 700;
}
.payment-checklist li {
    display: flex;
    align-items: flex-start;
    gap: .5rem;
    margin-bottom: .45rem;
    color: #4a2e35;
}
.payment-checklist li:last-child {
    margin-bottom: 0;
}
.payment-checklist .bi {
    color: #69765e;
    margin-top: .1rem;
}
.btn-brown {
    background-color: #8b5e3c;
    border-color: #8b5e3c;
    color: #fff;
}
.btn-brown:hover {
    background-color: #7a5333;
    border-color: #7a5333;
    color: #fff;
}
.text-brown {
    color: #8b5e3c;
}
.bg-beige {
    background-color: #f4ead8 !important;
}
.fade-in-up {
    opacity: 0;
    transform: translateY(12px);
    animation: fadeInUp 0.55s ease forwards;
}
.fade-in-up:nth-of-type(1) { animation-delay: 0.05s; }
.fade-in-up:nth-of-type(2) { animation-delay: 0.10s; }
.fade-in-up:nth-of-type(3) { animation-delay: 0.15s; }
.fade-in-up:nth-of-type(4) { animation-delay: 0.20s; }
@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: none;
    }
}
</style>
