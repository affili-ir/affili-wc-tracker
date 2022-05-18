<form style="margin-left:20px;" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
    <input type="hidden" name="action" value="set_settings" />
    <input type="hidden" name="affili_set_settings" value="<?php echo wp_create_nonce( 'eadkf#adk$fawlkaawwlRRe' ); ?>">

    <div class="card w-100 min-w-100">
        <div class="affili-form-group">
            <label class="affili-label" for="accountId"><?php _e('Account ID', $plugin_name); ?><span style="color:red;">*</span></label>
            <input required class="affili-form-control" dir="ltr" type="text" name="account_id" id="accountId" value="<?php echo $account_id ? $account_id : ''; ?>" />
        </div>

        <div class="affili-form-group">
            <label class="affili-label" for="customCode"><?php _e('Custom Code', $plugin_name); ?></label>
            <textarea rows="10" cols="10" style="height:100px;" class="affili-form-control" dir="ltr" name="custom_code" id="customCode"><?php echo $custom_code ? $custom_code : ''; ?></textarea>
        </div>
    </div>

    <input class="button button-primary" style="margin-top:15px;" value="<?php _e('Submit', $plugin_name); ?>" type="submit">
</form>
<?php
