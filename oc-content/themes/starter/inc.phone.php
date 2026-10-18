<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Phone numbers for display and call / WhatsApp links.
 * New numbers are saved in E.164 (+94771234567) by js/al-phone.js; numbers saved
 * before that have no country code, so they are read in the listing's or the
 * seller's country.
 */

// ISO 3166 country => calling code (from intl-tel-input 24.6.0 data)
function antique_dial_codes() {
  return array(
  'AC'=>'247', 'AD'=>'376', 'AE'=>'971', 'AF'=>'93', 'AG'=>'1', 'AI'=>'1', 'AL'=>'355', 'AM'=>'374',
  'AO'=>'244', 'AR'=>'54', 'AS'=>'1', 'AT'=>'43', 'AU'=>'61', 'AW'=>'297', 'AX'=>'358', 'AZ'=>'994',
  'BA'=>'387', 'BB'=>'1', 'BD'=>'880', 'BE'=>'32', 'BF'=>'226', 'BG'=>'359', 'BH'=>'973', 'BI'=>'257',
  'BJ'=>'229', 'BL'=>'590', 'BM'=>'1', 'BN'=>'673', 'BO'=>'591', 'BQ'=>'599', 'BR'=>'55', 'BS'=>'1',
  'BT'=>'975', 'BW'=>'267', 'BY'=>'375', 'BZ'=>'501', 'CA'=>'1', 'CC'=>'61', 'CD'=>'243', 'CF'=>'236',
  'CG'=>'242', 'CH'=>'41', 'CI'=>'225', 'CK'=>'682', 'CL'=>'56', 'CM'=>'237', 'CN'=>'86', 'CO'=>'57',
  'CR'=>'506', 'CU'=>'53', 'CV'=>'238', 'CW'=>'599', 'CX'=>'61', 'CY'=>'357', 'CZ'=>'420', 'DE'=>'49',
  'DJ'=>'253', 'DK'=>'45', 'DM'=>'1', 'DO'=>'1', 'DZ'=>'213', 'EC'=>'593', 'EE'=>'372', 'EG'=>'20',
  'EH'=>'212', 'ER'=>'291', 'ES'=>'34', 'ET'=>'251', 'FI'=>'358', 'FJ'=>'679', 'FK'=>'500', 'FM'=>'691',
  'FO'=>'298', 'FR'=>'33', 'GA'=>'241', 'GB'=>'44', 'GD'=>'1', 'GE'=>'995', 'GF'=>'594', 'GG'=>'44',
  'GH'=>'233', 'GI'=>'350', 'GL'=>'299', 'GM'=>'220', 'GN'=>'224', 'GP'=>'590', 'GQ'=>'240', 'GR'=>'30',
  'GT'=>'502', 'GU'=>'1', 'GW'=>'245', 'GY'=>'592', 'HK'=>'852', 'HN'=>'504', 'HR'=>'385', 'HT'=>'509',
  'HU'=>'36', 'ID'=>'62', 'IE'=>'353', 'IL'=>'972', 'IM'=>'44', 'IN'=>'91', 'IO'=>'246', 'IQ'=>'964',
  'IR'=>'98', 'IS'=>'354', 'IT'=>'39', 'JE'=>'44', 'JM'=>'1', 'JO'=>'962', 'JP'=>'81', 'KE'=>'254',
  'KG'=>'996', 'KH'=>'855', 'KI'=>'686', 'KM'=>'269', 'KN'=>'1', 'KP'=>'850', 'KR'=>'82', 'KW'=>'965',
  'KY'=>'1', 'KZ'=>'7', 'LA'=>'856', 'LB'=>'961', 'LC'=>'1', 'LI'=>'423', 'LK'=>'94', 'LR'=>'231',
  'LS'=>'266', 'LT'=>'370', 'LU'=>'352', 'LV'=>'371', 'LY'=>'218', 'MA'=>'212', 'MC'=>'377', 'MD'=>'373',
  'ME'=>'382', 'MF'=>'590', 'MG'=>'261', 'MH'=>'692', 'MK'=>'389', 'ML'=>'223', 'MM'=>'95', 'MN'=>'976',
  'MO'=>'853', 'MP'=>'1', 'MQ'=>'596', 'MR'=>'222', 'MS'=>'1', 'MT'=>'356', 'MU'=>'230', 'MV'=>'960',
  'MW'=>'265', 'MX'=>'52', 'MY'=>'60', 'MZ'=>'258', 'NA'=>'264', 'NC'=>'687', 'NE'=>'227', 'NF'=>'672',
  'NG'=>'234', 'NI'=>'505', 'NL'=>'31', 'NO'=>'47', 'NP'=>'977', 'NR'=>'674', 'NU'=>'683', 'NZ'=>'64',
  'OM'=>'968', 'PA'=>'507', 'PE'=>'51', 'PF'=>'689', 'PG'=>'675', 'PH'=>'63', 'PK'=>'92', 'PL'=>'48',
  'PM'=>'508', 'PR'=>'1', 'PS'=>'970', 'PT'=>'351', 'PW'=>'680', 'PY'=>'595', 'QA'=>'974', 'RE'=>'262',
  'RO'=>'40', 'RS'=>'381', 'RU'=>'7', 'RW'=>'250', 'SA'=>'966', 'SB'=>'677', 'SC'=>'248', 'SD'=>'249',
  'SE'=>'46', 'SG'=>'65', 'SH'=>'290', 'SI'=>'386', 'SJ'=>'47', 'SK'=>'421', 'SL'=>'232', 'SM'=>'378',
  'SN'=>'221', 'SO'=>'252', 'SR'=>'597', 'SS'=>'211', 'ST'=>'239', 'SV'=>'503', 'SX'=>'1', 'SY'=>'963',
  'SZ'=>'268', 'TC'=>'1', 'TD'=>'235', 'TG'=>'228', 'TH'=>'66', 'TJ'=>'992', 'TK'=>'690', 'TL'=>'670',
  'TM'=>'993', 'TN'=>'216', 'TO'=>'676', 'TR'=>'90', 'TT'=>'1', 'TV'=>'688', 'TW'=>'886', 'TZ'=>'255',
  'UA'=>'380', 'UG'=>'256', 'US'=>'1', 'UY'=>'598', 'UZ'=>'998', 'VA'=>'39', 'VC'=>'1', 'VE'=>'58',
  'VG'=>'1', 'VI'=>'1', 'VN'=>'84', 'VU'=>'678', 'WF'=>'681', 'WS'=>'685', 'XK'=>'383', 'YE'=>'967',
  'YT'=>'262', 'ZA'=>'27', 'ZM'=>'260', 'ZW'=>'263'
  );
}

