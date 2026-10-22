<?php
  // Quick-view (legacy) renders the listing page itself
  if (Params::getParam('contentOnly') == 1) {
    require_once osc_base_path() . 'oc-content/themes/starter/item.php';
    exit;
  }

  // Old modal links: comments and seller contact now live on their own pages
  $al_type = Params::getParam('type');
  if ($al_type == 'add_comment') {
    osc_redirect_to(osc_item_url() . '#al-comments');
  }
  if ($al_type == 'publicContact' && (int)Params::getParam('userId') > 0) {
    osc_redirect_to(osc_user_public_profile_url((int)Params::getParam('userId')) . '#al-contact');
  }

  $al_logged = osc_is_web_user_logged_in();
?>
<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
</head>
<body id="body-item-send-friend" class="al-share-page">
  <?php osc_current_web_theme_path('header.php'); ?>

  <div class="al-wrap al-share">
    <a class="al-share-item al-panel" href="<?php echo osc_item_url(); ?>">
      <span class="al-share-thumb">
        <?php if (osc_count_item_resources()) { ?>
          <img src="<?php echo osc_resource_thumbnail_url(); ?>" alt="" />
        <?php } else { ?>
          <span class="al-card-noimg"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/></svg></span>
        <?php } ?>
      </span>
      <span class="al-share-text">
        <span class="al-card-cat"><?php echo osc_esc_html(osc_item_category()); ?></span>
        <strong><?php echo osc_esc_html(osc_item_title()); ?></strong>
        <?php if (osc_price_enabled_at_items()) { ?><span class="al-share-price"><?php echo antique_format_price(osc_item_price()); ?></span><?php } ?>
      </span>
    </a>

    <section class="al-panel al-section-block">
      <h2 class="al-panel-title"><?php _e('Email this listing to a friend', 'starter'); ?></h2>
      <p class="al-muted"><?php _e('We will send your friend a link to this listing with your message. Their address is not stored.', 'starter'); ?></p>

      <form action="<?php echo osc_base_url(true); ?>" method="post" id="sendfriend" name="sendfriend" class="al-form">
        <input type="hidden" name="action" value="send_friend_post" />
        <input type="hidden" name="page" value="item" />
        <input type="hidden" name="id" value="<?php echo osc_item_id(); ?>" />

        <?php if ($al_logged) { ?>
          <input type="hidden" name="yourName" value="<?php echo osc_esc_html(osc_logged_user_name()); ?>" />
          <input type="hidden" name="yourEmail" value="<?php echo osc_esc_html(osc_logged_user_email()); ?>" />
        <?php } else { ?>
          <div class="al-form-row al-form-2">
            <label><span><?php _e('Your name', 'starter'); ?> <em>*</em></span><input type="text" name="yourName" required autocomplete="name" value="" /></label>
            <label><span><?php _e('Your email', 'starter'); ?> <em>*</em></span><input type="email" name="yourEmail" required autocomplete="email" value="" /></label>
          </div>
        <?php } ?>

        <div class="al-form-row al-form-2">
          <label><span><?php _e('Friend\'s name', 'starter'); ?> <em>*</em></span><input type="text" name="friendName" required value="" /></label>
          <label><span><?php _e('Friend\'s email', 'starter'); ?> <em>*</em></span><input type="email" name="friendEmail" required value="" /></label>
        </div>
        <label class="al-form-full"><span><?php _e('Message', 'starter'); ?> <em>*</em></span><textarea name="message" required rows="5"><?php echo osc_esc_html(sprintf(__('I thought you might like this: %s', 'starter'), osc_item_title())); ?></textarea></label>
        <?php osc_show_recaptcha(); ?>
        <div class="al-share-actions">
          <button type="submit" class="al-btn al-btn-brass"><?php _e('Send email', 'starter'); ?></button>
          <a class="al-text-link" href="<?php echo osc_item_url(); ?>"><?php _e('Back to listing', 'starter'); ?></a>
        </div>
      </form>
    </section>
  </div>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
