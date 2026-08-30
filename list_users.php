<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$prefix = DB_TABLE_PREFIX;
$res = $conn->query("SELECT pk_i_id, s_email, s_name FROM `{$prefix}t_user` LIMIT 5");
echo "<h3>Registered Users:</h3><ul>";
while ($row = $res->fetch_assoc()) {
    echo "<li>ID: " . $row['pk_i_id'] . " - Name: " . $row['s_name'] . " - Email: " . $row['s_email'] . "</li>";
}
echo "</ul>";
$conn->close();
?>
