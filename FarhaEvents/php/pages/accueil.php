<?php
    require_once "../config/connectDB.php";

    $query = "SELECT evenement.eventId, evenement.eventType, evenement.eventTitle, edition.image, edition.dateEvent
        FROM evenement
        LEFT JOIN edition ON evenement.eventId = edition.eventId
        ORDER BY edition.dateEvent ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /** --------- */

    $query = "SELECT DISTINCT eventType FROM evenement";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $eventType = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /** Search by title */

    if(isset($_POST["search"])) {
        $searchByTitle = "%" . trim($_POST["query"]) . "%";

        $query = "SELECT evenement.eventId, evenement.eventType, evenement.eventTitle, edition.image, edition.dateEvent
        FROM evenement
        LEFT JOIN edition ON evenement.eventId = edition.eventId
        WHERE evenement.eventTitle LIKE :searchByTitle
        ORDER BY edition.dateEvent ASC";
    
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":searchByTitle", $searchByTitle);
        $stmt->execute();

        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Filter par titre */

    if(isset($_POST["appliquer"])) {
        $categorie = isset($_POST["category"]) ? trim($_POST["category"]) : "";
        $f_date = trim($_POST["first-date"]);
        $s_date = trim($_POST["second-date"]);
    
        if (!empty($categorie) && !empty($f_date) && !empty($s_date)) {
            // Filter by both category AND date range
            $query = "SELECT evenement.eventId, evenement.eventType, evenement.eventTitle, edition.image, edition.dateEvent
                FROM evenement
                LEFT JOIN edition ON evenement.eventId = edition.eventId
                WHERE evenement.eventType = :eventType
                AND edition.dateEvent BETWEEN :first_date AND :last_date
                ORDER BY edition.dateEvent ASC";
    
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":eventType", $categorie);
            $stmt->bindParam(":first_date", $f_date);
            $stmt->bindParam(":last_date", $s_date);
        } 
        elseif (!empty($categorie)) {
            // Filter only by category
            $query = "SELECT evenement.eventId, evenement.eventType, evenement.eventTitle, edition.image, edition.dateEvent
                FROM evenement
                LEFT JOIN edition ON evenement.eventId = edition.eventId
                WHERE evenement.eventType = :eventType
                ORDER BY edition.dateEvent ASC";
    
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":eventType", $categorie);
        } 
        elseif (!empty($f_date) && !empty($s_date)) {
            // Filter only by date range
            $query = "SELECT evenement.eventId, evenement.eventType, evenement.eventTitle, edition.image, edition.dateEvent
                FROM evenement
                LEFT JOIN edition ON evenement.eventId = edition.eventId
                WHERE edition.dateEvent BETWEEN :first_date AND :last_date
                ORDER BY edition.dateEvent ASC";
    
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":first_date", $f_date);
            $stmt->bindParam(":last_date", $s_date);
        } 
        else {
            // No filter, fetch all events
            $query = "SELECT evenement.eventId, evenement.eventType, evenement.eventTitle, edition.image, edition.dateEvent
                FROM evenement
                LEFT JOIN edition ON evenement.eventId = edition.eventId
                ORDER BY edition.dateEvent ASC";
    
            $stmt = $pdo->prepare($query);
        }
    
        // Execute the query
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }    

    if (isset($_POST["nettoyer"])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
</head>
    <body class="lato-bold">
        <header>
            <a href="./accueil.php" style="text-decoration: none;"><h1 class="logo">farhaEvents</h1></a>
            <form method="POST">
                <input type="text" name="query" placeholder="Cherchez ce que vous voulez">
                <button type="submit" name="search">Rechercher</button>
            </form>
            <nav>
                <ul>
                    <div>
                        <a href="./accueil.php"><li>Accueil</li></a>
                    </div>
                    <div>
                        <a href="../pages/profil.php"><li>Mon profile</li></a>
                    </div>
                    <div>
                        <a href="./login.php"><li>Se connecter</li></a>
                    </div>
                </ul>
            </nav>
        </header>
        <main>
            <form method="POST" class="filter">
                <select name="category" id="">
                    <option value="">Filter par titre</option>
                    <?php foreach($eventType as $event): ?>
                        <option value="<?= htmlspecialchars($event["eventType"]); ?>"
                        <?php echo $event["eventType"] ?>>
                            <h4><?= htmlspecialchars($event["eventType"]); ?></h4>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="first-date" id="first-date">
                <input type="date" name="second-date" id="second-date">
                <button name="appliquer" type="submit" class="appliquer">Appliquer</button>
                <button name="nettoyer" type="submit" class="nettoyer">Nettoyer</button>

                <input type="hidden" name="id" value="<?= htmlspecialchars($event["eventType"]); ?>">
            </form>
            <div class="cards">
                <?php foreach($events as $event): ?>
                    <div class="card">
                        <img src="<?= htmlspecialchars($event["image"]); ?>">
                        <h3><?= htmlspecialchars($event["eventType"]); ?></h3>
                        <h4><?= htmlspecialchars($event["eventTitle"]); ?></h4>
                        <h6><?= htmlspecialchars($event["dateEvent"]); ?></h6>
                        <form action="details.php" method="POST">
                            <input type="hidden" name="eventId" value="<?= htmlspecialchars($event["eventId"]); ?>">
                            <button class="acheter" style="width: 100%;">J’achète</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>

        <!-- <script>
            const acheter = document.querySelectorAll(".acheter");

            acheter.forEach(button => {
                button.addEventListener("click", () => {
                    window.location.href = "details.php";
                });
            });
        </script> -->
    </body>
</html>