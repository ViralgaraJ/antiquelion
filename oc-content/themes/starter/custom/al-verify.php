<?php
  $al_uid = (int)osc_logged_user_id();
  if (!antique_is_seller($al_uid)) {
    echo '<script>window.location.replace(' . json_encode(antique_seller_setup_url()) . ');</script>';
    return;
  }
  $al_u = User::newInstance()->findByPrimaryKey($al_uid);
  $al_row = antique_seller_row($al_uid);
  $al_status = antique_verify_status($al_uid);
  $al_dealer = !empty($al_u['b_company']);
  $al_f = (array)Session::newInstance()->_get('al_verify_form');
  Session::newInstance()->_drop('al_verify_form');
  $al_v = function ($k, $col) use ($al_f, $al_row) { return isset($al_f[$k]) ? $al_f[$k] : (string)$al_row[$col]; };
?>
<div class="al-account-head">
  <h2><?php _e('Get verified', 'starter'); ?></h2>
  <?php echo antique_seller_badge($al_uid); ?>
</div>

<?php if ($al_status == ANTIQUE_VERIFY_OK) { ?>
  <div class="al-verify-state al-panel is-ok">
    <?php echo antique_seller_badge($al_uid); ?>
    <h3><?php _e('Your account is verified', 'starter'); ?></h3>
    <p><?php echo sprintf(__('Verified on %s. Buyers see the badge next to your name on your listings, your shop page and in messages.', 'starter'), osc_esc_html(date('j F Y', strtotime($al_row['dt_verified'])))); ?></p>
    <a class="al-btn al-btn-outline" href="<?php echo osc_user_public_profile_url($al_uid); ?>"><?php _e('See your shop page', 'starter'); ?></a>
  </div>
<?php } elseif ($al_status == ANTIQUE_VERIFY_PENDING) { ?>
  <div class="al-verify-state al-panel is-pending">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
    <h3><?php _e('We are checking your details', 'starter'); ?></h3>
    <p><?php echo sprintf(__('You sent your request on %s. Checks usually take up to 2 working days, and we will email you the result.', 'starter'), osc_esc_html(date('j F Y', strtotime($al_row['dt_verify_req'])))); ?></p>
  </div>
<?php } else { ?>
  <?php if ($al_status == ANTIQUE_VERIFY_DECLINED) { ?>
    <div class="al-notice al-verify-declined">
      <strong><?php _e('Your last request was not approved.', 'starter'); ?></strong>
      <?php if (trim((string)$al_row['s_verify_note']) !== '') { ?><span><?php echo sprintf(__('Reason: %s', 'starter'), osc_esc_html($al_row['s_verify_note'])); ?></span><?php } ?>
      <span><?php _e('Correct the details below and send a new request.', 'starter'); ?></span>
    </div>
  <?php } ?>

  <div class="al-verify-why al-panel">
    <ul>
      <li><strong><?php _e('A badge buyers trust', 'starter'); ?></strong><span><?php _e('Shown next to your name on every listing, your shop page and in messages.', 'starter'); ?></span></li>
      <li><strong><?php _e('Checked by our team', 'starter'); ?></strong><span><?php _e('We compare your name and address with your document. It is never shown to buyers.', 'starter'); ?></span></li>
      <li><strong><?php _e('Free, about 2 working days', 'starter'); ?></strong><span><?php _e('We email you when it is done and delete the document after checking it.', 'starter'); ?></span></li>
    </ul>
  </div>

  <form class="al-form al-panel al-verify-form" method="post" enctype="multipart/form-data" action="<?php echo osc_esc_html(osc_ajax_hook_url('al_verify')); ?>" novalidate>
    <input type="hidden" name="octoken" value="<?php echo osc_csrfguard_generate_token(); ?>" />
    <label class="al-form-full"><span><?php echo $al_dealer ? __('Registered business name', 'starter') : __('Full legal name', 'starter'); ?> <em>*</em></span>
      <input type="text" name="legal_name" maxlength="150" autocomplete="<?php echo $al_dealer ? 'organization' : 'name'; ?>" value="<?php echo osc_esc_html($al_v('legal', 's_legal_name') !== '' ? $al_v('legal', 's_legal_name') : ($al_dealer ? antique_seller_display_name($al_uid, '') : $al_u['s_name'])); ?>" />
    </label>
    <?php if ($al_dealer) { ?>
      <label class="al-form-full"><span><?php _e('Company or business registration number', 'starter'); ?> <em><?php _e('(if you have one)', 'starter'); ?></em></span>
        <input type="text" name="reg_number" maxlength="60" value="<?php echo osc_esc_html($al_v('reg', 's_reg_number')); ?>" />
      </label>
    <?php } ?>
    <label class="al-form-full"><span><?php echo $al_dealer ? __('Business address', 'starter') : __('Home address', 'starter'); ?> <em>*</em></span>
      <input type="text" name="address" maxlength="255" autocomplete="street-address" value="<?php echo osc_esc_html($al_v('address', 's_address')); ?>" placeholder="<?php echo osc_esc_html(__('Street, town, postcode, country', 'starter')); ?>" />
    </label>
    <label class="al-form-full"><span><?php _e('Website or social page', 'starter'); ?> <em><?php _e('(optional)', 'starter'); ?></em></span>
      <input type="text" name="website" maxlength="200" inputmode="url" value="<?php echo osc_esc_html($al_v('website', 's_website')); ?>" placeholder="https://" />
    </label>
    <label class="al-form-full al-verify-file"><span><?php echo $al_dealer ? __('Business registration or a recent business document', 'starter') : __('Passport, driving licence or national ID card', 'starter'); ?> <em>*</em></span>
      <input type="file" name="doc" accept="image/jpeg,image/png,image/webp,application/pdf" required />
      <small class="al-hint"><?php _e('JPG, PNG, WebP or PDF, up to 8 MB. Make sure your name and address are readable. You may cover your ID number.', 'starter'); ?></small>
    </label>
    <label class="al-check">
      <input type="checkbox" name="consent" value="1" />
      <span><?php echo sprintf(__('These details are true and I allow %s to check them. See the <a href="%s" target="_blank">Privacy policy</a>.', 'starter'), osc_esc_html(osc_page_title()), antique_info_page_url('al-privacy')); ?></span>
    </label>
    <button type="submit" class="al-btn al-btn-brass"><?php _e('Send for checking', 'starter'); ?></button>
  </form>
<?php } ?>
