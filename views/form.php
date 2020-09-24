<div class="card" style="margin-left:20px;">
    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
        <input type="hidden" name="action" value="set_access_token" />
        <input type="hidden" name="affili_set_access_token" value="<?php echo wp_create_nonce( 'eadkf#adk$fawlkaawwlRRe' ); ?>">
        <div class="affili-form-group">
            <label class="affili-label" for="accessToken"><?php _e('access token', 'affili'); ?></label>
            <input required class="affili-form-control" dir="ltr" type="text" name="access_token" id="accessToken" value="<?php echo $access_token ? $access_token->value : ''; ?>" />
        </div>

        <input class="button button-primary" value="<?php _e('submit', 'affili'); ?>" type="submit">
    </form>
</div>

<?php
