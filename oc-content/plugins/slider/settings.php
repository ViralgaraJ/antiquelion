<link href="<?php echo osc_base_url().'oc-content/plugins/slider/slideradminstyle.css'; ?>" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="<?php echo osc_base_url(). 'oc-content/plugins/slider/jscolor/jscolor.js'; ?>"></script>
<?Php
if(Params::getParam('backgroundcolor') != '') {
	$backgroundcolor = Params::getParam('backgroundcolor');
} else {
	$backgroundcolor = (getSliderBackgroundColor() != '') ? getSliderBackgroundColor() : '' ;
}if(Params::getParam('bordercolor') != '') {
	$bordercolor = Params::getParam('bordercolor');
} else {
	$bordercolor = (getSliderBorderColor() != '') ? getSliderBorderColor() : '' ;
}if(Params::getParam('borderwidth') != '') {
	$borderwidth = Params::getParam('borderwidth');
} else {
	$borderwidth = (getSliderBorderWidth() != '') ? getSliderBorderWidth() : '' ;
}if(Params::getParam('shadowcolor') != '') {
	$shadowcolor = Params::getParam('shadowcolor');
} else {
	$shadowcolor = (getSliderShadowColor() != '') ? getSliderShadowColor() : '' ;
}if(Params::getParam('width') != '') {
	$width = Params::getParam('width');
} else {
	$width = (getSliderWidth() != '') ? getSliderWidth() : '' ;
}if(Params::getParam('height') != '') {
	$height = Params::getParam('height');
} else {
	$height = (getSliderHeight() != '') ? getSliderHeight() : '' ;
}if(Params::getParam('caption') != '') {
	$caption = Params::getParam('caption');
} else {
	$caption = (getSliderCaption() != '') ? getSliderCaption() : '' ;
}if(Params::getParam('link') != '') {
	$link = Params::getParam('link');
} else {
	$link = (getSliderLink() != '') ? getSliderLink() : '' ;
}if(Params::getParam('openin') != '') {
	$openin = Params::getParam('openin');
} else {
	$openin = (getSliderOpenIn() != '') ? getSliderOpenIn() : '' ;
}if(Params::getParam('auto') != '') {
	$auto = Params::getParam('auto');
} else {
	$auto = (getSliderAuto() != '') ? getSliderAuto() : '' ;
}if(Params::getParam('pager') != '') {
	$pager = Params::getParam('pager');
} else {
	$pager = (getSliderPager() != '') ? getSliderPager() : '' ;
}if(Params::getParam('navigation') != '') {
	$navigation = Params::getParam('navigation');
} else {
	$navigation = (getSliderNavigation() != '') ? getSliderNavigation() : '' ;
}if(Params::getParam('speed') != '') {
	$speed = Params::getParam('speed');
} else {
	$speed = (getSliderSpeed() != '') ? getSliderSpeed() : '' ;
}if( Params::getParam('option') == 'stepone' ) {
	$dao_preference = new Preference();
	$dao_preference->update(array("s_value" => $backgroundcolor), array("s_section" =>"Slider", "s_name" => "backgroundcolor")) ;
	$dao_preference->update(array("s_value" => $bordercolor), array("s_section" =>"Slider", "s_name" => "bordercolor")) ;
	$dao_preference->update(array("s_value" => $borderwidth ), array("s_section" =>"Slider", "s_name" => "borderwidth")) ;
	$dao_preference->update(array("s_value" => $shadowcolor ), array("s_section" =>"Slider", "s_name" => "shadowcolor")) ;
	$dao_preference->update(array("s_value" => $width), array("s_section" =>"Slider", "s_name" => "width")) ;
	$dao_preference->update(array("s_value" => $height), array("s_section" =>"Slider", "s_name" => "height")) ;
	$dao_preference->update(array("s_value" => $caption ), array("s_section" =>"Slider", "s_name" => "caption")) ;
	$dao_preference->update(array("s_value" => $link ), array("s_section" =>"Slider", "s_name" => "link")) ;
	$dao_preference->update(array("s_value" => $openin ), array("s_section" =>"Slider", "s_name" => "openin")) ;
	$dao_preference->update(array("s_value" => $auto ), array("s_section" =>"Slider", "s_name" => "auto")) ;
	$dao_preference->update(array("s_value" => $pager), array("s_section" =>"Slider", "s_name" => "pager")) ;
	$dao_preference->update(array("s_value" => $navigation ), array("s_section" =>"Slider", "s_name" => "navigation")) ;
	$dao_preference->update(array("s_value" => $speed ), array("s_section" =>"Slider", "s_name" => "speed")) ;
	?>
	<div class="slidersuccess">
	<?php echo __('Settings Saved','Slider'); ?>
	</div>
	<?php
}unset($dao_preference) ;
?>
<div id="slidermenu">
<ul>
<li><a href="<?php echo osc_admin_render_plugin_url("slider/create.php"); ?>"><?php echo __('Create', 'Slider'); ?></a></li>
<li><a href="<?php echo osc_admin_render_plugin_url("slider/list.php"); ?>"><?php echo __('Manage', 'Slider'); ?></a></li>
<li class="current"><a href="#"><?php echo __('Settings', 'Slider'); ?></a></li>
<li><a href="<?php echo osc_admin_render_plugin_url("slider/help.php"); ?>"><?php echo __('F.A.Q. / Help', 'Slider'); ?></a></li>
</ul>
</div>

