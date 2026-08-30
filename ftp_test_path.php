<?php
$ftp_server = "antiquelion.com";
$ftp_user = "antique1";
$ftp_pass = "Amma@12345*#";

$conn_id = ftp_connect($ftp_server, 21, 30);
if (@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    ftp_pasv($conn_id, true);
    echo "Current dir: " . ftp_pwd($conn_id) . "\n";
    echo "Raw list:\n";
    print_r(ftp_nlist($conn_id, "."));
}
ftp_close($conn_id);
?>
