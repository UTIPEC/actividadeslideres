<?php
$db_host = 'bd';
$db_user = 'root';     
$db_pass = 'Utipec2026*$';
$db_name = 'indicadores';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["error" => "Error de conexi��n: " . $e->getMessage()]));
}
?>