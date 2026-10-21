<?php antique_info_page_open('al-privacy'); ?>

<h2><?php _e('Information we collect', 'starter'); ?></h2>
<ul>
  <li><strong><?php _e('Account details:', 'starter'); ?></strong> <?php _e('your name, email address, password (stored encrypted), phone numbers, account type and, if you add them, location, website and a short description.', 'starter'); ?></li>
  <li><strong><?php _e('Listings:', 'starter'); ?></strong> <?php _e('titles, descriptions, photos, prices, item location and the contact details you choose to show.', 'starter'); ?></li>
  <li><strong><?php _e('Activity:', 'starter'); ?></strong> <?php _e('bids (with the time and IP address of each bid), questions and comments, saved searches and messages sent through our contact forms.', 'starter'); ?></li>
  <li><strong><?php _e('Technical data:', 'starter'); ?></strong> <?php _e('your IP address and browser information, used to keep the site secure and to guess your country for currency and phone settings.', 'starter'); ?></li>
</ul>

<h2><?php _e('How we use it', 'starter'); ?></h2>
<ul>
  <li><?php _e('to run your account, publish your listings and process bids and auctions;', 'starter'); ?></li>
  <li><?php _e('to pass your message and the contact details you enter to the seller or user you write to;', 'starter'); ?></li>
  <li><?php _e('to send emails you need, such as account activation, password resets, auction results and the saved-search alerts you ask for;', 'starter'); ?></li>
  <li><?php _e('to prevent fraud, spam and abuse, and to meet legal obligations.', 'starter'); ?></li>
</ul>
<p><?php _e('We do not sell your personal information.', 'starter'); ?></p>

<h2><?php _e('What other people can see', 'starter'); ?></h2>
<p><?php _e('Your public profile shows your name, account type, the month you joined, your location and your active listings. Your phone number is shown to visitors who choose to reveal it on your listings. Bid histories show bidders\' names in shortened form (for example S***u); the seller of an auction you win sees your name and email address so you can complete the sale. Your email address is not shown unless you choose to show it on a listing.', 'starter'); ?></p>

<h2><?php _e('Services we use', 'starter'); ?></h2>
<ul>
  <li><?php _e('Country lookup uses a DB-IP database on our own server; your IP address is not sent to them.', 'starter'); ?></li>
  <li><?php _e('Exchange rates are downloaded from ExchangeRate-API; no personal data is sent.', 'starter'); ?></li>
  <li><?php _e('Fonts, flags and some scripts load from Google Fonts and public content delivery networks, which receive your IP address when your browser requests them.', 'starter'); ?></li>
  <li><?php _e('"View location on Google Maps" opens Google Maps, which has its own privacy policy.', 'starter'); ?></li>
  <li><?php _e('Our hosting and email providers process data on our behalf to run the site.', 'starter'); ?></li>
</ul>

<h2><?php _e('Cookies', 'starter'); ?></h2>
<p><?php _e('We use cookies that the site needs to work: to keep you signed in, to remember your display currency and detected country, and to protect forms against misuse. We do not use advertising cookies.', 'starter'); ?></p>

<h2><?php _e('How long we keep it', 'starter'); ?></h2>
<p><?php _e('We keep your account information while your account is open. When you delete your account, your profile and listings are removed; records we must keep for legal or security reasons, such as bid records, may be kept for a limited time.', 'starter'); ?></p>

<h2><?php _e('Your choices and rights', 'starter'); ?></h2>
<ul>
  <li><?php _e('See and correct your details in Account settings.', 'starter'); ?></li>
  <li><?php _e('Stop saved-search emails from the link in each email or from Saved searches.', 'starter'); ?></li>
  <li><?php _e('Delete your account at any time from Account settings.', 'starter'); ?></li>
  <li><?php echo sprintf(__('Ask for a copy of your data, or raise any privacy question, through our <a href="%s">contact form</a>. Depending on where you live you may also have the right to complain to your data protection authority.', 'starter'), osc_contact_url()); ?></li>
</ul>

<h2><?php _e('Changes', 'starter'); ?></h2>
<p><?php _e('We will update this page if the way we handle information changes. The date below shows the latest version.', 'starter'); ?></p>

<?php antique_info_page_close(); ?>
