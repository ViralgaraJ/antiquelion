<?php
  if (antique_is_seller()) {
    // already a seller: nothing to set up
    echo '<script>window.location.replace(' . json_encode(osc_item_post_url()) . ');</script>';
    return;
  }
  $al_u = User::newInstance()->findByPrimaryKey(osc_logged_user_id());
  $al_f = (array)Session::newInstance()->_get('al_seller_form');
  $al_v = function ($k, $default) use ($al_f) { return isset($al_f[$k]) ? $al_f[$k] : $default; };
  $al_type = $al_v('type', !empty($al_u['b_company']) ? 'dealer' : 'private');
  $al_country = $al_v('country', !empty($al_u['fk_c_country_code']) ? strtoupper($al_u['fk_c_country_code']) : strtoupper((string)antique_visitor_country()));
?>
<div class="al-account-head">
  <h2><?php _e('Start selling', 'starter'); ?></h2>
</div>

<div class="al-sell-intro al-panel">
  <ol class="al-sell-steps">
    <li class="is-done"><span>1</span><?php _e('Account created', 'starter'); ?></li>
    <li class="is-current"><span>2</span><?php _e('Seller details', 'starter'); ?></li>
    <li><span>3</span><?php _e('List your first item', 'starter'); ?></li>
  </ol>
  <p><?php _e('Selling is free. Buyers contact you directly or bid on your auctions, and you agree payment and delivery with them. Tell buyers who they are dealing with:', 'starter'); ?></p>
</div>

<form class="al-form al-panel al-sell-form" method="post" action="<?php echo osc_esc_html(osc_base_url(true) . '?page=ajax&action=runhook&hook=al_seller_setup'); ?>" novalidate>
  <input type="hidden" name="octoken" value="<?php echo osc_csrfguard_generate_token(); ?>" />

  <fieldset class="al-sell-type">
    <legend><?php _e('I am selling as', 'starter'); ?></legend>
    <label class="al-choice">
      <input type="radio" name="al_type" value="private"<?php echo $al_type != 'dealer' ? ' checked' : ''; ?> />
      <span><strong><?php _e('Private seller', 'starter'); ?></strong><em><?php _e('Selling items from my own collection or home.', 'starter'); ?></em></span>
    </label>
    <label class="al-choice">
      <input type="radio" name="al_type" value="dealer"<?php echo $al_type == 'dealer' ? ' checked' : ''; ?> />
      <span><strong><?php _e('Dealer or business', 'starter'); ?></strong><em><?php _e('An antiques shop, gallery or trader. Buyers see your business name.', 'starter'); ?></em></span>
    </label>
  </fieldset>

  <label class="al-form-full al-sell-business"<?php echo $al_type == 'dealer' ? '' : ' hidden'; ?>><span><?php _e('Business name', 'starter'); ?> <em>*</em></span>
    <input type="text" name="al_business" maxlength="100" value="<?php echo osc_esc_html($al_v('business', '')); ?>" />
  </label>

  <label class="al-form-full"><span><?php _e('Mobile phone', 'starter'); ?> <em>*</em></span>
    <span class="input-box"><input type="tel" name="s_phone_mobile" id="s_phone_mobile" value="<?php echo osc_esc_html($al_v('phone', (string)$al_u['s_phone_mobile'])); ?>" /></span>
    <small class="al-hint"><?php _e('Shown to buyers only when they choose to reveal it on your listings.', 'starter'); ?></small>
  </label>

  <div class="al-form-row al-form-2">
    <label><span><?php _e('Country', 'starter'); ?> <em>*</em></span>
      <?php echo antique_details_select('al_country', antique_details_countries(), $al_country, __('Select country', 'starter')); ?>
    </label>
    <label><span><?php _e('Town or city', 'starter'); ?> <em>*</em></span>
      <input type="text" name="al_city" maxlength="60" value="<?php echo osc_esc_html($al_v('city', (string)$al_u['s_city'])); ?>" />
    </label>
  </div>

  <label class="al-form-full"><span><?php _e('About you', 'starter'); ?> <em><?php _e('(optional)', 'starter'); ?></em></span>
    <textarea name="al_about" maxlength="1500" rows="4" placeholder="<?php echo osc_esc_html(__('What you collect or specialise in, and how long you have been dealing.', 'starter')); ?>"><?php echo osc_esc_html($al_v('about', '')); ?></textarea>
  </label>

  <label class="al-check al-sell-terms">
    <input type="checkbox" name="al_terms" value="1" />
    <span><?php echo sprintf(__('I will describe items honestly, only sell items I own or may sell, and follow the <a href="%1$s" target="_blank">Terms of use</a>, including the list of prohibited items.', 'starter'), antique_info_page_url('al-terms')); ?></span>
  </label>

  <button type="submit" class="al-btn al-btn-brass"><?php _e('Create seller account', 'starter'); ?></button>
</form>

<script>
  (function () {
    var biz = document.querySelector('.al-sell-business');
    document.querySelectorAll('input[name="al_type"]').forEach(function (r) {
      r.addEventListener('change', function () { biz.hidden = !(r.checked && r.value === 'dealer'); });
    });
  })();
</script>
<?php Session::newInstance()->_drop('al_seller_form'); ?>
