<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OG_WP_Branding {
	private $options;

	public function __construct( $options ) {
		$this->options = $options;
		
		add_action( 'og_wp_module_settings_branding', array( $this, 'render_settings' ) );
		
		// Branding Hooks
		add_action( 'login_enqueue_scripts', array( $this, 'custom_login_styles' ) );
		add_filter( 'login_headerurl', array( $this, 'custom_login_logo_url' ) );
		add_filter( 'admin_footer_text', array( $this, 'custom_admin_footer' ) );
		add_action( 'wp_before_admin_bar_render', array( $this, 'custom_admin_bar' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'custom_admin_styles' ) );
		add_action( 'admin_head', array( $this, 'custom_favicon' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'clean_dashboard_widgets' ) );
	}

	public function render_settings( $options ) {
		// Default WP Media upload script required
		wp_enqueue_media();
		?>
		<script>
		jQuery(document).ready(function($){
			$('.og-wp-upload-btn').click(function(e) {
				e.preventDefault();
				var target_input = $(this).prev('.og-wp-upload-input');
				var target_preview = $(this).next('.og-wp-upload-preview');
				var image = wp.media({ 
					title: 'Upload Image',
					multiple: false
				}).open()
				.on('select', function(e){
					var uploaded_image = image.state().get('selection').first();
					var image_url = uploaded_image.toJSON().url;
					target_input.val(image_url);
					target_preview.html('<img src="'+image_url+'" style="max-width:100px; max-height:50px; margin-top:10px; display:block;" />');
				});
			});
			$('.og-wp-color-picker').wpColorPicker();
			
			$('#btn-reset-branding').click(function() {
				if ( ! confirm("Reset all branding fields to their default values? (You must click Save Branding to apply)") ) return;
				
				// Reset color pickers
				$('input[name="og_wp_options[brand_login_bg]"]').wpColorPicker('color', '#f0f0f1');
				$('input[name="og_wp_options[brand_login_card_bg]"]').wpColorPicker('color', '#ffffff');
				$('input[name="og_wp_options[brand_admin_color]"]').wpColorPicker('color', '#1d2327');
				
				// Reset text inputs & previews
				$('input[name="og_wp_options[brand_login_logo]"]').val('');
				$('input[name="og_wp_options[brand_login_logo]"]').nextAll('.og-wp-upload-preview').html('');
				$('input[name="og_wp_options[brand_favicon]"]').val('');
				$('input[name="og_wp_options[brand_favicon]"]').nextAll('.og-wp-upload-preview').html('');
				$('input[name="og_wp_options[brand_footer_text]"]').val('Powered by OG of WP');
				
				// Reset checkboxes
				$('input[name="og_wp_options[brand_hide_wp_logo]"]').prop('checked', false);
				$('input[name="og_wp_options[brand_hide_howdy]"]').prop('checked', false);
				$('input[name="og_wp_options[brand_clean_dashboard]"]').prop('checked', false);
			});
		});
		</script>

		<h3>Login Page Customization</h3>
		<div class="og-wp-form-row">
			<label>Custom Login Logo</label>
			<div style="display:flex; flex-direction:column; align-items:flex-start;">
				<input type="text" name="og_wp_options[brand_login_logo]" class="og-wp-upload-input" value="<?php echo esc_attr($options['brand_login_logo'] ?? ''); ?>" style="width: 100%; margin-bottom: 5px;" />
				<button type="button" class="button og-wp-upload-btn">Select Image</button>
				<div class="og-wp-upload-preview">
					<?php if(!empty($options['brand_login_logo'])): ?>
						<img src="<?php echo esc_attr($options['brand_login_logo']); ?>" style="max-width:100px; max-height:50px; margin-top:10px; display:block;" />
					<?php endif; ?>
				</div>
			</div>
			<p class="description">Replaces the default WordPress logo on the login page.</p>
		</div>

		<div class="og-wp-form-row">
			<label>Login Page Background Color</label>
			<input type="text" name="og_wp_options[brand_login_bg]" class="og-wp-color-picker" value="<?php echo esc_attr($options['brand_login_bg'] ?? '#f0f0f1'); ?>" data-default-color="#f0f0f1" />
		</div>
		
		<div class="og-wp-form-row">
			<label>Login Card Background Color</label>
			<input type="text" name="og_wp_options[brand_login_card_bg]" class="og-wp-color-picker" value="<?php echo esc_attr($options['brand_login_card_bg'] ?? '#ffffff'); ?>" data-default-color="#ffffff" />
		</div>

		<hr style="margin:20px 0; border:0; border-top:1px solid #e2e8f0;">
		
		<h3>Admin Area Customization</h3>
		
		<div class="og-wp-form-row">
			<label>Custom Favicon</label>
			<div style="display:flex; flex-direction:column; align-items:flex-start;">
				<input type="text" name="og_wp_options[brand_favicon]" class="og-wp-upload-input" value="<?php echo esc_attr($options['brand_favicon'] ?? ''); ?>" style="width: 100%; margin-bottom: 5px;" />
				<button type="button" class="button og-wp-upload-btn">Select Favicon (ico/png)</button>
				<div class="og-wp-upload-preview">
					<?php if(!empty($options['brand_favicon'])): ?>
						<img src="<?php echo esc_attr($options['brand_favicon']); ?>" style="max-width:32px; max-height:32px; margin-top:10px; display:block;" />
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="og-wp-form-row">
			<label>Admin Menu & Bar Color</label>
			<br>
			<input type="text" name="og_wp_options[brand_admin_color]" class="og-wp-color-picker" value="<?php echo esc_attr($options['brand_admin_color'] ?? '#1d2327'); ?>" data-default-color="#1d2327" />
			<p class="description">Sets the primary background color for the left menu and top admin bar.</p>
		</div>

		<div class="og-wp-form-row">
			<label>Custom Footer Text</label>
			<input type="text" name="og_wp_options[brand_footer_text]" value="<?php echo esc_attr($options['brand_footer_text'] ?? 'Powered by OG of WP'); ?>" style="width:100%;" />
		</div>

		<div class="og-wp-form-row">
			<label>Hide WP Logo</label>
			<label class="og-wp-switch">
				<input type="checkbox" name="og_wp_options[brand_hide_wp_logo]" value="1" <?php checked( $options['brand_hide_wp_logo'] ?? '', '1' ); ?> />
				<span class="og-wp-slider"></span>
			</label>
			<p class="description">Removes the WordPress logo from the top-left admin bar.</p>
		</div>

		<div class="og-wp-form-row">
			<label>Hide "Howdy"</label>
			<label class="og-wp-switch">
				<input type="checkbox" name="og_wp_options[brand_hide_howdy]" value="1" <?php checked( $options['brand_hide_howdy'] ?? '', '1' ); ?> />
				<span class="og-wp-slider"></span>
			</label>
			<p class="description">Removes the "Howdy, " greeting from the top-right admin bar.</p>
		</div>

		<div class="og-wp-form-row">
			<label>Dashboard Widgets Cleaner</label>
			<label class="og-wp-switch">
				<input type="checkbox" name="og_wp_options[brand_clean_dashboard]" value="1" <?php checked( $options['brand_clean_dashboard'] ?? '', '1' ); ?> />
				<span class="og-wp-slider"></span>
			</label>
			<p class="description">Hides WordPress Events and News widget from the dashboard.</p>
		</div>
		
		<div style="margin-top:20px;">
			<button type="button" id="btn-reset-branding" class="button">Reset Branding to Defaults</button>
		</div>
		<?php
	}

	public function custom_login_styles() {
		$logo = !empty($this->options['brand_login_logo']) ? $this->options['brand_login_logo'] : '';
		$bg = !empty($this->options['brand_login_bg']) ? $this->options['brand_login_bg'] : '#f0f0f1';
		$card = !empty($this->options['brand_login_card_bg']) ? $this->options['brand_login_card_bg'] : '#ffffff';

		echo '<style type="text/css">';
		echo 'body.login { background-color: ' . esc_attr($bg) . ' !important; }';
		echo '#loginform { background-color: ' . esc_attr($card) . ' !important; }';
		
		if ( $logo ) {
			echo '.login h1 a { background-image: url(' . esc_url($logo) . ') !important; background-size: contain !important; width: 100% !important; }';
		}
		echo '</style>';
	}

	public function custom_login_logo_url() {
		return home_url();
	}

	public function custom_admin_footer() {
		if ( !empty($this->options['brand_footer_text']) ) {
			echo esc_html($this->options['brand_footer_text']);
		} else {
			echo 'Powered by <a href="https://astrake.com" target="_blank">Astrake</a>';
		}
	}

	public function custom_admin_bar() {
		global $wp_admin_bar;
		if ( !empty($this->options['brand_hide_wp_logo']) ) {
			$wp_admin_bar->remove_menu('wp-logo');
		}
		
		if ( !empty($this->options['brand_hide_howdy']) ) {
			$my_account = $wp_admin_bar->get_node('my-account');
			if ( $my_account ) {
				$new_title = str_replace( 'Howdy, ', '', $my_account->title );
				$wp_admin_bar->add_node( array(
					'id' => 'my-account',
					'title' => $new_title,
				) );
			}
		}
	}

	public function custom_admin_styles() {
		$color = !empty($this->options['brand_admin_color']) ? $this->options['brand_admin_color'] : '';
		if ( $color && $color !== '#1d2327' ) {
			echo '<style type="text/css">';
			echo '#adminmenu, #adminmenu .wp-submenu, #wpadminbar, #adminmenuback, #adminmenuwrap { background-color: ' . esc_attr($color) . ' !important; }';
			echo '</style>';
		}
	}

	public function custom_favicon() {
		if ( !empty($this->options['brand_favicon']) ) {
			echo '<link rel="shortcut icon" href="' . esc_url($this->options['brand_favicon']) . '" />';
		}
	}

	public function clean_dashboard_widgets() {
		if ( !empty($this->options['brand_clean_dashboard']) ) {
			remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
		}
	}
}
