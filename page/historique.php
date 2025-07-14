<?php
require_once '../inc/connexion.php';
requireLogin();

// Récupération anilay historique d'empreinte
$historique_query = "
    SELECT 
        e.id_emprunt,
        e.date_emprunt,
        e.date_retour,
        o.nom_objet,
        c.nom_categorie,
        proprietaire.nom as nom_proprietaire,
        emprunteur.nom as nom_emprunteur,
        CASE 
            WHEN e.id_membre = " . $_SESSION['user_id'] . " THEN 'emprunt'
            WHEN o.id_membre = " . $_SESSION['user_id'] . " THEN 'pret'
            ELSE 'autre'
        END as type_transaction
    FROM emprunt e
    JOIN objet o ON e.id_objet = o.id_objet
    JOIN categorie_objet c ON o.id_categorie = c.id_categorie
    JOIN membre proprietaire ON o.id_membre = proprietaire.id_membre
    JOIN membre emprunteur ON e.id_membre = emprunteur.id_membre
    WHERE e.id_membre = " . $_SESSION['user_id'] . " OR o.id_membre = " . $_SESSION['user_id'] . "
    ORDER BY e.date_emprunt DESC
    LIMIT 50
";
$historique_result = mysqli_query(dbconnect(), $historique_query);

// Statistiques historiques
$stats_query = "
    SELECT 
        COUNT(CASE WHEN e.id_membre = " . $_SESSION['user_id'] . " THEN 1 END) as total_emprunts,
        COUNT(CASE WHEN o.id_membre = " . $_SESSION['user_id'] . " THEN 1 END) as total_prets,
        COUNT(CASE WHEN e.id_membre = " . $_SESSION['user_id'] . " AND e.date_retour IS NOT NULL THEN 1 END) as emprunts_termines,
        COUNT(CASE WHEN o.id_membre = " . $_SESSION['user_id'] . " AND e.date_retour IS NOT NULL THEN 1 END) as prets_termines
    FROM emprunt e
    JOIN objet o ON e.id_objet = o.id_objet
    WHERE e.id_membre = " . $_SESSION['user_id'] . " OR o.id_membre = " . $_SESSION['user_id'];
$stats_result = mysqli_query(dbconnect(), $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique - Système d'emprunt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-exchange-alt"></i> Système d'emprunt
            </a>
            <div class="navbar-nav ms-auto">
                <a href="dashboard.php" class="nav-link me-3">
                    <i class="fas fa-home"></i> Accueil
                </a>
                <a href="liste_objet.php" class="nav-link me-3">
                    <i class="fas fa-list"></i> Objets
                </a>
                <a href="mes_objets.php" class="nav-link me-3">
                    <i class="fas fa-box"></i> Mes objets
                </a>
                <a href="mes_emprunts.php" class="nav-link me-3">
                    <i class="fas fa-handshake"></i> Emprunts
                </a>
                <span class="navbar-text me-3">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-history"></i> Historique des transactions</h2>
        </div>

        
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-download fa-2x mb-2"></i>
                        <h4><?= $stats['total_emprunts'] ?></h4>
                        <p class="mb-0">Emprunts effectués</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-upload fa-2x mb-2"></i>
                        <h4><?= $stats['total_prets'] ?></h4>
                        <p class="mb-0">Objets prêtés</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4><?= $stats['emprunts_termines'] ?></h4>
                        <p class="mb-0">Emprunts terminés</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-undo fa-2x mb-2"></i>
                        <h4><?= $stats['prets_termines'] ?></h4>
                        <p class="mb-0">Prêts terminés</p>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-clock"></i> Historique des 50 dernières transactions</h5>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($historique_result) > 0): ?>
                    <div class="timeline">
                        <?php while ($transaction = mysqli_fetch_assoc($historique_result)): ?>
                            <?php
                                $is_emprunt = $transaction['type_transaction'] === 'emprunt';
                                $icon = $is_emprunt ? 'fa-download' : 'fa-upload';
                                $color = $is_emprunt ? 'primary' : 'success';
                                $action = $is_emprunt ? 'Vous avez emprunté' : 'Vous avez prêté';
                                $from_to = $is_emprunt ? 'de ' . $transaction['nom_proprietaire'] : 'à ' . $transaction['nom_emprunteur'];
                                $status = $transaction['date_retour'] ? 'Terminé' : 'En cours';
                                $status_color = $transaction['date_retour'] ? 'success' : 'warning';
                            ?>
                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0">
                                    <div class="bg-<?= $color ?> text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas <?= $icon ?>"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title mb-1">
                                                        <?= $action ?> <strong><?= htmlspecialchars($transaction['nom_objet']) ?></strong>
                                                    </h6>
                                                    <p class="card-text mb-2">
                                                        <span class="badge bg-secondary"><?= htmlspecialchars($transaction['nom_categorie']) ?></span>
                                                        <small class="text-muted ms-2"><?= $from_to ?></small>
                                                    </p>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar"></i> 
                                                        Emprunté le <?= date('d/m/Y', strtotime($transaction['date_emprunt'])) ?>
                                                        <?php if ($transaction['date_retour']): ?>
                                                            • Rendu le <?= date('d/m/Y', strtotime($transaction['date_retour'])) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <span class="badge bg-<?= $status_color ?>">
                                                    <?= $status ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-history fa-3x mb-3"></i>
                        <h5>Aucun historique</h5>
                        <p>Vous n'avez pas encore d'activité d'emprunt</p>
                        <a href="liste_objet.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Commencer à emprunter
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>
</html>
