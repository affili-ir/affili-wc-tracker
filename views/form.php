<div class="card" style="margin-left:20px;">
    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
        <input type="hidden" name="action" value="set_account_id" />
        <input type="hidden" name="affili_set_account_id" value="<?php echo wp_create_nonce( 'eadkf#adk$fawlkaawwlRRe' ); ?>">
        <div class="affili-form-group">
            <label class="affili-label" for="accountId"><?php _e('account id', 'affili'); ?></label>
            <input required class="affili-form-control" dir="ltr" type="text" name="account_id" id="accountId" value="<?php echo $account_id ? $account_id->value : ''; ?>" />
        </div>

        <input class="button button-primary" value="<?php _e('submit', 'affili'); ?>" type="submit">
    </form>
</div>

<?php
