<?php
  $al_uid = (int)osc_logged_user_id();
  $al_periods = antique_stats_periods();
  $al_days = (int)Params::getParam('days');
  if (!isset($al_periods[$al_days])) { $al_days = 30; }
  $al_item = (int)Params::getParam('item');
  if ($al_item) {
    $al_it = Item::newInstance()->findByPrimaryKey($al_item);
    if (!$al_it || (int)$al_it['fk_i_user_id'] !== $al_uid) { $al_item = 0; }
  }
  $al_stats = antique_stats_seller($al_uid, $al_days, $al_item);
  $al_all = $al_item ? antique_stats_seller($al_uid, $al_days) : $al_stats;
  $al_t = $al_stats['totals'];
  $al_kpis = array(
    'views'     => array(__('Views', 'starter'), __('Times your listings were opened', 'starter')),
    'enquiries' => array(__('Enquiries', 'starter'), __('New conversations with buyers', 'starter')),
    'phone'     => array(__('Phone reveals', 'starter'), __('Buyers who tapped to see your number', 'starter')),
    'watchers'  => array(__('Watchlist adds', 'starter'), __('Buyers who saved a listing', 'starter')),
    'offers'    => array(__('Offers', 'starter'), __('Price offers received', 'starter')),
    'bids'      => array(__('Bids', 'starter'), __('Bids on your auctions', 'starter'))
  );
  $al_contacts = $al_t['enquiries'] + $al_t['phone'];
  $al_rate = $al_t['views'] > 0 ? round($al_contacts / $al_t['views'] * 100, 1) : 0;

  // chart: views per day (one series, so the title names it and there is no legend)
  $al_series = $al_stats['series'];
  $al_max = 0;
  foreach ($al_series as $al_v) { $al_max = max($al_max, $al_v['views']); }
  $al_step = $al_max <= 5 ? 1 : ($al_max <= 20 ? 5 : ($al_max <= 100 ? 20 : pow(10, floor(log10($al_max)))));
  $al_top = max($al_step, ceil($al_max / $al_step) * $al_step);
  $al_n = count($al_series);
  $al_w = 720; $al_h = 220; $al_pl = 36; $al_pb = 26; $al_pt = 10;
  $al_bw = ($al_w - $al_pl) / max(1, $al_n);
  $al_link = function ($days, $item) { $q = array('days' => $days); if ($item) { $q['item'] = $item; } return antique_stats_url($q); };
?>
<div class="al-account-head">
  <h2><?php _e('Analytics', 'starter'); ?></h2>
  <nav class="al-stats-periods" aria-label="<?php echo osc_esc_html(__('Period', 'starter')); ?>">
    <?php foreach ($al_periods as $al_d => $al_label) { ?>
      <a href="<?php echo osc_esc_html($al_link($al_d, $al_item)); ?>"<?php echo $al_d == $al_days ? ' class="is-active" aria-current="true"' : ''; ?>><?php echo osc_esc_html($al_label); ?></a>
    <?php } ?>
  </nav>
</div>

