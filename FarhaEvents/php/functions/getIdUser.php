<?php
    function getLastId() {
        global $pdo;
    
        $sql = "SELECT MAX(idUser) AS MaxId FROM utilisateur";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row["MaxId"] ? $row["MaxId"] + 1 : 1;
    }
?>