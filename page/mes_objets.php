<?php
require_once '../inc/connexion.php';
requireLogin();

// Gestion des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $objet_id = (int)($_POST['objet_id'] ?? 0);
    
    // Vérifier que l'objet appartient à l'utilisateur connecté
    $check_query = "SELECT id_objet FROM objet WHERE id_objet = $objet_id AND id_membre = " . $_SESSION['user_id'];
    $check_result = mysqli_query(dbconnect(), $check_query);
    
    if (!$check_result || mysqli_num_rows($check_result) === 0) {
        $_SESSION['error'] = "Objet non trouvé ou non autorisé.";
        header('Location: mes_objets.php');
        exit;
    }
    
    $success_message = '';
    $error_message = '';
    
    switch ($action) {
        case 'toggle_disponible':
            // Basculer la disponibilité de l'objet
            $toggle_query = "UPDATE objet SET disponible = 1 - disponible WHERE id_objet = $objet_id AND id_membre = " . $_SESSION['user_id'];
            if (mysqli_query(dbconnect(), $toggle_query)) {
                $success_message = "Statut de l'objet mis à jour avec succès.";
            } else {
                $error_message = "Erreur lors de la mise à jour du statut : " . mysqli_error(dbconnect());
            }
            break;
            
        case 'marquer_rendu':
            // Marquer l'emprunt comme rendu
            $emprunt_id = (int)($_POST['emprunt_id'] ?? 0);
            $rendu_query = "UPDATE emprunt SET date_retour = NOW() WHERE id_emprunt = $emprunt_id AND id_objet = $objet_id";
            if (mysqli_query(dbconnect(), $rendu_query)) {
                $success_message = "L'emprunt a été marqué comme rendu.";
            } else {
                $error_message = "Erreur lors du marquage de retour : " . mysqli_error(dbconnect());
            }
            break;
            
        default:
            $error_message = "Action non reconnue.";
            break;
    }
    
    // Stocker les messages en session pour l'affichage
    if ($success_message) {
        $_SESSION['success'] = $success_message;
    }
    if ($error_message) {
        $_SESSION['error'] = $error_message;
    }
    
    // Redirection pour éviter la resoumission
    header('Location: mes_objets.php');
    exit;
}


$query = "
    SELECT 
        o.id_objet,
        o.nom_objet,
        o.description,
        o.etat,
        o.disponible,
        o.date_ajout,
        c.nom_categorie,
        e.id_emprunt,
        e.date_emprunt,
        e.date_retour,
        emp.nom as nom_emprunteur,
        emp.email as email_emprunteur
    FROM objet o
    JOIN categorie_objet c ON o.id_categorie = c.id_categorie
    LEFT JOIN emprunt e ON o.id_objet = e.id_objet AND e.date_retour IS NULL
    LEFT JOIN membre emp ON e.id_membre = emp.id_membre
    WHERE o.id_membre = " . $_SESSION['user_id'] . "
    ORDER BY o.date_ajout DESC
";

$result = mysqli_query(dbconnect(), $query);


$stats_query = "
    SELECT 
        COUNT(*) as total_objets,
        COUNT(CASE WHEN disponible = 1 THEN 1 END) as objets_disponibles,
        COUNT(CASE WHEN e.date_retour IS NULL THEN 1 END) as objets_empruntes
    FROM objet o
    LEFT JOIN emprunt e ON o.id_objet = e.id_objet AND e.date_retour IS NULL
    WHERE o.id_membre = " . $_SESSION['user_id'];
