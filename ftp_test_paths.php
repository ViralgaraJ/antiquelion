<?php
$ftp_server = "antiquelion.com";
$ftp_user = "antique1";
$ftp_pass = "Amma@12345*#";

$conn_id = ftp_connect($ftp_server, 21, 30);
if (@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    ftp_pasv($conn_id, true);
    
    $paths = array(
        "oc-content/themes/starter/head.php",
        "public_html/oc-content/themes/starter/head.php",
        "antiquelion.com/oc-content/themes/starter/head.php",
        "www/oc-content/themes/starter/head.php"
    );
    
    foreach($paths as $p) {
        $size = @ftp_size($conn_id, $p);
        if ($size !== -1) {
            echo "FOUND REMOTE PATH: $p (size: $size)\n";
        } else {
            echo "Not found: $p\n";
        }
    }
} else {
    echo "Login failed\n";
}
ftp_close($conn_id);
?>
