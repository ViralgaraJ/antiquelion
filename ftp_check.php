<?php
$ftp_server = "antiquelion.com";
$ftp_user = "antique1";
$ftp_pass = "Amma@12345*#";

$conn_id = ftp_connect($ftp_server, 21, 30);
if (@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    ftp_pasv($conn_id, true);
    
    echo "Current Dir: " . ftp_pwd($conn_id) . "\n";
    $raw = ftp_rawlist($conn_id, ".");
    echo "Root Raw List:\n";
    print_r($raw);
    
    $raw_public = ftp_rawlist($conn_id, "public_html");
    echo "public_html Raw List:\n";
    print_r(array_slice($raw_public, 0, 15));
} else {
    echo "Login failed\n";
}
ftp_close($conn_id);
?>
