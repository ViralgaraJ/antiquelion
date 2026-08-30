<?php
set_time_limit(600);
ini_set('memory_limit', '256M');

$ftp_server = "antiquelion.com";
$ftp_user = "antique1";
$ftp_pass = "Amma@12345*#";

$conn_id = ftp_connect($ftp_server, 21, 60);
if (!$conn_id) {
    die("FTP Connection failed\n");
}

if (!ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    die("FTP Login failed\n");
}

ftp_pasv($conn_id, true);

$files_to_upload = array(
    'd:/XAMPP/htdocs/antique_site/oc-content/themes/starter/css/custom-home.css' => array(
        'oc-content/themes/starter/css/custom-home.css',
        'public_html/oc-content/themes/starter/css/custom-home.css'
    ),
    'd:/XAMPP/htdocs/antique_site/oc-content/themes/starter/head.php' => array(
        'oc-content/themes/starter/head.php',
        'public_html/oc-content/themes/starter/head.php'
    ),
    'd:/XAMPP/htdocs/antique_site/oc-content/themes/starter/item-post.php' => array(
        'oc-content/themes/starter/item-post.php',
        'public_html/oc-content/themes/starter/item-post.php'
    ),
    'd:/XAMPP/htdocs/antique_site/oc-content/themes/starter/item-edit.php' => array(
        'oc-content/themes/starter/item-edit.php',
        'public_html/oc-content/themes/starter/item-edit.php'
    )
);

foreach ($files_to_upload as $local_file => $remote_paths) {
    if (!file_exists($local_file)) {
        echo "Local missing: $local_file\n";
        continue;
    }
    
    $file_size = filesize($local_file);
    echo "Processing $local_file ($file_size bytes)...\n";
    
    foreach ($remote_paths as $remote_path) {
        $fp = fopen($local_file, 'r');
        if (ftp_fput($conn_id, $remote_path, $fp, FTP_BINARY)) {
            echo "  [SUCCESS] $remote_path uploaded (" . $file_size . " bytes)\n";
        } else {
            echo "  [FAIL] Could not upload $remote_path\n";
        }
        fclose($fp);
    }
}

ftp_close($conn_id);
echo "All Uploads Completed Successfully!\n";
?>
