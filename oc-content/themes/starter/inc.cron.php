<?php
/**
 * Background jobs: auction results, offer expiry, watchlist "ending soon" emails.
 *
 * A server cron requests index.php?page=cron every minute and the jobs run there,
 * so visitors' page loads never wait for them. If no cron request has arrived for
 * ANTIQUE_CRON_FALLBACK seconds (cron not set up yet, or broken), page loads run
 * them as before so auctions still close.
 */

define('ANTIQUE_CRON_FALLBACK', 600);
define('ANTIQUE_CRON_MIN_GAP', 20);   // page=cron is public; a flood of requests runs each job at most this often

function antique_cron_request() {
  return defined('__FROM_CRON__');
}

function antique_cron_seen() {
  osc_set_preference('cron_seen', time(), 'starter_theme', 'INTEGER');
}
osc_add_hook('cron', 'antique_cron_seen', 1);

/**
 * True when job $name should run in this request. $every is its spacing on page
 * loads (fallback only); cron requests run it every time, at most every MIN_GAP.
 */
function antique_job_due($name, $every) {
  $key = 'job_' . $name;
  $since = time() - (int)osc_get_preference($key, 'starter_theme');
  if (antique_cron_request()) {
    if ($since < ANTIQUE_CRON_MIN_GAP) { return false; }
  } else {
    if (time() - (int)osc_get_preference('cron_seen', 'starter_theme') < ANTIQUE_CRON_FALLBACK || $since < $every) { return false; }
  }
  osc_set_preference($key, time(), 'starter_theme', 'INTEGER');
  return true;
}
