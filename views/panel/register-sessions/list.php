<?php
/**
 * List register sessions
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\POS\Views
 */

defined( 'YITH_POS' ) || exit();

if ( ! class_exists( 'YITH_POS_Register_Session_List_Table' ) ) {
	require_once YITH_POS_INCLUDES_PATH . 'admin/list-tables/class-yith-pos-register-session-list-table.php';
}
$list_table = new YITH_POS_Register_Session_List_Table();
?>
<?php
$list_table->prepare_items();
$list_table->views();
?>
<form method="post">
	<div id="yith-pos-register-session-list" class="yith-plugin-ui--classic-wp-list-style yith-plugin-ui__wp-list-auto-h-scroll">
		<?php $list_table->display(); ?>
	</div>
</form>