<h2 class="render-title ">Settings</h2>
<form method="post" action="<?php osc_admin_base_url(true); ?>">
<input type="hidden" name="page" value="plugins" />
<input type="hidden" name="action" value="renderplugin" />
<input type="hidden" name="file" value="slider/settings.php" />
<input type="hidden" name="option" value="stepone" />
<fieldset>
<div class="form-horizontal">

<!--
<div class="form-row">
<div class="form-label"><?php _e('Background Color', 'Slider'); ?></div>
<div class="form-controls"><input type="text" class="color {hash:true}" value="<?php echo $backgroundcolor; ?>" name="backgroundcolor" class="xlarge"/> <?php _e('Default #FFFFFF', 'Slider'); ?></div>
</div>
<div class="form-row">
<div class="form-label"><?php _e('Border Color', 'Slider'); ?></div>
<div class="form-controls"><input type="text" class="color {hash:true}" value="<?php echo $bordercolor; ?>" name="bordercolor" class="xlarge"/> <?php _e('Default #FFFFFF', 'Slider'); ?></div>
</div>
<div class="form-row">
<div class="form-label"><?php _e('Border Width', 'Slider'); ?></div>
<div class="form-controls"><input type="text" value="<?php echo $borderwidth; ?>" name="borderwidth" class="xlarge"/> <?php _e('Default 10', 'Slider'); ?></div>
</div>
<div class="form-row">
<div class="form-label"><?php _e('Box Shadow Color', 'Slider'); ?></div>
<div class="form-controls"><input type="text" class="color {hash:true}" value="<?php echo $shadowcolor; ?>" name="shadowcolor" class="xlarge"/> <?php _e('Default #333333', 'Slider'); ?></div>
</div>
-->

<div class="form-row">
<div class="form-label"><?php _e('Width', 'Slider'); ?></div>
<div class="form-controls"><input type="text" value="<?php echo $width; ?>" name="width" class="xlarge"/> <?php _e('Set 0 for default full width', 'Slider'); ?></div>
</div>
<div class="form-row">
<div class="form-label"><?php _e('Height', 'Slider'); ?></div>
<div class="form-controls"><input type="text" value="<?php echo $height; ?>" name="height" class="xlarge"/> <?php _e('Set 0 for default full height', 'Slider'); ?></div>
</div>

<!--
<div class="form-row">
<div class="form-label"><?php _e('Caption', 'Slider'); ?></div>
<div class="form-controls">
<select name="caption">
<option <?php if($caption ==1){echo 'selected="selected"';}?> value='1'><?php _e('Enable', 'Slider'); ?></option>
<option <?php if($caption ==0){echo 'selected="selected"';}?> value='0'><?php _e('Disable', 'Slider'); ?></option>
</select> 
</div>
</div>
-->

<div class="form-row">
<div class="form-label"><?php _e('Link to URL', 'Slider'); ?></div>
<div class="form-controls">
<select name="link">
<option <?php if($link ==1){echo 'selected="selected"';}?> value='1'><?php _e('Enable', 'Slider'); ?></option>
<option <?php if($link ==0){echo 'selected="selected"';}?> value='0'><?php _e('Disable', 'Slider'); ?></option>
</select> 
</div>
</div>
<div class="form-row">
<div class="form-label"><?php _e('Open Link in', 'Slider'); ?></div>
<div class="form-controls">
<select name="openin">
<option <?php if($openin ==1){echo 'selected="selected"';}?> value='1'><?php _e('New Window', 'Slider'); ?></option>
<option <?php if($openin ==0){echo 'selected="selected"';}?> value='0'><?php _e('Same Window', 'Slider'); ?></option>
</select> 
</div>
</div>
<div class="form-row">
<div class="form-label"><?php _e('Auto Slide', 'Slider'); ?></div>
<div class="form-controls">
<select name="auto">
<option <?php if($auto ==1){echo 'selected="selected"';}?> value='1'><?php _e('Enable', 'Slider'); ?></option>
<option <?php if($auto ==0){echo 'selected="selected"';}?> value='0'><?php _e('Disable', 'Slider'); ?></option>
</select> 
</div>
</div>
<div class="form-row">
<div class="form-label"><?php _e('Pager', 'Slider'); ?></div>
<div class="form-controls">
<select name="pager">
<option <?php if($pager ==1){echo 'selected="selected"';}?> value='1'><?php _e('Enable', 'Slider'); ?></option>
<option <?php if($pager ==0){echo 'selected="selected"';}?> value='0'><?php _e('Disable', 'Slider'); ?></option>
</select> 
</div>
</div>
<div class="form-row">
<div class="form-label"><?php _e('Navigation', 'Slider'); ?></div>
<div class="form-controls">
<select name="navigation">
<option <?php if($navigation ==1){echo 'selected="selected"';}?> value='1'><?php _e('Enable', 'Slider'); ?></option>
<option <?php if($navigation ==0){echo 'selected="selected"';}?> value='0'><?php _e('Disable', 'Slider'); ?></option>
</select> 
</div>
</div>
<div class="form-row">
<div class="form-label"><?php _e('Speed', 'Slider'); ?></div>
<div class="form-controls"><input type="text" value="<?php echo $speed; ?>" name="speed" class="xlarge"> <?php _e('Default 500', 'Slider'); ?></div>
</div>			
<div class="form-actions">
<input type="submit" class="btn btn-submit" value="Save changes" />
</div>
</div>
</fieldset>
</form>