<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$res = $conn->query("SHOW TABLES");
echo "<h3>Tables List:</h3><ul>";
while ($row = $res->fetch_array()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";
$conn->close();
?>
