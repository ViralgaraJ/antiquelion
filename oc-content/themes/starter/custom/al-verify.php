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
  $al_types = antique_verify_doc_types($al_dealer);
  $al_type = $al_v('docType', 's_doc_type');
  if (!isset($al_types[$al_type])) { $al_type = ''; }
?>
<div class="al-account-head">
  <h2><?php _e('Get verified', 'starter'); ?></h2>
  <?php echo antique_seller_badge($al_uid); ?>
</div>

<?php if ($al_status != ANTIQUE_VERIFY_OK) { ?>
  <div class="al-sell-intro al-panel al-verify-steps">
    <ol class="al-sell-steps">
      <li class="is-done"><span>1</span><?php _e('Account created', 'starter'); ?></li>
      <li class="is-done"><span>2</span><?php _e('Seller details', 'starter'); ?></li>
      <li class="is-current"><span>3</span><?php echo $al_status == ANTIQUE_VERIFY_PENDING ? __('Being checked', 'starter') : __('Verify your identity', 'starter'); ?></li>
      <li><span>4</span><?php _e('List your first item', 'starter'); ?></li>
    </ol>
    <p><?php _e('To keep buyers safe, every seller on the marketplace is verified by our team before they can list items. It is free and usually takes up to 2 working days.', 'starter'); ?></p>
  </div>
<?php } ?>

<?php if ($al_status == ANTIQUE_VERIFY_OK) { ?>
  <div class="al-verify-state al-panel is-ok">
    <?php echo antique_seller_badge($al_uid); ?>
    <h3><?php _e('Your account is verified', 'starter'); ?></h3>
    <p><?php echo sprintf(__('Verified on %s. Buyers see the badge next to your name on your listings, your shop page and in messages.', 'starter'), osc_esc_html(date('j F Y', strtotime($al_row['dt_verified'])))); ?></p>
    <div class="al-verify-cta">
      <a class="al-btn al-btn-brass" href="<?php echo osc_item_post_url(); ?>"><?php _e('List an item', 'starter'); ?></a>
      <a class="al-btn al-btn-outline" href="<?php echo osc_user_public_profile_url($al_uid); ?>"><?php _e('See your shop page', 'starter'); ?></a>
    </div>
  </div>
<?php } elseif ($al_status == ANTIQUE_VERIFY_PENDING) { ?>
  <div class="al-verify-state al-panel is-pending">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
    <h3><?php _e('We are checking your details', 'starter'); ?></h3>
    <p><?php echo sprintf(__('You sent your request on %s. Checks usually take up to 2 working days. We will email you the result, and you can list items as soon as you are verified.', 'starter'), osc_esc_html(date('j F Y', strtotime($al_row['dt_verify_req'])))); ?></p>
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
    <fieldset class="al-form-full al-doc-types">
      <legend><?php echo $al_dealer ? __('Document you are sending', 'starter') : __('ID type', 'starter'); ?> <em>*</em></legend>
      <div class="al-doc-type-list">
        <?php foreach ($al_types as $al_k => $al_t) { ?>
          <label class="al-doc-type">
            <input type="radio" name="doc_type" value="<?php echo $al_k; ?>" data-sides="<?php echo (int)$al_t['sides']; ?>"<?php echo $al_type == $al_k ? ' checked' : ''; ?> />
            <span><?php echo osc_esc_html($al_t['label']); ?></span>
          </label>
        <?php } ?>
      </div>
    </fieldset>

    <div class="al-form-full al-doc-uploads" data-limit="<?php echo (int)antique_verify_upload_limit(); ?>" data-limit-text="<?php echo osc_esc_html(antique_verify_limit_text()); ?>">
      <?php foreach (array('doc' => array(__('Front', 'starter'), __('The side with your photo and name', 'starter')), 'doc_back' => array(__('Back', 'starter'), __('Optional, if your address is on the back', 'starter'))) as $al_name => $al_lbl) { ?>
        <label class="al-doc-drop" data-field="<?php echo $al_name; ?>"<?php echo $al_name == 'doc_back' ? ' hidden' : ''; ?>>
          <input type="file" name="<?php echo $al_name; ?>" accept="image/*,application/pdf" />
          <span class="al-doc-thumb" aria-hidden="true">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2.2"/><path d="M6 16c.6-1.5 1.7-2.3 3-2.3s2.4.8 3 2.3M14 10h4M14 13h3"/></svg>
          </span>
          <span class="al-doc-text">
            <strong><span class="al-doc-side" data-dealer="<?php echo osc_esc_html(__('Document', 'starter')); ?>"><?php echo osc_esc_html($al_lbl[0]); ?></span><?php if ($al_name == 'doc') { ?> <em>*</em><?php } ?></strong>
            <small class="al-doc-name" data-hint="<?php echo osc_esc_html($al_lbl[1]); ?>"><?php echo osc_esc_html($al_lbl[1]); ?></small>
            <span class="al-doc-acts"><span class="al-doc-btn"><?php _e('Take a photo or choose a file', 'starter'); ?></span><button type="button" class="al-doc-edit" hidden><?php _e('Edit', 'starter'); ?></button></span>
          </span>
        </label>
      <?php } ?>
      <small class="al-hint al-doc-hint"><?php echo sprintf(__('Photo (JPG, PNG, WebP, or straight from your phone camera) or PDF. Make sure every corner is in the picture and the text is sharp. You may cover your ID number. PDFs up to %s.', 'starter'), osc_esc_html(antique_verify_limit_text())); ?></small>
      <div class="al-doc-error" role="alert" hidden></div>
    </div>

    <label class="al-check">
      <input type="checkbox" name="consent" value="1" />
      <span><?php echo sprintf(__('These details are true and I allow %s to check them. See the <a href="%s" target="_blank">Privacy policy</a>.', 'starter'), osc_esc_html(osc_page_title()), antique_info_page_url('al-privacy')); ?></span>
    </label>
    <button type="submit" class="al-btn al-btn-brass"><?php _e('Send for checking', 'starter'); ?></button>
  </form>
<?php } ?>

