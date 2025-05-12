<?php
$host = 'localhost';
$dbname = 'kidstoyland';
$username = 'root'; // Replace with your MySQL username
$password = ''; // Replace with your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
define('HOST', '127.0.0.1');
    define('USER', 'root');
    define('PASS', '');
    define('DB_name', 'kidstoyland');

    function create_connection(){
        $conn = new mysqli(HOST, USER, PASS, DB_name);
        if($conn->connect_error){
            die( $conn->connect_error);
        }
        return $conn;
    }
?>