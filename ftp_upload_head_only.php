<?php
require __DIR__ . '/ftp_config.php';

$conn_id = ftp_connect($ftp_server, 21, 60);
if (ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    ftp_pasv($conn_id, true);
    
    $local_css = __DIR__ . '/oc-content/themes/starter/css/custom-home.css';
    $remote_css = 'oc-content/themes/starter/css/custom-home.css';
    
    $fp = fopen($local_css, 'r');
    if (ftp_fput($conn_id, $remote_css, $fp, FTP_BINARY)) {
        echo "Uploaded custom-home.css (" . filesize($local_css) . " bytes)\n";
        echo "Remote size: " . ftp_size($conn_id, $remote_css) . " bytes\n";
    } else {
        echo "Failed to upload custom-home.css\n";
    }
    fclose($fp);
    
    $local_head = __DIR__ . '/oc-content/themes/starter/head.php';
    $remote_head = 'oc-content/themes/starter/head.php';
    
    $fp = fopen($local_head, 'r');
    if (ftp_fput($conn_id, $remote_head, $fp, FTP_BINARY)) {
        echo "Uploaded head.php (" . filesize($local_head) . " bytes)\n";
        echo "Remote size: " . ftp_size($conn_id, $remote_head) . " bytes\n";
    } else {
        echo "Failed to upload head.php\n";
    }
    fclose($fp);
}
ftp_close($conn_id);
?>
