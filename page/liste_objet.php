<?php
require_once '../inc/connexion.php';
require_once '../inc/image_functions.php';
requireLogin();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'demander_emprunt') {
    $id_objet = intval($_POST['id_objet']);
    
    // miVérifier hoe disponible ve ilay objet
    $check_query = "SELECT o.*, m.nom as proprietaire 
                   FROM objet o 
                   JOIN membre m ON o.id_membre = m.id_membre 
                   WHERE o.id_objet = $id_objet AND o.disponible = 1 
                   AND o.id_membre != " . $_SESSION['user_id'];
    $check_result = mysqli_query(dbconnect(), $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        // miVérifier hoe tsisy  emprunt en cours
        $emprunt_check = "SELECT * FROM emprunt WHERE id_objet = $id_objet AND date_retour IS NULL";
        $emprunt_result = mysqli_query(dbconnect(), $emprunt_check);
        
        if (mysqli_num_rows($emprunt_result) == 0) {
            $insert_emprunt = "INSERT INTO emprunt (id_objet, id_membre, date_emprunt) 
                              VALUES ($id_objet, " . $_SESSION['user_id'] . ", CURDATE())";
            
            if (mysqli_query(dbconnect(), $insert_emprunt)) {
                $success = "Demande d'emprunt enregistrée avec succès !";
            } else {
                $error = "Erreur lors de l'enregistrement de la demande";
            }
        } else {
            $error = "Cet objet est déjà emprunté";
        }
    } else {
        $error = "Objet non disponible ou vous êtes le propriétaire";
    }
}


$categories_query = "SELECT * FROM categorie_objet ORDER BY nom_categorie";
$categories_result = mysqli_query(dbconnect(), $categories_query);

// Filtres
$filter_category = isset($_GET['categorie']) ? intval($_GET['categorie']) : 0;
$search = isset($_GET['search']) ? mysqli_real_escape_string(dbconnect(), $_GET['search']) : '';
$filter_disponible = isset($_GET['disponible']) ? true : false;

