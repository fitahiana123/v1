<?php
require_once '../inc/connexion.php';

// raha connecté ilay utilisateur dia alefa any amin'ny dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Système d'emprunt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    
    <section class="hero-section text-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h1 class="display-4 mb-4">
                        <i class="fas fa-exchange-alt"></i> Système d'emprunt d'objets
                    </h1>
                    <p class="lead mb-5">
                        Partagez vos objets avec votre communauté et empruntez ce dont vous avez besoin.
                        Simple, efficace et convivial !
                    </p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="login.php" class="btn btn-light btn-lg me-md-2">
                            <i class="fas fa-sign-in-alt"></i> Se connecter
                        </a>
                        <a href="inscription.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-user-plus"></i> S'inscrire
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col">
                    <h2 class="display-6">Fonctionnalités</h2>
                    <p class="text-muted">Découvrez tout ce que notre plateforme peut vous offrir</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 border-0 shadow">
                        <div class="card-body text-center p-4">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-boxes fa-lg"></i>
                            </div>
                            <h5>Catalogue d'objets</h5>
                            <p class="text-muted">Explorez une large gamme d'objets disponibles dans différentes catégories : bricolage, cuisine, esthétique, mécanique.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 border-0 shadow">
                        <div class="card-body text-center p-4">
                            <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-filter fa-lg"></i>
                            </div>
                            <h5>Filtres avancés</h5>
                            <p class="text-muted">Trouvez rapidement ce que vous cherchez grâce à nos filtres par catégorie et notre fonction de recherche.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 border-0 shadow">
                        <div class="card-body text-center p-4">
                            <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-handshake fa-lg"></i>
                            </div>
                            <h5>Gestion des emprunts</h5>
                            <p class="text-muted">Suivez en temps réel les emprunts en cours avec les dates de début et les informations sur les emprunteurs.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <section class="bg-light py-5">
        <div class="container text-center">
            <h3 class="mb-4">Prêt à commencer ?</h3>
            <p class="lead mb-4">Rejoignez notre communauté et commencez à partager dès aujourd'hui !</p>
            <a href="inscription.php" class="btn btn-primary btn-lg">
                <i class="fas fa-rocket"></i> Créer mon compte
            </a>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
</body>
</html>