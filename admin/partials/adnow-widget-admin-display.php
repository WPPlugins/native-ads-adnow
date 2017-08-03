<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://fsylum.net
 * @since      1.0.0
 *
 * @package    Adnow_Widget
 * @subpackage Adnow_Widget/admin/partials
 */
?>

<div class="wrap">
    <form action="options.php" method="post">
	    <div class="adnow-block account-block">
        <?php
            settings_fields( $this->plugin_name );
            do_settings_sections( $this->plugin_name );
        ?>
	    </div>
        <?php
            submit_button();
        ?>
    </form>
</div>