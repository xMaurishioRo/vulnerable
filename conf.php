<?php
$servername = "bdatahgyey2fmuqqzysf-mysql.services.clever-cloud.com"; 
$username = "ugb4sst7ni1x6mnn";
$password = "XUGVtJC9X7DkbHiNMKhi"; 
$database = "bdatahgyey2fmuqqzysf"; 


$conn = new mysqli($servername, $username, $password, $database);


if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
// $conn->query("CREATE TABLE IF NOT EXISTS users (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     username VARCHAR(255) NOT NULL UNIQUE,
//     password VARCHAR(255) NOT NULL
// )");
// $conn->query("CREATE TABLE IF NOT EXISTS comments (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     user_id INT NOT NULL,
//     comment TEXT NOT NULL,
//     FOREIGN KEY (user_id) REFERENCES users(id)
// )");




// echo "Conexión exitosa";
?>