<?php if (empty($al_all['items'])) { ?>
  <div class="al-empty-state">
    <h2><?php _e('No listings yet', 'starter'); ?></h2>
    <p><?php _e('Once you list an item you will see how many buyers view it, ask about it and save it.', 'starter'); ?></p>
    <a class="al-btn al-btn-ink" href="<?php echo osc_item_post_url(); ?>"><?php _e('Sell an item', 'starter'); ?></a>
  </div>
<?php } else { ?>

  <form class="al-stats-filter" method="get" action="<?php echo osc_esc_html(antique_stats_url()); ?>">
    <?php if (!osc_rewrite_enabled()) { ?><input type="hidden" name="page" value="custom" /><input type="hidden" name="route" value="al-stats" /><?php } ?>
    <input type="hidden" name="days" value="<?php echo $al_days; ?>" />
    <label for="al-stats-item"><?php _e('Listing', 'starter'); ?></label>
    <select id="al-stats-item" name="item" onchange="this.form.submit()">
      <option value="0"><?php _e('All listings', 'starter'); ?></option>
      <?php foreach ($al_all['items'] as $al_r) { ?>
        <option value="<?php echo (int)$al_r['pk_i_id']; ?>"<?php echo (int)$al_r['pk_i_id'] === $al_item ? ' selected' : ''; ?>><?php echo osc_esc_html(mb_substr((string)$al_r['s_title'], 0, 70)); ?></option>
      <?php } ?>
    </select>
    <noscript><button type="submit" class="al-btn al-btn-outline"><?php _e('Show', 'starter'); ?></button></noscript>
  </form>

  <div class="al-stats-kpis">
    <?php foreach ($al_kpis as $al_k => $al_lab) { $al_ch = antique_stats_change($al_t[$al_k], $al_stats['prev'][$al_k]); ?>
      <div class="al-stat-tile" title="<?php echo osc_esc_html($al_lab[1]); ?>">
        <span class="al-stat-label"><?php echo osc_esc_html($al_lab[0]); ?></span>
        <strong><?php echo number_format($al_t[$al_k]); ?></strong>
        <?php if ($al_ch !== '') { ?><span class="al-stat-change <?php echo $al_ch[0] === '-' ? 'is-down' : 'is-up'; ?>"><?php echo osc_esc_html(sprintf(__('%s vs previous %d days', 'starter'), $al_ch, $al_days)); ?></span><?php } ?>
      </div>
    <?php } ?>
    <div class="al-stat-tile is-wide">
      <span class="al-stat-label"><?php _e('Contact rate', 'starter'); ?></span>
      <strong><?php echo $al_rate; ?>%</strong>
      <span class="al-stat-note"><?php _e('Enquiries and phone reveals per 100 views', 'starter'); ?></span>
    </div>
  </div>

  <section class="al-panel al-stats-chart">
    <h3><?php _e('Views per day', 'starter'); ?></h3>
    <?php if ($al_max == 0) { ?>
      <p class="al-stats-none"><?php _e('No views in this period yet.', 'starter'); ?></p>
    <?php } else { ?>
      <div class="al-chart-wrap">
        <svg viewBox="0 0 <?php echo $al_w; ?> <?php echo $al_h; ?>" role="img" aria-label="<?php echo osc_esc_html(sprintf(__('Views per day over the last %d days, highest %d', 'starter'), $al_days, $al_max)); ?>" preserveAspectRatio="none">
          <?php for ($al_g = 0; $al_g <= $al_top; $al_g += $al_step) { $al_y = $al_h - $al_pb - ($al_h - $al_pb - $al_pt) * $al_g / $al_top; ?>
            <line class="al-chart-grid" x1="<?php echo $al_pl; ?>" x2="<?php echo $al_w; ?>" y1="<?php echo round($al_y, 1); ?>" y2="<?php echo round($al_y, 1); ?>" />
            <text class="al-chart-axis" x="<?php echo $al_pl - 6; ?>" y="<?php echo round($al_y + 4, 1); ?>" text-anchor="end"><?php echo $al_g; ?></text>
          <?php } ?>
          <?php $al_i = 0; foreach ($al_series as $al_d => $al_v) {
            $al_bh = ($al_h - $al_pb - $al_pt) * $al_v['views'] / $al_top;
            $al_x = $al_pl + $al_i * $al_bw;
            $al_gap = min(4, $al_bw * 0.25);
            $al_tip = date('D j M', strtotime($al_d)) . ': ' . sprintf(_n('%d view', '%d views', $al_v['views'], 'starter'), $al_v['views'])
              . ($al_v['enquiries'] ? ' · ' . sprintf(_n('%d enquiry', '%d enquiries', $al_v['enquiries'], 'starter'), $al_v['enquiries']) : '');
          ?>
            <g class="al-chart-col" data-tip="<?php echo osc_esc_html($al_tip); ?>">
              <rect class="al-chart-hit" x="<?php echo round($al_x, 1); ?>" y="<?php echo $al_pt; ?>" width="<?php echo round($al_bw, 1); ?>" height="<?php echo $al_h - $al_pb - $al_pt; ?>" />
              <?php if ($al_v['views'] > 0) { $al_bh = max(2, $al_bh); ?>
                <path class="al-chart-bar" d="<?php
                  $x0 = round($al_x + $al_gap / 2, 1); $x1 = round($al_x + $al_bw - $al_gap / 2, 1); $yb = $al_h - $al_pb; $yt = round($yb - $al_bh, 1); $rr = min(4, ($x1 - $x0) / 2, $al_bh);
                  echo "M{$x0},{$yb} V" . ($yt + $rr) . " Q{$x0},{$yt} " . ($x0 + $rr) . ",{$yt} H" . ($x1 - $rr) . " Q{$x1},{$yt} {$x1}," . ($yt + $rr) . " V{$yb} Z";
                ?>" />
              <?php } ?>
            </g>
            <?php if ($al_n <= 10 || $al_i % (int)ceil($al_n / 8) === 0) { ?>
              <text class="al-chart-axis" x="<?php echo round($al_x + $al_bw / 2, 1); ?>" y="<?php echo $al_h - 8; ?>" text-anchor="middle"><?php echo osc_esc_html(date('j M', strtotime($al_d))); ?></text>
            <?php } ?>
          <?php $al_i++; } ?>
          <line class="al-chart-base" x1="<?php echo $al_pl; ?>" x2="<?php echo $al_w; ?>" y1="<?php echo $al_h - $al_pb; ?>" y2="<?php echo $al_h - $al_pb; ?>" />
        </svg>
        <div class="al-chart-tip" role="status" hidden></div>
      </div>
      <details class="al-chart-table">
        <summary><?php _e('Show as a table', 'starter'); ?></summary>
        <table class="al-pay-table">
          <thead><tr><th><?php _e('Day', 'starter'); ?></th><th><?php _e('Views', 'starter'); ?></th><th><?php _e('Enquiries', 'starter'); ?></th><th><?php _e('Phone reveals', 'starter'); ?></th></tr></thead>
          <tbody><?php foreach (array_reverse($al_series, true) as $al_d => $al_v) { ?><tr><td><?php echo osc_esc_html(date('j M Y', strtotime($al_d))); ?></td><td><?php echo (int)$al_v['views']; ?></td><td><?php echo (int)$al_v['enquiries']; ?></td><td><?php echo (int)$al_v['phone']; ?></td></tr><?php } ?></tbody>
        </table>
      </details>
    <?php } ?>
  </section>

  <?php $al_tips = antique_stats_tips($al_stats); if ($al_tips) { ?>
    <section class="al-panel al-stats-tips">
      <h3><?php _e('Suggestions', 'starter'); ?></h3>
      <ul><?php foreach ($al_tips as $al_tip) { ?><li><?php echo osc_esc_html($al_tip); ?></li><?php } ?></ul>
    </section>
  <?php } ?>

  <?php if (!$al_item) { ?>
    <h3 class="al-stats-h"><?php _e('By listing', 'starter'); ?></h3>
    <div class="al-table-wrap">
      <table class="al-pay-table al-stats-table">
        <thead><tr>
          <th><?php _e('Listing', 'starter'); ?></th><th><?php _e('Views', 'starter'); ?></th><th><?php _e('Enquiries', 'starter'); ?></th><th><?php _e('Phone', 'starter'); ?></th><th><?php _e('Watching now', 'starter'); ?></th><th><?php _e('Offers / bids', 'starter'); ?></th><th><?php _e('All-time views', 'starter'); ?></th>
        </tr></thead>
        <tbody>
          <?php foreach ($al_stats['items'] as $al_r) { ?>
            <tr>
              <td><a href="<?php echo osc_esc_html($al_link($al_days, $al_r['pk_i_id'])); ?>"><?php echo osc_esc_html((string)$al_r['s_title']); ?></a>
                <?php if (!(int)$al_r['b_active'] || !(int)$al_r['b_enabled']) { ?><small><?php _e('Not live', 'starter'); ?></small><?php } ?></td>
              <td data-label="<?php echo osc_esc_html(__('Views', 'starter')); ?>"><?php echo (int)$al_r['views']; ?></td>
              <td data-label="<?php echo osc_esc_html(__('Enquiries', 'starter')); ?>"><?php echo (int)$al_r['enquiries']; ?></td>
              <td data-label="<?php echo osc_esc_html(__('Phone', 'starter')); ?>"><?php echo (int)$al_r['phone']; ?></td>
              <td data-label="<?php echo osc_esc_html(__('Watching now', 'starter')); ?>"><?php echo (int)$al_r['watching']; ?></td>
              <td data-label="<?php echo osc_esc_html(__('Offers / bids', 'starter')); ?>"><?php echo (int)$al_r['offers'] + (int)$al_r['bids']; ?></td>
              <td data-label="<?php echo osc_esc_html(__('All-time views', 'starter')); ?>"><?php echo (int)$al_r['views_all']; ?></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  <?php } else { ?>
    <p class="al-stats-back"><a href="<?php echo osc_esc_html($al_link($al_days, 0)); ?>">&larr; <?php _e('All listings', 'starter'); ?></a>
      <?php if (function_exists('antique_promote_url') && function_exists('antique_pay_enabled') && antique_pay_enabled()) { ?> · <a href="<?php echo osc_esc_html(antique_promote_url($al_item)); ?>"><?php _e('Promote this listing', 'starter'); ?></a><?php } ?></p>
  <?php } ?>

  <script>
    (function () {
      var wrap = document.querySelector('.al-chart-wrap');
      if (!wrap) { return; }
      var tip = wrap.querySelector('.al-chart-tip');
      function show(col, x) {
        wrap.querySelectorAll('.al-chart-col.is-hover').forEach(function (c) { c.classList.remove('is-hover'); });
        col.classList.add('is-hover');
        tip.textContent = col.getAttribute('data-tip');
        tip.hidden = false;
        var r = wrap.getBoundingClientRect();
        tip.style.left = Math.max(0, Math.min(r.width - tip.offsetWidth, x - r.left - tip.offsetWidth / 2)) + 'px';
      }
      wrap.querySelectorAll('.al-chart-col').forEach(function (col) {
        col.addEventListener('mousemove', function (e) { show(col, e.clientX); });
        col.addEventListener('click', function (e) { show(col, e.clientX); });
      });
      wrap.addEventListener('mouseleave', function () { tip.hidden = true; wrap.querySelectorAll('.al-chart-col.is-hover').forEach(function (c) { c.classList.remove('is-hover'); }); });
    })();
  </script>
<?php } ?>
