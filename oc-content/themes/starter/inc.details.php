<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Structured antique details: period, country of origin, main material, maker,
 * dimensions, provenance and shipping. Collected on the sell / edit form, shown in
 * "Item details" on the listing, used as search filters and in structured data.
 * Rows live in t_al_item_details (created on first use).
 */

define('ANTIQUE_DETAILS_SCHEMA', '1');

function antique_detail_options() {
  static $o = null;
  if ($o !== null) { return $o; }
  $o = array(
    'era' => array(
      'pre1700' => __('Before 1700', 'starter'),
      '1700s'   => __('18th century (1700s)', 'starter'),
      '1800s'   => __('19th century (1800s)', 'starter'),
      '1900_45' => __('Early 20th century (1900–1945)', 'starter'),
      '1945_70' => __('Mid-century (1945–1970)', 'starter'),
      '1970_99' => __('Late 20th century (1970–1999)', 'starter'),
      '2000'    => __('21st century (2000 onwards)', 'starter'),
      'unknown' => __('Unknown / unsure', 'starter')
    ),
    'material' => array(
      'wood'      => __('Wood', 'starter'),
      'silver'    => __('Silver', 'starter'),
      'gold'      => __('Gold', 'starter'),
      'brass'     => __('Brass or copper', 'starter'),
      'bronze'    => __('Bronze', 'starter'),
      'iron'      => __('Iron or steel', 'starter'),
      'ceramic'   => __('Ceramic or porcelain', 'starter'),
      'glass'     => __('Glass or crystal', 'starter'),
      'stone'     => __('Stone or marble', 'starter'),
      'textile'   => __('Textile', 'starter'),
      'paper'     => __('Paper or parchment', 'starter'),
      'leather'   => __('Leather', 'starter'),
      'gemstone'  => __('Gemstones or jewellery', 'starter'),
      'mixed'     => __('Mixed materials', 'starter'),
      'other'     => __('Other', 'starter')
    ),
    'shipping' => array(
      'worldwide' => __('Ships worldwide', 'starter'),
      'domestic'  => __('Ships within the seller\'s country', 'starter'),
      'pickup'    => __('Collection only', 'starter'),
      'custom'    => __('Ask the seller for a shipping quote', 'starter')
    ),
    'unit' => array('cm' => 'cm', 'in' => 'in')
  );
  return $o;
}

function antique_details_table() {
  return DB_TABLE_PREFIX . 't_al_item_details';
}

