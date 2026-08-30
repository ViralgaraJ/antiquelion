<?php
$conn = new mysqli('localhost', 'root', 'root', 'antique_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$search = "minutes";

$tables = [];
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    $cols = [];
    $res2 = $conn->query("SHOW COLUMNS FROM `$table`");
    while ($row2 = $res2->fetch_assoc()) {
        $type = strtolower($row2['Type']);
        if (strpos($type, 'varchar') !== false || strpos($type, 'text') !== false) {
            $cols[] = $row2['Field'];
        }
    }
    
    if (empty($cols)) continue;
    
    foreach ($cols as $col) {
        $res3 = $conn->query("SELECT * FROM `$table` WHERE `$col` LIKE '%" . $conn->real_escape_string($search) . "%'");
        if ($res3 && $res3->num_rows > 0) {
            echo "Match in table `$table` column `$col`:\n";
            while ($row3 = $res3->fetch_assoc()) {
                print_r($row3);
                echo "\n\n";
            }
        }
    }
}
?>
