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
	$toggle_btn   = $has_children ? '<button type="button" class="button-link yith-pos-toggle-children" aria-expanded="false" aria-controls="variations-' . esc_attr( $product_id ) . '" title="Toggle variations">+</button>' : '';
	// Stock breakdown toggle for product row.
	$stock_toggle_btn = '<button type="button" class="button-link yith-pos-toggle-stock" aria-expanded="false" aria-controls="stock-' . esc_attr( $product_id ) . '" title="Toggle stock breakdown">⛃</button>';

	echo '<tr class="yith-pos-stock-row"' . $toggle_attr . ' data-product-id="' . esc_attr( $product_id ) . '">';
	echo '<td class="column-toggle">' . $stock_toggle_btn . ' ' . $toggle_btn . '</td>';
	echo '<td class="column-id">' . esc_html( $product_id ) . '</td>';
	echo '<td class="column-name">' . esc_html( $product_name ) . '</td>';
	echo '<td class="column-type">' . esc_html( $type_label ) . '</td>';
	echo '<td class="column-stock">' . esc_html( $stock_qty ) . '</td>';
	echo '<td class="column-price">' . wp_kses_post( $price_html ) . '</td>';
	echo '</tr>';

    // Per-store stock breakdown for non-variable products only (hidden by default).
    if ( ! $has_children ) {
        $breakdown_html = yith_pos_get_stock_breakdown_html( $product );
        if ( $breakdown_html ) {
            echo '<tr id="stock-' . esc_attr( $product_id ) . '" class="yith-pos-stock-breakdown level-1" style="display:none">';
            echo '<td></td><td colspan="5">' . $breakdown_html . '</td>';
            echo '</tr>';
        }
    }

	if ( $has_children ) {
		$children = $product->get_children();
		if ( $children ) {
			echo '<tr id="variations-' . esc_attr( $product_id ) . '" class="yith-pos-variation-container" style="display:none">';
            echo '<td></td><td colspan="4">';
            echo '<table class="widefat fixed striped yith-pos-variations-table"><thead><tr><th class="column-toggle" style="width:40px"></th><th class="column-id">' . esc_html__( 'Variation ID', 'yith-point-of-sale-for-woocommerce' ) . '</th><th class="column-name">' . esc_html__( 'Name', 'yith-point-of-sale-for-woocommerce' ) . '</th><th class="column-stock">' . esc_html__( 'Stock', 'yith-point-of-sale-for-woocommerce' ) . '</th><th class="column-price">' . esc_html__( 'Price', 'yith-point-of-sale-for-woocommerce' ) . '</th></tr></thead><tbody>';
            foreach ( $children as $child_id ) {
				$variation = wc_get_product( $child_id );
				if ( ! $variation ) {
					continue;
				}
                $var_name     = $variation->get_name();
				$price_html_v = $variation->get_price_html();
				$manage_v     = $variation->managing_stock();
				$stock_v      = $manage_v ? wc_stock_amount( $variation->get_stock_quantity() ) : __( '—', 'yith-point-of-sale-for-woocommerce' );
                echo '<tr class="yith-pos-variation-row">';
                echo '<td class="column-toggle"><button type="button" class="button-link yith-pos-toggle-stock" aria-expanded="false" aria-controls="var-stock-' . esc_attr( $variation->get_id() ) . '" title="Toggle stock breakdown">⛃</button></td>';
				echo '<td class="column-id">' . esc_html( $variation->get_id() ) . '</td>';
                echo '<td class="column-name">' . esc_html( $var_name ) . '</td>';
				echo '<td class="column-stock">' . esc_html( $stock_v ) . '</td>';
				echo '<td class="column-price">' . wp_kses_post( $price_html_v ) . '</td>';
				echo '</tr>';

				// Variation per-store stock breakdown row.
                $variation_breakdown_html = yith_pos_get_stock_breakdown_html( $variation );
                if ( $variation_breakdown_html ) {
                    echo '<tr id="var-stock-' . esc_attr( $variation->get_id() ) . '" class="yith-pos-variation-stock-breakdown level-2" style="display:none"><td></td><td colspan="4">' . $variation_breakdown_html . '</td></tr>';
                }
			}
			echo '</tbody></table>';
			echo '</td></tr>';
		}
	}
}

/**
 * Build HTML for per-store stock breakdown for a product or variation.
 *
 * @param WC_Product $product The product.
 *
 * @return string
 */
