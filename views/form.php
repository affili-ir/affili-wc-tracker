<form style="margin-left:20px;" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
    <input type="hidden" name="action" value="set_account_id" />
    <input type="hidden" name="affili_set_account_id" value="<?php echo wp_create_nonce( 'eadkf#adk$fawlkaawwlRRe' ); ?>">

    <div class="card">
        <div class="affili-form-group">
            <label class="affili-label" for="accountId"><?php _e('Account ID', $plugin_name); ?></label>
            <input required class="affili-form-control" dir="ltr" type="text" name="account_id" id="accountId" value="<?php echo $account_id ? $account_id->value : ''; ?>" />
        </div>
    </div>
    <h2 class="affili-header-inform"><?php _e('Add Commission key', $plugin_name) ?></h2>
    <div class="card affili-card-inform">
        <div class="affili-form-group affili-d-inline-block affili-input-inline-grid">
            <label class="affili-label" for="affili-ir-select2-category"><?php _e('Category', $plugin_name) ?></label>
            <select class="affili-form-control" id="affili-ir-select2-categories" style="width:100%" name="category[id]"></select>
        </div>
        <div class="affili-form-group affili-d-inline-block affili-input-inline-grid">
            <label class="affili-label" for="commission-key"><?php _e('Commission key', $plugin_name) ?></label>
            <input class="affili-form-control" dir="ltr" type="text" name="category[commission-key]" id="commission-key" />
        </div>
    </div>

    <input class="button button-primary" style="margin-top:15px;" value="<?php _e('Submit', $plugin_name); ?>" type="submit">
</form>

<div class="wrap">
    <?php $list_table->display(); ?>
</div>

<form style="margin-left:20px;" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
    <input type="hidden" name="action" value="set_custom_code" />
    <input type="hidden" name="affili_custom_code" value="<?php echo wp_create_nonce( 'eadkf#adk$fawlkrrt2RRe' ); ?>">

    <h2 class="affili-header-inform"><?php _e('Custom Code', $plugin_name) ?></h2>
    <div class="card affili-card-inform">
        <div class="affili-form-group">
            <textarea rows="10" cols="10" style="height:100px;" required class="affili-form-control" dir="ltr" name="custom_code" id="custom-code"><?php echo $custom_code ? $custom_code->value : ''; ?></textarea>
        </div>
    </div>

    <input class="button button-primary" style="margin-top:15px;" value="<?php _e('Submit', $plugin_name); ?>" type="submit">
</form>

<?php
