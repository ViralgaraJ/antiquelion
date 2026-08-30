<?php
/*
Plugin Name: Print Ad
Plugin URI: http://www.osclass.org
Description: Allows users to print an ad
Version: 3.0
Author: Jesse modified by MB-themes.com
Author URI: http://www.osclass.org/
Short Name: printad
*/





function print_ad() {

    // make user information available
    View::newInstance()->_exportVariableToView('user', User::newInstance()->findByPrimaryKey(osc_item_user_id()));

    $path = osc_base_url() . 'oc-content/plugins/print_ad/';

    // Get item data
    $desc = osc_item_description();
    $desc =  htmlspecialchars($desc);
    $title = htmlspecialchars(osc_item_title());
    $pr = explode("<div", osc_item_formated_price(), 2);
    $price = $pr[0];
    
    $pub_date = osc_item_pub_date();
    $country = osc_item_country();
    $region = osc_item_region();
    $city = osc_item_city();
    $zip = osc_item_zip();

    // store user information
    $phone = osc_user_phone();
    $mobil = osc_user_phone_mobile();
    $mobil2 = osc_item_city_area();
    $website = osc_user_website();
    $address = osc_user_address();


    // Get & store all the image data for the current item
    osc_reset_resources();
    if( osc_count_item_resources() > 0 ) {
	for ( $pindex = 0; osc_has_item_resources() ; $pindex++ ) {
	    $image_id[] = osc_resource_id();
	    $image_path[] = osc_resource_path(); 
	    $image_ext[] = osc_resource_extension();
	}
    }

    // prepare arrays to be posted
    if(count($image_id)>0){
	$image_id = implode(",", $image_id);
	$image_path = implode(",", $image_path);
	$image_ext = implode(",", $image_ext);
    }
    View::newInstance()->_reset('resources') ; //reset resources array (no helper function exisits for this as of now, but has been suggested)

    echo '
    <script>
            function formpopup()
        {
            window.open("about :blank","printme","width=750,height=600,scrollbars=yes,menubar=yes");
            return true;
        }
    </script>

    <form name="printform" id="printform" action="'.$path.'print.php" method="post" target="printme" onsubmit="formpopup();">

	<input type="hidden" name="image_id" value="'.htmlspecialchars($image_id).'">
	<input type="hidden" name="image_path" value="'.htmlspecialchars($image_path).'">
	<input type="hidden" name="image_ext" value="'.htmlspecialchars($image_ext).'">

	<input type="hidden" name="contact_name" value="'.osc_item_contact_name().'">
	<input type="hidden" name="contact_email" value="'.osc_item_contact_email().'">
	<input type="hidden" name="contact_email_en" value="'.osc_item_show_email().'">
	<input type="hidden" name="contact_phone" value="'.$phone.'">
	<input type="hidden" name="mobil" value="'.$mobil.'">
	<input type="hidden" name="mobil2" value="'.$mobil2.'">
	<input type="hidden" name="contact_website" value="'.$website.'">
	<input type="hidden" name="contact_address" value="'.$address.'">

	<input type="hidden" name="site_title" value="'.osc_page_title().'">
	<input type="hidden" name="site_url" value="'.osc_base_url().'">
        <input type="hidden" name="desc" value="'.$desc.'">
        <input type="hidden" name="title" value="'.$title.'">
        <input type="hidden" name="price" value="'.$price.'">
        <input type="hidden" name="pub_date" value="'.$pub_date.'">
        <input type="hidden" name="country" value="'.$country.'">
        <input type="hidden" name="region" value="'.$region.'">
        <input type="hidden" name="city" value="'.$city.'">
        <input type="hidden" name="zip" value="'.$zip.'">

        <a id="print_ad" href="#" onClick="formpopup();document.printform.submit();return false;">' . __('Print listing', 'print_ad') . '</a>
    </form>

    '; //end echo

} //end print_ad()



    // This is needed in order to be able to activate the plugin
    osc_register_plugin(osc_plugin_path(__FILE__), '') ;
    // This is a hack to show a Uninstall link at plugins table (you could also use some other hook to show a custom option panel)
    osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', '') ;


?>