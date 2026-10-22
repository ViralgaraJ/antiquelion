<?php
  $locales = __get('locales');
  $user = osc_user();
?>

<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
</head>
<body id="body-user-profile" class="body-ua al-account-page">
  <?php osc_current_web_theme_path('header.php'); ?>

  <div class="al-wrap al-account-layout">
    <aside class="al-account-side"><?php echo antique_account_nav(); ?></aside>

    <main class="al-account-main modify_profile">
      <div class="al-account-head"><h2><?php _e('Account settings', 'starter'); ?></h2></div>

      <form action="<?php echo osc_base_url(true); ?>" method="post" class="al-form">
        <input type="hidden" name="page" value="user" />
        <input type="hidden" name="action" value="profile_post" />

        <section class="al-panel">
          <h3 class="al-panel-title"><?php _e('Profile', 'starter'); ?></h3>
          <?php osc_run_hook('user_profile_top'); ?>

          <?php $al_has_photo = antique_avatar_url(osc_logged_user_id()) !== '' || strpos(starter_profile_picture(osc_logged_user_id(), 'large', true), '/images/profile-') === false; ?>
          <div class="al-avatar-row" id="al-avatar">
            <img class="al-avatar-img" src="<?php echo osc_esc_html(starter_profile_picture(osc_logged_user_id(), 'large', true)); ?>" alt="<?php echo osc_esc_html(__('Your profile photo', 'starter')); ?>" width="96" height="96" />
            <div class="al-avatar-body">
              <strong><?php _e('Profile photo', 'starter'); ?></strong>
              <span><?php _e('A clear photo of you or your shop builds trust with buyers. JPG, PNG or WebP, up to 8 MB.', 'starter'); ?></span>
              <div class="al-avatar-actions">
                <label class="al-btn al-btn-outline al-avatar-pick">
                  <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" hidden />
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg>
                  <span><?php echo $al_has_photo ? __('Change photo', 'starter') : __('Upload photo', 'starter'); ?></span>
                </label>
                <button type="button" class="al-text-link is-danger al-avatar-remove"<?php echo $al_has_photo ? '' : ' hidden'; ?>><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg><?php _e('Remove', 'starter'); ?></button>
              </div>
              <div class="al-avatar-msg" role="status" aria-live="polite"></div>
            </div>
          </div>
          <script>
            (function () {
              var box = document.getElementById('al-avatar');
              var input = box.querySelector('input[type=file]'), img = box.querySelector('.al-avatar-img');
              var pickText = box.querySelector('.al-avatar-pick span'), remove = box.querySelector('.al-avatar-remove'), msg = box.querySelector('.al-avatar-msg');
              var url = <?php echo json_encode(osc_base_url(true) . '?page=ajax&action=runhook&hook=al_avatar'); ?>;
              var token = <?php echo json_encode(osc_csrfguard_generate_token()); ?>;
              var t = <?php echo json_encode(array('saving' => __('Saving…', 'starter'), 'saved' => __('Photo updated.', 'starter'), 'removed' => __('Photo removed.', 'starter'), 'change' => __('Change photo', 'starter'), 'upload' => __('Upload photo', 'starter'), 'big' => __('This file is too big. The limit is 8 MB.', 'starter'), 'fail' => __('Something went wrong. Please try again.', 'starter'), 'confirm' => __('Remove your profile photo?', 'starter'))); ?>;
              function say(text, bad) { msg.textContent = text; msg.className = 'al-avatar-msg' + (bad ? ' is-error' : ' is-ok'); }
              function send(data, previous) {
                data.append('octoken', token);
                box.classList.add('is-busy');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', url, true);
                xhr.onload = function () {
                  box.classList.remove('is-busy');
                  try {
                    var res = JSON.parse(xhr.responseText);
                    if (!res.success) { throw new Error(res.error || t.fail); }
                    img.src = res.url;
                    document.querySelectorAll('#al-header .al-account-btn img').forEach(function (h) { h.src = res.url; });
                    remove.hidden = !!res.removed;
                    pickText.textContent = res.removed ? t.upload : t.change;
                    say(res.removed ? t.removed : t.saved, false);
                  } catch (e) { if (previous) { img.src = previous; } say(e.message || t.fail, true); }
                };
                xhr.onerror = function () { box.classList.remove('is-busy'); if (previous) { img.src = previous; } say(t.fail, true); };
                xhr.send(data);
              }
              input.addEventListener('change', function () {
                var f = input.files && input.files[0];
                input.value = '';
                if (!f) { return; }
                if (f.size > 8 * 1024 * 1024) { say(t.big, true); return; }
                var previous = img.src;
                img.src = URL.createObjectURL(f);     // preview straight away
                say(t.saving, false);
                var d = new FormData(); d.append('photo', f); send(d, previous);
              });
              remove.addEventListener('click', function () {
                if (!window.confirm(t.confirm)) { return; }
                var d = new FormData(); d.append('op', 'delete'); send(d, null);
              });
            })();
          </script>

          <div class="al-form-row al-form-2">
            <label><span><?php _e('Full name', 'starter'); ?> <em>*</em></span><?php UserForm::name_text(osc_user()); ?></label>
            <label><span><?php _e('Account type', 'starter'); ?></span><?php UserForm::is_company_select(osc_user(), __('Private seller', 'starter'), __('Dealer / business', 'starter')); ?></label>
          </div>
          <div class="al-form-row al-form-2">
            <label><span><?php _e('Mobile phone', 'starter'); ?></span><span class="input-box"><?php UserForm::mobile_text(osc_user()); ?></span></label>
            <label><span><?php _e('Other phone', 'starter'); ?></span><span class="input-box"><?php UserForm::phone_land_text(osc_user()); ?></span></label>
          </div>
          <label class="al-form-full"><span><?php _e('About you', 'starter'); ?></span><?php UserForm::multilanguage_info($locales, osc_user()); ?></label>
          <label class="al-form-full"><span><?php _e('Website', 'starter'); ?></span><?php UserForm::website_text(osc_user()); ?></label>
        </section>

        <section class="al-panel">
          <h3 class="al-panel-title"><?php _e('Location', 'starter'); ?></h3>
          <?php osc_run_hook('user_profile_sidebar'); ?>
          <input type="hidden" name="countryId" id="countryId" class="sCountry" value="<?php echo osc_esc_html($user['fk_c_country_code']); ?>"/>
          <input type="hidden" name="regionId" id="regionId" class="sRegion" value="<?php echo osc_esc_html($user['fk_i_region_id']); ?>"/>
          <input type="hidden" name="cityId" id="cityId" class="sCity" value="<?php echo osc_esc_html($user['fk_i_city_id']); ?>"/>
          <div class="al-form-full" id="location-picker">
            <span><?php _e('Country, region or city', 'starter'); ?></span>
            <input type="text" name="term" id="term" class="term" placeholder="<?php echo osc_esc_html(__('Start typing your city', 'starter')); ?>" value="<?php echo osc_esc_html(starter_get_term(Params::getParam('term'), starter_ajax_country(), starter_ajax_region(), starter_ajax_city())); ?>" autocomplete="off"/>
            <div class="shower-wrap"><div class="shower" id="shower"><div class="option service min-char"><?php _e('Type country, region or city', 'starter'); ?></div></div></div>
            <div class="loader"></div>
          </div>
          <div class="al-form-row al-form-3">
            <label><span><?php _e('Area', 'starter'); ?></span><?php UserForm::city_area_text(osc_user()); ?></label>
            <label><span><?php _e('Street address', 'starter'); ?></span><?php UserForm::address_text(osc_user()); ?></label>
            <label><span><?php _e('Postcode', 'starter'); ?></span><?php UserForm::zip_text(osc_user()); ?></label>
          </div>
          <?php osc_run_hook('user_form'); ?>
        </section>

        <div class="al-form-actions"><button type="submit" class="al-btn al-btn-ink"><?php _e('Save changes', 'starter'); ?></button></div>
      </form>

      <div class="al-settings-grid">
        <section class="al-panel">
          <h3 class="al-panel-title"><?php _e('Email address', 'starter'); ?></h3>
          <form action="<?php echo osc_base_url(true); ?>" method="post" id="user_email_change" class="al-form user-change">
            <input type="hidden" name="page" value="user" />
            <input type="hidden" name="action" value="change_email_post" />
            <p class="al-muted"><?php _e('Current:', 'starter'); ?> <strong><?php echo osc_esc_html(osc_logged_user_email()); ?></strong></p>
            <label><span><?php _e('New email', 'starter'); ?> <em>*</em></span><input type="email" name="new_email" id="new_email" value="" autocomplete="email" /></label>
            <button type="submit" class="al-btn al-btn-outline"><?php _e('Change email', 'starter'); ?></button>
          </form>
        </section>

        <section class="al-panel">
          <h3 class="al-panel-title"><?php _e('Password', 'starter'); ?></h3>
          <form action="<?php echo osc_base_url(true); ?>" method="post" id="user_password_change" class="al-form user-change">
            <input type="hidden" name="page" value="user" />
            <input type="hidden" name="action" value="change_password_post" />
            <label><span><?php _e('Current password', 'starter'); ?> <em>*</em></span><input type="password" name="password" id="password" value="" autocomplete="current-password" /></label>
            <label><span><?php _e('New password', 'starter'); ?> <em>*</em></span><input type="password" name="new_password" id="new_password" value="" autocomplete="new-password" /></label>
            <label><span><?php _e('Repeat new password', 'starter'); ?> <em>*</em></span><input type="password" name="new_password2" id="new_password2" value="" autocomplete="new-password" /></label>
            <button type="submit" class="al-btn al-btn-outline"><?php _e('Change password', 'starter'); ?></button>
          </form>
        </section>
      </div>

      <section class="al-panel al-danger-zone">
        <div>
          <h3 class="al-panel-title"><?php _e('Delete account', 'starter'); ?></h3>
          <p class="al-muted"><?php _e('Permanently removes your account and all your listings.', 'starter'); ?></p>
        </div>
        <a class="al-btn al-btn-danger" href="<?php echo osc_base_url(true) . '?page=user&action=delete&id=' . osc_user_id() . '&secret=' . $user['s_secret']; ?>" onclick="return confirm('<?php echo osc_esc_js(__('Delete your account and all your listings? This cannot be undone.', 'starter')); ?>');"><?php _e('Delete account', 'starter'); ?></a>
      </section>
    </main>
  </div>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>