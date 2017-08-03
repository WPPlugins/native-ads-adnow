<?php
class Adnow_Widget_Add_Area {
	public $page_plugin;
	public $widgets;
	public $aabd;
	public $select_widgets;
	public $page_type_all;
	public $page_area_all;
	public $request_uri;
	public $operation_all;
	private $option_name = 'Adnow_Widget';

	public function __construct(){
		global $wpdb;

		$this->page_plugin = $this->getPluginStatus();
		$this->widgets = array();
    	$this->select_widgets = array();
    	$this->operation_all = array('remove', 'close', 'preview', 'save');
    	$this->page_type_all = array('post', 'page', 'main', 'category', 'archive', 'search');
    	$this->page_area_all = array('wp_head','wp_footer','loop_start','loop_end','comment_form_before','comment_form_after','dynamic_sidebar_before','dynamic_sidebar_after','content_after','content_before','the_excerpt');
    	
		$request = $_SERVER["REQUEST_URI"];
    	if(!empty($request)){
	    	$this->request_uri = esc_url($request);
    	}else{
			$this->request_uri = '/';
    	}

    	$post_operation = !empty($_POST['operation']) ? sanitize_text_field($_POST['operation']) : false;

    	if(!empty($post_operation) and in_array($post_operation, $this->operation_all)){
    		if(!empty($_POST['widget_id']) and !empty($_POST['action_area']) and !empty($_POST['type_post'])){
    			$id_area_post = true;
    			$post_widget_id = intval($_POST['widget_id']);
    			if(!$post_widget_id){
    				$id_area_post = false;
    			}
				$post_action_area = sanitize_text_field($_POST['action_area']);
				if(!in_array($post_action_area, $this->page_area_all)){
    				$id_area_post = false;
				}
				$post_type_post =sanitize_text_field($_POST['type_post']); 
				if(!in_array($post_type_post, $this->page_type_all)){
    				$id_area_post = false;
				}
    		}else{
    			$id_area_post = false;
    		}
    	} else {
    		$post_operation = false;
    	}

		switch ($post_operation) {
			case 'remove':
	    		if(!empty($id_area_post)){
    				$this->remove_ad($post_action_area, $post_type_post, $post_widget_id);
	    		}
			break;

			case 'close':
	    		if(!empty($id_area_post)){
    				$this->remove_ad($post_action_area, $post_type_post, $post_widget_id, '-preview-adnow');
	    		}
			break;

			case 'preview':
    			if(!empty($id_area_post)){
		    		$id_widget = $this->add_widget_array($post_widget_id, $post_action_area, $post_type_post);
		    	}
			break;

			case 'save':
				$previews = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", '%-preview-adnow%'));
				foreach ($previews as $key => $double) {
					$updates = $wpdb->query( $wpdb->prepare("UPDATE $wpdb->options SET option_name = REPLACE (option_name, '-preview-adnow', '') WHERE option_name = %s", sanitize_text_field($double)));
				}
    			$plugin_status = $this->getPluginStatus();
    			if(!empty($plugin_status)){
		            $del = $wpdb->delete($wpdb->options, array( 'option_name' => 'edit_area'));
		        }
			break;
		}
	}

	private function getPluginStatus(){
		global $wpdb;
		$edit_area = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s", 'edit_area'));
		if(!empty($edit_area)){
			return true;
		}else{
			return false;
		}
	}

	private function addHeadPanel(){
		$token = get_option( $this->option_name . '_key' );
		$json = '';
		set_error_handler(array($this, "warning_handler"), E_WARNING);
		$json = file_get_contents('http://wp_plug.adnow.com/wp_aadb.php?token='.$token);
		restore_error_handler();
		if(!empty($json)){
			$widgets = json_decode($json, true);
			if(!empty($widgets['widget'])){
		        $this->widgets = $widgets['widget'];
		        $this->aabd = $widgets['aadb'];
		        if(is_array($this->widgets)){
		        	foreach ($this->widgets as $key => $value) {
		        		$this->select_widgets[$key] = $value['title'];
		        	}
		        }
			}else{
				$this->widgets = array();
			}
		} 
		
		$headpanel = '';
		if($this->is_user_role('administrator')  and $this->getPluginStatus() === true ){
			$headpanel .= '<div_adnblock class="header_fix_top head_panel">
				<div_adnblock class="container_top">
					<div_adnblock class="header-actions">
						<form id="form_save" method="post" action="'.$this->request_uri.'">
							<div_adnblock class="adn_title">Edit place</div_adnblock>
							<div_adnblock class="adn_actions">
								<div_adnblock class="adn_pages">
									<div_adnblock class="adn_name">Site Pages</div_adnblock>';	
									foreach ($this->page_type_all as $type) {
										$headpanel .= $this->get_home_page($type);
									}
			$headpanel .= '</div_adnblock>
							</div_adnblock>
							<input name="operation" type="hidden" value="save">
							<button_adnblock onclick="document.getElementById(\'form_save\').submit()" id="all_save" class="adn_save">Save</button_adnblock>
						</form>
					</div_adnblock>
				</div_adnblock>
			</div_adnblock>';
		}
		return $headpanel;
	}

