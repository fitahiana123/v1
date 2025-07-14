<?php
require_once '../inc/connexion.php';
requireLogin();

// mivérifié hoe connécter ve ilay utilisateur 
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $id_objet = intval($_POST['id_objet']);
    
    if ($action === 'demander_emprunt') {
        // Vérifier que l'objet est disponible
        $check_query = "SELECT o.*, m.nom as proprietaire, m.email as email_proprietaire 
                       FROM objet o 
                       JOIN membre m ON o.id_membre = m.id_membre 
                       WHERE o.id_objet = $id_objet AND o.disponible = 1 
                       AND o.id_membre != $user_id";
        $check_result = mysqli_query(dbconnect(), $check_query);
        
        if (!$check_result) {
            $error = "Erreur lors de la vérification de l'objet : " . mysqli_error(dbconnect());
        } elseif (mysqli_num_rows($check_result) > 0) {
            // manao vérification hoe tsisy empreint en cours 
            $emprunt_check = "SELECT * FROM emprunt WHERE id_objet = $id_objet AND date_retour IS NULL";
            $emprunt_result = mysqli_query(dbconnect(), $emprunt_check);
            
            if (!$emprunt_result) {
                $error = "Erreur lors de la vérification de l'emprunt : " . mysqli_error(dbconnect());
            } elseif (mysqli_num_rows($emprunt_result) == 0) {
                $insert_emprunt = "INSERT INTO emprunt (id_objet, id_membre, date_emprunt) 
                                  VALUES ($id_objet, $user_id, CURDATE())";
                
                if (mysqli_query(dbconnect(), $insert_emprunt)) {
                    $success = "Demande d'emprunt enregistrée avec succès !";
                } else {
                    $error = "Erreur lors de l'enregistrement de la demande : " . mysqli_error(dbconnect());
                }
            } else {
                $error = "Cet objet est déjà emprunté";
            }
        } else {
            $error = "Objet non disponible";
        }
    }
}


$mes_emprunts_query = "
    SELECT 
        e.id_emprunt,
        e.date_emprunt,
        o.nom_objet,
        o.description,
        c.nom_categorie,
        m.nom as proprietaire,
        m.email as email_proprietaire,
        m.ville
    FROM emprunt e
    JOIN objet o ON e.id_objet = o.id_objet
    JOIN categorie_objet c ON o.id_categorie = c.id_categorie
    JOIN membre m ON o.id_membre = m.id_membre
    WHERE e.id_membre = $user_id AND e.date_retour IS NULL
    ORDER BY e.date_emprunt DESC
";
$mes_emprunts_result = mysqli_query(dbconnect(), $mes_emprunts_query);

if (!$mes_emprunts_result) {
    die('Erreur dans la requête mes emprunts : ' . mysqli_error(dbconnect()));
}


$emprunts_de_mes_objets_query = "
    SELECT 
        e.id_emprunt,
        e.date_emprunt,
        o.nom_objet,
        o.description,
        c.nom_categorie,
        m.nom as emprunteur,
        m.email as email_emprunteur,
        m.ville as ville_emprunteur
    FROM emprunt e
    JOIN objet o ON e.id_objet = o.id_objet
    JOIN categorie_objet c ON o.id_categorie = c.id_categorie
    JOIN membre m ON e.id_membre = m.id_membre
    WHERE o.id_membre = $user_id AND e.date_retour IS NULL
    ORDER BY e.date_emprunt DESC
";
$emprunts_de_mes_objets_result = mysqli_query(dbconnect(), $emprunts_de_mes_objets_query);

if (!$emprunts_de_mes_objets_result) {
    die('Erreur dans la requête emprunts de mes objets : ' . mysqli_error(dbconnect()));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes emprunts - Système d'emprunt</title>
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
                    <i class="fas fa-list"></i> Liste des objets
                </a>
                <a href="mes_objets.php" class="nav-link me-3">
                    <i class="fas fa-box"></i> Mes objets
                </a>
                <span class="navbar-text me-3">
                    <i class="fas fa-user"></i> <?= htmlspecialchars(isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Utilisateur') ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Mes emprunts -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5><i class="fas fa-handshake"></i> Mes emprunts en cours</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($mes_emprunts_result) > 0): ?>
                            <?php while ($emprunt = mysqli_fetch_assoc($mes_emprunts_result)): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title">
                                                    <i class="fas fa-box"></i> <?= htmlspecialchars($emprunt['nom_objet']) ?>
                                                </h6>
                                                <p class="card-text">
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($emprunt['nom_categorie']) ?></span><br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-user"></i> Propriétaire : <?= htmlspecialchars($emprunt['proprietaire']) ?><br>
                                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($emprunt['ville']) ?><br>
                                                        <i class="fas fa-calendar"></i> Emprunté le <?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?>
                                                    </small>
                                                </p>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="mailto:<?= $emprunt['email_proprietaire'] ?>">
                                                        <i class="fas fa-envelope"></i> Contacter
                                                    </a></li>
                                                    <li><a class="dropdown-item text-success" href="#">
                                                        <i class="fas fa-undo"></i> Signaler retour
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-info-circle fa-2x mb-3"></i>
                                <p>Vous n'avez aucun emprunt en cours</p>
                                <a href="liste_objet.php" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Parcourir les objets
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Emprunts de mes objets -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-share"></i> Mes objets empruntés</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($emprunts_de_mes_objets_result) > 0): ?>
                            <?php while ($emprunt = mysqli_fetch_assoc($emprunts_de_mes_objets_result)): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="card-title">
                                                    <i class="fas fa-box"></i> <?= htmlspecialchars($emprunt['nom_objet']) ?>
                                                </h6>
                                                <p class="card-text">
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($emprunt['nom_categorie']) ?></span><br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-user"></i> Emprunteur : <?= htmlspecialchars($emprunt['emprunteur']) ?><br>
                                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($emprunt['ville_emprunteur']) ?><br>
                                                        <i class="fas fa-calendar"></i> Depuis le <?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?>
                                                    </small>
                                                </p>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="mailto:<?= $emprunt['email_emprunteur'] ?>">
                                                        <i class="fas fa-envelope"></i> Contacter
                                                    </a></li>
                                                    <li><a class="dropdown-item text-success" href="#">
                                                        <i class="fas fa-check"></i> Confirmer retour
                                                    </a></li>
                                                    <li><a class="dropdown-item text-warning" href="#">
                                                        <i class="fas fa-exclamation"></i> Signaler problème
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-info-circle fa-2x mb-3"></i>
                                <p>Aucun de vos objets n'est actuellement emprunté</p>
                                <a href="ajouter_objet.php" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Ajouter un objet
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>

</body>
</html>
