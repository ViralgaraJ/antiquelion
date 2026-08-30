<?php
    
    $ra_numads            = '';
    $dao_preference = new Preference();
    if(Params::getParam('ra_numads') != '') {
        $ra_numads = Params::getParam('ra_numads');
    } else {
        $ra_numads = (osc_related_ra_numads() != '') ? osc_related_ra_numads() : '' ;
    }
    
    
    $picOnly            = '';
    $dao_preference = new Preference();
    if(Params::getParam('picOnly') != '') {
        $picOnly = Params::getParam('picOnly');
    } else {
        $picOnly = (osc_related_picOnly() != '') ? osc_related_picOnly() : '' ;
    }
    
    $autoembed            = '';
    $dao_preference = new Preference();
    if(Params::getParam('autoembed') != '') {
        $autoembed = Params::getParam('autoembed');
    } else {
        $autoembed = (osc_related_autoembed() != '') ? osc_related_autoembed() : '' ;
    }
    
    $css            = '';
    $dao_preference = new Preference();
    if(Params::getParam('css') != '') {
        $css = Params::getParam('css');
    } else {
        $css = (osc_related_css() != '') ? osc_related_css() : '' ;
    }
    
    
    if( Params::getParam('option') == 'stepone' ) {
        $dao_preference->update(array("s_value" => $ra_numads), array("s_section" =>"related_ads", "s_name" => "related_ra_numads")) ;
        $dao_preference->update(array("s_value" => $picOnly), array("s_section" =>"related_ads", "s_name" => "related_picOnly")) ;
        $dao_preference->update(array("s_value" => $autoembed), array("s_section" =>"related_ads", "s_name" => "related_autoembed")) ;
        $dao_preference->update(array("s_value" => $css), array("s_section" =>"related_ads", "s_name" => "related_css")) ;
        echo '<div style="text-align:center; font-size:22px; background-color:#2aa4db;border: 4px solid #e1e1e1;border-radius:6px;"><p>' . __('Settings Saved', 'related') . '.</p></div>';
    }
    unset($dao_preference) ;
    
?>

<form action="<?php osc_admin_base_url(true); ?>" method="post">
    <input type="hidden" name="page" value="plugins" />
    <input type="hidden" name="action" value="renderplugin" />
    <input type="hidden" name="file" value="related_ads/admin.php" />
    <input type="hidden" name="option" value="stepone" />
    <div>
    <fieldset>
        <h2><?php _e('Related Ads Preferences', 'related'); ?></h2>
        <br /> 
        <label for="ra_numads" style="font-weight: bold;"><?php _e('Number of related ads you want to show (Default is 4) ', 'related'); ?></label>:<br />
        <input type="text" name="ra_numads" id="ra_numads" value="<?php echo $ra_numads; ?>" />
        <br />
        <br />
        <label for="picOnly" style="font-weight: bold;"><?php _e('Show ads with pictures only','related'); ?></label>:<br />
        <select name="picOnly" id="picOnly">
        	<option <?php if($picOnly ==0){echo 'selected="selected"';}?> value='0'><?php _e('No', 'related'); ?></option>
        	<option <?php if($picOnly ==1){echo 'selected="selected"';}?> value='1'><?php _e('Yes', 'related'); ?></option>
        </select>
        <br />
        <br />
        <label for="css" style="font-weight: bold;"><?php _e('Disable default Css (You can use your custom css)','related'); ?></label>:<br />
        <select name="css" id="css">
        	<option <?php if($css ==0){echo 'selected="selected"';}?> value='0'><?php _e('No', 'related'); ?></option>
        	<option <?php if($css ==1){echo 'selected="selected"';}?> value='1'><?php _e('Yes', 'related'); ?></option>
        </select>
        <br />
        <br />
        <label for="autoembed" style="font-weight: bold;"><?php _e('Embed related ads automatically to your item page','related'); ?></label>:<br />
        <select name="autoembed" id="autoembed">
        	<option <?php if($autoembed ==0){echo 'selected="selected"';}?> value='0'><?php _e('No', 'related'); ?></option>
        	<option <?php if($autoembed ==1){echo 'selected="selected"';}?> value='1'><?php _e('Yes', 'related'); ?></option>
        </select>
        <br />
        <br />
        <input type="submit" value="<?php _e('Save', 'related'); ?>" />
        <br />
        <br />
     </fieldset>
    </div>
</form>
