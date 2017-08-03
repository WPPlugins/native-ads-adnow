<?php
class Adnow_Widget_Admin {
	private $plugin_name;
	public $token;
	public $message_error = '';
	public $json;
	public $widgets;
	private $option_name = 'Adnow_Widget';
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->token = false;
		$json = $this->json = '';
		$options_token = get_option( $this->option_name . '_key' );
		if(!empty($options_token)){
			set_error_handler(array($this, "warning_handler"), E_WARNING);
			$json = file_get_contents('http://wp_plug.adnow.com/wp_aadb.php?token='.$options_token.'&validate=1');
			restore_error_handler();
			$widgets_val = json_decode($json, true);
			if($widgets_val["validate"] === false){
				$this->message_error = 'You have entered an invalid token!';
			}
			$this->token = $options_token;
		}
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/adnow-widget-admin.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/adnow-widget-admin.js', array( 'jquery' ), $this->version, false );
	}

	public function add_options_page() {
		$this->plugin_screen_hook_suffix = add_menu_page(
			__( 'Edit place', 'adnow-widget' ),
			__( 'Adnow Plugin', 'adnow-widget' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_options_page')
		);
	}

	public function make_menu() {
	   add_submenu_page($this->plugin_name, 'Edit place', 'Edit place', 'manage_options', 'edit_place', array( $this, 'setting_adnow' ));
	}

	public function setting_adnow() {
	    include_once 'partials/adnow-widget-setting-display.php';
	}

	public function display_options_page() {
		include_once 'partials/adnow-widget-admin-display.php';
	}

	public function register_setting() {
		add_settings_section(
			$this->option_name . '_general',
			'',
			array( $this, $this->option_name . '_general_cb' ),
			$this->plugin_name
		);
		
		add_settings_section(
			$this->option_name . '_head_account_key',
			'',
			array(),
			$this->plugin_name
		);	

		add_settings_section(
			$this->option_name . '_impressions',
			'',
			array( $this, $this->option_name . '_impressions_cb' ),
			$this->plugin_name
		);

		add_settings_section(
			$this->option_name . '_turn',
			'',
			array( $this, $this->option_name . '_turn_cb' ),
			$this->plugin_name
		);

		register_setting( $this->plugin_name, $this->option_name . '_general');
		register_setting( $this->plugin_name, $this->option_name . '_key');
		register_setting( $this->plugin_name, $this->option_name . '_turn');
	}

	public function Adnow_Widget_general_cb() {
		if($this->token !== false){
			set_error_handler(array($this, "warning_handler"), E_WARNING);
			$this->json = file_get_contents('http://wp_plug.adnow.com/wp_aadb.php?token='.$this->token);
			restore_error_handler();
			$this->widgets = json_decode($this->json, true);
			$account_id = !empty($this->widgets['account']['id']) ? $this->widgets['account']['id'] : '';
			$account_email = !empty($this->widgets['account']['email']) ? $this->widgets['account']['email'] : '';
		} ?>
		 	<div class="account display_block">
	            <div class="title">Account</div>
	            <div class="text">
			 		<p><b>Token</b><input autocomplete="off" type="text" name="<?php echo esc_html($this->option_name)?>_key" id="<?php echo esc_html($this->option_name) ?>_key" value="<?php echo esc_html($this->token) ?>"><span class="message_error"><?php echo $this->message_error?></span></p>
				<?php if($this->token !== false and $this->message_error == '') : ?>
		            <p><b>ID</b> <span><?php echo esc_html($account_id) ?></span></p>
			 		<p><b>E-mail</b> <span><?php echo esc_html($account_email) ?></span></p>
				 	<p><a href="https://adnow.com/" class="site" target="_blank">adnow.com</a> <span><a href="https://adnow.com/" class="help">Help</a></span></p>
			 		<div class="submit_cover success"><a href="<?php echo admin_url(); ?>admin.php?page=edit_place" class="submit">Manage Places</a></div>
				<?php else: ?>
					<input class="checkbox" autocomplete="off" type="hidden" name="<?php echo esc_html($this->option_name) . '_turn' ?>" id="<?php echo esc_html($this->option_name) . '_turn' ?>" value="before"><br>
					
				<?php endif; ?>
				</div>
			</div>
			<?php
	}

	 public function Adnow_Widget_turn_cb(){
	 	$turn = get_option( $this->option_name . '_turn' ); ?>
	 	<?php if($this->token !== false and $this->message_error == '') : ?>
	 	<div class="display_block adblock">
            <div class="title">Antiadblock</div>
            <div class="text">
                <div class="checkbox_cover <?php echo !empty($turn) ? 'success' : ''?>">
                	<label>
                        <input class="checkbox" type="checkbox" name="<?php echo esc_html($this->option_name) . '_turn' ?>" id="<?php echo esc_html($this->option_name) . '_turn' ?>" value="before" <?php checked( $turn, 'before' ); ?>>
                        <span class="check"><i></i></span>
                        <span class="name">Activate Adblock</span>
                    </label>
                </div>
            </div>
        </div>
		<?php
		endif;
	 }

	public function Adnow_Widget_impressions_cb(){
	 	if($this->token !== false and $this->message_error == ''){
		 	$impressions = !empty($this->widgets['impressions']) ? $this->widgets['impressions'] : 0;
		 	$impressions = number_format($impressions, 0, '', ' '); 
	 	} ?>
	 	<?php if($this->token !== false  and $this->message_error == '') : ?>
	 	<div class="display_block stats">
            <div class="title">Antiadblock stats for today</div>
            <div class="text">
                <div class="adn_name">Impressions</div>
                <div class="value"><?php echo esc_html($impressions) ?></div>
            </div>
        </div>
		<?php
		endif;
	}

	public function warning_handler($errno, $errstr) { 
		$this->message_error = 'Problem retrieving data from the server!';
	}
}