<?php
  $user_keep = osc_user();
  $is_me = osc_is_web_user_logged_in() && osc_logged_user_id() == osc_user_id();
  $can_contact = !$is_me && (!osc_reg_user_can_contact() || osc_is_web_user_logged_in());

  $phone = trim((string)(osc_user_phone_mobile() != '' ? osc_user_phone_mobile() : osc_user_phone_land()));
  $phone_ok = strlen($phone) >= 4;
  $phone_login = $phone_ok && osc_get_preference('reg_user_can_see_phone', 'osclass') == 1 && !osc_is_web_user_logged_in();
  $phone_digits = preg_replace('/[^0-9+]/', '', $phone);

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
            <span class="al-seller-name"><?php echo osc_esc_html(osc_user_name()); ?></span>
            <div class="al-seller-sub">
              <?php
                $sub = array(osc_user_field('b_company') == 1 ? __('Dealer', 'starter') : __('Private seller', 'starter'));
                if (osc_user_regdate() != '') { $sub[] = sprintf(__('Member since %s', 'starter'), date('M Y', strtotime(osc_user_regdate()))); }
                echo osc_esc_html(implode(' · ', $sub));
              ?>
            </div>
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
            <?php if ($can_contact) { ?>
              <a class="al-btn al-btn-brass al-btn-block" href="#al-contact"><?php _e('Message seller', 'starter'); ?></a>
            <?php } ?>
            <?php if ($phone_ok) { ?>
              <?php if ($phone_login) { ?>
                <a class="al-btn al-btn-outline al-btn-block" href="<?php echo osc_user_login_url(); ?>"><?php _e('Sign in to see phone number', 'starter'); ?></a>
              <?php } else { ?>
                <button type="button" class="al-btn al-btn-outline al-btn-block al-reveal-phone" data-phone="<?php echo osc_esc_html($phone); ?>" data-tel="<?php echo osc_esc_html($phone_digits); ?>">
                  <span><?php echo osc_esc_html(substr($phone, 0, max(0, strlen($phone) - 4))); ?>xxxx · <?php _e('Show number', 'starter'); ?></span>
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
        <h2><?php echo sprintf(__('Listings from %s', 'starter'), osc_esc_html(osc_user_name())); ?></h2>
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

      <?php if ($can_contact) { ?>
        <section class="al-panel al-section-block" id="al-contact">
          <h2 class="al-panel-title"><?php echo sprintf(__('Message %s', 'starter'), osc_esc_html(osc_user_name())); ?></h2>
          <form action="<?php echo osc_base_url(true); ?>" method="post" name="contact_form" id="contact_form_public" class="al-form">
            <input type="hidden" name="page" value="user" />
            <input type="hidden" name="action" value="contact_post" />
            <input type="hidden" name="id" value="<?php echo osc_user_id(); ?>" />
            <div class="al-form-row al-form-3">
              <label><span><?php _e('Your name', 'starter'); ?> <em>*</em></span><input type="text" name="yourName" required value="<?php echo osc_esc_html(osc_is_web_user_logged_in() ? osc_logged_user_name() : ''); ?>" /></label>
              <label><span><?php _e('Email', 'starter'); ?> <em>*</em></span><input type="email" name="yourEmail" required value="<?php echo osc_esc_html(osc_is_web_user_logged_in() ? osc_logged_user_email() : ''); ?>" /></label>
              <label><span><?php _e('Phone (optional)', 'starter'); ?></span><input type="tel" name="phoneNumber" value="" /></label>
            </div>
            <label class="al-form-full"><span><?php _e('Message', 'starter'); ?> <em>*</em></span><textarea name="message" required rows="5"></textarea></label>
            <?php osc_show_recaptcha(); ?>
            <button type="submit" class="al-btn al-btn-brass"><?php _e('Send message', 'starter'); ?></button>
          </form>
        </section>
      <?php } else if (!$is_me) { ?>
        <section class="al-panel al-section-block" id="al-contact">
          <h2 class="al-panel-title"><?php _e('Contact this seller', 'starter'); ?></h2>
          <p class="al-muted"><?php _e('Please sign in to message sellers.', 'starter'); ?></p>
          <a class="al-btn al-btn-ink" href="<?php echo osc_user_login_url(); ?>"><?php _e('Sign in', 'starter'); ?></a>
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
