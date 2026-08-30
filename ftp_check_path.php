<?php
$ftp_server = "antiquelion.com";
$ftp_user = "antique1";
$ftp_pass = "Amma@12345*#";

$conn_id = ftp_connect($ftp_server, 21, 30) or die("Could not connect to $ftp_server");

if (@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    ftp_pasv($conn_id, true);
    $pwd = ftp_pwd($conn_id);
    echo "PWD: $pwd\n";
    $list = ftp_nlist($conn_id, ".");
    echo "Root contents:\n";
    print_r($list);
    
    if (in_array("public_html", $list) || in_array("./public_html", $list)) {
        echo "public_html folder exists!\n";
        $list_pub = ftp_nlist($conn_id, "public_html");
        echo "public_html contents:\n";
        print_r($list_pub);
    }
}
ftp_close($conn_id);
?>
