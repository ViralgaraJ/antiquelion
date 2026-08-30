<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$prefix = DB_TABLE_PREFIX;
$res = $conn->query("SELECT pk_i_id FROM `{$prefix}t_item` LIMIT 5");
echo "<h3>Listing URLs:</h3><ul>";
while ($row = $res->fetch_assoc()) {
    $url = osc_base_url() . "index.php?page=item&id=" . $row['pk_i_id'];
    echo "<li><a href='$url' target='_blank'>Item ID: " . $row['pk_i_id'] . " - $url</a></li>";
}
echo "</ul>";
$conn->close();
?>