function yith_pos_get_stock_breakdown_html( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

    $rows = array();

	// Principale (general) stock.
	$general_stock = $product->managing_stock() ? wc_stock_amount( $product->get_stock_quantity() ) : '—';
	$rows[]        = array(
		'label' => __( 'Principale', 'yith-point-of-sale-for-woocommerce' ),
		'qty'   => $general_stock,
	);

	// Multistock per store if enabled and present.
	$multi_enabled = 'yes' === $product->get_meta( '_yith_pos_multistock_enabled', true );
	$multi_stock   = $product->get_meta( '_yith_pos_multistock' );
	$multi_stock   = ! ! $multi_stock ? $multi_stock : array();

    if ( $multi_enabled && ! empty( $multi_stock ) ) {
        foreach ( $multi_stock as $store_id => $qty ) {
            $store      = yith_pos_get_store( intval( $store_id ) );
            $store_name = $store instanceof YITH_POS_Store ? $store->get_name() : sprintf( __( 'Store #%d', 'yith-point-of-sale-for-woocommerce' ), intval( $store_id ) );
            $rows[]     = array(
                'label'    => $store_name,
                'qty'      => wc_stock_amount( $qty ),
                'store_id' => intval( $store_id ),
            );
        }
    }

	if ( empty( $rows ) ) {
		return '';
	}

	ob_start();
    echo '<table class="widefat fixed striped yith-pos-stock-breakdown-table">';
    echo '<thead><tr><th style="width:40%">' . esc_html__( 'Location', 'yith-point-of-sale-for-woocommerce' ) . '</th><th style="width:30%">' . esc_html__( 'Stock', 'yith-point-of-sale-for-woocommerce' ) . '</th><th style="width:30%">' . esc_html__( 'Actions', 'yith-point-of-sale-for-woocommerce' ) . '</th></tr></thead>';
    echo '<tbody>';
    $product_id = $product->get_id();
    $nonce      = wp_create_nonce( 'yith_pos_stock_nonce' );
    foreach ( $rows as $r ) {
        $is_general = ( $r['label'] === __( 'Principale', 'yith-point-of-sale-for-woocommerce' ) );
        $store_id   = $is_general ? 'general' : (string) ( $r['store_id'] ?? '' );
        echo '<tr>';
        echo '<td>' . esc_html( $r['label'] ) . '</td>';
        echo '<td><input type="number" step="1" min="0" class="small-text yith-pos-stock-input" data-product-id="' . esc_attr( $product_id ) . '" data-store-id="' . esc_attr( $store_id ) . '" value="' . esc_attr( is_numeric( $r['qty'] ) ? $r['qty'] : 0 ) . '" /> ' . ( is_numeric( $r['qty'] ) ? '' : esc_html__( '(not managed)', 'yith-point-of-sale-for-woocommerce' ) ) . '</td>';
        echo '<td><button type="button" class="button yith-pos-save-stock" data-nonce="' . esc_attr( $nonce ) . '" data-product-id="' . esc_attr( $product_id ) . '" data-store-id="' . esc_attr( $store_id ) . '">' . esc_html__( 'Save', 'yith-point-of-sale-for-woocommerce' ) . '</button></td>';
        echo '</tr>';
    }

    // Add new stock for non-set stores.
    $all_stores = yith_pos_get_stores( array( 'fields' => 'stores' ) );
    $all_store_ids = array();
    $non_set = array();
    if ( is_array( $all_stores ) ) {
        foreach ( $all_stores as $store_obj ) {
            if ( $store_obj instanceof YITH_POS_Store ) {
                $sid = (int) $store_obj->get_id();
                $all_store_ids[] = $sid;
                if ( empty( $multi_stock ) || ! isset( $multi_stock[ $sid ] ) ) {
                    $non_set[ $sid ] = $store_obj->get_name();
                }
            }
        }
    }

    if ( ! empty( $non_set ) ) {
        echo '<tr class="yith-pos-add-stock-row">';
        echo '<td colspan="3">';
        echo '<div class="yith-pos-add-stock-bar">';
        echo '<button type="button" class="button yith-pos-add-stock-trigger">' . esc_html__( 'Ajouter un nouveau stock', 'yith-point-of-sale-for-woocommerce' ) . '</button>';
        echo '<span class="yith-pos-add-stock-form" style="display:none;margin-left:8px;">';
        echo '<select class="yith-pos-add-stock-select">';
        foreach ( $non_set as $ns_id => $ns_name ) {
            echo '<option value="' . esc_attr( $ns_id ) . '">' . esc_html( $ns_name ) . '</option>';
        }
        echo '</select> ';
        echo '<button type="button" class="button button-primary yith-pos-add-stock-confirm" data-product-id="' . esc_attr( $product_id ) . '" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Ajouter', 'yith-point-of-sale-for-woocommerce' ) . '</button>';
        echo '</span>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';

	return (string) ob_get_clean();
}
?>
<div class="wrap yith-pos-stock-wrap">
	<?php
	// Build export URL with nonce.
	$export_nonce = wp_create_nonce( 'yith_pos_stock_export' );
	$export_url   = add_query_arg(
		array(
			'action'   => 'yith_pos_export_stock',
			'_wpnonce' => $export_nonce,
		),
		admin_url( 'admin-post.php' )
	);
	?>
	<div class="yith-pos-stock-toolbar" style="float:right; display:flex; gap:8px; align-items:center;">
		<a href="<?php echo esc_url( $export_url ); ?>" class="button button-primary"><?php echo esc_html__( 'Export CSV', 'yith-point-of-sale-for-woocommerce' ); ?></a>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php?action=yith_pos_import_stock' ) ); ?>">
			<?php wp_nonce_field( 'yith_pos_stock_import' ); ?>
			<input type="file" name="yith_pos_stock_file" accept=".csv" required />
			<button type="submit" class="button"><?php echo esc_html__( 'Import CSV', 'yith-point-of-sale-for-woocommerce' ); ?></button>
		</form>
	</div>
	<h2><?php echo esc_html__( 'Stock', 'yith-point-of-sale-for-woocommerce' ); ?></h2>
	<?php if ( isset( $_GET['yith_pos_import_updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p>
			<?php printf( esc_html__( 'Stock updated for %d rows.', 'yith-point-of-sale-for-woocommerce' ), absint( $_GET['yith_pos_import_updated'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		</p></div>
	<?php endif; ?>
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
<style>
.yith-pos-stock-toolbar form{ display:inline-flex; gap:6px; align-items:center; }
.yith-pos-stock-table .button-link { text-decoration: none; font-size: 14px; }
.yith-pos-stock-table .column-toggle { white-space: nowrap; }

/* Default: all rows white */
.yith-pos-stock-row td,
.yith-pos-variation-row td,
.yith-pos-stock-breakdown td,
.yith-pos-variation-stock-breakdown td { background: #ffffff; }

/* When expanded, tint the main row and its subcontent */
.is-expanded > td { background: #f3f4f6; }
.is-expanded + .yith-pos-stock-breakdown.level-1 td { background: #f3f4f6; }
.yith-pos-stock-row.is-expanded + .yith-pos-variation-container td { background: #f3f4f6; }
.yith-pos-variation-row.is-expanded + .yith-pos-variation-stock-breakdown.level-2 td { background: #f3f4f6; }

/* Save feedback */
.yith-pos-save-feedback { margin-left: 8px; color: #1a7f37; opacity: 0; transition: opacity .2s ease-in-out; font-weight: 600; }
.yith-pos-save-feedback.show { opacity: 1; }

/* Add stock CTA row */
.yith-pos-add-stock-row td { background: #e6f4ea; }
.yith-pos-add-stock-bar .yith-pos-add-stock-trigger { background: #1a7f37; border-color: #146c2e; color: #fff; }
.yith-pos-add-stock-bar .yith-pos-add-stock-trigger:hover { background: #146c2e; }
</style>
<script>
(function(){
    document.addEventListener('click', function(e){
        // Toggle variations group under a product row
        var btnVar = e.target.closest('.yith-pos-toggle-children');
        if(btnVar){
            var row = btnVar.closest('tr');
            var productId = row && row.getAttribute('data-product-id');
            if(!productId){return;}
            var container = document.getElementById('variations-' + productId);
            if(!container){return;}
            var expanded = btnVar.getAttribute('aria-expanded') === 'true';
            btnVar.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            btnVar.textContent = expanded ? '+' : '−';
            container.style.display = expanded ? 'none' : '';
            // Tint the parent product row when its variations are shown
            if(row){
                if(expanded){ row.classList.remove('is-expanded'); }
                else { row.classList.add('is-expanded'); }
            }
            return;
        }

        // Toggle stock breakdown for product or variation
        var btnStock = e.target.closest('.yith-pos-toggle-stock');
        if(btnStock){
            var targetId = btnStock.getAttribute('aria-controls');
            if(!targetId){return;}
            var target = document.getElementById(targetId);
            if(!target){return;}
            var expanded = btnStock.getAttribute('aria-expanded') === 'true';
            btnStock.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            target.style.display = expanded ? 'none' : '';

            // Add/remove expanded class on the owning row for background tint
            var ownerRow = btnStock.closest('tr');
            if(ownerRow){
                if(expanded){ ownerRow.classList.remove('is-expanded'); }
                else { ownerRow.classList.add('is-expanded'); }
            }
            return;
        }
    });

    // Add stock CTA toggling and action
    document.addEventListener('click', function(e){
        var trigger = e.target.closest('.yith-pos-add-stock-trigger');
        if(trigger){
            var form = trigger.parentElement.querySelector('.yith-pos-add-stock-form');
            if(form){ form.style.display = form.style.display === 'none' ? '' : 'none'; }
            return;
        }
        var addBtn = e.target.closest('.yith-pos-add-stock-confirm');
        if(addBtn){
            var breakdownTable = addBtn.closest('.yith-pos-stock-breakdown-table');
            var productId = addBtn.getAttribute('data-product-id');
            var nonce = addBtn.getAttribute('data-nonce');
            var select = addBtn.parentElement.querySelector('.yith-pos-add-stock-select');
            var storeId = select ? select.value : '';
            if(!productId || !storeId){ return; }

            var form = new FormData();
            form.append('action', 'yith_pos_add_store_stock');
            form.append('nonce', nonce);
            form.append('product_id', productId);
            form.append('store_id', storeId);

            addBtn.disabled = true;
            fetch(ajaxurl, { method: 'POST', body: form })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    addBtn.disabled = false;
                    if(!data || !data.success){
                        alert((data && data.data && data.data.message) ? data.data.message : 'Error');
                        return;
                    }
                    // Inject new row above add-stock row
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + select.options[select.selectedIndex].text + '</td>' +
                        '<td><input type="number" step="1" min="0" class="small-text yith-pos-stock-input" data-product-id="' + productId + '" data-store-id="' + storeId + '" value="0" /></td>' +
                        '<td><button type="button" class="button yith-pos-save-stock" data-nonce="' + nonce + '" data-product-id="' + productId + '" data-store-id="' + storeId + '">Enregistrer</button></td>';
                    var addRow = breakdownTable.querySelector('tbody .yith-pos-add-stock-row');
                    if(addRow){ addRow.parentNode.insertBefore(tr, addRow); }

                    // Remove selected option from the dropdown; hide CTA if none left
                    select.remove(select.selectedIndex);
                    if(select.options.length === 0){
                        var wrapper = addRow;
                        if(wrapper){ wrapper.remove(); }
                    }
                })
                .catch(function(){ addBtn.disabled = false; alert('Error'); });
        }
    });
    // AJAX: Save stock
    document.addEventListener('click', function(e){
        var btn = e.target.closest('.yith-pos-save-stock');
        if(!btn){return;}
        var productId = btn.getAttribute('data-product-id');
        var storeId = btn.getAttribute('data-store-id');
        var nonce = btn.getAttribute('data-nonce');
        var row = btn.closest('tr');
        var input = row && row.querySelector('.yith-pos-stock-input');
        var qty = input ? input.value : '';
        if(!productId || !storeId){return;}

        var form = new FormData();
        form.append('action', 'yith_pos_update_stock');
        form.append('nonce', nonce);
        form.append('product_id', productId);
        form.append('store_id', storeId);
        form.append('qty', qty);

        btn.disabled = true;
        fetch(ajaxurl, { method: 'POST', body: form })
            .then(function(r){ return r.json(); })
            .then(function(data){
                btn.disabled = false;
                if(!data || !data.success){
                    alert((data && data.data && data.data.message) ? data.data.message : 'Error');
                    return;
                }
                // Show success feedback in French as requested
                var feedback = btn.parentElement.querySelector('.yith-pos-save-feedback');
                if(!feedback){
                    feedback = document.createElement('span');
                    feedback.className = 'yith-pos-save-feedback';
                    feedback.setAttribute('aria-live', 'polite');
                    btn.parentElement.appendChild(feedback);
                }
                feedback.textContent = 'Enregistré';
                feedback.classList.add('show');
                setTimeout(function(){ feedback.classList.remove('show'); }, 1500);
            })
            .catch(function(){ btn.disabled = false; alert('Error'); });
    });
})();
</script>


