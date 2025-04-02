<?php
    session_start();
    require_once("../config/connectDB.php");

    if (!isset($_SESSION["user"])) {
        echo "<p>Aucun billet trouvé ou utilisateur non connecté.</p>";
        exit;
    }

    $userId = $_SESSION["user"]["user_id"];

    $query = "
        SELECT 
            b.billetId,
            b.typeBillet AS ticketType,
            b.placeNum AS quantity,
            ev.eventTitle,
            ed.dateEvent,
            ed.timeEvent,
            ed.NumSalle AS numSalle,
            CASE 
                WHEN b.typeBillet = 'Normal' THEN ev.TariffNormal 
                ELSE ev.TariffReduit 
            END AS ticketPrice
        FROM 
            billet b
        JOIN 
            reservation r ON b.idReservation = r.idReservation
        JOIN 
            edition ed ON r.editionId = ed.editionId
        JOIN 
            evenement ev ON ed.eventId = ev.eventId
        WHERE 
            r.idUser = :idUser
    ";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":idUser", $userId);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tickets)) {
        echo "<p>Aucun billet trouvé pour cet utilisateur.</p>";
        exit;
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Tickets</title>
    <link rel="stylesheet" href="../../css/ticket.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header>
        <a href="./accueil.php" style="text-decoration: none;"><h1 class="logo">farhaEvents</h1></a>
        <nav>
            <ul>
                <a href="./accueil.php"><li>Accueil</li></a>
                <a href="./profil.php"><li>Mon profile</li></a>
                <a href="./login.php"><li>Se connecter</li></a>
            </ul>
        </nav>
    </header>
    <div class="tickets">
        <?php foreach ($tickets as $ticket) : ?>
            <div class="ticket">
                <div class="left-section">
                    <p style="font-weight: bold;">#<?= htmlspecialchars($ticket["billetId"]) ?></p>
                    <p>Numéro de ticket</p>
                </div>
                <div class="middle-section">
                    <h1><?= htmlspecialchars($ticket["eventTitle"]) ?></h1>
                    <p style="background: #F6F0EB; width: fit-content; padding: 15px;">
                        <strong><?= htmlspecialchars($ticket["dateEvent"]) ?> à <?= htmlspecialchars($ticket["timeEvent"]) ?></strong>
                    </p>
                    <p class="association"><strong>ASSOCIATION FARHA</strong></p>
                    <p style="display: inline;">Tarif: <strong style="font-size: 1.2rem"><?= htmlspecialchars($ticket["ticketPrice"]) ?> MAD</strong></p>
                    <p style="display: inline; margin-left: 20px">Type: <strong style="font-size: 1.2rem"><?= htmlspecialchars($ticket["ticketType"]) ?></strong></p>
                    <p>Adresse: <strong>Centre Culturel Farha, Tanger</strong></p>
                </div>
                <div class="right-section">
                    <div class="barcode"></div>
                    <div class="details">
                        <p>SALLE</p>
                        <h2><?= htmlspecialchars($ticket["numSalle"]) ?></h2>
                        <p style="margin-top: 50px;">PLACE</p>
                        <h2><?= htmlspecialchars($ticket["quantity"]) ?></h2>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>