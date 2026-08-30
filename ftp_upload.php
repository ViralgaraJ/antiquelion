<?php
set_time_limit(300);
$ftp_server = "antiquelion.com";
$ftp_user = "antique1";
$ftp_pass = "Amma@12345*#";

$conn_id = ftp_connect($ftp_server, 21, 30) or die("Could not connect to $ftp_server");

if (@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    echo "Connected successfully.\n";
    ftp_pasv($conn_id, true);
    
    $files = array(
        'oc-content/themes/starter/css/custom-home.css',
        'oc-content/themes/starter/css/style.css',
        'oc-content/themes/starter/functions.php',
        'oc-content/themes/starter/contact.php',
        'oc-content/themes/starter/alert-form.php',
        'oc-content/themes/starter/head.php',
        'oc-content/themes/starter/header.php',
        'oc-content/themes/starter/footer.php',
        'oc-content/themes/starter/inc.location-modal.php',
        'oc-content/themes/starter/item-send-friend.php',
        'oc-content/themes/starter/item-post.php',
        'oc-content/themes/starter/item-edit.php',
        'oc-content/themes/starter/user-items.php',
        'oc-content/themes/starter/search.php',
        'oc-content/themes/starter/main.php',
        'oc-includes/osclass/core/Params.php'
    );
    
    $base = 'd:/XAMPP/htdocs/antique_site/';
    foreach($files as $file) {
        $local = $base . $file;
        if (!file_exists($local)) {
            echo "Local file missing: $local\n";
            continue;
        }
        
        $target_files = array($file, 'public_html/' . $file);
        foreach($target_files as $remote) {
            $fp = fopen($local, 'r');
            if (@ftp_fput($conn_id, $remote, $fp, FTP_BINARY)) {
                echo "Uploaded: $remote (" . filesize($local) . " bytes)\n";
            } else {
                echo "Notice: $remote not placed\n";
            }
            fclose($fp);
        }
    }
} else {
    echo "Login failed\n";
}

ftp_close($conn_id);
echo "FTP Upload Completed.\n";
?>