// Construction de la clause WHERE
$where_conditions = [];
if ($filter_category > 0) {
    $where_conditions[] = "o.id_categorie = $filter_category";
}
if (!empty($search)) {
    $where_conditions[] = "(o.nom_objet LIKE '%$search%' OR m.nom LIKE '%$search%')";
}
if ($filter_disponible) {
    $where_conditions[] = "o.disponible = 1 AND e.id_emprunt IS NULL";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Requête principale avec images
$query = "
    SELECT 
        o.id_objet,
        o.nom_objet,
        o.description,
        o.etat,
        o.disponible,
        c.nom_categorie,
        m.nom as proprietaire,
        m.ville,
        m.id_membre as id_proprietaire,
        e.id_emprunt,
        e.date_emprunt,
        e.date_retour,
        e.id_membre as id_emprunteur,
        emp.nom as nom_emprunteur,
        (SELECT img.nom_image FROM images_objet img WHERE img.id_objet = o.id_objet ORDER BY img.id_image LIMIT 1) as image_principale
    FROM objet o
    JOIN categorie_objet c ON o.id_categorie = c.id_categorie
    JOIN membre m ON o.id_membre = m.id_membre
    LEFT JOIN emprunt e ON o.id_objet = e.id_objet AND e.date_retour IS NULL
    LEFT JOIN membre emp ON e.id_membre = emp.id_membre
    $where_clause
    ORDER BY c.nom_categorie, o.nom_objet
";

$result = mysqli_query(dbconnect(), $query);

// Statistiques
$stats_query = "
    SELECT 
        COUNT(DISTINCT o.id_objet) as total_objets,
        COUNT(DISTINCT CASE WHEN e.date_retour IS NULL THEN e.id_emprunt END) as emprunts_en_cours,
        COUNT(DISTINCT o.id_membre) as proprietaires
    FROM objet o
    LEFT JOIN emprunt e ON o.id_objet = e.id_objet
";
$stats_result = mysqli_query(dbconnect(), $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des objets - Système d'emprunt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .object-card {
            transition: transform 0.2s;
        }
        .object-card:hover {
            transform: translateY(-2px);
        }
        .borrowed-overlay {
            background: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #ffc107;
        }
        .available-overlay {
            background: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
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
                <a href="mes_objets.php" class="nav-link me-3">
                    <i class="fas fa-box"></i> Mes objets
                </a>
                <a href="mes_emprunts.php" class="nav-link me-3">
                    <i class="fas fa-handshake"></i> Emprunts
                </a>
                <span class="navbar-text me-3">
                    <i class="fas fa-user"></i> Bonjour, <?= htmlspecialchars($_SESSION['user_name']) ?>
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
        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-boxes fa-2x mb-2"></i>
                        <h4><?= $stats['total_objets'] ?></h4>
                        <p class="mb-0">Objets total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-handshake fa-2x mb-2"></i>
                        <h4><?= $stats['emprunts_en_cours'] ?></h4>
                        <p class="mb-0">Emprunts en cours</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h4><?= $stats['proprietaires'] ?></h4>
                        <p class="mb-0">Propriétaires</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card mb-4 shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i> Filtres de recherche</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label fw-bold">Rechercher</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Nom d'objet ou propriétaire..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="categorie" class="form-label fw-bold">Catégorie</label>
                        <select class="form-select" id="categorie" name="categorie">
                            <option value="0">Toutes les catégories</option>
                            <?php 
                            // Réinitialiser le résultat des catégories
                            mysqli_data_seek($categories_result, 0);
                            while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                <option value="<?= $category['id_categorie'] ?>" 
                                        <?= $filter_category == $category['id_categorie'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['nom_categorie']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Disponibilité</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="disponible" name="disponible" 
                                   <?= $filter_disponible ? 'checked' : '' ?>>
                            <label class="form-check-label" for="disponible">
                                <i class="fas fa-check-circle text-success me-1"></i>Disponibles uniquement
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="w-100">
                            <button type="submit" class="btn btn-primary me-2 w-100 mb-2">
                                <i class="fas fa-search me-1"></i> Filtrer
                            </button>
                            <a href="liste_objet.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-redo me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des objets -->
        <div class="row">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($objet = mysqli_fetch_assoc($result)): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card object-card h-100 shadow <?= $objet['id_emprunt'] ? 'borrowed-overlay' : 'available-overlay' ?>">
                            <!-- Image de l'objet -->
                            <div class="position-relative">
                                <?php
                                $image_path = !empty($objet['image_principale']) ? 
                                    '../assets/images/objets/' . $objet['image_principale'] : 
                                    '../assets/images/objets/default.png';
                                if (!file_exists($image_path)) {
                                    $image_path = '../assets/images/objets/default.png';
                                }
                                ?>
                                <img src="<?= $image_path ?>" class="card-img-top" alt="<?= htmlspecialchars($objet['nom_objet']) ?>" 
                                     style="height: 200px; object-fit: cover; cursor: pointer;" 
                                     onclick="window.location.href='detail_objet.php?id=<?= $objet['id_objet'] ?>'">
                                <div class="position-absolute top-0 end-0 m-2">
                                    <?php if ($objet['id_emprunt']): ?>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-handshake"></i> Emprunté
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> Disponible
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span class="badge bg-secondary"><?= htmlspecialchars($objet['nom_categorie']) ?></span>
                                <small class="text-muted">
                                    <i class="fas fa-star me-1"></i><?= htmlspecialchars($objet['etat']) ?>
                                </small>
                            </div>
                            <div class="card-body")
                                <h5 class="card-title">
                                    <i class="fas fa-box"></i> <?= htmlspecialchars($objet['nom_objet']) ?>
                                </h5>
                                
                                <div class="mb-2">
                                    <strong><i class="fas fa-user"></i> Propriétaire :</strong><br>
                                    <?= htmlspecialchars($objet['proprietaire']) ?>
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($objet['ville']) ?>
                                    </small>
                                </div>

                                <?php if ($objet['id_emprunt']): ?>
                                    <div class="alert alert-warning mb-2">
                                        <strong><i class="fas fa-info-circle"></i> Emprunt en cours :</strong><br>
                                        <small>
                                            <i class="fas fa-user"></i> <?= htmlspecialchars($objet['nom_emprunteur']) ?><br>
                                            <i class="fas fa-calendar"></i> Depuis le <?= date('d/m/Y', strtotime($objet['date_emprunt'])) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <?php if (!$objet['id_emprunt'] && $objet['id_proprietaire'] != $_SESSION['user_id']): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="demander_emprunt">
                                        <input type="hidden" name="id_objet" value="<?= $objet['id_objet'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm w-100" 
                                                onclick="return confirm('Voulez-vous vraiment emprunter cet objet ?')">
                                            <i class="fas fa-handshake"></i> Emprunter cet objet
                                        </button>
                                    </form>
                                <?php elseif ($objet['id_proprietaire'] == $_SESSION['user_id']): ?>
                                    <span class="text-muted">
                                        <i class="fas fa-crown"></i> Votre objet
                                    </span>
                                    <a href="mes_objets.php" class="btn btn-outline-primary btn-sm float-end">
                                        <i class="fas fa-edit"></i> Gérer
                                    </a>
                                <?php else: ?>
                                    <span class="text-warning">
                                        <i class="fas fa-clock"></i> Non disponible
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h5>Aucun objet trouvé</h5>
                        <p>Aucun objet ne correspond à vos critères de recherche.</p>
                        <a href="liste_objet.php" class="btn btn-primary">
                            <i class="fas fa-redo"></i> Voir tous les objets
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>
</html>