<form style="margin-left:20px;" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
    <input type="hidden" name="action" value="set_account_id" />
    <input type="hidden" name="affili_set_account_id" value="<?php echo wp_create_nonce( 'eadkf#adk$fawlkaawwlRRe' ); ?>">
    <div class="card">
        <div class="affili-form-group">
            <label class="affili-label" for="accountId"><?php _e('Account ID', $plugin_name); ?></label>
            <input required class="affili-form-control" dir="ltr" type="text" name="account_id" id="accountId" value="<?php echo $account_id ? $account_id->value : ''; ?>" />
        </div>
    </div>
    <h2 style="font-family:inherit;"><?php _e('Add Commission key', $plugin_name) ?></h2>
    <div class="card" style="min-width:100%;">
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

<?php
