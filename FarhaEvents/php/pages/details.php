    <?php
        session_start();
        require_once "../config/connectDB.php";

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (isset($_POST["eventId"])) {
                $eventId = $_POST["eventId"];

                $query = "SELECT evenement.eventId, evenement.eventTitle, evenement.eventDescription, edition.NumSalle, evenement.TariffNormal, evenement.TariffReduit, edition.editionId, edition.NumSalle, edition.image, edition.dateEvent, edition.timeEvent
                    FROM evenement
                    LEFT JOIN edition ON evenement.eventId = edition.eventId
                    WHERE evenement.eventId = :eventId";

                $stmt = $pdo->prepare($query);

                $stmt->bindParam(":eventId", $eventId);

                $stmt->execute();

                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (isset($_POST["valider"])) {
                    $purchase = [
                        'eventId' => $_POST["eventId"],
                        'dateEvent' => $_POST["dateEvent"],
                        'timeEvent' => $_POST["timeEvent"],
                        'ticketType' => $_POST["ticketType"],
                        'ticketPrice' => $_POST["ticketPrice"],
                        'quantity' => $_POST["quantity"],
                        'numSalle' => $_POST["numSalle"],
                        'total' => $_POST["quantity"] * $_POST["ticketPrice"]
                    ];
                    
                    if (!isset($_SESSION["user"]["tickets"])) {
                        $_SESSION["user"]["tickets"] = [];
                    }
                    $_SESSION["user"]["tickets"][] = $purchase;

                    try {
                        !isset($_SESSION["user"]["user_id"]) 
                            ? header("Location: login.php") 
                            : $idUser = $_SESSION["user"]["user_id"];
                    
                        $ticketType = $_POST["ticketType"] ?? null;
                        if (isset($_GET['success']) && $_GET['success'] == 1 && !empty($_SESSION["user"]["tickets"])) {
                            $latestPurchase = end($_SESSION["user"]["tickets"]);
                            $ticketType = $latestPurchase['ticketType'];
                            $quantity = $latestPurchase["quantity"];
                        } 
                        else {
                            $ticketType = $_POST["ticketType"];
                            $quantity = (int)$_POST["quantity"];
                        }

                        $qteBilletsNormal = ($ticketType === "Type normal") ? $quantity : 0;
                        $qteBilletsReduit = ($ticketType === "Type réduit") ? $quantity : 0;
                    
                        $queryCheck = "SELECT (salle.capSalle - COALESCE(SUM(reservation.qteBilletsNormal + reservation.qteBilletsReduit), 0)) AS availableCapacity
                        FROM edition
                        LEFT JOIN salle ON edition.NumSalle = salle.NumSalle
                        LEFT JOIN reservation ON edition.editionId = reservation.editionId
                        WHERE edition.eventId = :eventId
                        GROUP BY edition.eventId, edition.NumSalle, salle.capSalle";
                    
                        $stmtCheck = $pdo->prepare($queryCheck);
                        $stmtCheck->bindParam(":eventId", $eventId);
                        $stmtCheck->execute();
                        $available = $stmtCheck->fetch(PDO::FETCH_ASSOC)['availableCapacity'];
                    
                        if ($available >= $quantity) {
                            if (empty($events) || !isset($events[0]["editionId"])) {
                                echo "<p>Erreur: ID d'édition non trouvé dans les données de l'événement.</p>";
                                return;
                            }
                            $editionId = $events[0]["editionId"];

                            $pdo->beginTransaction();

                            if (!isset($_SESSION["user"]["user_id"])) {
                                echo "<p>Erreur: Vous devez être connecté pour réserver.</p>";
                                exit;
                            }

                            $queryReservation = "INSERT INTO reservation(qteBilletsNormal, qteBilletsReduit, editionId, idUser) 
                                VALUES(:qteBilletsNormal, :qteBilletsReduit, :editionId, :idUser)";

                            $stmtReservation = $pdo->prepare($queryReservation);
                            $stmtReservation->bindParam(":qteBilletsNormal", $qteBilletsNormal);
                            $stmtReservation->bindParam(":qteBilletsReduit", $qteBilletsReduit);
                            $stmtReservation->bindParam(":editionId", $editionId);
                            $stmtReservation->bindParam(":idUser", $idUser);

                            $stmtReservation->execute();

                            // ----------------

                            $idReservation = $pdo->lastInsertId();

                            $queryBillet = "INSERT INTO billet(billetId, typeBillet, placeNum, idReservation)
                                VALUES(:billetId, :typeBillet, :placeNum, :idReservation)";

                            $stmtBillet = $pdo->prepare($queryBillet);

                            include "../functions/getIdBillet.php";

                            $id = getLastId();

                            $stmtBillet = $pdo->prepare($queryBillet);
                            $stmtBillet->bindParam(":billetId", $id);
                            $stmtBillet->bindParam(":typeBillet", $ticketType);
                            $stmtBillet->bindParam(":placeNum", $quantity);
                            $stmtBillet->bindParam(":idReservation", $idReservation);
                            
                            $stmtBillet->execute();

                            $purchase['billetId'] = $id;

                            $_SESSION["user"]["tickets"][count($_SESSION["user"]["tickets"]) - 1] = $purchase;

                            $pdo->commit();

                            header("Location: " . $_SERVER['PHP_SELF'] . "?eventId=" . urlencode($eventId) . "&success=1");
                            exit();
                        }
                        else {
                            echo "Événement non trouvé ou capacité indisponible";
                        }
                    }
                    catch(PDOException $e) {
                        $pdo->rollBack();
                        echo "<p>Erreur lors de la réservation: " . $e->getMessage() . "</p>";
                    }
                }
            } else {
                echo "Error: Event ID not provided.";
            }
        } else {
            if (isset($_GET['eventId'])) {
                $eventId = $_GET['eventId'];
                $query = "SELECT evenement.eventId, edition.NumSalle, evenement.eventTitle, evenement.eventDescription, evenement.TariffNormal, evenement.TariffReduit, edition.image, edition.dateEvent, edition.timeEvent
                    FROM evenement
                    LEFT JOIN edition ON evenement.eventId = edition.eventId
                    WHERE evenement.eventId = :eventId";

                $stmt = $pdo->prepare($query);
                $stmt->bindParam(":eventId", $eventId);
                $stmt->execute();
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    ?>

<?php include "../../views/details.html" ?>