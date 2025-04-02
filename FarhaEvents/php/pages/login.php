<?php
    session_start();

    if($_SERVER["REQUEST_METHOD"] === "POST") {
        require_once "../config/connectDB.php";

        $email = $_POST["email_user"];
        $password = $_POST["pass_user"];

        $errors = [];

        if(empty($email)) $errors[] = "Email is required.";
        if(empty($password)) $errors[] = "Password is required.";

        if(!empty($errors)) {
            echo "<pre>";
            print_r($errors);
            echo "</pre>";
        }
        else {
            $query = "SELECT * FROM `utilisateur` WHERE mailUser = :mailUser AND motPasse = :motPasse";
    
            $stmt = $pdo->prepare($query);

            $stmt->bindParam(":mailUser", $email);
            $stmt->bindParam(":motPasse", $password);

            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if($user) {
                if(!isset($_SESSION["client"])) {
                    $_SESSION["user"] = [];
                }
                $_SESSION["user"]["user_id"] = $user["idUser"];

                if (!isset($_SESSION["user"]["tickets"])) {
                    $_SESSION["user"]["tickets"] = [];
                }

                session_regenerate_id(true);

                header("Location: accueil.php");
                exit();
            }
            else {
                echo "User not found!";
            }
        }
    }
?>

<?php
    include "../../views/login.html";