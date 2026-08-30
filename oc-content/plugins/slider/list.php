<link href="<?php echo osc_base_url().'oc-content/plugins/slider/slideradminstyle.css'; ?>" rel="stylesheet" type="text/css" />
<div id="slidermenu">
<ul>
<li><a href="<?php echo osc_admin_render_plugin_url("slider/create.php"); ?>"><?php echo __('Create', 'Slider'); ?></a></li>
<li class="current"><a href="#"><?php echo __('Manage', 'Slider'); ?></a></li>
<li><a href="<?php echo osc_admin_render_plugin_url("slider/settings.php"); ?>"><?php echo __('Settings', 'Slider'); ?></a></li>
<li><a href="<?php echo osc_admin_render_plugin_url("slider/help.php"); ?>"><?php echo __('F.A.Q. / Help', 'Slider'); ?></a></li>
</ul>
</div>
<table cellspacing="0" cellpadding="0" class="table">
<thead>
<tr>
<th class="col-bulkactions">#</th>
<th class="col-file">File</th>
<th class="col-action">Action</th>
<th class="col-action">Caption</th>
<th class="col-action">Link to URL</th>
</tr>
</thead>
<tbody>
<?php $slides = ModelSlider::newInstance()->getSlider(); ?>
<?php foreach($slides as $slide) { ?>
	<tr>
	<td><?php echo $slide['id']; ?></td>
	<td class="col-file"><div id="media_list_pic"><img style="max-width: 140px; max-height: 100px;" src="<?php echo osc_base_url().'oc-content/plugins/slider/images/'.$slide['uniqname']; ?>"></div> <div id="media_list_filename"><?php echo $slide['imagename']; ?></div></td>
	<td><a href="<?php echo osc_admin_render_plugin_url("slider/edit.php").'?actions=edit&id='.$slide['id']; ?>"><?php _e('Edit', 'Slider'); ?></a> | <a onclick="javascript:return confirm('<?php _e('This action can not be undone. Are you sure you want to continue?', 'Slider'); ?>')" href="<?php echo osc_admin_render_plugin_url("slider/edit.php").'?actions=delete&id='.$slide['id']; ?>"><?php _e('Delete' ,'Slider'); ?></a></td>
	<td><?php echo $slide['caption']; ?></td>	
	<td><?php echo $slide['link']; ?></td>		
	</tr>
	<?php } ?>
</tbody>
</table>