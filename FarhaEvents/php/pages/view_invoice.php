<?php
    session_start();
    require_once "../config/connectDB.php";

    if (!isset($_GET['reservation_id']) || !isset($_SESSION["user"]["user_id"])) {
        header("Location: profile.php");
        exit();
    }

    $reservationId = $_GET['reservation_id'];
    $userId = $_SESSION["user"]["user_id"];

    $query = "SELECT r.idReservation, e.eventTitle, ed.dateEvent, (r.qteBilletsNormal * ev.TariffNormal + r.qteBilletsReduit * ev.TariffReduit) AS total
        FROM reservation r
        JOIN edition ed ON r.editionId = ed.editionId
        JOIN evenement e ON ed.eventId = e.eventId
        JOIN evenement ev ON ed.eventId = ev.eventId
        WHERE r.idReservation = :reservationId AND r.idUser = :userId";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":reservationId", $reservationId);
    $stmt->bindParam(":userId", $userId);
    $stmt->execute();
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        echo "Facture non trouvée ou accès non autorisé.";
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bill</title>
    <link rel="stylesheet" href="../../css/view_invoice.css">
</head>
<body>
    <h1>Facture #<?= htmlspecialchars($invoice['idReservation']) ?></h1>
    <p>Événement: <?= htmlspecialchars($invoice['eventTitle']) ?></p>
    <p>Date: <?= htmlspecialchars($invoice['dateEvent']) ?></p>
    <p>Total payé: <?= number_format($invoice['total'], 2) ?> €</p>
    <a href="profil.php">Retour au profil</a>
</body>
</html>