<?php
$ftp_server = "antiquelion.com";
$ftp_user = "antique1";
$ftp_pass = "Amma@12345*#";

$conn_id = ftp_connect($ftp_server, 21, 30);
if (@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    ftp_pasv($conn_id, true);
    echo "Current FTP Directory: " . ftp_pwd($conn_id) . "\n";
    $list = ftp_nlist($conn_id, ".");
    echo "Directory Contents:\n";
    print_r($list);
} else {
    echo "FTP Login Failed\n";
}
ftp_close($conn_id);
?>