function antique_details_schema() {
  if (osc_get_preference('details_schema', 'starter_theme') == ANTIQUE_DETAILS_SCHEMA) { return; }
  $ok = Item::newInstance()->dao->query("CREATE TABLE IF NOT EXISTS `" . antique_details_table() . "` (
    `fk_i_item_id` int(10) unsigned NOT NULL,
    `s_era` varchar(20) DEFAULT NULL,
    `s_origin` char(2) DEFAULT NULL,
    `s_material` varchar(20) DEFAULT NULL,
    `s_maker` varchar(120) DEFAULT NULL,
    `d_height` decimal(10,2) DEFAULT NULL,
    `d_width` decimal(10,2) DEFAULT NULL,
    `d_depth` decimal(10,2) DEFAULT NULL,
    `s_unit` varchar(2) NOT NULL DEFAULT 'cm',
    `s_provenance` text,
    `s_shipping` varchar(12) DEFAULT NULL,
    PRIMARY KEY (`fk_i_item_id`),
    KEY `idx_era` (`s_era`),
    KEY `idx_material` (`s_material`),
    KEY `idx_origin` (`s_origin`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  if ($ok) {
    osc_set_preference('details_schema', ANTIQUE_DETAILS_SCHEMA, 'starter_theme');
  }
}
osc_add_hook('init', 'antique_details_schema', 1);

function antique_item_details($itemId) {
  static $cache = array();
  $itemId = (int)$itemId;
  if (isset($cache[$itemId])) { return $cache[$itemId]; }
  $row = array();
  if ($itemId > 0) {
    $res = Item::newInstance()->dao->query("SELECT * FROM `" . antique_details_table() . "` WHERE fk_i_item_id = " . $itemId);
    if ($res && $res->numRows() > 0) { $row = $res->row(); }
  }
  return $cache[$itemId] = array_merge(array(
    's_era' => '', 's_origin' => '', 's_material' => '', 's_maker' => '',
    'd_height' => null, 'd_width' => null, 'd_depth' => null, 's_unit' => 'cm',
    's_provenance' => '', 's_shipping' => ''
  ), $row);
}

function antique_details_countries() {
  static $list = null;
  if ($list === null) {
    $list = array();
    foreach ((array)Country::newInstance()->listAll() as $c) {
      $list[strtoupper($c['pk_c_code'])] = $c['s_name'];
    }
    asort($list);
  }
  return $list;
}

function antique_details_select($name, $options, $value, $placeholder) {
  $html = '<select name="' . $name . '" id="' . $name . '" class="al-select">';
  $html .= '<option value="">' . osc_esc_html($placeholder) . '</option>';
  foreach ($options as $k => $label) {
    $html .= '<option value="' . osc_esc_html($k) . '"' . ((string)$value === (string)$k ? ' selected' : '') . '>' . osc_esc_html($label) . '</option>';
  }
  return $html . '</select>';
}

function antique_details_num($v) {
  return ($v === null || $v === '' || (float)$v <= 0) ? '' : rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.');
}

/**
 * The "Item details" block on the sell and edit forms.
 */
function antique_details_form($itemId = 0) {
  $o = antique_detail_options();
  $d = antique_item_details($itemId);
  // keep what the seller typed when the form comes back with an error
  foreach (array('era', 'origin', 'material', 'maker', 'height', 'width', 'depth', 'unit', 'provenance', 'shipping') as $f) {
    $key = in_array($f, array('height', 'width', 'depth')) ? 'd_' . $f : 's_' . $f;
    if (Params::existParam('al_' . $f)) { $d[$key] = Params::getParam('al_' . $f); }
  }
  ?>
  <div class="al-details-form">
    <div class="al-details-head">
      <h3><?php _e('About the item', 'starter'); ?></h3>
      <p><?php _e('Buyers search and filter by these details. The more you fill in, the easier your item is to find.', 'starter'); ?></p>
    </div>
    <div class="al-details-grid">
      <label class="al-df"><span><?php _e('Period', 'starter'); ?></span>
        <?php echo antique_details_select('al_era', $o['era'], $d['s_era'], __('Select period', 'starter')); ?>
      </label>
      <label class="al-df"><span><?php _e('Main material', 'starter'); ?></span>
        <?php echo antique_details_select('al_material', $o['material'], $d['s_material'], __('Select material', 'starter')); ?>
      </label>
      <label class="al-df"><span><?php _e('Country of origin', 'starter'); ?></span>
        <?php echo antique_details_select('al_origin', antique_details_countries(), strtoupper((string)$d['s_origin']), __('Select country', 'starter')); ?>
      </label>
      <label class="al-df"><span><?php _e('Maker, artist or brand', 'starter'); ?> <em><?php _e('(optional)', 'starter'); ?></em></span>
        <input type="text" name="al_maker" id="al_maker" maxlength="120" value="<?php echo osc_esc_html($d['s_maker']); ?>" placeholder="<?php echo osc_esc_html(__('e.g. Wedgwood', 'starter')); ?>" />
      </label>
      <fieldset class="al-df al-df-wide al-dims">
        <legend><?php _e('Dimensions', 'starter'); ?> <em><?php _e('(optional)', 'starter'); ?></em></legend>
        <div class="al-dims-row">
          <label><span><?php _e('Height', 'starter'); ?></span><input type="number" name="al_height" min="0" step="any" inputmode="decimal" value="<?php echo osc_esc_html(antique_details_num($d['d_height'])); ?>" /></label>
          <label><span><?php _e('Width', 'starter'); ?></span><input type="number" name="al_width" min="0" step="any" inputmode="decimal" value="<?php echo osc_esc_html(antique_details_num($d['d_width'])); ?>" /></label>
          <label><span><?php _e('Depth', 'starter'); ?></span><input type="number" name="al_depth" min="0" step="any" inputmode="decimal" value="<?php echo osc_esc_html(antique_details_num($d['d_depth'])); ?>" /></label>
          <label class="al-dims-unit"><span><?php _e('Unit', 'starter'); ?></span><?php echo str_replace('<option value="">' . osc_esc_html(__('Unit', 'starter')) . '</option>', '', antique_details_select('al_unit', $o['unit'], $d['s_unit'] ? $d['s_unit'] : 'cm', __('Unit', 'starter'))); ?></label>
        </div>
      </fieldset>
      <label class="al-df al-df-wide"><span><?php _e('Provenance and history', 'starter'); ?> <em><?php _e('(optional)', 'starter'); ?></em></span>
        <textarea name="al_provenance" id="al_provenance" maxlength="2000" rows="3" placeholder="<?php echo osc_esc_html(__('Where the item comes from, previous owners, marks, receipts or certificates.', 'starter')); ?>"><?php echo osc_esc_html($d['s_provenance']); ?></textarea>
      </label>
      <label class="al-df al-df-wide"><span><?php _e('Shipping', 'starter'); ?></span>
        <?php echo antique_details_select('al_shipping', $o['shipping'], $d['s_shipping'] ? $d['s_shipping'] : 'worldwide', __('Select shipping', 'starter')); ?>
      </label>
    </div>
  </div>
  <?php
}

function antique_details_save($item) {
  if (!Params::existParam('al_shipping') && !Params::existParam('al_era')) { return; }  // not our form (e.g. admin quick edit)
  $o = antique_detail_options();
  $pick = function ($name, $allowed) { $v = (string)Params::getParam($name); return isset($allowed[$v]) ? $v : null; };
  $num = function ($name) { $v = (float)str_replace(',', '.', (string)Params::getParam($name)); return ($v > 0 && $v < 100000) ? round($v, 2) : null; };
  $origin = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string)Params::getParam('al_origin')), 0, 2));
  $countries = antique_details_countries();

  $row = array(
    'fk_i_item_id' => (int)$item['pk_i_id'],
    's_era'        => $pick('al_era', $o['era']),
    's_origin'     => isset($countries[$origin]) ? $origin : null,
    's_material'   => $pick('al_material', $o['material']),
    's_maker'      => trim(mb_substr(strip_tags((string)Params::getParam('al_maker')), 0, 120)),
    'd_height'     => $num('al_height'),
    'd_width'      => $num('al_width'),
    'd_depth'      => $num('al_depth'),
    's_unit'       => $pick('al_unit', $o['unit']) ?: 'cm',
    's_provenance' => trim(mb_substr(strip_tags((string)Params::getParam('al_provenance')), 0, 2000)),
    's_shipping'   => $pick('al_shipping', $o['shipping'])
  );
  $dao = Item::newInstance()->dao;
  $dao->query("DELETE FROM `" . antique_details_table() . "` WHERE fk_i_item_id = " . (int)$item['pk_i_id']);
  $dao->insert(antique_details_table(), $row);
}
osc_add_hook('posted_item', 'antique_details_save');
osc_add_hook('edited_item', 'antique_details_save');

