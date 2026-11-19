<?php
  require_once 'functions.php';
  starter_backoffice_menu(__('Integrations & security', 'starter'));
  $al_token = osc_csrfguard_generate_token();
  $al_action = antique_integrations_admin_url();
  $al_admin = (int)osc_logged_admin_id();
  $al_2fa = antique_2fa_row($al_admin);
  $al_codes = json_decode((string)Session::newInstance()->_get('al_2fa_codes'), true);
  Session::newInstance()->_drop('al_2fa_codes');
  $al_hidden = function ($act) use ($al_token) { return '<input type="hidden" name="octoken" value="' . $al_token . '" /><input type="hidden" name="al_int" value="' . $act . '" />'; };
?>
<style>
  .alin { max-width: 900px; font-size: 14px; }
  .alin-card { margin: 0 0 18px; padding: 18px 20px; border: 1px solid #E5DFD3; border-radius: 8px; background: #fff; }
  .alin-card h3 { margin: 0 0 6px; font-size: 17px; color: #14213D; }
  .alin-meta { color: #6B7280; font-size: 13px; }
  .alin label { display: flex; flex-direction: column; gap: 4px; margin: 0 0 14px; }
  .alin label.alin-check { flex-direction: row; align-items: center; gap: 8px; }
  .alin input[type=text], .alin input[type=password] { width: 100%; max-width: 560px; height: 36px; padding: 0 10px; border: 1px solid #D9D2C3; border-radius: 4px; }
  .alin code { display: inline-block; padding: 4px 8px; border-radius: 4px; background: #F3EFE6; word-break: break-all; }
  .alin-btn { height: 36px; padding: 0 16px; border: 1px solid #14213D; border-radius: 4px; background: #14213D; color: #fff; font-weight: 700; cursor: pointer; }
  .alin-btn.is-light { background: #fff; color: #14213D; }
  .alin-btn.is-bad { background: #fff; border-color: #B42318; color: #B42318; }
  .alin-tag { display: inline-block; padding: 1px 8px; border-radius: 999px; background: #F3EFE6; color: #4B5563; font-size: 12px; font-weight: 700; vertical-align: middle; }
  .alin-tag.is-ok { background: #DCF5E7; color: #067647; }
  .alin-codes { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; margin: 10px 0; padding: 12px; border: 1px dashed #A67C2E; border-radius: 6px; background: #FBF9F4; font-family: monospace; font-size: 15px; }
  .alin-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 10px; }
  .alin-row label { margin: 0; }
  #alin-qr { width: 180px; height: 180px; margin: 10px 0; }
  .alin ol { margin: 6px 0 12px 18px; }
</style>

<div class="mb-body alin">

  <!-- Two-step sign-in -->
  <div class="alin-card">
    <h3><?php _e('Two-step sign-in for your admin account', 'starter'); ?>
      <span class="alin-tag<?php echo $al_2fa && $al_2fa['b_enabled'] ? ' is-ok' : ''; ?>"><?php echo $al_2fa && $al_2fa['b_enabled'] ? __('On', 'starter') : __('Off', 'starter'); ?></span></h3>
    <p class="alin-meta"><?php _e('After your password, oc-admin asks for a 6-digit code from an authenticator app on your phone (Google Authenticator, Microsoft Authenticator, 1Password...). A stolen password alone can no longer open the admin. Each admin turns it on for their own account.', 'starter'); ?></p>

    <?php if (is_array($al_codes) && $al_codes) { ?>
      <p><strong><?php _e('Your recovery codes. Print them or keep them in a password manager. Each works once if you lose your phone. They will not be shown again.', 'starter'); ?></strong></p>
      <div class="alin-codes"><?php foreach ($al_codes as $al_c) { ?><span><?php echo osc_esc_html($al_c); ?></span><?php } ?></div>
    <?php } ?>

    <?php if ($al_2fa && $al_2fa['b_enabled']) { ?>
      <p class="alin-meta"><?php echo sprintf(__('Turned on %s.', 'starter'), osc_esc_html($al_2fa['dt_enabled'])); ?> <?php $al_left = count((array)json_decode((string)$al_2fa['s_recovery'], true)); echo sprintf(_n('%d recovery code left.', '%d recovery codes left.', $al_left, 'starter'), $al_left); ?></p>
      <form method="post" action="<?php echo osc_esc_html($al_action); ?>" class="alin-row">
        <?php echo $al_hidden('2fa_off'); ?>
        <label><?php _e('Current code', 'starter'); ?><input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" style="width:140px" required /></label>
        <button type="submit" class="alin-btn is-bad"><?php _e('Turn off', 'starter'); ?></button>
      </form>
    <?php } elseif ($al_2fa) { $al_uri = antique_2fa_otpauth($al_2fa['s_secret']); ?>
      <ol>
        <li><?php _e('Open your authenticator app and add an account by scanning this code:', 'starter'); ?></li>
      </ol>
      <div id="alin-qr" data-uri="<?php echo osc_esc_html($al_uri); ?>"></div>
      <p class="alin-meta"><?php _e('Cannot scan? Enter this key by hand (time based):', 'starter'); ?> <code><?php echo osc_esc_html(trim(chunk_split($al_2fa['s_secret'], 4, ' '))); ?></code></p>
      <ol start="2"><li><?php _e('Enter the 6-digit code the app shows:', 'starter'); ?></li></ol>
      <form method="post" action="<?php echo osc_esc_html($al_action); ?>" class="alin-row">
        <?php echo $al_hidden('2fa_confirm'); ?>
        <label><?php _e('Code', 'starter'); ?><input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" style="width:140px" required /></label>
        <button type="submit" class="alin-btn"><?php _e('Turn on', 'starter'); ?></button>
      </form>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
      <script>
        (function () {
          var el = document.getElementById('alin-qr');
          if (window.QRCode && el) { new QRCode(el, { text: el.getAttribute('data-uri'), width: 180, height: 180, correctLevel: QRCode.CorrectLevel.M }); }
        })();
      </script>
    <?php } else { ?>
      <form method="post" action="<?php echo osc_esc_html($al_action); ?>">
        <?php echo $al_hidden('2fa_start'); ?>
        <button type="submit" class="alin-btn"><?php _e('Set up two-step sign-in', 'starter'); ?></button>
      </form>
    <?php } ?>
  </div>

  <!-- Google sign-in -->
  <form method="post" action="<?php echo osc_esc_html($al_action); ?>" class="alin-card">
    <?php echo $al_hidden('google'); ?>
    <h3><?php _e('Sign in with Google', 'starter'); ?>
      <span class="alin-tag<?php echo antique_google_enabled() ? ' is-ok' : ''; ?>"><?php echo antique_google_enabled() ? __('On', 'starter') : __('Off', 'starter'); ?></span></h3>
    <p class="alin-meta"><?php _e('Adds "Continue with Google" to the sign-in and register pages. In Google Cloud Console: APIs & Services > OAuth consent screen (External, app name AntiqueLion, your logo and privacy page), then Credentials > Create credentials > OAuth client ID > Web application, and add this exact address under "Authorized redirect URIs":', 'starter'); ?></p>
    <p><code><?php echo osc_esc_html(antique_google_redirect_uri()); ?></code></p>
    <label class="alin-check"><input type="checkbox" name="google_enabled" value="1"<?php echo antique_google_setting('enabled') === '1' ? ' checked' : ''; ?> /> <strong><?php _e('Show "Continue with Google"', 'starter'); ?></strong></label>
    <label><strong><?php _e('Client ID', 'starter'); ?></strong><input type="text" name="google_client_id" value="<?php echo osc_esc_html(antique_google_setting('client_id')); ?>" placeholder="1234567890-abc.apps.googleusercontent.com" autocomplete="off" /></label>
    <label><strong><?php _e('Client secret', 'starter'); ?></strong>
      <span class="alin-meta"><?php echo antique_google_setting('client_secret') !== '' ? __('Saved. Leave empty to keep it.', 'starter') : __('Not set yet.', 'starter'); ?></span>
      <input type="password" name="google_client_secret" value="" autocomplete="new-password" /></label>
    <button type="submit" class="alin-btn"><?php _e('Save', 'starter'); ?></button>
  </form>

  <!-- Push -->
  <div class="alin-card">
    <h3><?php _e('App install and notifications', 'starter'); ?>
      <span class="alin-tag<?php echo antique_push_ready() ? ' is-ok' : ''; ?>"><?php echo antique_push_ready() ? __('On', 'starter') : __('Off', 'starter'); ?></span></h3>
    <p class="alin-meta"><?php _e('Visitors can install AntiqueLion to their phone\'s home screen. Members can turn on notifications under Account > Email settings or in Messages; they then get an alert for every email the site sends them (messages, offers, bids, auction results, payments). No outside service or account is needed.', 'starter'); ?></p>
    <form method="post" action="<?php echo osc_esc_html($al_action); ?>">
      <?php echo $al_hidden('push'); ?>
      <label class="alin-check"><input type="checkbox" name="push_enabled" value="1"<?php echo osc_get_preference('push_enabled', 'starter_theme') !== '0' ? ' checked' : ''; ?> /> <strong><?php _e('Offer push notifications to members', 'starter'); ?></strong></label>
      <button type="submit" class="alin-btn"><?php _e('Save', 'starter'); ?></button>
    </form>
    <?php if (antique_push_ready()) { ?>
      <form method="post" action="<?php echo osc_esc_html($al_action); ?>" class="alin-row" style="margin-top:16px">
        <?php echo $al_hidden('push_test'); ?>
        <label><?php _e('Send a test to member ID', 'starter'); ?><input type="text" name="user" style="width:120px" required /></label>
        <button type="submit" class="alin-btn is-light"><?php _e('Send test', 'starter'); ?></button>
      </form>
    <?php } ?>
  </div>
</div>
