<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Branded HTML email (same look as the auction emails) with a plain-text part.
 * $paragraphs are trusted HTML fragments: escape any user text before passing it.
 */
function antique_mail($to, $toName, $subject, $heading, $paragraphs, $ctaUrl = '', $ctaLabel = '') {
  if (trim((string)$to) === '') { return false; }
  $site = osc_esc_html(osc_page_title());
  $body = '<div style="background:#F7F4EE;padding:28px 12px;font-family:Arial,Helvetica,sans-serif;color:#14213D;">'
        . '<div style="max-width:560px;margin:0 auto;background:#FFFFFF;border:1px solid #E6DFD1;border-radius:6px;overflow:hidden;">'
        . '<div style="padding:18px 28px;border-bottom:3px solid #A67C2E;font-family:Georgia,serif;font-size:22px;color:#14213D;">' . $site . '</div>'
        . '<div style="padding:26px 28px;font-size:15px;line-height:1.6;">'
        . '<h1 style="margin:0 0 16px;font-family:Georgia,serif;font-size:24px;font-weight:normal;color:#14213D;">' . osc_esc_html($heading) . '</h1>'
        . '<p style="margin:0 0 14px;">' . sprintf(__('Hello %s,', 'starter'), osc_esc_html($toName)) . '</p>';
  foreach ($paragraphs as $para) {
    $body .= '<p style="margin:0 0 14px;">' . $para . '</p>';
  }
  if ($ctaUrl !== '') {
    $body .= '<p style="margin:22px 0 6px;"><a href="' . osc_esc_html($ctaUrl) . '" style="display:inline-block;padding:12px 24px;background:#A67C2E;color:#FFFFFF;text-decoration:none;border-radius:4px;font-weight:bold;">' . osc_esc_html($ctaLabel) . '</a></p>';
  }
  $body .= '</div><div style="padding:16px 28px;background:#FBF9F4;border-top:1px solid #EFE9DD;font-size:12px;color:#6B7280;">'
         . sprintf(__('You are receiving this email because of your activity on %s.', 'starter'), '<a href="' . osc_base_url() . '" style="color:#8A6420;">' . $site . '</a>')
         . '</div></div></div>';

  return @osc_sendMail(array(
    'from'     => _osc_from_email_aux(),
    'to'       => $to,
    'to_name'  => $toName,
    'subject'  => $subject,
    'body'     => $body,
    'alt_body' => trim(preg_replace("/\n{3,}/", "\n\n", html_entity_decode(strip_tags(str_replace(array('</p>', '</h1>', '</div>', '<br/>', '<br>'), "\n", $body)), ENT_QUOTES, 'UTF-8')))
  ));
}

// Quoted user text for an email body: escaped, line breaks kept, shortened
function antique_mail_quote($text, $max = 400) {
  $text = trim((string)$text);
  if (mb_strlen($text) > $max) { $text = rtrim(mb_substr($text, 0, $max)) . '…'; }
  return '<span style="display:block;padding:12px 16px;border-left:3px solid #D9B26A;background:#FBF9F4;color:#1E2D4F;">' . nl2br(osc_esc_html($text)) . '</span>';
}
