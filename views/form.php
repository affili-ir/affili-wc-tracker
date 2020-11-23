<form style="margin-left:20px;" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
    <input type="hidden" name="action" value="set_account_id" />
    <input type="hidden" name="affili_set_account_id" value="<?php echo wp_create_nonce( 'eadkf#adk$fawlkaawwlRRe' ); ?>">
    <div class="card">
        <div class="affili-form-group">
            <label class="affili-label" for="accountId"><?php _e('account id', $plugin_name); ?></label>
            <input required class="affili-form-control" dir="ltr" type="text" name="account_id" id="accountId" value="<?php echo $account_id ? $account_id->value : ''; ?>" />
        </div>
    </div>
    <h2 style="font-family:inherit;"><?php echo __('categories', $plugin_name) ?></h2>
    <div class="card" style="min-width:100%;">
        <?php foreach($main_cats as $category) : ?>
            <?php $val = $woocommerce->findCommissionKey($category->term_id); ?>
            <div class="affili-form-group affili-d-inline-block affili-input-inline-grid">
                <label class="affili-label" for="category<?php echo $category->term_id ?>"><?php echo $category->name ?></label>
                <input class="affili-form-control" dir="ltr" type="text" name="category[<?php echo $category->term_id ?>]" id="category<?php echo $category->term_id ?>" value="<?php echo $val ? $val->value : ''; ?>" />
            </div>
        <?php endforeach; ?>
    </div>

    <input class="button button-primary" style="margin-top:15px;" value="<?php _e('submit', $plugin_name); ?>" type="submit">
</form>




<?php
