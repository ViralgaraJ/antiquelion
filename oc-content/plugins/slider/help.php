<link href="<?php echo osc_base_url().'oc-content/plugins/slider/slideradminstyle.css'; ?>" rel="stylesheet" type="text/css" />
<div id="slidermenu">
<ul>
<li><a href="<?php echo osc_admin_render_plugin_url("slider/create.php"); ?>"><?php echo _e('Create', 'Slider'); ?></a></li>
<li><a href="<?php echo osc_admin_render_plugin_url("slider/list.php"); ?>"><?php echo _e('Manage', 'Slider'); ?></a></li>
<li><a href="<?php echo osc_admin_render_plugin_url("slider/settings.php"); ?>"><?php echo _e('Settings', 'Slider'); ?></a></li>
<li class="current"><a href="#"><?php echo _e('F.A.Q. / Help', 'Slider'); ?></a></li>
</ul>
</div>

<h2 class="render-title"><?php _e('OSC Slider Help', 'Slider'); ?></h2>
<h3><?php _e('What is OSC Slider Plugin?', 'Slider'); ?></h3>
<p><?php _e('OSC Slider Plugin allows you to show a JQuery Slideshow on any part of your site you want.', 'Slider'); ?></p>
<h3><?php _e('How does OSC Slider Plugin work?', 'Slider'); ?></h3>
<p><?php _e('In order to use OSC Slider Plugin, you should edit your theme files and add the following line anywhere in the code you want the JQuery Slideshow to appear.', 'Slider'); ?>:</p>
<h3><?php _e('Recommened Place', 'Slider'); ?></h3>
<p><?php _e('Locate these line in your main.php &ltdiv class="content home"&gt', 'Slider'); ?>.</p>
<pre>					&ltdiv class="content home"&gt'					</pre>
<p><?php _e('Replace the above line with this', 'Slider'); ?></p>
<pre>					&ltdiv class="content home"&gt					
					&lt?php osc_slider(); ?&gt					</pre>