<?php
/*
  Plugin Name: Social Bookmarks Plugin
  Plugin URI: https://osclasspoint.com/
  Description: Social bookmarks for item detail page
  Version: 1.2.0
  Author: OsclassPoint
  Author URI: https://osclasspoint.com
  Author Email: info@osclasspoint.com
  Short Name: social_bookmarks
  Plugin update URI: social_bookmarks
  Support URI: https://forums.osclasspoint.com/free-plugins/
  Product Key: vaqhSSRVtwzmb9BN8qxU
*/

function social_bookmarks() {
  $content  = '<div class="social-bookmarks">' ;
  $content .= '<ul>' ;
  // twitter
  $content .= '<li class="twitter"><a href="https://twitter.com/share" class="twitter-share-button" data-count="horizontal">Tweet</a><script type="text/javascript" src="https://platform.twitter.com/widgets.js"></script></li>' ;
  // facebook
  $content .= '<li class="facebook"><div id="fb-root"></div><script src="https://connect.facebook.net/en_US/all.js#xfbml=1"></script><fb:like href="" send="false" layout="button_count" show_faces="false" font=""></fb:like></li>' ;
  // pinterest
  $content .= '<li class="pinterest"><script type="text/javascript" src="//assets.pinterest.com/js/pinit.js"></script><a href="#" class="pin-it-button" count-layout="horizontal"><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="Pin It" /></a></li>' ;
  $content .= '</ul>' ;
  // clear
  $content .= '<div class="clear"></div>' ;
  $content .= '</div>';
  // pinterest
  $content .= '<script type="text/javascript" >$(document).ready(function() {var media = $("img[src*=\"oc-content/uploads/\"]").attr("src"); if(media==undefined) { media = ""; $(".pinterest").remove(); } else { media = "&media="+escape(media); };$(".pinterest").find("a").attr("href","https://pinterest.com/pin/create/button/?url="+escape(document.URL)+"&description="+escape(document.title)+media);});</script>';
  
  echo $content ;
}

function social_bookmarks_header( ) {
  $location   = Rewrite::newInstance()->get_location() ;
  $section  = Rewrite::newInstance()->get_section() ;
  
  if($location == 'item' && $section == '') {
    echo '
    <style type="text/css">
      .social-bookmarks ul { margin: 10px 0; list-style: none; }
      .social-bookmarks ul li { float: left; }
      .social-bookmarks .clear { clear:both; }
    </style>';
  }
}

/**
 *  HOOKS
 */
osc_register_plugin(osc_plugin_path(__FILE__), '');
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', '');

osc_add_hook('item_detail', 'social_bookmarks');
osc_add_hook('header', 'social_bookmarks_header');
?>