<?php antique_info_page_open('al-about'); ?>

<h2><?php _e('What AntiqueLion is', 'starter'); ?></h2>
<p><?php _e('AntiqueLion connects people who own antiques, vintage pieces and collectibles with people looking for them. Dealers and private collectors list items at a fixed price or run a timed auction, and buyers contact them directly.', 'starter'); ?></p>
<p><?php _e('We are a listing and auction platform, not a shop. Items are owned, described and shipped by their sellers, and payment is agreed between buyer and seller.', 'starter'); ?></p>

<h2><?php _e('Built for buyers in any country', 'starter'); ?></h2>
<ul>
  <li><?php _e('Prices are shown in the seller\'s currency, with an approximate conversion into yours.', 'starter'); ?></li>
  <li><?php _e('Auction countdowns use your local time.', 'starter'); ?></li>
  <li><?php _e('Phone numbers include the country code, so calling or messaging a seller abroad works first time.', 'starter'); ?></li>
  <li><?php _e('Every listing shows where the item is, so you can ask about shipping before you commit.', 'starter'); ?></li>
</ul>

<h2><?php _e('Selling with us', 'starter'); ?></h2>
<p><?php _e('Listing is free. Add clear photos and an honest description, choose a fixed price or an auction, and answer buyers\' questions from your account.', 'starter'); ?></p>
<p class="al-info-actions">
  <a class="al-btn al-btn-brass" href="<?php echo osc_item_post_url(); ?>"><?php _e('Sell an item', 'starter'); ?></a>
  <a class="al-btn al-btn-outline" href="<?php echo antique_info_page_url('al-safety'); ?>"><?php _e('How it works', 'starter'); ?></a>
</p>

<h2><?php _e('Get in touch', 'starter'); ?></h2>
<p><?php echo sprintf(__('Questions, feedback or a listing to report? <a href="%s">Send us a message</a> and we will reply by email.', 'starter'), osc_contact_url()); ?></p>

<?php antique_info_page_close(false); ?>
