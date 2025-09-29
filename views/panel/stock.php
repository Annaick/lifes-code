<?php
/**
 * Stock management view (read-only, initial version)
 *
 * @var array $args
 */

defined( 'YITH_POS' ) || exit;

// Pagination params.
$paged     = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$per_page  = 20;
$offset    = ( $paged - 1 ) * $per_page;

// Query products including variations count separately; we display parent products and expand variations inline.
$query_args = array(
	'post_type'      => array( 'product' ),
	'post_status'    => array( 'publish', 'private' ),
	'posts_per_page' => $per_page,
	'offset'         => $offset,
	'fields'         => 'ids',
);

$product_ids = get_posts( $query_args ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query

// Total for basic pagination.
$total = (int) wp_count_posts( 'product' )->publish + (int) wp_count_posts( 'product' )->private;
$total_pages = max( 1, (int) ceil( $total / $per_page ) );

// Helpers.
function yith_pos_render_stock_row( $product ) {
	/* @var WC_Product $product */
	$price_html   = $product->get_price_html();
	$manage_stock = $product->managing_stock();
	$stock_qty    = $manage_stock ? wc_stock_amount( $product->get_stock_quantity() ) : __( '—', 'yith-point-of-sale-for-woocommerce' );
$type_label   = ucfirst( $product->get_type() );

	$product_name = $product->get_name();
	$product_id   = $product->get_id();

	$has_children = $product->is_type( 'variable' );
	$toggle_attr  = $has_children ? ' data-has-children="1"' : '';
	$toggle_btn   = $has_children ? '<button type="button" class="button-link yith-pos-toggle-children" aria-expanded="false" aria-controls="variations-' . esc_attr( $product_id ) . '">+</button>' : '';

	echo '<tr class="yith-pos-stock-row"' . $toggle_attr . ' data-product-id="' . esc_attr( $product_id ) . '">';
	echo '<td class="column-toggle">' . $toggle_btn . '</td>';
	echo '<td class="column-id">' . esc_html( $product_id ) . '</td>';
	echo '<td class="column-name">' . esc_html( $product_name ) . '</td>';
	echo '<td class="column-type">' . esc_html( $type_label ) . '</td>';
	echo '<td class="column-stock">' . esc_html( $stock_qty ) . '</td>';
	echo '<td class="column-price">' . wp_kses_post( $price_html ) . '</td>';
	echo '</tr>';

	if ( $has_children ) {
		$children = $product->get_children();
		if ( $children ) {
			echo '<tr id="variations-' . esc_attr( $product_id ) . '" class="yith-pos-variation-container" style="display:none">';
			echo '<td></td><td colspan="4">';
			echo '<table class="widefat fixed striped yith-pos-variations-table"><thead><tr><th class="column-id">' . esc_html__( 'Variation ID', 'yith-point-of-sale-for-woocommerce' ) . '</th><th class="column-name">' . esc_html__( 'Attributes', 'yith-point-of-sale-for-woocommerce' ) . '</th><th class="column-stock">' . esc_html__( 'Stock', 'yith-point-of-sale-for-woocommerce' ) . '</th><th class="column-price">' . esc_html__( 'Price', 'yith-point-of-sale-for-woocommerce' ) . '</th></tr></thead><tbody>';
			foreach ( $children as $child_id ) {
				$variation = wc_get_product( $child_id );
				if ( ! $variation ) {
					continue;
				}
				$attrs        = wc_get_formatted_variation( $variation, true, false, true );
				$price_html_v = $variation->get_price_html();
				$manage_v     = $variation->managing_stock();
				$stock_v      = $manage_v ? wc_stock_amount( $variation->get_stock_quantity() ) : __( '—', 'yith-point-of-sale-for-woocommerce' );
				echo '<tr>';
				echo '<td class="column-id">' . esc_html( $variation->get_id() ) . '</td>';
				echo '<td class="column-name">' . wp_kses_post( $attrs ) . '</td>';
				echo '<td class="column-stock">' . esc_html( $stock_v ) . '</td>';
				echo '<td class="column-price">' . wp_kses_post( $price_html_v ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
			echo '</td></tr>';
		}
	}
}

?>
<div class="wrap yith-pos-stock-wrap">
	<h2><?php echo esc_html__( 'Stock', 'yith-point-of-sale-for-woocommerce' ); ?></h2>
	<p class="description"><?php echo esc_html__( 'Listing of products and variations with their stock and price. Initial read-only version.', 'yith-point-of-sale-for-woocommerce' ); ?></p>
	<table class="widefat fixed striped yith-pos-stock-table">
		<thead>
			<tr>
				<th class="column-toggle" style="width:40px"></th>
				<th class="column-id" style="width:100px"><?php echo esc_html__( 'ID', 'yith-point-of-sale-for-woocommerce' ); ?></th>
				<th class="column-name"><?php echo esc_html__( 'Name', 'yith-point-of-sale-for-woocommerce' ); ?></th>
				<th class="column-type" style="width:120px"><?php echo esc_html__( 'Type', 'yith-point-of-sale-for-woocommerce' ); ?></th>
				<th class="column-stock" style="width:120px"><?php echo esc_html__( 'Stock', 'yith-point-of-sale-for-woocommerce' ); ?></th>
				<th class="column-price" style="width:160px"><?php echo esc_html__( 'Price', 'yith-point-of-sale-for-woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $product_ids as $pid ) {
				$product = wc_get_product( $pid );
				if ( ! $product ) {
					continue;
				}
				yith_pos_render_stock_row( $product );
			}
			?>
		</tbody>
	</table>
	<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav">
			<div class="tablenav-pages">
				<?php
				$base_url = remove_query_arg( 'paged' );
				for ( $i = 1; $i <= $total_pages; $i++ ) {
					$url   = esc_url( add_query_arg( 'paged', $i, $base_url ) );
					$class = $i === $paged ? ' class="page-numbers current"' : ' class="page-numbers"';
					echo '<a' . $class . ' href="' . $url . '">' . esc_html( $i ) . '</a> ';
				}
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
<script>
(function(){
	document.addEventListener('click', function(e){
		var btn = e.target.closest('.yith-pos-toggle-children');
		if(!btn){return;}
		var row = btn.closest('tr');
		var productId = row && row.getAttribute('data-product-id');
		if(!productId){return;}
		var container = document.getElementById('variations-' + productId);
		if(!container){return;}
		var expanded = btn.getAttribute('aria-expanded') === 'true';
		btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
		btn.textContent = expanded ? '+' : '−';
		container.style.display = expanded ? 'none' : '';
	});
})();
</script>


