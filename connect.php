<?php
    $host = "localhost";
    $user = "root";
    $password = "";
    $database = "do-an-1";
    $charset = 'utf8mb4';

    $conn = new mysqli($host, $user, $password, $database);

    if ($conn->connect_error) {
        die("KẾT NỐI THẤT BẠI: " . $conn->connect_error);
    }

    // Đặt charset cho kết nối
    if (!$conn->set_charset($charset)) {
        die("Lỗi khi thiết lập charset: " . $conn->error);
    }
?>