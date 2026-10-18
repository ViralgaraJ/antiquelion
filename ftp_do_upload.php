<?php
set_time_limit(300);
require __DIR__ . '/ftp_config.php';

$conn_id = ftp_connect($ftp_server, 21, 30) or die("Cannot connect");
if (@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    ftp_pasv($conn_id, true);
    
    $local_head = __DIR__ . '/oc-content/themes/starter/head.php';
    $local_css  = __DIR__ . '/oc-content/themes/starter/css/custom-home.css';
    $local_post = __DIR__ . '/oc-content/themes/starter/item-post.php';
    $local_edit = __DIR__ . '/oc-content/themes/starter/item-edit.php';
    
    $upload_map = array(
        $local_head => array(
            'oc-content/themes/starter/head.php',
            'public_html/oc-content/themes/starter/head.php'
        ),
        $local_css => array(
            'oc-content/themes/starter/css/custom-home.css',
            'public_html/oc-content/themes/starter/css/custom-home.css'
        ),
        $local_post => array(
            'oc-content/themes/starter/item-post.php',
            'public_html/oc-content/themes/starter/item-post.php'
        ),
        $local_edit => array(
            'oc-content/themes/starter/item-edit.php',
            'public_html/oc-content/themes/starter/item-edit.php'
        )
    );
    
    foreach($upload_map as $local => $remotes) {
        foreach($remotes as $remote) {
            $fp = fopen($local, 'r');
            if (@ftp_fput($conn_id, $remote, $fp, FTP_BINARY)) {
                echo "SUCCESS: Uploaded $local -> $remote (" . filesize($local) . " bytes)\n";
            } else {
                echo "FAILED: Could not upload to $remote\n";
            }
            fclose($fp);
        }
    }
} else {
    echo "FTP Login failed\n";
}
ftp_close($conn_id);
?>