$stats_result = mysqli_query(dbconnect(), $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes objets - Système d'emprunt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .object-card {
            transition: transform 0.2s;
        }
        .object-card:hover {
            transform: translateY(-2px);
        }
        .borrowed-card {
            border-left: 4px solid #ffc107;
            background: rgba(255, 193, 7, 0.05);
        }
        .available-card {
            border-left: 4px solid #28a745;
            background: rgba(40, 167, 69, 0.05);
        }
        .unavailable-card {
            border-left: 4px solid #dc3545;
            background: rgba(220, 53, 69, 0.05);
        }
    </style>
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
                    <i class="fas fa-list"></i> Liste des objets
                </a>
                <a href="ajouter_objet.php" class="nav-link me-3">
                    <i class="fas fa-plus"></i> Ajouter
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
        <!-- Messages de feedback -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-box"></i> Mes objets</h2>
            <a href="ajouter_objet.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Ajouter un objet
            </a>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-boxes fa-2x mb-2"></i>
                        <h4><?= $stats['total_objets'] ?></h4>
                        <p class="mb-0">Total objets</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4><?= $stats['objets_disponibles'] ?></h4>
                        <p class="mb-0">Disponibles</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-handshake fa-2x mb-2"></i>
                        <h4><?= $stats['objets_empruntes'] ?></h4>
                        <p class="mb-0">Empruntés</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des objets -->
        <div class="row">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($objet = mysqli_fetch_assoc($result)): ?>
                    <?php 
                        $card_class = 'available-card';
                        $status_badge = '<span class="badge bg-success"><i class="fas fa-check"></i> Disponible</span>';
                        
                        if ($objet['id_emprunt']) {
                            $card_class = 'borrowed-card';
                            $status_badge = '<span class="badge bg-warning"><i class="fas fa-handshake"></i> Emprunté</span>';
                        } elseif (!$objet['disponible']) {
                            $card_class = 'unavailable-card';
                            $status_badge = '<span class="badge bg-danger"><i class="fas fa-times"></i> Non disponible</span>';
                        }
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card object-card h-100 <?= $card_class ?>">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span class="badge bg-secondary"><?= htmlspecialchars($objet['nom_categorie']) ?></span>
                                <?= $status_badge ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-box"></i> <?= htmlspecialchars($objet['nom_objet']) ?>
                                </h5>
                                
                                <?php if ($objet['description']): ?>
                                    <p class="card-text text-muted">
                                        <?= htmlspecialchars(substr($objet['description'], 0, 100)) ?>
                                        <?= strlen($objet['description']) > 100 ? '...' : '' ?>
                                    </p>
                                <?php endif; ?>

                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-star"></i> État : <strong><?= htmlspecialchars($objet['etat']) ?></strong><br>
                                        <i class="fas fa-calendar"></i> Ajouté le <?= date('d/m/Y', strtotime($objet['date_ajout'])) ?>
                                    </small>
                                </div>

                                <?php if ($objet['id_emprunt']): ?>
                                    <div class="alert alert-warning mb-2">
                                        <strong><i class="fas fa-info-circle"></i> Emprunté par :</strong><br>
                                        <small>
                                            <i class="fas fa-user"></i> <?= htmlspecialchars($objet['nom_emprunteur']) ?><br>
                                            <i class="fas fa-envelope"></i> <?= htmlspecialchars($objet['email_emprunteur']) ?><br>
                                            <i class="fas fa-calendar"></i> Depuis le <?= date('d/m/Y', strtotime($objet['date_emprunt'])) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-outline-primary btn-sm flex-fill">
                                        <i class="fas fa-edit"></i> Modifier
                                    </button>
                                    <?php if ($objet['id_emprunt']): ?>
                                        <form method="POST" class="flex-fill" onsubmit="return confirm('Confirmer que cet objet a été rendu ?');">
                                            <input type="hidden" name="action" value="marquer_rendu">
                                            <input type="hidden" name="objet_id" value="<?= $objet['id_objet'] ?>">
                                            <input type="hidden" name="emprunt_id" value="<?= $objet['id_emprunt'] ?>">
                                            <button type="submit" class="btn btn-outline-success btn-sm w-100">
                                                <i class="fas fa-undo"></i> Marquer rendu
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" class="flex-fill" onsubmit="return confirm('<?= $objet['disponible'] ? 'Suspendre' : 'Activer' ?> cet objet ?');">
                                            <input type="hidden" name="action" value="toggle_disponible">
                                            <input type="hidden" name="objet_id" value="<?= $objet['id_objet'] ?>">
                                            <button type="submit" class="btn btn-outline-<?= $objet['disponible'] ? 'warning' : 'success' ?> btn-sm w-100">
                                                <i class="fas fa-<?= $objet['disponible'] ? 'pause' : 'play' ?>"></i> 
                                                <?= $objet['disponible'] ? 'Suspendre' : 'Activer' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm flex-fill" onclick="return confirm('Supprimer définitivement cet objet ?')">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <h5>Vous n'avez pas encore d'objets</h5>
                        <p>Commencez à partager vos objets avec la communauté !</p>
                        <a href="ajouter_objet.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> Ajouter mon premier objet
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>

</body>
</html>
