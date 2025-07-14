<?php
// Fonctions utilitaires pour la gestion des images

function uploadImages($objet_id, $files) {
    $upload_dir = '../assets/images/objets/';
    $uploaded_files = [];
    $errors = [];
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    if (isset($files['images']) && is_array($files['images']['name'])) {
        for ($i = 0; $i < count($files['images']['name']); $i++) {
            if ($files['images']['error'][$i] == UPLOAD_ERR_OK) {
                $tmp_name = $files['images']['tmp_name'][$i];
                $name = $files['images']['name'][$i];
                
                // Générer un nom unique
                $extension = pathinfo($name, PATHINFO_EXTENSION);
                $new_name = 'objet_' . $objet_id . '_' . time() . '_' . $i . '.' . $extension;
                $upload_path = $upload_dir . $new_name;
                
                // Vérifier le type de fichier
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array(strtolower($extension), $allowed_types)) {
                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        // Insérer en base de données
                        $insert_image = "INSERT INTO images_objet (id_objet, nom_image) VALUES ($objet_id, '$new_name')";
                        if (mysqli_query(dbconnect(), $insert_image)) {
                            $uploaded_files[] = $new_name;
                        } else {
                            $errors[] = "Erreur BDD pour $name";
                        }
                    } else {
                        $errors[] = "Erreur upload pour $name";
                    }
                } else {
                    $errors[] = "Type de fichier non autorisé pour $name";
                }
            }
        }
    }
    
    return ['uploaded' => $uploaded_files, 'errors' => $errors];
}

function getObjetImages($objet_id) {
    $query = "SELECT * FROM images_objet WHERE id_objet = $objet_id ORDER BY id_image";
    $result = mysqli_query(dbconnect(), $query);
    $images = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $images[] = $row;
    }
    
    return $images;
}

function getImagePath($image_name) {
    if (empty($image_name) || !file_exists('../assets/images/objets/' . $image_name)) {
        return '../assets/images/objets/default.png';
    }
    return '../assets/images/objets/' . $image_name;
}

function deleteImage($image_id, $objet_id) {
    // Vérifier que l'image appartient à l'objet
    $check_query = "SELECT nom_image FROM images_objet WHERE id_image = $image_id";
    $check_result = mysqli_query(dbconnect(), $check_query);
    
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $image = mysqli_fetch_assoc($check_result);
        $image_path = '../assets/images/objets/' . $image['nom_image'];
        
        // Supprimer le fichier
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        
        // Supprimer de la base
        $delete_query = "DELETE FROM images_objet WHERE id_image = $image_id";
        return mysqli_query(dbconnect(), $delete_query);
    }
    
    return false;
}
?>