	private function getRecheck($action_area){
		global $wpdb;
		$type_post = $this->getTypePage();
		$count_add_page = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s", $action_area.'_add_'.$type_post));

		if(!isset($count_add_page)){
			$inc = $wpdb->query( $wpdb->prepare( "INSERT INTO $wpdb->options ( option_name, option_value, autoload ) VALUES ( %s, %s, %s )", $action_area.'_add_'.$type_post, 'yes', 'no' ) );
		}
		return $count_add_page;
	}

	private function getCode($action_area, $size='big'){
		global $wpdb;
		$adnblock = '';
		$type_post = $this->getTypePage();
		$vision = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s", $action_area.'-'.$type_post));
		$vision_preview = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s", $action_area.'-'.$type_post.'-preview-adnow'));
		if(!empty($vision)){
			$vision_arr = esc_html($vision->option_value);
			if(!empty($this->widgets[$vision_arr])){

				$adnblock = '
				<adnblock class="top_index_block_adnow" id="'.$action_area.'">
					<form id="form_'.$action_area.'" method="post" action="'.$this->request_uri.'#'.$action_area.'">';
					if($this->is_user_role('administrator')  and $this->getPluginStatus() === true ){
						$adnblock .= '<input name="widget_id" type="hidden" value="'.$vision_arr.'">
						<input name="action_area" type="hidden" value="'.$action_area.'">
						<input name="type_post" type="hidden" value="'.$type_post.'">
						<input name="operation" type="hidden" value="remove">
							<button_adnblock onclick="document.getElementById(\'form_'.$action_area.'\').submit()" class="add_widget_plus_content">
								<span_adnblock class="remove_widget">Remove widgets</span_adnblock>
								<span_adnblock class="id_title_widget"><strong>'.esc_html($this->select_widgets[$vision_arr]).'</strong> (ID:'.$vision_arr.')</span_adnblock>
							</button_adnblock>
						';
					}
					$adnblock .= '
						<div class="prev" data-widget="'.$vision_arr.'">'.base64_decode($this->widgets[$vision_arr]['code']).'</div>
					</form>
				</adnblock>';
			}
		} elseif(!empty($vision_preview)){	
			$vision_arr = esc_html($vision_preview->option_value);
			if(!empty($this->widgets[$vision_arr])){
				if($this->is_user_role('administrator')  and $this->getPluginStatus() === true ){
				$adnblock = '
				<adnblock class="top_index_block_adnow" id="'.$action_area.'">
					<form id="form_'.$action_area.'" method="post" action="'.$this->request_uri.'#'.$action_area.'">';
						$adnblock .= '<input name="widget_id" type="hidden" value="'.$vision_arr.'">
						<input name="action_area" type="hidden" value="'.$action_area.'">
						<input name="type_post" type="hidden" value="'.$type_post.'">
						<input name="operation" type="hidden" value="close">
							<button_adnblock onclick="document.getElementById(\'form_'.$action_area.'\').submit()" class="add_widget_plus_content">
								<span_adnblock class="remove_widget close_prev">Close view widget</span_adnblock>
								<span_adnblock class="id_title_widget"><strong>'.esc_html($this->select_widgets[$vision_arr]).'</strong> (ID:'.$vision_arr.')</span_adnblock>
							</button_adnblock>
						';
					$adnblock .= '
						<div class="prev view_prev" data-widget="'.$vision_arr.'">'.base64_decode($this->widgets[$vision_arr]['code']).'</div>
					</form>
				</adnblock>';
				}
			}
		} else{	
			if($this->is_user_role('administrator')  and $this->getPluginStatus() === true ){
				$select_in = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s", 'obhod'));
		        
		        $ids_all = array();
		        $ids = !empty($select_in->option_value) ? explode(",", $select_in->option_value) : array();
		        $ids = array_diff($ids, array(''));

				$adnblock = '
				<adnblock class="top_index_block_adnow"  id="'.$action_area.'">
					<form id="form_'.$action_area.'"  method="post" action="'.$this->request_uri.'#'.$action_area.'">
						<div_adnblock class="adnow_widget_block adn_'.$size.'">
							<div_adnblock class="adn_name">Place widgets here</div_adnblock>
							<div_adnblock class="adn_form">
								<select name="widget_id" onfocus="this.parentNode.parentNode.classList.add(\'focused\');" onblur="this.parentNode.parentNode.classList.remove(\'focused\');"><option></option>';
									foreach ($this->select_widgets as $key => $value) {
										if(!in_array($type_post.'-'.$key, $ids)){
											$adnblock .= '<option value="'.$key.'">'.$value.'</option>';
										}
									}
									$adnblock .= ' 
								</select>
								<input name="action_area" type="hidden" value="'.$action_area.'">
								<input name="type_post" type="hidden" value="'.$type_post.'">
								<input name="operation" type="hidden" value="preview">
								<button_adnblock onclick="document.getElementById(\'form_'.$action_area.'\').submit()" class="adn_submit add_widget_plus_content">Preview</button_adnblock>
							</div_adnblock>
						</div_adnblock>
					</form>
				</adnblock>';
			}
		}
		return $adnblock;
	}

