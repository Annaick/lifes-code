<?php
/**
 * Licence Banner template
 *
 * @var string $mode
 * @var string $slug
 * @var string $plugin_name
 * @var string $activation_url
 * @var string $landing_url
 * @package YITH/PluginUpgrade
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

$classes = implode( ' ', array( 'yith-plugin-upgrade-licence-banner', "yith-plugin-upgrade-licence-banner--$mode", 'yith-plugin-ui' ) );
?>
	<div></div>
<?php

