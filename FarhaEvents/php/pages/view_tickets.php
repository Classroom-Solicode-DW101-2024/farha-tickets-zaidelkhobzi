<?php
    session_start();
    require_once "../config/connectDB.php";

    if (!isset($_GET['reservation_id']) || !isset($_SESSION["user"]["user_id"])) {
        header("Location: profile.php");
        exit();
    }

    $reservationId = $_GET['reservation_id'];
    $userId = $_SESSION["user"]["user_id"];

    // Fetch tickets
    $query = "SELECT b.billetId, b.typeBillet, b.placeNum, e.eventTitle, ed.dateEvent
            FROM billet b
            JOIN reservation r ON b.idReservation = r.idReservation
            JOIN edition ed ON r.editionId = ed.editionId
            JOIN evenement e ON ed.eventId = e.eventId
            WHERE b.idReservation = :reservationId AND r.idUser = :userId";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":reservationId", $reservationId);
    $stmt->bindParam(":userId", $userId);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tickets)) {
        echo "Aucun billet trouvé ou accès non autorisé.";
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Billets</title>
    <link rel="stylesheet" href="../../css/view_tickets.css">
</head>
<body>
    <h1>My Tickets</h1>
    <ul>
        <?php foreach ($tickets as $ticket): ?>
            <li>
                Événement: <?= htmlspecialchars($ticket['eventTitle']) ?><br>
                Date: <?= htmlspecialchars($ticket['dateEvent']) ?><br>
                Type: <?= htmlspecialchars($ticket['typeBillet']) ?><br>
                Numéro de place: <?= htmlspecialchars($ticket['placeNum']) ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <a href="profil.php">Retour au profil</a>
</body>
</html>