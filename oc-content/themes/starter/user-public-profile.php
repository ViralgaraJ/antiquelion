<?php
  $user_keep = osc_user();
  $al_sid = (int)osc_user_id();
  $is_me = osc_is_web_user_logged_in() && osc_logged_user_id() == $al_sid;

  $phone = trim((string)(osc_user_phone_mobile() != '' ? osc_user_phone_mobile() : osc_user_phone_land()));
  $phone_ok = strlen($phone) >= 4;
  $phone_login = $phone_ok && osc_get_preference('reg_user_can_see_phone', 'osclass') == 1 && !osc_is_web_user_logged_in();
  $al_phone = antique_phone_parts($phone, array(osc_user_field('fk_c_country_code')));
  $phone_digits = $al_phone['e164'] !== '' ? $al_phone['e164'] : preg_replace('/[^0-9+]/', '', $phone);
  $phone = $al_phone['display'];

  $place = implode(', ', array_filter(array(osc_user_city(), osc_user_region(), osc_user_country())));
  $about = osc_user_info();
  $al_name = antique_seller_display_name($al_sid, osc_user_name());
  $al_shop = antique_shop($al_sid);
  $al_rating = antique_seller_rating($al_sid);
  $al_resp = antique_shop_response($al_sid);
  $al_sold = antique_shop_sold_count($al_sid);
  $al_active = (int)osc_user_items_validated();
  $al_rv = antique_seller_reviews($al_sid, 100);
  $al_viewer = osc_is_web_user_logged_in() ? (int)osc_logged_user_id() : 0;
  $al_policies = array_filter(array(
    __('Shipping and delivery', 'starter') => $al_shop['shipping'],
    __('Returns', 'starter') => $al_shop['returns'],
    __('Payment', 'starter') => $al_shop['payment']
  ), function ($v) { return $v !== ''; });
?>
<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <?php if ($about != '' || $al_shop['tagline'] !== '') { ?><meta name="description" content="<?php echo osc_esc_html(mb_substr($al_shop['tagline'] !== '' ? $al_shop['tagline'] : $about, 0, 160)); ?>" /><?php } ?>
  <?php if ($al_shop['banner']) { ?><meta property="og:image" content="<?php echo osc_esc_html($al_shop['banner']); ?>" /><?php } ?>
