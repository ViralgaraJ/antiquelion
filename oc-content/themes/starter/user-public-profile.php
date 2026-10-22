<?php
  $user_keep = osc_user();
  $is_me = osc_is_web_user_logged_in() && osc_logged_user_id() == osc_user_id();
  $can_contact = !$is_me && (!osc_reg_user_can_contact() || osc_is_web_user_logged_in());

  $phone = trim((string)(osc_user_phone_mobile() != '' ? osc_user_phone_mobile() : osc_user_phone_land()));
  $phone_ok = strlen($phone) >= 4;
  $phone_login = $phone_ok && osc_get_preference('reg_user_can_see_phone', 'osclass') == 1 && !osc_is_web_user_logged_in();
  $al_phone = antique_phone_parts($phone, array(osc_user_field('fk_c_country_code')));
  $phone_digits = $al_phone['e164'] !== '' ? $al_phone['e164'] : preg_replace('/[^0-9+]/', '', $phone);
  $phone = $al_phone['display'];

  $place = implode(', ', array_filter(array(osc_user_city(), osc_user_region(), osc_user_country())));
  $about = osc_user_info();
?>
<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <script src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js'); ?>"></script>
</head>
<body id="body-user-public-profile" class="al-profile-page">
  <?php View::newInstance()->_exportVariableToView('user', $user_keep); ?>
  <?php osc_current_web_theme_path('header.php'); ?>

  <div class="al-wrap al-profile">
    <aside class="al-profile-side">
      <div class="al-panel al-seller">
        <?php osc_run_hook('user_public_profile_sidebar_top'); ?>
        <div class="al-seller-head">
          <img class="al-seller-avatar" src="<?php echo starter_profile_picture(osc_user_id(), 'medium'); ?>" alt="" width="56" height="56" />
          <div>
            <span class="al-seller-name"><?php echo osc_esc_html(antique_seller_display_name(osc_user_id(), osc_user_name())); ?></span>
            <?php echo antique_seller_badge(osc_user_id()); ?>
            <div class="al-seller-sub">
              <?php
                $sub = array(osc_user_field('b_company') == 1 ? __('Dealer', 'starter') : __('Private seller', 'starter'));
                if (osc_user_regdate() != '') { $sub[] = sprintf(__('Member since %s', 'starter'), date('M Y', strtotime(osc_user_regdate()))); }
                echo osc_esc_html(implode(' · ', $sub));
              ?>
            </div>
            <?php echo antique_rating_line(osc_user_id()); ?>
          </div>
        </div>

        <ul class="al-meta">
          <?php if ($place !== '') { ?>
            <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg><?php echo osc_esc_html($place); ?></li>
          <?php } ?>
          <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 7h-9M14 17H5M17 20a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM7 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg><?php echo sprintf(_n('%d active listing', '%d active listings', (int)osc_user_items_validated(), 'starter'), (int)osc_user_items_validated()); ?></li>
          <?php if (osc_user_website() != '') { ?>
            <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg><a href="<?php echo osc_esc_html(osc_user_website()); ?>" target="_blank" rel="nofollow noopener"><?php echo osc_esc_html(preg_replace('#^https?://#', '', osc_user_website())); ?></a></li>
          <?php } ?>
        </ul>

        <?php if ($about != '') { ?>
          <p class="al-profile-about"><?php echo nl2br(osc_esc_html($about)); ?></p>
        <?php } ?>

        <?php if (!$is_me) { ?>
          <div class="al-seller-actions">
            <a class="al-btn al-btn-brass al-btn-block" href="#al-contact"><?php _e('Message seller', 'starter'); ?></a>
            <?php if ($phone_ok) { ?>
              <?php if ($phone_login) { ?>
                <a class="al-btn al-btn-outline al-btn-block" href="<?php echo osc_user_login_url(); ?>"><?php _e('Sign in to see phone number', 'starter'); ?></a>
              <?php } else { ?>
                <button type="button" class="al-btn al-btn-outline al-btn-block al-reveal-phone" data-phone="<?php echo osc_esc_html($phone); ?>" data-tel="<?php echo osc_esc_html($phone_digits); ?>">
                  <span><?php _e('Show phone number', 'starter'); ?></span>
                </button>
              <?php } ?>
            <?php } ?>
          </div>
        <?php } else { ?>
          <a class="al-btn al-btn-outline al-btn-block" href="<?php echo osc_user_profile_url(); ?>" style="margin-top:18px"><?php _e('Edit your profile', 'starter'); ?></a>
        <?php } ?>

        <?php if (function_exists('show_feedback_overall')) { ?><div class="al-profile-plugin"><?php echo show_feedback_overall(); ?> <?php echo leave_feedback(); ?></div><?php } ?>
        <?php if (function_exists('ur_hook_show_rating_stars')) { ?><div class="al-profile-plugin"><?php echo ur_show_rating_stars(); ?> <?php echo ur_add_rating_link(); ?></div><?php } ?>
        <?php osc_run_hook('user_public_profile_sidebar_bottom'); ?>
      </div>
    </aside>

    <main class="al-profile-main">
      <div class="al-section-head">
        <h2><?php echo sprintf(__('Listings from %s', 'starter'), osc_esc_html(antique_seller_display_name(osc_user_id(), osc_user_name()))); ?></h2>
      </div>
      <?php echo starter_banner('public', 1); ?>
      <?php osc_run_hook('user_public_profile_items_top'); ?>

      <?php if (osc_count_items() > 0) { ?>
        <div class="al-grid al-profile-grid">
          <?php while (osc_has_items()) { include __DIR__ . '/inc.al-card.php'; } ?>
        </div>
        <?php $al_pages = osc_pagination_items(); ?>
        <?php if (trim(strip_tags((string)$al_pages)) !== '') { ?>
          <nav class="al-pagination" aria-label="<?php echo osc_esc_html(__('Pages', 'starter')); ?>"><?php echo $al_pages; ?></nav>
        <?php } ?>
      <?php } else { ?>
        <div class="al-empty-state"><h2><?php _e('No active listings right now', 'starter'); ?></h2><p><?php _e('Save a search to hear when this seller lists something new.', 'starter'); ?></p></div>
      <?php } ?>

      <?php
        $al_rv = antique_seller_reviews(osc_user_id(), 100);
        $al_viewer = osc_is_web_user_logged_in() ? (int)osc_logged_user_id() : 0;
      ?>
      <section class="al-panel al-section-block al-reviews" id="al-reviews">
        <h2 class="al-panel-title"><?php _e('Reviews', 'starter'); ?></h2>
        <?php if ($al_rv) { ?>
          <?php echo antique_rating_summary(osc_user_id()); ?>
          <div class="al-review-list">
            <?php foreach ($al_rv as $r) { echo antique_review_html($r, $al_viewer); } ?>
          </div>
        <?php } else { ?>
          <p class="al-muted"><?php echo $is_me ? __('No reviews yet. Buyers can review you after they win your auction, you accept their offer, or you talk with them about an item.', 'starter') : __('No reviews yet. Buyers can review this seller after buying from them.', 'starter'); ?></p>
        <?php } ?>
      </section>

      <?php if (!$is_me) { ?>
        <section class="al-panel al-section-block" id="al-contact">
          <h2 class="al-panel-title"><?php echo sprintf(__('Message %s', 'starter'), osc_esc_html(antique_seller_display_name(osc_user_id(), osc_user_name()))); ?></h2>
          <?php if (osc_is_web_user_logged_in()) { ?>
            <?php echo antique_msg_compose(0, osc_user_id(), antique_seller_display_name(osc_user_id(), osc_user_name())); ?>
          <?php } else { ?>
            <p class="al-muted"><?php _e('Sign in to message this seller. Your conversation stays on the site and your email address is never shown.', 'starter'); ?></p>
            <div class="al-inline-actions">
              <a class="al-btn al-btn-ink" href="<?php echo osc_esc_html(antique_login_back_url(osc_user_public_profile_url(osc_user_id()) . '#al-contact')); ?>"><?php _e('Sign in', 'starter'); ?></a>
              <a class="al-btn al-btn-outline" href="<?php echo osc_register_account_url(); ?>"><?php _e('Create an account', 'starter'); ?></a>
            </div>
          <?php } ?>
        </section>
      <?php } ?>
    </main>
  </div>

  <script>
    (function () {
      var btn = document.querySelector('.al-reveal-phone');
      if (!btn) return;
      btn.addEventListener('click', function () {
        var phone = btn.getAttribute('data-phone'), tel = btn.getAttribute('data-tel');
        var wrap = document.createElement('div');
        wrap.className = 'al-phone-revealed';
        var call = document.createElement('a');
        call.className = 'al-btn al-btn-outline al-btn-block';
        call.href = 'tel:' + tel;
        call.textContent = '<?php echo osc_esc_js(__('Call', 'starter')); ?> ' + phone;
        wrap.appendChild(call);
        if (tel.charAt(0) === '+') {
          var wa = document.createElement('a');
          wa.className = 'al-btn al-btn-whatsapp al-btn-block';
          wa.href = 'https://wa.me/' + tel.replace('+', '');
          wa.target = '_blank'; wa.rel = 'noopener';
          wa.textContent = 'WhatsApp';
          wrap.appendChild(wa);
        }
        btn.replaceWith(wrap);
      });
    })();
  </script>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