	private function getTypePage(){
		if(is_front_page()){
			$type_post = 'main';
		} elseif(is_search()){
			$type_post = 'search';
		} elseif(is_page()){
			$type_post = 'page';
		} elseif(is_single()){
			$type_post = 'post';
		} elseif(is_category()){
			$type_post = 'category';
		} elseif(is_archive()){
			$type_post = 'archive';
		} else{
			$type_post = 'other';
		}
		 return $type_post;
	}	

    private function get_home_page($param){
        global $wpdb;
        $type_post_active = $this->getTypePage();
        $type_page = $param;
        $adv_active = $param == $type_post_active ? 'adn_active' : '';

        $home_page = home_url();
        if(!empty($param)){
            switch ($param) {
                case 'page':
                    $post_guid = $wpdb->get_col($wpdb->prepare("SELECT id FROM $wpdb->posts WHERE post_status = %s AND post_type = %s ORDER BY id DESC LIMIT 1", 'publish', 'page')); 
                    $home_page = !empty($post_guid[0]) ? get_site_url().'/?p='.$post_guid[0] : get_site_url().'/'; 
                break;

                case 'post':
                    $post_guid = $wpdb->get_col($wpdb->prepare("SELECT id FROM $wpdb->posts WHERE post_status = %s AND post_type = %s ORDER BY id DESC  LIMIT 1", 'publish', 'post')); 
                    $home_page = !empty($post_guid[0]) ? get_site_url().'/?p='.$post_guid[0] : get_site_url().'/'; 
                break;

                case 'attachment':
                    $post_guid = $wpdb->get_col($wpdb->prepare("SELECT id FROM $wpdb->posts WHERE post_status = %s AND post_type = %s ORDER BY id DESC  LIMIT 1", 'publish', 'attachment')); 
                    $home_page = !empty($post_guid[0]) ? get_site_url().'/?p='.$post_guid[0] : get_site_url().'/'; 
                break;

                case 'category':
	                $categories = get_the_category();
					if ( ! empty( $categories ) ) {
						$home_page = esc_url(get_category_link($categories[0]->term_id));
					}
                break;

                case 'archive':
					$string = wp_get_archives('type=monthly&limit=1&echo=0&format=html');
					$regexp = "<a\s[^>]*(?:href=[\'\"])(\"??)([^\"\' >]*?)\\1[^>]*>(.*)<\/a>";
					if(preg_match_all("/$regexp/siU", $string, $matches, PREG_SET_ORDER)) {
					    $home_page =  $matches[0][2];
					}
                break;

                case 'search':
					$home_page = home_url().'/?s=+';
                break;
            }
        }
        
        global $cache_page_secret;
		if(!empty($cache_page_secret)){
		    $home_page = add_query_arg( 'donotcachepage', $cache_page_secret,  $home_page );
		}

        return '<a class="adn_button '.$adv_active.'" href="'.esc_url($home_page).'">'.ucfirst($type_page).'</a>';
    }

    private function add_widget_array($id_widget, $action_area, $type_post){
        global $wpdb;
        $backup = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM $wpdb->options WHERE option_name = %s", $action_area.'-'.$type_post.'-preview-adnow'));
        if(count($backup)==0){
            $inc = $wpdb->query( $wpdb->prepare( "INSERT INTO $wpdb->options ( option_name, option_value, autoload ) VALUES ( %s, %s, %s )", $action_area.'-'.$type_post.'-preview-adnow', $id_widget, 'no' ) );
            
            $this->obhod($type_post.'-'.$id_widget, 'add');
        }
        return $inc;

    }

    private function obhod($id_widget, $action){
        global $wpdb;
        $obhod = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM $wpdb->options WHERE option_name = %s", 'obhod'));
        if(count($obhod)==0){
            $add_ob = $wpdb->query($wpdb->prepare("INSERT INTO $wpdb->options ( option_name, option_value, autoload ) VALUES ( %s, %s, %s )", 'obhod', '', 'no'));
        }
        switch ($action) {
            case 'add':
            $wpdb->query($wpdb->prepare("UPDATE $wpdb->options SET option_value = CONCAT(option_value, %s) WHERE option_name='obhod'", $id_widget.','));
            break;

            case 'remove':
            $wpdb->query($wpdb->prepare("UPDATE $wpdb->options SET option_value = REPLACE(option_value, %s, '')  WHERE option_name='obhod'", $id_widget.','));
            break;
        }
    }

