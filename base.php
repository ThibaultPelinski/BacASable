<?php

// Paramètres de la base de données
$host = 'localhost';
$dbname = 'boursorama'; 
$user = 'root';
$password = '';

// Créer une connexion à la base de données
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $password); 
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}

// Fonction pour stocker les données dans la base de données
function storeData($data) {
    global $conn;

    // Préparer la requête SQL
    $sql = "INSERT INTO cours (date_heure_collecte, cours, cours_ouverture, cours_haut, cours_bas, volumes)
            VALUES (:date_heure_collecte, :cours, :cours_ouverture, :cours_haut, :cours_bas, :volumes)";
    $stmt = $conn->prepare($sql);

    // Lier les paramètres à la requête SQL
    $stmt->bindParam(':date_heure_collecte', $data['date_heure_collecte']);
    $stmt->bindParam(':cours', $data['cours']);
    $stmt->bindParam(':cours_ouverture', $data['cours_ouverture']);
    $stmt->bindParam(':cours_haut', $data['cours_haut']);
    $stmt->bindParam(':cours_bas', $data['cours_bas']);
    $stmt->bindParam(':volumes', $data['volumes']);

    // Exécuter la requête SQL
    $stmt->execute();
}

$url = 'https://www.boursorama.com/cours/1rPMC/';
$data = collectData($url);
storeData($data);

?>