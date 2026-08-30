<?php
set_time_limit(300);
$ftp_server = "antiquelion.com";
$ftp_user = "antique1";
$ftp_pass = "Amma@12345*#";

$conn_id = ftp_connect($ftp_server, 21, 60);
if (ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    ftp_pasv($conn_id, true);
    
    $local_files = array(
        'd:/XAMPP/htdocs/antique_site/oc-content/themes/starter/item-post.php' => 'oc-content/themes/starter/item-post.php',
        'd:/XAMPP/htdocs/antique_site/oc-content/themes/starter/item-edit.php' => 'oc-content/themes/starter/item-edit.php',
        'd:/XAMPP/htdocs/antique_site/oc-content/themes/starter/head.php' => 'oc-content/themes/starter/head.php',
        'd:/XAMPP/htdocs/antique_site/oc-content/themes/starter/css/custom-home.css' => 'oc-content/themes/starter/css/custom-home.css'
    );
    
    foreach ($local_files as $local => $remote) {
        $fp = fopen($local, 'r');
        if (ftp_fput($conn_id, $remote, $fp, FTP_BINARY)) {
            echo "Uploaded $remote (" . filesize($local) . " bytes)\n";
        } else {
            echo "Failed $remote\n";
        }
        fclose($fp);
        
        $remote_pub = 'public_html/' . $remote;
        $fp = fopen($local, 'r');
        if (ftp_fput($conn_id, $remote_pub, $fp, FTP_BINARY)) {
            echo "Uploaded $remote_pub (" . filesize($local) . " bytes)\n";
        } else {
            echo "Failed $remote_pub\n";
        }
        fclose($fp);
    }
}
ftp_close($conn_id);
?>
