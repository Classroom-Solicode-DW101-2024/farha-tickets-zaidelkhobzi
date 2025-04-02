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

                // Check if the form was submitted with "valider"
                if (isset($_POST["valider"])) {
                    // Store purchase details in session (optional, for confirmation)
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

                            /** end($_SESSION["user"]["tickets"])
                             * Moves the pointer to the last element of the tickets array and returns that element.
                             * For example, if $_SESSION["user"]["tickets"] is structured like this:
                                $_SESSION["user"]["tickets"] = [
                                    ["id" => 1, "quantity" => 2, "event" => "Concert A"],
                                    ["id" => 2, "quantity" => 3, "event" => "Concert B"],
                                    ["id" => 3, "quantity" => 1, "event" => "Concert C"]
                                ];
                             * Then end($_SESSION["user"]["tickets"]) would return:
                                ** ["id" => 3, "quantity" => 1, "event" => "Concert C"]
                                ** And $latestPurchase["quantity"] would be 1.
                             */
                        } 
                        else {
                            $ticketType = $_POST["ticketType"];
                            $quantity = (int)$_POST["quantity"];
                        }

                        $qteBilletsNormal = ($ticketType === "Type normal") ? $quantity : 0;
                        $qteBilletsReduit = ($ticketType === "Type réduit") ? $quantity : 0;
                    
                        // Verify available capacity before insertion
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
                            $editionId = $events[0]["editionId"]; // Use the first editionId from the result

                            // Start transaction
                            $pdo->beginTransaction();

                            /** beginTransaction() and commit()
                             * These are methods provided by PHP's PDO (PHP Data Objects) extension for managing database transactions.
                             * A transaction is a way to group multiple database operations (like INSERT, UPDATE, or DELETE statements) into a single "all-or-nothing" unit of work.
                             * This ensures that either all operations succeed, or none of them are applied, maintaining data consistency(اتساق البيانات).
                                ** $pdo->beginTransaction(): This starts a transaction. It tells the database to hold off on permanently(بشكل دائم) applying any changes until you explicitly say so. Any SQL statements executed after this are part of the transaction and are not finalized until you commit or roll back. Changes made during the transaction are not saved to the database until committed.
                                    Returns true on success or false on failure.
                                ** $pdo->commit(): This ends the transaction successfully. It tells the database to apply all the changes made during the transaction permanently. Once committed, the data is saved, and the transaction is complete.
                                o There’s also a related method, $pdo->rollBack(), which we use in the catch block. It undoes(تُلغي) all changes made during the transaction if something goes wrong, reverting(يُعيد) the database to its state before the transaction began. rollBack() only works within a transaction that was started using beginTransaction().
                            * Why Are They Used in My Code ?
                                ** In my code, i'm performing two related database operations:
                                    1. Inserting a record into the reservation table (to record the booking details like quantity and user).
                                    2. Inserting a record into the billet table (to create a ticket linked to that reservation).
                                ** These operations are interdependent(مترابطة مع بعضها البعض):
                                    o The billet table has a foreign key (idReservation) that references the idReservation in the reservation table.
                                    o If the reservation insert succeeds but the billet insert fails (e.g., due to a database error), you’d end up with an incomplete booking—an orphaned reservation without tickets.
                                    o Conversely, if the billet insert succeeds but the reservation fails, you’d have tickets without a valid reservation, which violates your database’s integrity.
                                ** Using a transaction ensures that both inserts either succeed together or fail together.
                                    o If everything works: Both inserts are executed, and $pdo->commit() finalizes them in the database.
                                    o If something fails: The catch block triggers $pdo->rollBack(), undoing any changes made so far. For example, if the billet insert fails due to a constraint violation, the reservation insert is also rolled back, keeping your database consistent.
                            * Why Is This Important?
                                    1. Data Consistency: Transactions prevent partial updates. In your case, you don’t want a reservation without tickets or tickets without a reservation.
                                    2. Atomicity: This is a principle of database transactions (part of ACID properties—Atomicity, Consistency, Isolation, Durability). Atomicity ensures that all operations in a transaction are treated as a single unit.
                                    3. Error Recovery: If an error occurs (e.g., a database crash, constraint violation, or network issue), the rollback ensures no "half-done" changes are left behind.
                            * When you execute a Database Definition Language (DDL) statement—like CREATE TABLE, DROP TABLE, or ALTER TABLE—inside a transaction, MySQL behaves differently. DDL statements define or modify the structure of database objects (tables, schemas, etc.), unlike Data Manipulation Language (DML) statements (like INSERT or UPDATE), which deal with the data itself.
                                ** Here’s the key point: in MySQL, DDL statements are not transactional in the same way DML statements are. When you issue a DDL statement within a transaction, MySQL automatically performs an implicit COMMIT. This means:
                                    1. All changes made in the transaction up to that point are permanently saved—whether you wanted them to be or not.
                                    2. You lose the ability to roll back any of those prior changes, because the transaction is effectively closed by the implicit COMMIT.
                                    3. After the DDL statement, a new transaction begins (if autocommit is off).
                                ** What is an Implicit COMMIT?
                                    o An implicit COMMIT happens when MySQL automatically commits changes before or after executing a certain SQL statement, even if a transaction is active.
                                ** Implications
                                    o If you’re mixing DML (data changes) and DDL (structure changes) in a transaction and relying on the ability to roll back, this implicit COMMIT can catch you off guard. (catch someone off guard = to surprise someone by doing something that they are not expecting or ready for)
                                    o To avoid issues, it’s a good practice to separate DDL operations from transactions that involve DML. For example, run CREATE TABLE or DROP TABLE outside of a transaction or ensure the transaction only contains DML statements that can be rolled back.
                            */

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

                            // Get the last inserted reservation ID
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
                            
                            // Add billetId to the $purchase array
                            $purchase['billetId'] = $id;

                            // Update the session with the modified $purchase array
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
            // echo "Error: Invalid request method.";
            // Handle popup display
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