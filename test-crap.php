<?php

// URL de la page contenant les données de cotation
$url = 'https://www.boursorama.com/cours/1rPEC/';


// $urlForum = 'https://www.boursorama.com/bourse/forum/1rPEC/detail/464333251/';
// Récupérer le contenu HTML de la page
$html = file_get_contents($url);

// Créer un nouvel objet DOMDocument 
$dom = new DOMDocument();

// Supprimer les avertissements liés à l'analyse HTML
libxml_use_internal_errors(true);

// Créer un objet DOMXPath pour requêter le document HTML
$xpath = new DOMXPath($dom);

// Rechercher les boutons avec la classe spécifiée
$buttons = $xpath->query('//button[contains(@class, "c-quote-chart__menu-button-icon") and contains(@class, "c-icon") and contains(@class, "c-icon--download")]');

 
// Si un bouton de téléchargement est trouvé
if ($buttons->length > 0) {
    // Convertir le noeud DOM en un élément DOM
    $buttonElement = $buttons->item(0);
      
        
    // Extraire l'URL de téléchargement à partir de l'attribut data-url
    $downloadUrl = $buttonElement->getAttribute('data-url'); 

    // Télécharger le fichier CSV
    $csvData = file_get_contents($downloadUrl);

    // Si les données sont récupérées avec succès
    if ($csvData !== false) {
        // Enregistrer les données dans un fichier CSV
        file_put_contents('historique_cotations.csv', $csvData);

        echo "L'historique des cotations a été enregistré dans le fichier historique_cotations.csv.";
    } else {
        echo "Erreur lors du téléchargement des données.";
    }
} else {
    echo "Bouton de téléchargement non trouvé sur la page.";
}



// Paramètres de la base de données
$host = 'localhost';
$dbname = 'boursorama';
$user = 'root';
$password = 'root';

// Créer une connexion à la base de données
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}

// Fonction pour collecter les données à partir de l'URL donnée
function collectData($url) {
    // Récupérer le contenu de la page web
    $html = file_get_contents($url);
    
    // Créer un objet DOM à partir du HTML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Capturer les erreurs générées par loadHTML()
    if (!$dom->loadHTML($html)) { // Vérifier que loadHTML() a réussi
        die('Erreur lors du chargement du HTML');
    }

    libxml_clear_errors(); // Effacer les erreurs capturées
    // Utiliser XPath pour extraire les données pertinentes
    $xpath = new DOMXPath($dom);

    // Obtenir la date/heure de collecte
    $date_heure_collecte = date('Y-m-d H:i:s');


    
     
    // Retourner les données sous forme de tableau associatif
    return array(
        'date_heure_collecte' => $date_heure_collecte,
        'cours' => $cours,
        'cours_ouverture' => $cours_ouverture,
        'cours_haut' => $cours_haut,
        'cours_bas' => $cours_bas,
        'volumes' => $volumes,
        'cours_cloture' => $cours_cloture
    );
}

// Fonction pour extraire les données en utilisant XPath
function extractData($xpath, $query) {
    $results = $xpath->query($query);
    if ($results->length > 0) { // Vérifier que la requête XPath a retourné au moins un résultat
        $element = $results->item(0);
         trim($element->textContent); // Supprimer les espaces blancs en trop
    } else {
        return null;
    }
}


// Fonction pour stocker les données dans la base de données
function collectDataLive($data) {
    global $conn;

    // Préparer la requête SQL
    $sql = "INSERT INTO cours_live (date_heure_collecte, cours, cours_ouverture, cours_haut, cours_bas, volumes)
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

function collectDataFinJournee($data, $id) {
    global $conn;

    // Ajouter la date de collecte au tableau de données
    $data['date_collecte'] = date('Y-m-d', strtotime($data['date_heure_collecte']));

    $sql = "INSERT INTO cours_fin_journee (id, date_collecte, cours_ouverture, cours_haut, cours_bas, cours_cloture, volumes)
            VALUES (:id, :date_collecte, :cours_ouverture, :cours_haut, :cours_bas, :cours_cloture, :volumes)
            ON DUPLICATE KEY UPDATE
            cours_ouverture = :cours_ouverture,
            cours_haut = :cours_haut,
            cours_bas = :cours_bas,
            cours_cloture = :cours_cloture,
            volumes = :volumes;";

    $stmt = $conn->prepare($sql);

    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':date_collecte', $data['date_collecte']);
    $stmt->bindParam(':cours_ouverture', $data['cours_ouverture']);
    $stmt->bindParam(':cours_haut', $data['cours_haut']);
    $stmt->bindParam(':cours_bas', $data['cours_bas']);
    $stmt->bindParam(':cours_cloture', $data['cours_cloture']);
    $stmt->bindParam(':volumes', $data['volumes']);

    $stmt->execute();

    // Valider la transaction
    $conn->commit();
    
    // Ajouter un délai entre chaque collecte de données
    sleep(1);
}


// Fonction pour collecter les messages du forum à partir de l'URL donnée
function collectForumMessages($urlForum) {
    // Récupérer le contenu de la page web
    $html = file_get_contents($urlForum);
    if ($html === false) { // Vérifier si la récupération du HTML a échoué
        die('Erreur lors de la récupération du contenu HTML');
    }


    // Créer un objet DOM à partir du HTML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Capturer les erreurs générées par loadHTML()
    if (!$dom->loadHTML($html)) { // Vérifier que loadHTML() a réussi
        die('Erreur lors du chargement du HTML');
    }

    libxml_clear_errors(); // Effacer les erreurs capturées

    // Créer un objet DOMXPath pour requêter le document HTML
    $xpath = new DOMXPath($dom);


    // Tableau pour stocker les messages collectés
    $collectedMessages = [];

    // Extraire les informations de chaque message
    $messages = $xpath->query("//div[contains(@class, 'c-message')]");

    foreach ($messages as $message) {
        // Extraire l'auteur du message
        $authorNode = $xpath->query(".//div[contains(@class, 'c-profile-card__name')]/button/a", $message)->item(0);
        $author = $authorNode ? $authorNode->textContent : 'Auteur inconnu';

        // Extraire la date du message
        $dateNode = $xpath->query(".//span[contains(@class, 'c-source__time')]", $message)->item(0);
        $date = $dateNode ? $dateNode->textContent : 'Date inconnue';

        // Extraire le contenu du message
        $contentNode = $xpath->query(".//p[contains(@class, 'c-message__text c-message__text--shifted')]", $message)->item(0);
        $content = $contentNode ? $contentNode->textContent : 'Contenu indisponible';

        // Ajouter le message au tableau
        $collectedMessages[] = [
            'date' => $date,
            'author' => $author,
            'content' => $content
        ];
    }
    
    // Retourner les messages collectés sous forme de tableau associatif
    return $collectedMessages;
}

 
// Définir l'URL du forum
$url = "https://www.boursorama.com/bourse/forum/1rPEC/detail/464182461/";

// Collecter les messages du forum
$messages = collectForumMessages($url);
print_r($messages);



// Exemple d'utilisation
$url = 'https://www.boursorama.com/cours/1rPEC/';
$data = collectData($url);
print_r($data);
collectDataLive($data);
collectDataFinJournee($data, 1); // Passez la valeur de l'ID en tant que deuxième paramètre
 
?>
