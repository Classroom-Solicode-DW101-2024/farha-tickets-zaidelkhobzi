<?php
    if($_SERVER["REQUEST_METHOD"] === "POST") {
        require_once "../config/connectDB.php";

        $nom = $_POST["nom_user"];
        $prenom = $_POST["prenom_user"];
        $email = $_POST["email_user"];
        $password = $_POST["pass_user"];

        $errors = [];

        if(empty($nom)) $errors[] = "Nom is required.";
        if(empty($prenom)) $errors[] = "Prenom is required.";
        if(empty($email)) $errors[] = "Email is required.";
        if(empty($password)) $errors[] = "Password is required.";

        if(!empty($errors)) {
            echo "<pre>";
            print_r($errors);
            echo "</pre>";
        }
        else {
            $query = "INSERT INTO utilisateur (`idUser`, `nomUser`, `prenomUser`, `mailUser`, `motPasse`) VALUES (:idUser, :nomUser, :prenomUser, :mailUser, :motPasse);";
    
            $stmt = $pdo->prepare($query);

            include "../functions/getIdUser.php";

            $id = getLastId();
    
            $stmt->bindParam(":idUser", $id);
            $stmt->bindParam(":nomUser", $nom);
            $stmt->bindParam(":prenomUser", $prenom);
            $stmt->bindParam(":mailUser", $email);
            $stmt->bindParam(":motPasse", $password);
    
            if ($stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                echo "Error: " . $stmt->errorInfo()[2];
            }
        }
    }
?>

<?php
    include "../../views/signup.html";