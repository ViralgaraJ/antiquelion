<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Front-end speed: the theme stylesheets are joined into one minified file, so a page
 * makes one CSS request instead of six. The file is rebuilt automatically whenever one
 * of the sources changes (its name carries a hash of their modification times), and
 * written to oc-content/uploads/cache/ (git-ignored). If it cannot be written, the
 * separate files are used as before.
 */

function antique_asset_cache_dir() { return osc_content_path() . 'uploads/cache/'; }
function antique_asset_cache_url() { return osc_content_url() . 'uploads/cache/'; }

/**
 * Enqueue $files (paths relative to the theme folder) as one bundle.
 */
function antique_enqueue_css_bundle($handle, $files) {
  $dir = __DIR__ . '/';
  $sig = '';
  foreach ($files as $f) { $sig .= $f . '@' . (is_file($dir . $f) ? filemtime($dir . $f) : 0) . ';'; }
  $name = $handle . '-' . substr(md5($sig), 0, 12) . '.css';
  $path = antique_asset_cache_dir() . $name;

  if (!is_file($path) && !antique_build_css_bundle($files, $path, $handle)) {
    foreach ($files as $i => $f) {
      osc_enqueue_style($handle . '-' . $i, osc_current_web_theme_url($f) . '?v=' . filemtime($dir . $f));
    }
    return;
  }
  osc_enqueue_style($handle, antique_asset_cache_url() . $name);
}

function antique_build_css_bundle($files, $path, $handle) {
  $dir = antique_asset_cache_dir();
  if (!is_dir($dir) && !@mkdir($dir, 0755, true)) { return false; }
  if (!is_file($dir . 'index.php')) { @file_put_contents($dir . 'index.php', '<?php // silence'); }
  $out = '';
  foreach ($files as $f) {
    $src = @file_get_contents(__DIR__ . '/' . $f);
    if ($src === false) { return false; }
    $out .= '/* ' . $f . ' */' . "\n" . antique_minify_css($src, osc_current_web_theme_url(dirname($f) . '/')) . "\n";
  }
  // write then rename, so a visitor never gets a half-written file
  $tmp = $path . '.' . getmypid() . '.tmp';
  if (@file_put_contents($tmp, $out) === false || !@rename($tmp, $path)) { @unlink($tmp); return false; }
  // drop older builds of this bundle
  foreach ((array)glob($dir . $handle . '-*.css') as $old) {
    if ($old !== $path && filemtime($old) < time() - 3600) { @unlink($old); }
  }
  return true;
}

/**
 * Conservative CSS minifier: comments and whitespace only. Relative url()s are made
 * absolute against $base because the bundle lives in another folder.
 */
function antique_minify_css($css, $base) {
  $css = preg_replace('/@charset\s+[^;]+;/i', '', $css);
  $css = preg_replace_callback('/url\(\s*([\'"]?)(?!data:|https?:|\/\/|\/|#)([^\'")]+)\1\s*\)/i', function ($m) use ($base) {
    return 'url(' . $m[1] . $base . $m[2] . $m[1] . ')';
  }, $css);
  $css = preg_replace('#/\*(?!!).*?\*/#s', '', $css);
  // quoted strings ([style*="display: none"], content: "...") must stay exactly as written
  $strings = array();
  $css = preg_replace_callback('/"(?:[^"\\\\\r\n]|\\\\.)*"|\'(?:[^\'\\\\\r\n]|\\\\.)*\'/', function ($m) use (&$strings) {
    $strings[] = $m[0];
    return "\x01" . (count($strings) - 1) . "\x02";
  }, $css);
  $css = preg_replace('/\s+/', ' ', $css);
  // only around characters where spacing never matters (not before ":" — "a :hover" differs from "a:hover")
  $css = preg_replace('/\s*([{};,])\s*/', '$1', $css);
  // a space after ":" never matters ("color: red", "(max-width: 640px)")
  $css = preg_replace('/:\s+/', ':', $css);
  $css = str_replace(';}', '}', $css);
  $css = preg_replace_callback('/\x01(\d+)\x02/', function ($m) use ($strings) { return $strings[(int)$m[1]]; }, $css);
  return trim($css);
}
