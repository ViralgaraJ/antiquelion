<link href="<?php echo osc_base_url().'oc-content/plugins/slider/slideradminstyle.css'; ?>" rel="stylesheet" type="text/css" />
<?php 
$id=Params::getParam('id');
$caption = Params::getParam('caption');
$link = Params::getParam('link');
$slidebyid= ModelSlider::newInstance()->getSliderById($id);
if( Params::getParam('actions') == 'delete' ){
	unlink(osc_content_path() . "plugins/slider/images/" . $slidebyid['uniqname']);
	ModelSlider::newInstance()->deleteSlider($id);
	?>
	<div class="slidersuccess">
	<?php echo _e('The slider has been deleted.','Slider'); ?>
	</div>
	<?php
	echo "<script>location.href='".osc_admin_render_plugin_url("slider/list.php")."'</script>";
}
else if( Params::getParam('option') == 'stepone' ) {
	if( Params::getParam('update') == 1) {
		// Where the file is going to be placed
		$target_path = osc_content_path() . "plugins/slider/images/";
		// Check that the uploaded file is actually an image
		$valid_mime_types = array("image/jpg","image/jpeg","image/png","image/gif");
		if (in_array($_FILES["image"]["type"], $valid_mime_types)){
			$destination = osc_content_path() . "plugins/slider/images/" . $_FILES["image"]["name"];
			$imagename = $_FILES["image"]["name"];
			$uniqname = uniqid() . '.' . pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
			$destination = osc_content_path() . "plugins/slider/images/" . $uniqname;		
			if(move_uploaded_file($_FILES["image"]["tmp_name"], $destination)){
				unlink(osc_content_path() . "plugins/slider/images/" . $slidebyid['uniqname']);
				ModelSlider::newInstance()->updateSlider($id,$uniqname,$imagename,$caption,$link);
				?>
				<div class="slidersuccess">
				<?php echo _e('The slider has been updated.','Slider'); ?>
				</div>
				<?php
				echo "<script>location.href='".osc_admin_render_plugin_url("slider/list.php")."'</script>";
			} 
			else{
				?>
				<div class="slidererror">
				<?php echo _e('There was an error uploading the file, please try again!','Slider'); ?>
				</div>
				<?php
			}
		}
		else{
			?>
			<div class="slidererror">
			<?php echo _e('File type not allowed, Allowed file: *.jpg,*.jpeg,*.png,*.gif','Slider'); ?>
			</div>
			<?php
		}	
	}
	else{
		$imagename = Params::getParam('update');
		$uniqname = uniqid() . '.' . pathinfo($imagename, PATHINFO_EXTENSION);
		$source = osc_content_path() . "plugins/slider/images/" . $imagename;
		$destination = osc_content_path() . "plugins/slider/images/". $uniqname;
		$actualimagename = ModelSlider::newInstance()->getSliderByImage($imagename);
		if (copy($source, $destination )) {
			unlink(osc_content_path() . "plugins/slider/images/" . $slidebyid['uniqname']);
			ModelSlider::newInstance()->updateSlider($id,$uniqname,$actualimagename['imagename'],$caption,$link);
			?>
			<div class="slidersuccess">
			<?php echo _e('The slider has been updated.','Slider'); ?>
			</div>
			<?php
			echo "<script>location.href='".osc_admin_render_plugin_url("slider/list.php")."'</script>";
		}
		else{
			?>
			<div class="slidererror">
			<?php echo _e('There was an error uploading the file, please try again!','Slider'); ?>
			</div>
			<?php
		}	
		
	}
}
?>
<div id="slidermenu">
<ul>
<li><a href="<?php echo osc_admin_render_plugin_url("slider/create.php"); ?>"><?php echo _e('Create', 'Slider'); ?></a></li>
<li><a href="<?php echo osc_admin_render_plugin_url("slider/list.php"); ?>"><?php echo _e('Manage', 'Slider'); ?></a></li>
<li class="current"><a href="#"><?php echo __('Edit', 'Slider'); ?></a></li>
<li><a href="<?php echo osc_admin_render_plugin_url("slider/settings.php"); ?>"><?php echo _e('Settings', 'Slider'); ?></a></li>
<li><a href="<?php echo osc_admin_render_plugin_url("slider/help.php"); ?>"><?php echo _e('F.A.Q. / Help', 'Slider'); ?></a></li>
</ul>
</div>
<h2 class="render-title ">Update Image</h2>
<form method="post" action="<?php osc_admin_base_url(true); ?>" enctype="multipart/form-data">
<input type="hidden" name="page" value="plugins" />
<input type="hidden" name="action" value="renderplugin" />
<input type="hidden" name="file" value="slider/edit.php" />
<input type="hidden" name="option" value="stepone" />
<input type="hidden" name="id" value="<?php echo $slidebyid['id']; ?>" />
<input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
<fieldset>
<div class="form-horizontal">
<div class="form-row">
<div class="form-label"><?php _e('Upload image', 'Slider'); ?></div>
<div class="form-controls">
<input type="radio" name="update" value="1" checked />  
<input type="file" size="50" name="image" value="" />
</div>
</div>
<div class="form-row">
<div class="form-label"><?php _e('Or Select a image:','Slider'); ?></div>
<div class="form-controls">&nbsp;</div>
</div>
<div class="form-row">			
<table cellspacing="0" cellpadding="0" class="table">
<thead>
<tr>
<th class="col-bulkactions "><?php _e('#', 'Slider'); ?></th>
<th class="col-file "><?php _e('File', 'Slider'); ?></th>
</tr>
</thead>
<tbody>
<?php $slides = ModelSlider::newInstance()->getSlider(); ?>
<?php foreach($slides as $slide) { ?>
	<tr>
	<td class="col-bulkactions"><input type="radio" name="update" value="<?php echo $slide['uniqname']; ?>" <?php if($slide['uniqname'] == $slidebyid['uniqname']){ echo 'checked'; } ?>/></td>
	<td class="col-file"><div id="media_list_pic"><img style="max-width: 140px; max-height: 120px;" src="<?php echo osc_base_url().'oc-content/plugins/slider/images/'.$slide['uniqname']; ?>"></div> <div id="media_list_filename"><?php echo $slide['imagename']; ?></div></td>
	</tr>
	<?php } ?>
</tbody>
</table>
</div>		
<div class="form-row">
<div class="form-label"><?php _e('Caption', 'Slider'); ?></div>
<div class="form-controls"><input type="text" name="caption" value="<?php echo $slidebyid['caption']; ?>" placeholder="This is a caption" class="xlarge"/></div>
</div>
<div class="form-row">
<div class="form-label"><?php _e('Link to URL', 'Slider'); ?></div>
<div class="form-controls"><input class="inputtext" type="text" name="link" value="<?php echo $slidebyid['link']; ?>" placeholder="http://www.example.com" class="xlarge" /></div>
</div>
<div class="form-actions">
<input type="submit" class="btn btn-submit" value="Save changes">
</div>
</div>
</fieldset>
</form>