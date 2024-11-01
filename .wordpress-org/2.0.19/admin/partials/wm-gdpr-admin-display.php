<?php

	$background = isset( $this->wm_gdpr['background'] ) ? $this->wm_gdpr['background'] : '#FFFFFF';
	$foreground = isset( $this->wm_gdpr['foreground'] ) ? $this->wm_gdpr['foreground'] : '#000000';

?>
<style>
    p.form-field {
display: block;
        clear: both;
        padding: 10px 0;
    }
    p.form-field input[type="text"] {
        width: 300px;
    }
</style>
<h1><?php echo esc_html ( get_admin_page_title () ); ?></h1>
<div class="wrap <?php echo $this->plugin_name; ?>">
	<?php settings_errors(); ?>
	<form method="post" name="wm_gdpr_options" action="options.php">
		<?php
			settings_fields ( $this->plugin_name );
			do_settings_sections ( $this->plugin_name );
		?>
        <h2><?php _e( 'Main Settings', $this->plugin_name ); ?></h2>
        
        
        <?php
	        do_action( 'wm_gdpr__form_parser');
        ?>
		

		<div class="<?php echo $this->plugin_name; ?>-advice">
            <?php do_action($this->plugin_name . '-status'); ?>
        </div>
        <h2><?php _e('Customize your footer bar style:', $this->plugin_name); ?></h2>
        <fieldset>
            <legend class="screen-reader-text"><span><?php _e ( 'background color', $this->plugin_name ); ?></span>
            </legend>
            <label for="<?php echo $this->plugin_name; ?>-background">
                <span><?php _e ( 'Background Color:', $this->plugin_name ); ?></span>
            </label>
                <input type="text" class="wm-color-picker" id="<?php echo $this->plugin_name; ?>-background" name="<?php echo $this->plugin_name; ?>[background]" value="<?php if( !empty ( $background ) ) { echo $background; } ?>"/>
        </fieldset>
        <fieldset>
            <legend class="screen-reader-text"><span><?php _e ( 'foreground color', $this->plugin_name ); ?></span>
            </legend>
            <label for="<?php echo $this->plugin_name; ?>-foreground">
                <span><?php _e ( 'Foreground Color:', $this->plugin_name ); ?></span>
            </label>
                <input type="text" class="wm-color-picker" id="<?php echo $this->plugin_name; ?>-foreground" name="<?php echo $this->plugin_name; ?>[foreground]" value="<?php if( !empty ( $foreground ) ) { echo $foreground; } ?>"/>
        </fieldset>



		<?php submit_button ( __ ( 'Save all changes', $this->plugin_name ), 'primary', 'submit', true ); ?>
	</form>
</div>
<?php if ( empty( $this->wm_gdpr['id_iubenda'] ) ): ?>
	<div class="<?php echo $this->plugin_name; ?>-advice">
		<?php _e ( 'Do you want to include <strong>Iubenda Pro</strong> privacy policy statement?', $this->plugin_name ); ?>
		<em>
			<?php _e ( 'With Iubenda Pro you can easily make your website completely compliant to GDPR. Click on the button below to discover our rates.', $this->plugin_name ); ?>
		</em>
		<a class="button button-secondary" href="<?php echo $this->shop_url; ?>"
		   target="_blank"><?php _e ( 'Add Iubenda Pro', $this->plugin_name ) ?></a>

	</div>
<?php endif; ?>
 <em class="<?php echo $this->plugin_name; ?>-credits">Powered by <a href="https://webme.it" target="_blank">WebMe.it</a></em>

