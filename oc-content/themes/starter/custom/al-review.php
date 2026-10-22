<?php
  $al_uid = (int)osc_logged_user_id();
  $al_item_id = (int)Params::getParam('item');
  $al_item = antique_msg_item($al_item_id);
  $al_existing = antique_review_find($al_uid, $al_item_id);
  $al_basis = $al_existing ? $al_existing['s_basis'] : antique_review_basis($al_uid, $al_item_id);
  $al_labels = antique_review_basis_labels();
  if (!$al_item || $al_basis === '' || ($al_existing && !antique_review_can_edit($al_existing))) { ?>
    <div class="al-account-head"><h2><?php _e('Leave a review', 'starter'); ?></h2></div>
    <div class="al-empty-state">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.4l-5.8 3.1 1.1-6.5L2.6 9.4l6.5-.9z"/></svg>
      <h2><?php echo $al_existing ? __('This review can no longer be changed', 'starter') : __('You cannot review this seller yet', 'starter'); ?></h2>
      <p><?php echo $al_existing ? sprintf(__('Reviews can be edited for %d days after they are posted.', 'starter'), ANTIQUE_REVIEW_EDIT_DAYS) : __('You can review a seller after you win their auction, they accept your offer, or you have talked with them about the item.', 'starter'); ?></p>
      <a class="al-btn al-btn-ink" href="<?php echo antique_inbox_url(); ?>"><?php _e('Go to messages', 'starter'); ?></a>
    </div>
<?php
    return;
  }
  $al_seller = antique_msg_user($al_item['user']);
  $al_f = (array)Session::newInstance()->_get('al_review_form');
  Session::newInstance()->_drop('al_review_form');
  $al_rating = isset($al_f['rating']) ? (int)$al_f['rating'] : ($al_existing ? (int)$al_existing['i_rating'] : 0);
  $al_body = isset($al_f['body']) ? $al_f['body'] : ($al_existing ? $al_existing['s_body'] : '');
  $al_words = array(1 => __('Poor', 'starter'), 2 => __('Below expectations', 'starter'), 3 => __('Fine', 'starter'), 4 => __('Good', 'starter'), 5 => __('Excellent', 'starter'));
?>
<div class="al-account-head">
  <h2><?php echo $al_existing ? __('Edit your review', 'starter') : __('Leave a review', 'starter'); ?></h2>
</div>

<div class="al-review-target al-panel">
  <?php if ($al_item['thumb']) { ?><img src="<?php echo osc_esc_html($al_item['thumb']); ?>" alt="" width="64" height="64" /><?php } ?>
  <div>
    <strong><?php echo osc_esc_html($al_item['title']); ?></strong>
    <span><?php echo sprintf(__('Sold by %s', 'starter'), '<a href="' . osc_esc_html($al_seller['url']) . '">' . osc_esc_html($al_seller['name']) . '</a>'); ?></span>
    <em class="al-review-basis<?php echo $al_basis != 'message' ? ' is-verified' : ''; ?>"><?php echo osc_esc_html($al_labels[$al_basis]); ?></em>
  </div>
</div>

<form class="al-form al-panel al-review-form" method="post" action="<?php echo osc_esc_html(osc_ajax_hook_url('al_review')); ?>" novalidate>
  <input type="hidden" name="octoken" value="<?php echo osc_csrfguard_generate_token(); ?>" />
  <input type="hidden" name="op" value="save" />
  <input type="hidden" name="item" value="<?php echo (int)$al_item_id; ?>" />
  <input type="hidden" name="back" value="<?php echo osc_esc_html(antique_review_url($al_item_id)); ?>" />

  <fieldset class="al-star-pick">
    <legend><?php _e('Your rating', 'starter'); ?> <em>*</em></legend>
    <div class="al-star-row">
      <?php for ($i = 1; $i <= 5; $i++) { ?>
        <label title="<?php echo osc_esc_html($al_words[$i]); ?>">
          <input type="radio" name="rating" value="<?php echo $i; ?>"<?php echo $al_rating == $i ? ' checked' : ''; ?> />
          <svg width="36" height="36" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.4l-5.8 3.1 1.1-6.5L2.6 9.4l6.5-.9z"/></svg>
          <span class="al-sr-only"><?php echo sprintf(_n('%d star', '%d stars', $i, 'starter'), $i) . ' - ' . osc_esc_html($al_words[$i]); ?></span>
        </label>
      <?php } ?>
      <span class="al-star-word" data-words="<?php echo osc_esc_html(json_encode(array_values($al_words))); ?>"><?php echo $al_rating ? osc_esc_html($al_words[$al_rating]) : __('Choose 1 to 5 stars', 'starter'); ?></span>
    </div>
  </fieldset>

  <label class="al-form-full"><span><?php _e('Your review', 'starter'); ?> <em>*</em></span>
    <textarea name="body" rows="6" minlength="<?php echo ANTIQUE_REVIEW_MIN; ?>" maxlength="<?php echo ANTIQUE_REVIEW_MAX; ?>" required placeholder="<?php echo osc_esc_html(__('Was the item as described? How was communication, packing and delivery?', 'starter')); ?>"><?php echo osc_esc_html($al_body); ?></textarea>
    <small class="al-hint"><?php _e('Your review appears on the seller\'s shop page with your first name. Be fair and specific, and leave out personal details.', 'starter'); ?></small>
  </label>

  <button type="submit" class="al-btn al-btn-brass"><?php echo $al_existing ? __('Update review', 'starter') : __('Post review', 'starter'); ?></button>
</form>

<script>
  (function () {
    var pick = document.querySelector('.al-star-pick'), word = pick.querySelector('.al-star-word');
    var words = JSON.parse(word.getAttribute('data-words'));
    var labels = pick.querySelectorAll('label');
    function paint(n) { labels.forEach(function (l, i) { l.classList.toggle('is-on', i < n); }); }
    function current() { var c = pick.querySelector('input:checked'); return c ? +c.value : 0; }
    labels.forEach(function (l, i) {
      l.addEventListener('mouseenter', function () { paint(i + 1); word.textContent = words[i]; });
      l.querySelector('input').addEventListener('change', function () { paint(i + 1); word.textContent = words[i]; });
    });
    pick.querySelector('.al-star-row').addEventListener('mouseleave', function () { var n = current(); paint(n); if (n) { word.textContent = words[n - 1]; } });
    paint(current());
  })();
</script>
