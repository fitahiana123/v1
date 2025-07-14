<?php
require_once '../inc/connexion.php';
require_once '../inc/image_functions.php';
requireLogin();

$objet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($objet_id <= 0) {
    header('Location: liste_objet.php');
    exit;
}

// Récupération des détails de l'objet
$objet_query = "
    SELECT 
        o.*,
        c.nom_categorie,
        m.nom as proprietaire,
        m.email as email_proprietaire,
        m.ville,
        m.id_membre as id_proprietaire
    FROM objet o
    JOIN categorie_objet c ON o.id_categorie = c.id_categorie
    JOIN membre m ON o.id_membre = m.id_membre
    WHERE o.id_objet = $objet_id
";

$objet_result = mysqli_query(dbconnect(), $objet_query);

if (!$objet_result || mysqli_num_rows($objet_result) === 0) {
    header('Location: liste_objet.php');
    exit;
}

$objet = mysqli_fetch_assoc($objet_result);

// Récupération des images
$images = getObjetImages($objet_id);

// Historique des emprunts
$historique_query = "
    SELECT 
        e.date_emprunt,
        e.date_retour,
        m.nom as emprunteur,
        m.email as email_emprunteur
    FROM emprunt e
    JOIN membre m ON e.id_membre = m.id_membre
    WHERE e.id_objet = $objet_id
    ORDER BY e.date_emprunt DESC
";
$historique_result = mysqli_query(dbconnect(), $historique_query);

// Vérifier si l'objet est actuellement emprunté
$emprunt_actuel_query = "
    SELECT e.*, m.nom as emprunteur, m.email as email_emprunteur
    FROM emprunt e
    JOIN membre m ON e.id_membre = m.id_membre
    WHERE e.id_objet = $objet_id AND e.date_retour IS NULL
";
$emprunt_actuel_result = mysqli_query(dbconnect(), $emprunt_actuel_query);
$emprunt_actuel = mysqli_num_rows($emprunt_actuel_result) > 0 ? mysqli_fetch_assoc($emprunt_actuel_result) : null;

// Gestion de la demande d'emprunt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'demander_emprunt') {
    if (!$emprunt_actuel && $objet['disponible'] && $objet['id_proprietaire'] != $_SESSION['user_id']) {
        $insert_emprunt = "INSERT INTO emprunt (id_objet, id_membre, date_emprunt) 
                          VALUES ($objet_id, " . $_SESSION['user_id'] . ", CURDATE())";
        
        if (mysqli_query(dbconnect(), $insert_emprunt)) {
            $_SESSION['success'] = "Demande d'emprunt enregistrée avec succès !";
            header("Location: detail_objet.php?id=$objet_id");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($objet['nom_objet']) ?> - Système d'emprunt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .bg-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .gallery-img {
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .gallery-img:hover {
            transform: scale(1.1);
        }
        .main-image {
            height: 400px;
            object-fit: cover;
            border-radius: 15px;
        }
        .status-badge {
            font-size: 1.1em;
            padding: 0.5rem 1rem;
        }
    </style>
</head>
<body class="bg-gradient">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-exchange-alt me-2"></i> Système d'emprunt
            </a>
            <div class="navbar-nav ms-auto">
                <a href="dashboard.php" class="nav-link me-3">
                    <i class="fas fa-home me-1"></i> Accueil
                </a>
                <a href="liste_objet.php" class="nav-link me-3">
                    <i class="fas fa-list me-1"></i> Liste des objets
                </a>
                <a href="mes_objets.php" class="nav-link me-3">
                    <i class="fas fa-box me-1"></i> Mes objets
                </a>
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Colonne gauche - Images -->
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <!-- Image principale -->
                        <?php 
                        $main_image = !empty($images) ? $images[0]['nom_image'] : 'default.png';
                        $main_image_path = getImagePath($main_image);
                        ?>
                        <img id="mainImage" src="<?= $main_image_path ?>" class="w-100 main-image mb-3" 
                             alt="<?= htmlspecialchars($objet['nom_objet']) ?>">
                        
                        <!-- Galerie d'images -->
                        <?php if (count($images) > 1): ?>
                            <div class="row g-2">
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="col-3">
                                        <img src="<?= getImagePath($image['nom_image']) ?>" 
                                             class="w-100 gallery-img" 
                                             alt="Image <?= $index + 1 ?>"
                                             onclick="changeMainImage('<?= getImagePath($image['nom_image']) ?>')">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Colonne droite - Détails -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-box me-2"></i><?= htmlspecialchars($objet['nom_objet']) ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Statut -->
                        <div class="mb-3">
                            <?php if ($emprunt_actuel): ?>
                                <span class="badge bg-warning status-badge">
                                    <i class="fas fa-handshake me-1"></i>Emprunté
                                </span>
                            <?php elseif ($objet['disponible']): ?>
                                <span class="badge bg-success status-badge">
                                    <i class="fas fa-check me-1"></i>Disponible
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger status-badge">
                                    <i class="fas fa-times me-1"></i>Non disponible
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Informations de l'objet -->
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Catégorie :</strong></div>
                            <div class="col-sm-8">
                                <span class="badge bg-secondary"><?= htmlspecialchars($objet['nom_categorie']) ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>État :</strong></div>
                            <div class="col-sm-8">
                                <i class="fas fa-star text-warning me-1"></i><?= htmlspecialchars($objet['etat']) ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong>Ajouté le :</strong></div>
                            <div class="col-sm-8"><?= date('d/m/Y', strtotime($objet['date_ajout'])) ?></div>
                        </div>

                        <?php if (!empty($objet['description'])): ?>
                            <div class="mb-3">
                                <strong>Description :</strong>
                                <p class="mt-2"><?= nl2br(htmlspecialchars($objet['description'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Propriétaire -->
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6><i class="fas fa-user me-2"></i>Propriétaire</h6>
                                <p class="mb-1"><strong><?= htmlspecialchars($objet['proprietaire']) ?></strong></p>
                                <p class="mb-1"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($objet['ville']) ?></p>
                                <p class="mb-0"><i class="fas fa-envelope me-1"></i><?= htmlspecialchars($objet['email_proprietaire']) ?></p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-3">
                            <?php if ($objet['id_proprietaire'] == $_SESSION['user_id']): ?>
                                <a href="mes_objets.php" class="btn btn-outline-primary">
                                    <i class="fas fa-edit me-1"></i>Gérer cet objet
                                </a>
                            <?php elseif (!$emprunt_actuel && $objet['disponible']): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="demander_emprunt">
                                    <button type="submit" class="btn btn-success" 
                                            onclick="return confirm('Voulez-vous vraiment emprunter cet objet ?')">
                                        <i class="fas fa-handshake me-1"></i>Emprunter cet objet
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <a href="liste_objet.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historique des emprunts -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historique des emprunts</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($historique_result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Emprunteur</th>
                                            <th>Date d'emprunt</th>
                                            <th>Date de retour</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($emprunt = mysqli_fetch_assoc($historique_result)): ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-user me-1"></i>
                                                    <?= htmlspecialchars($emprunt['emprunteur']) ?>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?></td>
                                                <td>
                                                    <?= $emprunt['date_retour'] ? date('d/m/Y', strtotime($emprunt['date_retour'])) : '-' ?>
                                                </td>
                                                <td>
                                                    <?php if ($emprunt['date_retour']): ?>
                                                        <span class="badge bg-success">Rendu</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">En cours</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-info-circle fa-3x mb-3"></i>
                                <p>Aucun emprunt pour cet objet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;
        }
    </script>
</body>
</html>
