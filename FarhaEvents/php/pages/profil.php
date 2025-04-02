<?php
    session_start();
    require_once "../config/connectDB.php";

    // Check if user is logged in
    if (!isset($_SESSION["user"]["user_id"])) {
        header("Location: login.php");
        exit();
    }

    $userId = $_SESSION["user"]["user_id"];

    // Fetch user info
    $queryUser = "SELECT nomUser, prenomUser, mailUser FROM utilisateur WHERE idUser = :userId";
    $stmtUser = $pdo->prepare($queryUser);
    $stmtUser->bindParam(":userId", $userId);

    if (!$stmtUser->execute()) {
        die("Erreur: Impossible de récupérer les informations de l'utilisateur.");
    }

    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        die("Erreur: Utilisateur non trouvé.");
    }

    // Update user info if form submitted
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_info"])) {
        $nom = $_POST["nom"];
        $prenom = $_POST["prenom"];
        $email = $_POST["email"];

        $updateQuery = "UPDATE utilisateur SET nomUser = :nom, prenomUser = :prenom, mailUser = :email WHERE idUser = :userId";
        $stmtUpdate = $pdo->prepare($updateQuery);
        $stmtUpdate->bindParam(":nom", $nom);
        $stmtUpdate->bindParam(":prenom", $prenom);
        $stmtUpdate->bindParam(":email", $email);
        $stmtUpdate->bindParam(":userId", $userId);
        
        if ($stmtUpdate->execute()) {
            $user = ["nomUser" => $nom, "prenomUser" => $prenom, "mailUser" => $email]; // Update local data
            $successMessage = "Informations mises à jour avec succès !";
        } else {
            $errorMessage = "Erreur lors de la mise à jour.";
        }
    }

    // Fetch purchase history
    $queryPurchases = "
        SELECT r.idReservation AS invoice_ref, e.dateEvent AS purchase_date, 
            (r.qteBilletsNormal * ev.TariffNormal + r.qteBilletsReduit * ev.TariffReduit) AS total_paid
        FROM reservation r
        JOIN edition e ON r.editionId = e.editionId
        JOIN evenement ev ON e.eventId = ev.eventId
        WHERE r.idUser = :userId
        ORDER BY e.dateEvent DESC";
    $stmtPurchases = $pdo->prepare($queryPurchases);
    $stmtPurchases->bindParam(":userId", $userId);
    $stmtPurchases->execute();
    $purchases = $stmtPurchases->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil Utilisateur</title>
    <link rel="stylesheet" href="../../css/profil.css">
</head>
<body>
    <h1>Mon Profil</h1>

    <!-- Personal Info Section -->
    <h2>Mes Informations Personnelles</h2>
    <?php if (isset($successMessage)) echo "<p style='color: green;'>$successMessage</p>"; ?>
    <?php if (isset($errorMessage)) echo "<p style='color: red;'>$errorMessage</p>"; ?>
    <form method="POST">
        <label>Nom: <input type="text" name="nom" value="<?= htmlspecialchars($user['nomUser']) ?>" required></label><br>
        <label>Prénom: <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenomUser']) ?>" required></label><br>
        <label>Email: <input type="email" name="email" value="<?= htmlspecialchars($user['mailUser']) ?>" required></label><br>
        <button type="submit" name="update_info">Mettre à jour</button>
    </form>

    <!-- Purchase History Section -->
    <h2>Historique de mes Achats</h2>
    <?php if (empty($purchases)): ?>
        <p>Aucun achat effectué pour le moment.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Référence Facture</th>
                    <th>Date d'Achat</th>
                    <th>Total Payé</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($purchases as $purchase): ?>
                    <tr>
                        <td><?= htmlspecialchars($purchase['invoice_ref']) ?></td>
                        <td><?= htmlspecialchars($purchase['purchase_date']) ?></td>
                        <td><?= number_format($purchase['total_paid'], 2) ?> €</td>
                        <td>
                            <a href="view_tickets.php?reservation_id=<?= $purchase['invoice_ref'] ?>" class="button">Voir mes billets</a>
                            <a href="view_invoice.php?reservation_id=<?= $purchase['invoice_ref'] ?>" class="button">Voir ma facture</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>