<?php if (!in_array($al_status, array(ANTIQUE_VERIFY_OK, ANTIQUE_VERIFY_PENDING), true)) { ?>
<script>
(function () {
  var form = document.querySelector('.al-verify-form');
  if (!form) { return; }
  var wrap = form.querySelector('.al-doc-uploads');
  var limit = parseInt(wrap.getAttribute('data-limit'), 10) || 2097152;
  var limitText = wrap.getAttribute('data-limit-text');
  var errBox = wrap.querySelector('.al-doc-error');
  var back = wrap.querySelector('[data-field=doc_back]');
  var dealer = <?php echo $al_dealer ? 'true' : 'false'; ?>;
  var MAX_SIDE = 2400, TARGET = Math.min(limit / 2.2, 1600000);

  function showError(t) { errBox.textContent = t || ''; errBox.hidden = !t; }
  function syncType() {
    var r = form.querySelector('input[name=doc_type]:checked');
    var two = r && r.getAttribute('data-sides') === '2';
    back.hidden = !two;
    if (!two) { clear(back); }
    var side = wrap.querySelector('[data-field=doc] .al-doc-side');
    side.textContent = two || !r ? <?php echo json_encode(__('Front', 'starter')); ?> : (dealer && r.value.indexOf('business') === 0 ? side.getAttribute('data-dealer') : <?php echo json_encode(__('Photo page', 'starter')); ?>);
  }
  function clear(drop) {
    var input = drop.querySelector('input');
    input.value = '';
    drop.classList.remove('has-file');
    drop.alOriginal = null; drop.alCurrent = null;
    drop.querySelector('.al-doc-edit').hidden = true;
    var name = drop.querySelector('.al-doc-name');
    name.textContent = name.getAttribute('data-hint');
    drop.querySelector('.al-doc-btn').textContent = <?php echo json_encode(__('Take a photo or choose a file', 'starter')); ?>;
    var th = drop.querySelector('.al-doc-thumb img'); if (th) { th.remove(); }
  }
  function setFile(drop, file) {
    var input = drop.querySelector('input');
    drop.alCurrent = file;
    drop.querySelector('.al-doc-edit').hidden = !drop.alOriginal;
    try { var dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; } catch (e) { /* older browsers keep the original file */ }
    drop.classList.add('has-file');
    drop.querySelector('.al-doc-name').textContent = file.name + ' · ' + (file.size < 1048576 ? Math.max(1, Math.round(file.size / 1024)) + ' KB' : (file.size / 1048576).toFixed(1) + ' MB');
    drop.querySelector('.al-doc-btn').textContent = <?php echo json_encode(__('Change', 'starter')); ?>;
    var th = drop.querySelector('.al-doc-thumb'), old = th.querySelector('img'); if (old) { old.remove(); }
    if (/^image\//.test(file.type)) { var img = document.createElement('img'); img.alt = ''; img.src = URL.createObjectURL(file); th.appendChild(img); }
  }
  // Phone photos are often 4-12 MB (or HEIC): shrink them to a sharp JPEG the server accepts
  function shrink(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file), img = new Image();
      img.onload = function () {
        var w = img.naturalWidth, h = img.naturalHeight, k = Math.min(1, MAX_SIDE / Math.max(w, h));
        var c = document.createElement('canvas'); c.width = Math.round(w * k); c.height = Math.round(h * k);
        var ctx = c.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height); ctx.drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url);
        var q = 0.88;
        (function out() {
          c.toBlob(function (b) {
            if (!b) { reject(); return; }
            if (b.size > TARGET && q > 0.6) { q -= 0.1; out(); return; }
            if (b.size > TARGET && c.width > 1200) {
              // still too big: make it smaller and try again
              var s2 = document.createElement('canvas'); s2.width = Math.round(c.width * 0.75); s2.height = Math.round(c.height * 0.75);
              s2.getContext('2d').drawImage(c, 0, 0, s2.width, s2.height); c = s2; q = 0.82; out(); return;
            }
            resolve(new File([b], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' }));
          }, 'image/jpeg', q);
        })();
      };
      img.onerror = function () { URL.revokeObjectURL(url); reject(); };
      img.src = url;
    });
  }
  wrap.querySelectorAll('.al-doc-drop input').forEach(function (input) {
    input.addEventListener('change', function () {
      var drop = input.closest('.al-doc-drop'), file = input.files && input.files[0];
      showError('');
      if (!file) { return; }
      var isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
      if (isPdf) {
        if (file.size > limit) { clear(drop); showError(<?php echo json_encode(__('This PDF is too big. The limit is %s. Send a photo instead.', 'starter')); ?>.replace('%s', limitText)); return; }
        drop.alOriginal = null; setFile(drop, file); return;
      }
      if (file.type && !/^image\//.test(file.type)) { clear(drop); showError(<?php echo json_encode(__('Choose a photo or a PDF file.', 'starter')); ?>); return; }
      if (!window.alImageEditor) { accept(drop, file); return; }
      drop.alOriginal = file;
      editDoc(drop);
    });
  });
  // a photo, after editing: keep it if it is small enough, otherwise shrink it
  function accept(drop, file) {
    var ok = /^image\/(jpeg|png|webp)$/.test(file.type);
    if (ok && file.size <= TARGET) { setFile(drop, file); return; }
    drop.classList.add('is-busy');
    shrink(file).then(function (f) { setFile(drop, f); }, function () {
      clear(drop);
      showError(<?php echo json_encode(__('This photo could not be read. Choose a JPG or PNG photo, or take the photo again with your camera.', 'starter')); ?>);
    }).then(function () { drop.classList.remove('is-busy'); });
  }
  function editDoc(drop) {
    var input = drop.querySelector('input');
    window.alImageEditor.open(drop.alOriginal, {
      title: <?php echo json_encode(__('Check your document photo', 'starter')); ?>,
      aspects: [[<?php echo json_encode(__('Free', 'starter')); ?>, NaN], [<?php echo json_encode(__('ID card', 'starter')); ?>, 85.6 / 54], [<?php echo json_encode(__('Passport page', 'starter')); ?>, 125 / 88], [<?php echo json_encode(__('A4 page', 'starter')); ?>, 210 / 297]],
      max: MAX_SIDE, quality: 0.9,
      applyLabel: <?php echo json_encode(__('Use this photo', 'starter')); ?>
    }).then(function (edited) {
      if (edited) { accept(drop, edited); return; }
      // cancelled: keep the photo they had, or nothing
      if (drop.alCurrent) { window.alImageEditor.setInputFile(input, drop.alCurrent); } else { clear(drop); }
    }, function (e) { clear(drop); showError(e.message); });
  }
  wrap.querySelectorAll('.al-doc-edit').forEach(function (b) {
    b.addEventListener('click', function (e) {
      e.preventDefault(); e.stopPropagation();
      var drop = b.closest('.al-doc-drop');
      if (drop.alOriginal) { editDoc(drop); }
    });
  });
  form.querySelectorAll('input[name=doc_type]').forEach(function (r) { r.addEventListener('change', syncType); });
  syncType();
  form.addEventListener('submit', function (e) {
    var busy = wrap.querySelector('.is-busy'), total = 0;
    wrap.querySelectorAll('.al-doc-drop:not([hidden]) input').forEach(function (i) { if (i.files && i.files[0]) { total += i.files[0].size; } });
    if (busy) { e.preventDefault(); showError(<?php echo json_encode(__('Please wait a moment, the photo is still being prepared.', 'starter')); ?>); return; }
    if (total > limit) { e.preventDefault(); showError(<?php echo json_encode(__('These files are too big to send together. The limit is %s.', 'starter')); ?>.replace('%s', limitText)); return; }
    var btn = form.querySelector('button[type=submit]'); btn.disabled = true; btn.textContent = <?php echo json_encode(__('Sending…', 'starter')); ?>;
  });
})();
</script>
<?php } ?>
