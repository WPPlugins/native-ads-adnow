<?php

class Adnow_Widget_Area {
    public function __construct(){
        global $wpdb;
        $edit_area = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s", 'edit_area'));
        if(!isset($edit_area)){
            $inc = $wpdb->query( $wpdb->prepare( "INSERT INTO $wpdb->options ( option_name, option_value, autoload ) VALUES ( %s, %s, %s )", 'edit_area', 'yes', 'no' ) );
        }
    }
}

$token = get_option( $this->option_name . '_key' );
if(!empty($token)){
    $json = '';
    set_error_handler("warning_handler", E_WARNING);
	$json = file_get_contents('http://wp_plug.adnow.com/wp_aadb.php?token='.$token.'&validate=1');
    restore_error_handler();
	$widgets = json_decode($json, true);
} else{
	$widgets["validate"] = false;
}

function warning_handler($errno, $errstr) { 
    return false;
}

if($widgets["validate"] !== false){
	$edit_area = new Adnow_Widget_Area; 
	$url = !empty($_GET['url']) ? sanitize_text_field($_GET['url']) : home_url();
}else{
	$url = admin_url()."admin.php?page=adnow-widget";
}

global $cache_page_secret;
if(!empty($cache_page_secret)){
    $url = add_query_arg( 'donotcachepage', $cache_page_secret,  $url );
}

$src = '<scr'; $src .= 'ipt>document.location.href="'.esc_html($url).'"</'; $src .= 'scr'; $src .= 'ipt>';
echo $src;
exit;