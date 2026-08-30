<?php
    $addr = array();
    if( ( $item['s_address'] != '' ) && ( $item['s_address'] != null ) ) { $addr[] = $item['s_address']; }
    if( ( $item['s_city'] != '' ) && ( $item['s_city'] != null ) ) { $addr[] = $item['s_city']; }
    if( ( $item['s_zip'] != '' ) && ( $item['s_zip'] != null ) ) { $addr[] = $item['s_zip']; }
    // Only include region if city is empty, to prevent region-city mismatches from confusing Google Maps
    if( empty($item['s_city']) ) {
        if( ( $item['s_region'] != '' ) && ( $item['s_region'] != null ) ) { $addr[] = $item['s_region']; }
    }
    if( ( $item['s_country'] != '' ) && ( $item['s_country'] != null ) ) { $addr[] = $item['s_country']; }
    $address = implode(", ", $addr);

    $map_query = "";
    if($item['d_coord_lat'] != '' && $item['d_coord_long'] != '') {
        $map_query = $item['d_coord_lat'] . ',' . $item['d_coord_long'];
    } else if($address != '') {
        $map_query = $address;
    }
?>

<?php if($map_query != '') { ?>
    <div id="itemMap" style="width: 100%; height: 360px; overflow: hidden; border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-top: 15px;">
      <iframe 
        width="100%" 
        height="100%" 
        frameborder="0" 
        style="border:0; vertical-align: middle;" 
        src="https://maps.google.com/maps?q=<?php echo urlencode($map_query); ?>&t=&z=14&ie=UTF8&iwloc=&output=embed" 
        allowfullscreen>
      </iframe>
    </div>
<?php } else { ?>
    <div class="box-empty" style="padding: 20px; text-align: center; color: #909399; border: 1px dashed #cbd5e1; border-radius: 8px; margin-top: 15px;">
        <span><?php _e('Location map is not available', 'google_maps'); ?></span>
    </div>
<?php } ?>