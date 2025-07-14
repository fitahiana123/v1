<?php
require_once '../inc/connexion.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = mysqli_real_escape_string(dbconnect(), $_POST['nom']);
    $date_naissance = $_POST['date_naissance'];
    $genre = $_POST['genre'];
    $email = mysqli_real_escape_string(dbconnect(), $_POST['email']);
    $ville = mysqli_real_escape_string(dbconnect(), $_POST['ville']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($nom) || empty($date_naissance) || empty($genre) || empty($email) || empty($ville) || empty($password)) {
        $error = 'Veuillez remplir tous les champs obligatoires';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères';
    } else {
        // Vérifier si l'email existe déjà
        $check_query = "SELECT id_membre FROM membre WHERE email = '$email'";
        $check_result = mysqli_query(dbconnect(), $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Cet email est déjà utilisé';
        } else {
            // manao insertion anah membre vaovao 
            $hashed_password = $password; 
            
            $insert_query = "INSERT INTO membre (nom, date_naissance, genre, email, ville, mdp) 
                           VALUES ('$nom', '$date_naissance', '$genre', '$email', '$ville', '$hashed_password')";
            
            if (mysqli_query(dbconnect(), $insert_query)) {
                $success = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
            } else {
                $error = 'Erreur lors de l\'inscription : ' . mysqli_error(dbconnect());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Système d'emprunt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-4">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-success text-white text-center">
                        <h4><i class="fas fa-user-plus"></i> Inscription</h4>
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
                                <br><a href="login.php" class="btn btn-primary btn-sm mt-2">Se connecter</a>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom complet *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="nom" name="nom" required 
                                               value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>">
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="date_naissance" class="form-label">Date de naissance *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                        <input type="date" class="form-control" id="date_naissance" name="date_naissance" required
                                               value="<?= isset($_POST['date_naissance']) ? $_POST['date_naissance'] : '' ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="genre" class="form-label">Genre *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                        <select class="form-control" id="genre" name="genre" required>
                                            <option value="">Sélectionner...</option>
                                            <option value="Homme" <?= (isset($_POST['genre']) && $_POST['genre'] === 'Homme') ? 'selected' : '' ?>>Homme</option>
                                            <option value="Femme" <?= (isset($_POST['genre']) && $_POST['genre'] === 'Femme') ? 'selected' : '' ?>>Femme</option>
                                            <option value="Autre" <?= (isset($_POST['genre']) && $_POST['genre'] === 'Autre') ? 'selected' : '' ?>>Autre</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="ville" class="form-label">Ville *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <input type="text" class="form-control" id="ville" name="ville" required
                                               value="<?= isset($_POST['ville']) ? htmlspecialchars($_POST['ville']) : '' ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Mot de passe *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                    </div>
                                    <small class="form-text text-muted">Minimum 6 caractères</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success w-100 mb-3">
                                <i class="fas fa-user-plus"></i> S'inscrire
                            </button>
                        </form>

                        <div class="text-center">
                            <p class="mb-0">Déjà un compte ?</p>
                            <a href="login.php" class="btn btn-outline-primary">
                                <i class="fas fa-sign-in-alt"></i> Se connecter
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>
</html>