function antique_details_delete($itemId) {
  Item::newInstance()->dao->query("DELETE FROM `" . antique_details_table() . "` WHERE fk_i_item_id = " . (int)$itemId);
}
osc_add_hook('delete_item', 'antique_details_delete');

/**
 * label => text for the listing page, in display order. Empty fields are left out.
 */
function antique_details_facts($itemId) {
  $o = antique_detail_options();
  $d = antique_item_details($itemId);
  $c = antique_details_countries();
  $f = array();
  if (isset($o['era'][$d['s_era']]) && $d['s_era'] != 'unknown') { $f[__('Period', 'starter')] = $o['era'][$d['s_era']]; }
  if (isset($o['material'][$d['s_material']])) { $f[__('Material', 'starter')] = $o['material'][$d['s_material']]; }
  if ($d['s_origin'] && isset($c[strtoupper($d['s_origin'])])) { $f[__('Origin', 'starter')] = $c[strtoupper($d['s_origin'])]; }
  if ($d['s_maker'] !== '') { $f[__('Maker', 'starter')] = $d['s_maker']; }
  $dims = array();
  foreach (array('d_height' => __('H', 'starter'), 'd_width' => __('W', 'starter'), 'd_depth' => __('D', 'starter')) as $k => $l) {
    if (antique_details_num($d[$k]) !== '') { $dims[] = $l . ' ' . antique_details_num($d[$k]); }
  }
  if ($dims) { $f[__('Dimensions', 'starter')] = implode(' × ', $dims) . ' ' . $d['s_unit']; }
  if (isset($o['shipping'][$d['s_shipping']])) { $f[__('Shipping', 'starter')] = $o['shipping'][$d['s_shipping']]; }
  return $f;
}

// Search filters: sEra, sMaterial, sOrigin
function antique_details_search() {
  $o = antique_detail_options();
  $era = Params::getParam('sEra');
  $mat = Params::getParam('sMaterial');
  $origin = strtoupper(preg_replace('/[^A-Za-z]/', '', (string)Params::getParam('sOrigin')));
  $conds = array();
  if (isset($o['era'][$era])) { $conds[] = "al_det.s_era = '" . $era . "'"; }
  if (isset($o['material'][$mat])) { $conds[] = "al_det.s_material = '" . $mat . "'"; }
  if (strlen($origin) == 2) { $conds[] = "al_det.s_origin = '" . $origin . "'"; }
  if (!$conds) { return; }
  $search = Search::newInstance();
  $search->addJoinTable('al_item_details', antique_details_table() . ' al_det', DB_TABLE_PREFIX . 't_item.pk_i_id = al_det.fk_i_item_id', 'INNER');
  $search->addConditions(implode(' AND ', $conds));
}
osc_add_hook('search_conditions', 'antique_details_search');

/**
 * Countries that appear as an origin on at least one live listing: code => name (count).
 */
function antique_details_origin_counts() {
  $res = Item::newInstance()->dao->query("SELECT d.s_origin, COUNT(*) AS n FROM `" . antique_details_table() . "` d
    JOIN `" . DB_TABLE_PREFIX . "t_item` i ON i.pk_i_id = d.fk_i_item_id
    WHERE d.s_origin IS NOT NULL AND i.b_enabled = 1 AND i.b_active = 1 AND i.b_spam = 0
    GROUP BY d.s_origin");
  $c = antique_details_countries();
  $out = array();
  if ($res) {
    foreach ($res->result() as $r) {
      $code = strtoupper($r['s_origin']);
      if (isset($c[$code])) { $out[$code] = array($c[$code], (int)$r['n']); }
    }
  }
  uasort($out, function ($a, $b) { return strcmp($a[0], $b[0]); });
  return $out;
}
