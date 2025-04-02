<?php
    $dsn = "mysql:host=localhost;dbname=FarhaEvents";
    $user = "root";
    $pass = "";
    $options = [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // echo "Connected Successfully";
    }
    catch(PDOException $e) {
        die("Failed to connect to database, " + $e->getMessage());
    }