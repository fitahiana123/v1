<?php
require_once '../inc/connexion.php';
requireLogin();

// Statistiques générales
$stats_query = "
    SELECT 
        COUNT(DISTINCT o.id_objet) as total_objets,
        COUNT(DISTINCT CASE WHEN e.date_retour IS NULL THEN e.id_emprunt END) as emprunts_en_cours,
        COUNT(DISTINCT o.id_membre) as proprietaires,
        COUNT(DISTINCT c.id_categorie) as total_categories
    FROM objet o
    LEFT JOIN emprunt e ON o.id_objet = e.id_objet
    LEFT JOIN categorie_objet c ON o.id_categorie = c.id_categorie
";
$stats_result = mysqli_query(dbconnect(), $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Mes objets
$mes_objets_query = "
    SELECT COUNT(*) as mes_objets 
    FROM objet 
    WHERE id_membre = " . $_SESSION['user_id'];
$mes_objets_result = mysqli_query(dbconnect(), $mes_objets_query);
$mes_objets = mysqli_fetch_assoc($mes_objets_result);

// Mes emprunts en cours
$mes_emprunts_query = "
    SELECT COUNT(*) as mes_emprunts
    FROM emprunt 
    WHERE id_membre = " . $_SESSION['user_id'] . " AND date_retour IS NULL";
$mes_emprunts_result = mysqli_query(dbconnect(), $mes_emprunts_query);
$mes_emprunts = mysqli_fetch_assoc($mes_emprunts_result);

// Objets empruntés de mes objets
$objets_empruntes_query = "
    SELECT COUNT(*) as objets_empruntes
    FROM emprunt e
    JOIN objet o ON e.id_objet = o.id_objet
    WHERE o.id_membre = " . $_SESSION['user_id'] . " AND e.date_retour IS NULL";
$objets_empruntes_result = mysqli_query(dbconnect(), $objets_empruntes_query);
$objets_empruntes = mysqli_fetch_assoc($objets_empruntes_result);

// Derniers emprunts (activité récente)
$activite_query = "
    SELECT 
        o.nom_objet,
        m.nom as proprietaire,
        emp.nom as emprunteur,
        e.date_emprunt,
        e.date_retour
    FROM emprunt e
    JOIN objet o ON e.id_objet = o.id_objet
    JOIN membre m ON o.id_membre = m.id_membre
    JOIN membre emp ON e.id_membre = emp.id_membre
    ORDER BY e.date_emprunt DESC
    LIMIT 5
";
$activite_result = mysqli_query(dbconnect(), $activite_query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Système d'emprunt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .activity-item {
            border-left: 3px solid #007bff;
            padding-left: 15px;
        }
        .category-progress {
            height: 8px;
        }
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
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
                <a href="liste_objet.php" class="nav-link me-3">
                    <i class="fas fa-list"></i> Objets
                </a>
                <a href="mes_objets.php" class="nav-link me-3">
                    <i class="fas fa-box"></i> Mes objets
                </a>
                <a href="mes_emprunts.php" class="nav-link me-3">
                    <i class="fas fa-handshake"></i> Emprunts
                </a>
                <div class="dropdown me-3">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profil.php">
                            <i class="fas fa-user-edit"></i> Mon profil
                        </a></li>
                        <li><a class="dropdown-item" href="historique.php">
                            <i class="fas fa-history"></i> Historique
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Section de bienvenue -->
        <div class="welcome-section text-center">
            <h1 class="display-5 mb-3">
                <i class="fas fa-home"></i> Tableau de bord
            </h1>
            <p class="lead mb-4">
                Bienvenue <?= htmlspecialchars($_SESSION['user_name']) ?> ! 
                Voici un aperçu de votre activité et des statistiques de la plateforme.
            </p>
            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                <a href="liste_objet.php" class="btn btn-light btn-lg me-md-2">
                    <i class="fas fa-search"></i> Parcourir les objets
                </a>
                <a href="ajouter_objet.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-plus"></i> Ajouter un objet
                </a>
            </div>
        </div>

        <!-- Statistiques personnelles -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-box fa-2x mb-3"></i>
                        <h3><?= $mes_objets['mes_objets'] ?></h3>
                        <p class="mb-0">Mes objets</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-warning text-white h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-handshake fa-2x mb-3"></i>
                        <h3><?= $mes_emprunts['mes_emprunts'] ?></h3>
                        <p class="mb-0">Mes emprunts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-info text-white h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-share fa-2x mb-3"></i>
                        <h3><?= $objets_empruntes['objets_empruntes'] ?></h3>
                        <p class="mb-0">Objets prêtés</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card bg-success text-white h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-2x mb-3"></i>
                        <h3><?= $stats['total_objets'] ?></h3>
                        <p class="mb-0">Total objets</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Activité récente -->
            <div class="col-lg-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-clock"></i> Activité récente</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($activite_result) > 0): ?>
                            <?php while ($activite = mysqli_fetch_assoc($activite_result)): ?>
                                <div class="activity-item mb-3 pb-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?= htmlspecialchars($activite['nom_objet']) ?></strong><br>
                                            <small class="text-muted">
                                                <i class="fas fa-user"></i> <?= htmlspecialchars($activite['emprunteur']) ?>
                                                ← <?= htmlspecialchars($activite['proprietaire']) ?>
                                            </small>
                                        </div>
                                        <small class="text-muted">
                                            <?= date('d/m', strtotime($activite['date_emprunt'])) ?>
                                        </small>
                                    </div>
                                    <?php if ($activite['date_retour']): ?>
                                        <span class="badge bg-success mt-1">
                                            <i class="fas fa-check"></i> Rendu
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning mt-1">
                                            <i class="fas fa-clock"></i> En cours
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">
                                <i class="fas fa-info-circle"></i><br>
                                Aucune activité récente
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bolt"></i> Actions rapides</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="liste_objet.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column justify-content-center">
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <span>Parcourir les objets</span>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="ajouter_objet.php" class="btn btn-outline-success w-100 h-100 d-flex flex-column justify-content-center">
                            <i class="fas fa-plus fa-2x mb-2"></i>
                            <span>Ajouter un objet</span>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="mes_emprunts.php" class="btn btn-outline-warning w-100 h-100 d-flex flex-column justify-content-center">
                            <i class="fas fa-handshake fa-2x mb-2"></i>
                            <span>Mes emprunts</span>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="historique.php" class="btn btn-outline-info w-100 h-100 d-flex flex-column justify-content-center">
                            <i class="fas fa-history fa-2x mb-2"></i>
                            <span>Historique</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>
</html>