/**
 * @param string $phone     number as saved
 * @param array  $countries country codes to read a number without +code in, most likely first
 * @return array e164 ('+94771234567', or '' when unusable) and display ('+94 77 123 4567' style)
 */
function antique_phone_parts($phone, $countries = array()) {
  $raw = trim((string)$phone);
  $digits = preg_replace('/\D/', '', $raw);
  if (strlen($digits) < 6) {
    return array('e164' => '', 'display' => $raw);
  }

  $map = antique_dial_codes();
  $dial = '';
  $national = '';

  if (strpos($raw, '+') === 0 || strpos($digits, '00') === 0) {
    $digits = strpos($raw, '+') === 0 ? $digits : substr($digits, 2);
    // longest matching calling code (1 to 3 digits)
    for ($n = 3; $n >= 1; $n--) {
      if (in_array(substr($digits, 0, $n), $map, true)) { $dial = substr($digits, 0, $n); break; }
    }
    $national = $dial !== '' ? substr($digits, strlen($dial)) : $digits;
  } else {
    $countries[] = 'LK';   // the site's first market: early numbers are Sri Lankan
    foreach ($countries as $cc) {
      $cc = strtoupper(trim((string)$cc));
      if ($cc !== '' && isset($map[$cc])) { $dial = $map[$cc]; break; }
    }
    $national = $digits;
    if (strpos($national, $dial) === 0 && strlen($national) >= 11) {
      $national = substr($national, strlen($dial));   // saved as 94771234567
    } elseif ($national[0] === '0' && $dial !== '39') {
      $national = substr($national, 1);               // trunk prefix (Italy keeps it)
    }
  }

  if ($dial === '' || strlen($national) < 4) {
    return array('e164' => '', 'display' => $raw);
  }

  return array(
    'e164' => '+' . $dial . $national,
    'display' => '+' . $dial . ' ' . antique_phone_group($national)
  );
}

// readable groups: 77 204 0312, 201 555 0123, 7911 123456
function antique_phone_group($n) {
  $patterns = array(8 => array(4, 4), 9 => array(2, 3, 4), 10 => array(3, 3, 4), 11 => array(4, 3, 4));
  $len = strlen($n);
  if (!isset($patterns[$len])) {
    return trim(chunk_split($n, 3, ' '));
  }
  $out = array();
  $pos = 0;
  foreach ($patterns[$len] as $size) { $out[] = substr($n, $pos, $size); $pos += $size; }
  return implode(' ', $out);
}