</head>
<body id="body-user-public-profile" class="al-profile-page al-shop-page">
  <?php View::newInstance()->_exportVariableToView('user', $user_keep); ?>
  <?php osc_current_web_theme_path('header.php'); ?>

  <div class="al-wrap al-shop">
    <?php osc_run_hook('user_public_profile_sidebar_top'); ?>
    <header class="al-shop-hero">
      <div class="al-shop-banner<?php echo $al_shop['banner'] ? '' : ' is-pattern'; ?>"<?php echo $al_shop['banner'] ? ' style="background-image:url(\'' . osc_esc_html($al_shop['banner']) . '\')"' : ''; ?>></div>
      <div class="al-shop-id">
        <img class="al-shop-avatar" src="<?php echo starter_profile_picture($al_sid, 'medium'); ?>" alt="" width="104" height="104" />
        <div class="al-shop-title">
          <h1><?php echo osc_esc_html($al_name); ?></h1>
          <div class="al-shop-badges">
            <?php echo antique_seller_badge($al_sid); ?>
            <span class="al-shop-type"><?php echo osc_user_field('b_company') == 1 ? __('Dealer', 'starter') : __('Private seller', 'starter'); ?></span>
          </div>
          <?php if ($al_shop['tagline'] !== '') { ?><p class="al-shop-tagline"><?php echo osc_esc_html($al_shop['tagline']); ?></p><?php } ?>
          <?php echo antique_rating_line($al_sid); ?>
        </div>
        <div class="al-shop-actions">
          <?php if ($is_me) { ?>
            <a class="al-btn al-btn-ink" href="<?php echo antique_shop_settings_url(); ?>"><?php _e('Edit shop page', 'starter'); ?></a>
          <?php } else { ?>
            <a class="al-btn al-btn-brass" href="#al-contact"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><?php _e('Message seller', 'starter'); ?></a>
            <?php if ($phone_ok) { ?>
              <?php if ($phone_login) { ?>
                <a class="al-btn al-btn-outline" href="<?php echo osc_user_login_url(); ?>"><?php _e('Sign in to see phone number', 'starter'); ?></a>
              <?php } else { ?>
                <button type="button" class="al-btn al-btn-outline al-reveal-phone" data-phone="<?php echo osc_esc_html($phone); ?>" data-tel="<?php echo osc_esc_html($phone_digits); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg><span><?php _e('Show phone number', 'starter'); ?></span></button>
              <?php } ?>
            <?php } ?>
          <?php } ?>
        </div>
      </div>
      <ul class="al-shop-stats">
        <li><strong><?php echo $al_active; ?></strong><span><?php echo _n('item for sale', 'items for sale', $al_active, 'starter'); ?></span></li>
        <li><strong><?php echo $al_sold; ?></strong><span><?php echo _n('item sold', 'items sold', $al_sold, 'starter'); ?></span></li>
        <li><strong><?php echo $al_rating['count'] ? number_format($al_rating['avg'], 1) : '–'; ?></strong><span><?php echo sprintf(_n('%d review', '%d reviews', $al_rating['count'], 'starter'), $al_rating['count']); ?></span></li>
        <?php if ($al_resp && $al_resp['label'] !== '') { ?>
          <li><strong><?php echo (int)$al_resp['rate']; ?>%</strong><span><?php echo sprintf(__('replies, usually %s', 'starter'), osc_esc_html($al_resp['label'])); ?></span></li>
        <?php } ?>
        <li><strong><?php echo osc_esc_html(date('Y', strtotime(osc_user_regdate()))); ?></strong><span><?php _e('member since', 'starter'); ?></span></li>
      </ul>
    </header>

    <nav class="al-shop-tabs" aria-label="<?php echo osc_esc_html(__('Shop sections', 'starter')); ?>">
      <a href="#al-listings" class="is-active"><?php _e('Listings', 'starter'); ?> <em><?php echo $al_active; ?></em></a>
      <a href="#al-reviews"><?php _e('Reviews', 'starter'); ?> <em><?php echo $al_rating['count']; ?></em></a>
      <a href="#al-about"><?php _e('About', 'starter'); ?></a>
      <?php if (!$is_me) { ?><a href="#al-contact"><?php _e('Contact', 'starter'); ?></a><?php } ?>
    </nav>

    <section id="al-listings" class="al-shop-section">
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
    </section>

    <div class="al-shop-cols">
      <section class="al-panel al-section-block al-reviews" id="al-reviews">
        <h2 class="al-panel-title"><?php _e('Reviews', 'starter'); ?></h2>
        <?php if ($al_rv) { ?>
          <?php echo antique_rating_summary($al_sid); ?>
          <div class="al-review-list">
            <?php foreach ($al_rv as $r) { echo antique_review_html($r, $al_viewer); } ?>
          </div>
        <?php } else { ?>
          <p class="al-muted"><?php echo $is_me ? __('No reviews yet. Buyers can review you after they win your auction, you accept their offer, or you talk with them about an item.', 'starter') : __('No reviews yet. Buyers can review this seller after buying from them.', 'starter'); ?></p>
        <?php } ?>
      </section>

      <aside class="al-shop-side">
        <section class="al-panel al-section-block" id="al-about">
          <h2 class="al-panel-title"><?php _e('About', 'starter'); ?></h2>
          <?php if ($about != '') { ?>
            <p class="al-shop-about"><?php echo nl2br(osc_esc_html($about)); ?></p>
          <?php } else { ?>
            <p class="al-muted"><?php echo $is_me ? sprintf(__('Tell buyers about yourself in <a href="%s">Shop settings</a>.', 'starter'), antique_shop_settings_url()) : __('This seller has not written an introduction yet.', 'starter'); ?></p>
          <?php } ?>
          <ul class="al-meta">
            <?php if ($place !== '') { ?><li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg><?php echo osc_esc_html($place); ?></li><?php } ?>
            <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/></svg><?php echo sprintf(__('Member since %s', 'starter'), osc_esc_html(date('F Y', strtotime(osc_user_regdate())))); ?></li>
            <?php if (osc_user_website() != '') { ?>
              <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg><a href="<?php echo osc_esc_html(osc_user_website()); ?>" target="_blank" rel="nofollow noopener"><?php echo osc_esc_html(preg_replace('#^https?://#', '', rtrim(osc_user_website(), '/'))); ?></a></li>
            <?php } ?>
          </ul>
          <?php if ($al_policies) { ?>
            <div class="al-shop-policies">
              <?php foreach ($al_policies as $title => $text) { ?>
                <div><h3><?php echo osc_esc_html($title); ?></h3><p><?php echo nl2br(osc_esc_html($text)); ?></p></div>
              <?php } ?>
            </div>
          <?php } elseif ($is_me) { ?>
            <p class="al-muted al-shop-nopolicy"><?php echo sprintf(__('Add your shipping, returns and payment policies in <a href="%s">Shop settings</a>.', 'starter'), antique_shop_settings_url()); ?></p>
          <?php } ?>
          <?php if (function_exists('show_feedback_overall')) { ?><div class="al-profile-plugin"><?php echo show_feedback_overall(); ?> <?php echo leave_feedback(); ?></div><?php } ?>
          <?php if (function_exists('ur_hook_show_rating_stars')) { ?><div class="al-profile-plugin"><?php echo ur_show_rating_stars(); ?> <?php echo ur_add_rating_link(); ?></div><?php } ?>
          <?php osc_run_hook('user_public_profile_sidebar_bottom'); ?>
        </section>

        <?php if (!$is_me) { ?>
          <section class="al-panel al-section-block" id="al-contact">
            <h2 class="al-panel-title"><?php echo sprintf(__('Message %s', 'starter'), osc_esc_html($al_name)); ?></h2>
            <?php if (osc_is_web_user_logged_in()) { ?>
              <?php echo antique_msg_compose(0, $al_sid, $al_name); ?>
            <?php } else { ?>
              <p class="al-muted"><?php _e('Sign in to message this seller. Your conversation stays on the site and your email address is never shown.', 'starter'); ?></p>
              <div class="al-inline-actions">
                <a class="al-btn al-btn-ink" href="<?php echo osc_esc_html(antique_login_back_url(osc_user_public_profile_url($al_sid) . '#al-contact')); ?>"><?php _e('Sign in', 'starter'); ?></a>
                <a class="al-btn al-btn-outline" href="<?php echo osc_register_account_url(); ?>"><?php _e('Create an account', 'starter'); ?></a>
              </div>
            <?php } ?>
          </section>
        <?php } ?>
      </aside>
    </div>
  </div>

  <script>
    (function () {
      var btn = document.querySelector('.al-reveal-phone');
      if (btn) {
        btn.addEventListener('click', function () {
          var phone = btn.getAttribute('data-phone'), tel = btn.getAttribute('data-tel');
          var wrap = document.createElement('div');
          wrap.className = 'al-phone-revealed';
          var call = document.createElement('a');
          call.className = 'al-btn al-btn-outline';
          call.href = 'tel:' + tel;
          call.setAttribute('aria-label', '<?php echo osc_esc_js(__('Call', 'starter')); ?> ' + phone);
          call.innerHTML = btn.querySelector('svg').outerHTML;
          call.appendChild(document.createTextNode(phone));
          wrap.appendChild(call);
          if (tel.charAt(0) === '+') {
            var wa = document.createElement('a');
            wa.className = 'al-btn al-btn-whatsapp';
            wa.href = 'https://wa.me/' + tel.replace('+', '');
            wa.target = '_blank'; wa.rel = 'noopener';
            wa.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35zM12.04 21.5h-.01a9.4 9.4 0 0 1-4.8-1.31l-.34-.2-3.57.93.95-3.48-.22-.36a9.4 9.4 0 0 1-1.44-5.02c0-5.2 4.24-9.44 9.45-9.44 2.52 0 4.89.98 6.67 2.77a9.37 9.37 0 0 1 2.76 6.68c0 5.2-4.24 9.43-9.45 9.43zm8.04-17.47A11.3 11.3 0 0 0 12.04.7C5.77.7.67 5.8.67 12.06c0 2 .52 3.96 1.52 5.68L.57 23.3l5.7-1.5a11.33 11.33 0 0 0 5.77 1.47h.01c6.26 0 11.36-5.1 11.36-11.36 0-3.03-1.18-5.89-3.33-8.03z"/></svg>WhatsApp';
            wrap.appendChild(wa);
          }
          btn.replaceWith(wrap);
        });
      }
      // highlight the section in view
      var tabs = document.querySelectorAll('.al-shop-tabs a');
      if (!('IntersectionObserver' in window) || !tabs.length) { return; }
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (!e.isIntersecting) { return; }
          tabs.forEach(function (t) { t.classList.toggle('is-active', t.getAttribute('href') === '#' + e.target.id); });
        });
      }, { rootMargin: '-35% 0px -55% 0px' });
      tabs.forEach(function (t) { var s = document.querySelector(t.getAttribute('href')); if (s) { io.observe(s); } });
    })();
  </script>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