    private function remove_ad($action_area, $type_post, $id_widget, $preview=''){
        global $wpdb;

        $nal = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM $wpdb->options WHERE option_name = %s", $action_area.'-'.$type_post.$preview));

        if(!empty($nal)){
            $del = $wpdb->delete($wpdb->options, array( 'option_name' => $action_area.'-'.$type_post.$preview));
            $this->obhod($type_post.'-'.$id_widget, 'remove');
        }
    }


	public function wp_head_area() {
		echo $this->addHeadPanel();
	}

	public function wp_footer_area() {
		echo $this->getCode('wp_footer');
	}

	public function loop_start_area() {
		$recheck = $this->getRecheck('loop_start');
		if(!isset($recheck)){
			echo $this->getCode('loop_start');
		}
	}

	public function loop_end_area() {
		$recheck = $this->getRecheck('loop_end');
		if(!isset($recheck)){
			echo $this->getCode('loop_end');
		}
	}

	public function comment_form_before_area() {
		echo $this->getCode('comment_form_before');
	}
	
	public function comment_form_after_area() {
		echo $this->getCode('comment_form_after');
	}
		
	public function dynamic_sidebar_before_area() {
		$recheck = $this->getRecheck('dynamic_sidebar_before');
		if(!isset($recheck)){
			echo $this->getCode('dynamic_sidebar_before', 'small');
		}
	}	

	public function dynamic_sidebar_after_area() {
		$recheck = $this->getRecheck('dynamic_sidebar_after');
		if(!isset($recheck)){
			echo $this->getCode('dynamic_sidebar_after', 'small');
		}
	}
	
	public function content_after_area($content) {
		$recheck = $this->getRecheck('content_after');
		if(!isset($recheck)){
			$adnblock = $this->getCode('content_after');
			$content = $content.$adnblock;
		}
		return $content;
	}

	public function content_before_area($content) {
		$recheck = $this->getRecheck('content_before');
		if(!isset($recheck)){
			$adnblock = $this->getCode('content_before');
			$content = $adnblock.$content;
		}
		return $content;
	}

	public function excerpt_after_area($content) {
		$recheck = $this->getRecheck('the_excerpt');
		if(!isset($recheck)){
			$adnblock = $this->getCode('the_excerpt');
			$content = $content.$adnblock;
		}
		return $content;
	}

	public function get_the_archive_title_area($content) {
		$adnblock = $this->getCode('get_the_archive_title');
		$content = $content.$adnblock;
		return $content;
	}

	public function is_user_role( $role, $user_id = null ) {
		$user = is_numeric( $user_id ) ? get_userdata( $user_id ) : wp_get_current_user();

		if( ! $user )
			return false;

		return in_array( $role, (array) $user->roles );
	}

	public function empty_povt() {
		global $wpdb;

		foreach ($this->page_area_all as $key => $row) {
			foreach ($this->page_type_all as $add) {
				$wpdb->delete($wpdb->options, array( 'option_name' => $row.'_add_'.$add));
			}
		}
	}

	public function add_obhod() {
        $options_turn = get_option( $this->option_name . '_turn' );
        if(!empty($options_turn)){
        	if(!empty($this->aabd)){
				echo base64_decode($this->aabd);
        	}
        }
	}

	public function modify_admin_bar( $wp_admin_bar ) {
		$token = get_option( $this->option_name . '_key' );
		$json = '';
		set_error_handler(array($this, "warning_handler"), E_WARNING);
		$json = file_get_contents('http://wp_plug.adnow.com/wp_aadb.php?token='.$token.'&validate=1');
		restore_error_handler();
		$widgets = json_decode($json, true);
		if($widgets["validate"] !== false and $this->getPluginStatus() !== true){
			$args = array(
				'id'    => 'edit_place',
				'title' => 'Edit place Adnow',
				'href'  => admin_url().'admin.php?page=edit_place&url='.$this->request_uri,
				'meta'  => array( 'class' => 'my-toolbar-page' )
			);
		}else{
			$args = array(
				'id'    => 'adnow_widget',
				'title' => 'Adnow plugin',
				'href'  => admin_url().'admin.php?page=adnow-widget',
				'meta'  => array( 'class' => 'my-toolbar-page' )
			);
		}
		$wp_admin_bar->add_node( $args );
	}

	public function warning_handler($errno, $errstr) { 
		return false;
	}
}
