<?php
require_once '../inc/connexion.php';
requireLogin();

$error = '';
$success = '';


$categories_query = "SELECT * FROM categorie_objet ORDER BY nom_categorie";
$categories_result = mysqli_query(dbconnect(), $categories_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_objet = mysqli_real_escape_string(dbconnect(), $_POST['nom_objet']);
    $description = mysqli_real_escape_string(dbconnect(), $_POST['description']);
    $id_categorie = intval($_POST['id_categorie']);
    $etat = mysqli_real_escape_string(dbconnect(), $_POST['etat']);
    $disponible = isset($_POST['disponible']) ? 1 : 0;
    
    if (empty($nom_objet) || empty($id_categorie)) {
        $error = 'Veuillez remplir tous les champs obligatoires';
    } else {
        // manisy aniay objet
        $insert_query = "INSERT INTO objet (nom_objet, description, id_categorie, id_membre, etat, disponible, date_ajout) 
                        VALUES ('$nom_objet', '$description', $id_categorie, " . $_SESSION['user_id'] . ", '$etat', $disponible, NOW())";
        
        if (mysqli_query(dbconnect(), $insert_query)) {
            $success = 'Objet ajouté avec succès !';
            
            $_POST = array();
        } else {
            $error = 'Erreur lors de l\'ajout : ' . mysqli_error(dbconnect());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un objet - Système d'emprunt</title>
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
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4><i class="fas fa-plus"></i> Ajouter un nouvel objet</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                                <br><a href="mes_objets.php" class="btn btn-primary btn-sm mt-2">Voir mes objets</a>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="nom_objet" class="form-label">Nom de l'objet *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-box"></i></span>
                                        <input type="text" class="form-control" id="nom_objet" name="nom_objet" 
                                               required value="<?= isset($_POST['nom_objet']) ? htmlspecialchars($_POST['nom_objet']) : '' ?>">
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="id_categorie" class="form-label">Catégorie *</label>
                                    <select class="form-select" id="id_categorie" name="id_categorie" required>
                                        <option value="">Choisir une catégorie</option>
                                        <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                            <option value="<?= $category['id_categorie'] ?>" 
                                                    <?= (isset($_POST['id_categorie']) && $_POST['id_categorie'] == $category['id_categorie']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['nom_categorie']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" 
                                          placeholder="Décrivez votre objet, son état, ses spécificités..."><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="etat" class="form-label">État</label>
                                    <select class="form-select" id="etat" name="etat">
                                        <option value="Excellent" <?= (isset($_POST['etat']) && $_POST['etat'] === 'Excellent') ? 'selected' : '' ?>>Excellent</option>
                                        <option value="Très bon" <?= (isset($_POST['etat']) && $_POST['etat'] === 'Très bon') ? 'selected' : '' ?>>Très bon</option>
                                        <option value="Bon" <?= (isset($_POST['etat']) && $_POST['etat'] === 'Bon') ? 'selected' : 'selected' ?>>Bon</option>
                                        <option value="Acceptable" <?= (isset($_POST['etat']) && $_POST['etat'] === 'Acceptable') ? 'selected' : '' ?>>Acceptable</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Disponibilité</label>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="disponible" name="disponible" 
                                               <?= (!isset($_POST['disponible']) || $_POST['disponible']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="disponible">
                                            <i class="fas fa-check-circle text-success"></i> Disponible pour emprunt
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="images" class="form-label">Images (optionnel)</label>
                                <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                                <small class="form-text text-muted">Vous pouvez sélectionner plusieurs images (JPG, PNG, GIF)</small>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Ajouter l'objet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>
</html>
