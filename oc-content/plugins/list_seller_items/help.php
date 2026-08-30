<div id="settings_form" style="border: 1px solid #ccc; background: #eee; ">
    <div style="padding: 0 20px 20px;">
        <div>
            <fieldset>
                <legend>
                    <h1><?php _e('Seller Post Help', 'list_seller_items') ; ?></h1>
                </legend>
                <h2>
                    <?php _e('What is Seller Post Plugin?', 'list_seller_items') ; ?>
                </h2>
                <p>
                    <?php _e('Seller Post plugin allows you to display a link that will display all seller items for sale', 'list_seller_items') ; ?>.
                </p>
                <h2>
                    <?php _e('How does Seller Post plugin work?', 'list_seller_items') ; ?>
                </h2>
                <p>
                    <?php _e('In order to use Seller Post plugin, you should edit your theme item detail page (<code>item_detail.php</code>) and add the following line anywhere in the code you want the Seller Post link to appear', 'list_seller_items') ; ?>:
                </p>
                <pre>
                    &lt;?php seller_post(); ?&gt;
                </pre>
                <h2>
                <?php _e('Could I cutomize the style of Seller Post plugin?', 'list_seller_items') ; ?>
                </h2>
                <p>
                    <?php _e("Of course you can. Seller Post display a link only you can use css to make a button or anything else", 'list_seller_items') ; ?>.
                </p>
                <p>
                    <?php _e("You can in the default theme use it like a button by using this code", 'list_seller_items') ; ?>:
                </p>
                <pre>
                    &lt;strong class="share"&gt;&lt;?php seller_post(); ?&gt;&lt;strong&gt;
                </pre>
            </fieldset>
        </div>
    </div>
</div>