<?php
    $host = "localhost";
    $user = "root";
    $password = "";
    $database = "do-an-1";
    $charset = 'utf8mb4';
    $dsn = "mysql:host=$host;dbname=$database;charset=$charset";
    $options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $conn = new mysqli($host, $user, $password, $database);

    if($conn->connect_error){
        die("KET NOI THAT BAI" . $conn->connect_error);
    }

    try {
    $pdo = new PDO($dsn, $user, $password, $options);
    } catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
?>
