<?php
set_time_limit(300);
require __DIR__ . '/ftp_config.php';

$conn_id = ftp_connect($ftp_server, 21, 30) or die("Cannot connect\n");
if (ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    ftp_pasv($conn_id, true);
    
    $sync_files = array(
        __DIR__ . '/oc-content/themes/starter/head.php' => 'oc-content/themes/starter/head.php',
        __DIR__ . '/oc-content/themes/starter/css/custom-home.css' => 'oc-content/themes/starter/css/custom-home.css',
        __DIR__ . '/oc-content/themes/starter/item-post.php' => 'oc-content/themes/starter/item-post.php',
        __DIR__ . '/oc-content/themes/starter/item-edit.php' => 'oc-content/themes/starter/item-edit.php'
    );
    
    foreach ($sync_files as $local => $remote) {
        $fp = fopen($local, 'r');
        if (ftp_fput($conn_id, $remote, $fp, FTP_BINARY)) {
            echo "OK: $remote\n";
        } else {
            echo "FAIL: $remote\n";
        }
        fclose($fp);
        
        $remote_pub = 'public_html/' . $remote;
        $fp = fopen($local, 'r');
        if (ftp_fput($conn_id, $remote_pub, $fp, FTP_BINARY)) {
            echo "OK: $remote_pub\n";
        } else {
            echo "FAIL: $remote_pub\n";
        }
        fclose($fp);
    }
}
ftp_close($conn_id);
?>
