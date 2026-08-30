<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Antique Bidding System - Database Installer (v2)</h2>";

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("<p style='color:red;'>Connection failed: " . $conn->connect_error . "</p>");
}
echo "<p style='color:green;'>Connected successfully to database: " . DB_NAME . "</p>";

$prefix = DB_TABLE_PREFIX;

// 1. Create Auctions Table
$sql_auctions = "CREATE TABLE IF NOT EXISTS `{$prefix}t_item_auctions` (
    `fk_i_item_id` INT(10) UNSIGNED NOT NULL,
    `d_starting_price` DECIMAL(15, 4) NOT NULL DEFAULT '0.0000',
    `d_reserve_price` DECIMAL(15, 4) NOT NULL DEFAULT '0.0000',
    `d_min_increment` DECIMAL(15, 4) NOT NULL DEFAULT '1.0000',
    `dt_start_date` DATETIME NOT NULL,
    `dt_end_date` DATETIME NOT NULL,
    `b_active` TINYINT(1) NOT NULL DEFAULT '1',
    PRIMARY KEY (`fk_i_item_id`),
    INDEX `idx_end_date` (`dt_end_date`, `b_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

if ($conn->query($sql_auctions) === TRUE) {
    echo "<p style='color:green;'>Table <b>{$prefix}t_item_auctions</b> created or already exists.</p>";
} else {
    echo "<p style='color:red;'>Error creating Auctions table: " . $conn->error . "</p>";
}

// 2. Create Bids Table
$sql_bids = "CREATE TABLE IF NOT EXISTS `{$prefix}t_item_bids` (
    `pk_i_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `fk_i_item_id` INT(10) UNSIGNED NOT NULL,
    `fk_i_user_id` INT(10) UNSIGNED NOT NULL,
    `d_amount` DECIMAL(15, 4) NOT NULL,
    `dt_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `s_status` ENUM('active', 'withdrawn', 'won') NOT NULL DEFAULT 'active',
    `s_ip_address` VARCHAR(45) NOT NULL,
    PRIMARY KEY (`pk_i_id`),
    INDEX `idx_item_bids` (`fk_i_item_id`, `d_amount` DESC),
    INDEX `idx_user_bids` (`fk_i_user_id`),
    FOREIGN KEY (`fk_i_item_id`) REFERENCES `{$prefix}t_item_auctions` (`fk_i_item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

if ($conn->query($sql_bids) === TRUE) {
    echo "<p style='color:green;'>Table <b>{$prefix}t_item_bids</b> created or already exists.</p>";
} else {
    echo "<p style='color:red;'>Error creating Bids table: " . $conn->error . "</p>";
}

// 3. Populate Default Auctions for Existing Items
$items_res = $conn->query("SELECT pk_i_id, i_price FROM `{$prefix}t_item` WHERE pk_i_id NOT IN (SELECT fk_i_item_id FROM `{$prefix}t_item_auctions`)");
if ($items_res && $items_res->num_rows > 0) {
    echo "<p>Found " . $items_res->num_rows . " existing items. Populating default auction settings...</p>";
    $count = 0;
    while ($item = $items_res->fetch_assoc()) {
        $itemId = $item['pk_i_id'];
        $startingPrice = ($item['i_price'] !== null && $item['i_price'] > 0) ? ($item['i_price'] / 1000000) : 100.00;
        $startDate = date('Y-m-d H:i:s');
        $endDate = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $conn->query("INSERT INTO `{$prefix}t_item_auctions` (fk_i_item_id, d_starting_price, d_reserve_price, d_min_increment, dt_start_date, dt_end_date, b_active) VALUES ($itemId, $startingPrice, 0.00, 100.00, '$startDate', '$endDate', 1)");
        $count++;
    }
    echo "<p style='color:green;'>Populated default auctions for $count items.</p>";
} else {
    echo "<p>No existing items need default auction population.</p>";
}

// 4. Register and Enable the Plugin in oc_t_preference
$plugin_name = 'antique_bids/index.php';

// Installed Plugins preference
$res_inst = $conn->query("SELECT s_value FROM `{$prefix}t_preference` WHERE s_name = 'installed_plugins'");
if ($res_inst && $res_inst->num_rows > 0) {
    $row = $res_inst->fetch_assoc();
    $installed = @unserialize($row['s_value']);
    if (!is_array($installed)) $installed = [];
    if (!in_array($plugin_name, $installed)) {
        $installed[] = $plugin_name;
        $val = serialize($installed);
        $conn->query("UPDATE `{$prefix}t_preference` SET s_value = '" . $conn->real_escape_string($val) . "' WHERE s_name = 'installed_plugins'");
        echo "<p style='color:green;'>Plugin registered as installed.</p>";
    } else {
        echo "<p>Plugin already listed as installed.</p>";
    }
}

// Active Plugins preference
$res_act = $conn->query("SELECT s_value FROM `{$prefix}t_preference` WHERE s_name = 'active_plugins'");
if ($res_act && $res_act->num_rows > 0) {
    $row = $res_act->fetch_assoc();
    $active = @unserialize($row['s_value']);
    if (!is_array($active)) $active = [];
    if (!in_array($plugin_name, $active)) {
        $active[] = $plugin_name;
        $val = serialize($active);
        $conn->query("UPDATE `{$prefix}t_preference` SET s_value = '" . $conn->real_escape_string($val) . "' WHERE s_name = 'active_plugins'");
        echo "<p style='color:green;'>Plugin registered as active (enabled).</p>";
    } else {
        echo "<p>Plugin already listed as active.</p>";
    }
}

$conn->close();
echo "<h3>Installation Completed Successfully!</h3>";
?>
