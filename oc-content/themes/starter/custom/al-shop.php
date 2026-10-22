<?php
  $al_uid = (int)osc_logged_user_id();
  if (!antique_is_seller($al_uid)) {
    echo '<script>window.location.replace(' . json_encode(antique_seller_setup_url()) . ');</script>';
    return;
  }
  $al_u = User::newInstance()->findByPrimaryKey($al_uid);
  $al_shop = antique_shop($al_uid);
  $al_dao = User::newInstance()->dao;
  $al_desc = $al_dao->query(sprintf("SELECT s_info FROM `%st_user_description` WHERE fk_i_user_id = %d AND fk_c_locale_code = %s", DB_TABLE_PREFIX, $al_uid, $al_dao->escape(osc_current_user_locale())));
  $al_about = ($al_desc && $al_desc->numRows() > 0) ? (string)$al_desc->row()['s_info'] : '';
  $al_name = antique_seller_display_name($al_uid, $al_u['s_name']);
?>
<div class="al-account-head">
  <h2><?php _e('Shop settings', 'starter'); ?></h2>
  <a class="al-btn al-btn-outline" href="<?php echo osc_user_public_profile_url($al_uid); ?>"><?php _e('View my shop', 'starter'); ?></a>
</div>
<p class="al-account-intro"><?php _e('This is what buyers see on your shop page. Clear policies answer questions before buyers have to ask.', 'starter'); ?></p>

<form class="al-form al-shop-form" method="post" enctype="multipart/form-data" action="<?php echo osc_esc_html(osc_ajax_hook_url('al_shop')); ?>">
  <input type="hidden" name="octoken" value="<?php echo osc_csrfguard_generate_token(); ?>" />

  <section class="al-panel">
    <h3 class="al-shop-h"><?php _e('Banner', 'starter'); ?></h3>
    <div class="al-shop-banner-preview<?php echo $al_shop['banner'] ? '' : ' is-empty'; ?>"<?php echo $al_shop['banner'] ? ' style="background-image:url(\'' . osc_esc_html($al_shop['banner']) . '\')"' : ''; ?>>
      <?php if (!$al_shop['banner']) { ?><span><?php _e('No banner yet. Your shop shows the AntiqueLion pattern.', 'starter'); ?></span><?php } ?>
    </div>
    <label class="al-form-full al-shop-file"><span><?php echo $al_shop['banner'] ? __('Replace banner', 'starter') : __('Upload a banner', 'starter'); ?></span>
      <input type="file" name="banner" accept="image/jpeg,image/png,image/webp" />
      <small class="al-hint"><?php _e('A wide photo of your shop, stand or collection. JPG, PNG or WebP, at least 1200 × 300 pixels. We crop it to 4:1.', 'starter'); ?></small>
    </label>
    <?php if ($al_shop['banner']) { ?>
      <label class="al-check"><input type="checkbox" name="remove_banner" value="1" /><span><?php _e('Remove the banner', 'starter'); ?></span></label>
    <?php } ?>
  </section>

  <section class="al-panel">
    <h3 class="al-shop-h"><?php _e('About your shop', 'starter'); ?></h3>
    <label class="al-form-full"><span><?php _e('Tagline', 'starter'); ?> <em><?php _e('(one line under your name)', 'starter'); ?></em></span>
      <input type="text" name="tagline" maxlength="160" value="<?php echo osc_esc_html($al_shop['tagline']); ?>" placeholder="<?php echo osc_esc_html(__('e.g. Georgian silver and Ceylon furniture since 1998', 'starter')); ?>" />
    </label>
    <label class="al-form-full"><span><?php _e('About', 'starter'); ?></span>
      <textarea name="about" rows="5" maxlength="1500" placeholder="<?php echo osc_esc_html(__('What you collect or specialise in, how long you have been dealing, where buyers can visit you.', 'starter')); ?>"><?php echo osc_esc_html($al_about); ?></textarea>
    </label>
    <label class="al-form-full"><span><?php _e('Website or social page', 'starter'); ?> <em><?php _e('(optional)', 'starter'); ?></em></span>
      <input type="text" name="website" maxlength="200" inputmode="url" value="<?php echo osc_esc_html((string)$al_u['s_website']); ?>" placeholder="https://" />
    </label>
  </section>

  <section class="al-panel">
    <h3 class="al-shop-h"><?php _e('Shop policies', 'starter'); ?></h3>
    <label class="al-form-full"><span><?php _e('Shipping and delivery', 'starter'); ?></span>
      <textarea name="policy_ship" rows="3" maxlength="<?php echo ANTIQUE_SHOP_POLICY_MAX; ?>" placeholder="<?php echo osc_esc_html(__('e.g. Ships within 3 working days, insured and tracked. Large furniture by courier or collection from Galle.', 'starter')); ?>"><?php echo osc_esc_html($al_shop['shipping']); ?></textarea>
    </label>
    <label class="al-form-full"><span><?php _e('Returns', 'starter'); ?></span>
      <textarea name="policy_returns" rows="3" maxlength="<?php echo ANTIQUE_SHOP_POLICY_MAX; ?>" placeholder="<?php echo osc_esc_html(__('e.g. Returns accepted within 14 days if the item is not as described. Buyer pays return postage.', 'starter')); ?>"><?php echo osc_esc_html($al_shop['returns']); ?></textarea>
    </label>
    <label class="al-form-full"><span><?php _e('Payment', 'starter'); ?></span>
      <textarea name="policy_pay" rows="3" maxlength="<?php echo ANTIQUE_SHOP_POLICY_MAX; ?>" placeholder="<?php echo osc_esc_html(__('e.g. Card or cash on collection, bank transfer for regular buyers.', 'starter')); ?>"><?php echo osc_esc_html($al_shop['payment']); ?></textarea>
    </label>
  </section>

  <div class="al-shop-save">
    <button type="submit" class="al-btn al-btn-brass"><?php _e('Save shop page', 'starter'); ?></button>
  </div>
</form>
