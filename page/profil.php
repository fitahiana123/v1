<?php
require_once '../inc/connexion.php';
requireLogin();

$error = '';
$success = '';


$user_query = "SELECT * FROM membre WHERE id_membre = " . $_SESSION['user_id'];
$user_result = mysqli_query(dbconnect(), $user_query);
$user = mysqli_fetch_assoc($user_result);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = mysqli_real_escape_string(dbconnect(), $_POST['nom']);
    $email = mysqli_real_escape_string(dbconnect(), $_POST['email']);
    $ville = mysqli_real_escape_string(dbconnect(), $_POST['ville']);
    $genre = $_POST['genre'];
    $nouveau_mdp = $_POST['nouveau_mdp'];
    $confirmer_mdp = $_POST['confirmer_mdp'];
    
    
    if (empty($nom) || empty($email) || empty($ville)) {
        $error = 'Veuillez remplir tous les champs obligatoires';
    } elseif (!empty($nouveau_mdp) && $nouveau_mdp !== $confirmer_mdp) {
        $error = 'Les mots de passe ne correspondent pas';
    } elseif (!empty($nouveau_mdp) && strlen($nouveau_mdp) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères';
    } else {
        
        $check_email = "SELECT id_membre FROM membre WHERE email = '$email' AND id_membre != " . $_SESSION['user_id'];
        $check_result = mysqli_query(dbconnect(), $check_email);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Cet email est déjà utilisé par un autre utilisateur';
        } else {
            // Construction de la requête de mise à jour
            $update_query = "UPDATE membre SET 
                           nom = '$nom', 
                           email = '$email', 
                           ville = '$ville', 
                           genre = '$genre'";
            
            if (!empty($nouveau_mdp)) {
                $update_query .= ", mdp = '$nouveau_mdp'";
            }
            
            $update_query .= " WHERE id_membre = " . $_SESSION['user_id'];
            
            if (mysqli_query(dbconnect(), $update_query)) {
                $success = 'Profil mis à jour avec succès !';
                $_SESSION['user_name'] = $nom;
                $_SESSION['user_email'] = $email;
                
                
                $user_result = mysqli_query(dbconnect(), $user_query);
                $user = mysqli_fetch_assoc($user_result);
            } else {
                $error = 'Erreur lors de la mise à jour : ' . mysqli_error(dbconnect());
            }
        }
    }
}


$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM objet WHERE id_membre = " . $_SESSION['user_id'] . ") as mes_objets,
        (SELECT COUNT(*) FROM emprunt WHERE id_membre = " . $_SESSION['user_id'] . " AND date_retour IS NULL) as mes_emprunts_cours,
        (SELECT COUNT(*) FROM emprunt e JOIN objet o ON e.id_objet = o.id_objet WHERE o.id_membre = " . $_SESSION['user_id'] . " AND e.date_retour IS NULL) as mes_objets_empruntes,
        (SELECT COUNT(*) FROM emprunt WHERE id_membre = " . $_SESSION['user_id'] . ") as total_emprunts_effectues
";
$stats_result = mysqli_query(dbconnect(), $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil - Système d'emprunt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    
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
        <div class="row">
            
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-user-edit"></i> Modifier mon profil</h5>
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
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom complet *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="nom" name="nom" 
                                               required value="<?= htmlspecialchars($user['nom']) ?>">
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               required value="<?= htmlspecialchars($user['email']) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="ville" class="form-label">Ville *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <input type="text" class="form-control" id="ville" name="ville" 
                                               required value="<?= htmlspecialchars($user['ville']) ?>">
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="genre" class="form-label">Genre *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                        <select class="form-control" id="genre" name="genre" required>
                                            <option value="Homme" <?= $user['genre'] === 'Homme' ? 'selected' : '' ?>>Homme</option>
                                            <option value="Femme" <?= $user['genre'] === 'Femme' ? 'selected' : '' ?>>Femme</option>
                                            <option value="Autre" <?= $user['genre'] === 'Autre' ? 'selected' : '' ?>>Autre</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="date_naissance" class="form-label">Date de naissance</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                    <input type="date" class="form-control" id="date_naissance" 
                                           value="<?= $user['date_naissance'] ?>" readonly>
                                </div>
                                <small class="form-text text-muted">La date de naissance ne peut pas être modifiée</small>
                            </div>

                            <hr>
                            <h6><i class="fas fa-key"></i> Changer le mot de passe (optionnel)</h6>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nouveau_mdp" class="form-label">Nouveau mot de passe</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="nouveau_mdp" name="nouveau_mdp">
                                    </div>
                                    <small class="form-text text-muted">Laissez vide pour garder le mot de passe actuel</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="confirmer_mdp" class="form-label">Confirmer le nouveau mot de passe</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirmer_mdp" name="confirmer_mdp">
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Enregistrer les modifications
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Statistiques du profil -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h6><i class="fas fa-chart-pie"></i> Mes statistiques</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px;">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                            <h5><?= htmlspecialchars($user['nom']) ?></h5>
                            <p class="text-muted mb-0">
                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($user['ville']) ?><br>
                                <small>Membre depuis <?= date('F Y', strtotime($user['date_naissance'])) ?></small>
                            </p>
                        </div>

                        <hr>

                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="border rounded p-2">
                                    <h4 class="text-primary mb-0"><?= $stats['mes_objets'] ?></h4>
                                    <small class="text-muted">Objets partagés</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="border rounded p-2">
                                    <h4 class="text-warning mb-0"><?= $stats['mes_emprunts_cours'] ?></h4>
                                    <small class="text-muted">Emprunts en cours</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <h4 class="text-info mb-0"><?= $stats['mes_objets_empruntes'] ?></h4>
                                    <small class="text-muted">Objets prêtés</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <h4 class="text-success mb-0"><?= $stats['total_emprunts_effectues'] ?></h4>
                                    <small class="text-muted">Total emprunts</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h6><i class="fas fa-info-circle"></i> Informations</h6>
                    </div>
                    <div class="card-body">
                        <p class="card-text">
                            <i class="fas fa-shield-alt text-success"></i> Votre profil est sécurisé<br>
                            <i class="fas fa-eye text-info"></i> Visible par les autres membres<br>
                            <i class="fas fa-bell text-warning"></i> Notifications activées
                        </p>
                        <a href="historique.php" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-history"></i> Voir l'historique
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>

</body>
</html>
