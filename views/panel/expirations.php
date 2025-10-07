<?php
/**
 * Expirations management view (initial duplicate of Stock panel)
 *
 * @var array $args
 */

defined( 'YITH_POS' ) || exit;

// For now, reuse the same listing/pagination as stock, to be adapted for lots/expiry later.
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

// Compute near-expiry and expired notices for the products on this page.
$warning_days = (int) get_option( 'yith_pos_expiration_warning_days', 30 );
$now_ts       = (int) current_time( 'timestamp' );
$near         = array();
$expired      = array();
foreach ( (array) $product_ids as $pid_chk ) {
    $prod = wc_get_product( $pid_chk );
    if ( ! $prod ) { continue; }
    $to_check = array( $prod );
    if ( $prod->is_type( 'variable' ) ) {
        foreach ( (array) $prod->get_children() as $child_id ) {
            $v = wc_get_product( $child_id );
            if ( $v ) { $to_check[] = $v; }
        }
    }
    foreach ( $to_check as $pobj ) {
        $entries = $pobj->get_meta( '_yith_pos_expirations' );
        $entries = is_array( $entries ) ? $entries : array();
        foreach ( $entries as $e ) {
            $date_str = isset( $e['date'] ) ? (string) $e['date'] : '';
            if ( ! $date_str ) { continue; }
            $exp_ts = strtotime( $date_str . ' 00:00:00' );
            if ( false === $exp_ts ) { continue; }
            $days = (int) floor( ( $exp_ts - $now_ts ) / DAY_IN_SECONDS );
            $row  = array(
                'product' => $pobj->get_name(),
                'product_id' => $pobj->get_id(),
                'code'    => isset( $e['code'] ) ? (string) $e['code'] : '',
                'qty'     => isset( $e['qty'] ) ? (int) $e['qty'] : 0,
                'date'    => $date_str,
                'days'    => $days,
            );
            if ( $days < 0 ) {
                $expired[] = $row;
            } elseif ( $days <= $warning_days ) {
                $near[] = $row;
            }
        }
    }
}

// Reuse the stock breakdown helper for now; will be replaced by lot/expiry breakdown.
if ( ! function_exists( 'yith_pos_render_exp_row' ) ) {
	function yith_pos_render_exp_row( $product ) {
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
		// Expirations toggle for product row.
		$stock_toggle_btn = '<button type="button" class="button-link yith-pos-toggle-stock" aria-expanded="false" aria-controls="stock-' . esc_attr( $product_id ) . '" title="Toggle expirations">🕒</button>';

		echo '<tr class="yith-pos-stock-row"' . $toggle_attr . ' data-product-id="' . esc_attr( $product_id ) . '">';
		echo '<td class="column-toggle">' . $stock_toggle_btn . ' ' . $toggle_btn . '</td>';
		echo '<td class="column-id">' . esc_html( $product_id ) . '</td>';
		echo '<td class="column-name">' . esc_html( $product_name ) . '</td>';
		echo '<td class="column-type">' . esc_html( $type_label ) . '</td>';
		echo '<td class="column-stock">' . esc_html( $stock_qty ) . '</td>';
		echo '<td class="column-price">' . wp_kses_post( $price_html ) . '</td>';
		echo '</tr>';

		// Expirations breakdown for non-variable products only (hidden by default).
		if ( ! $has_children ) {
			$breakdown_html = yith_pos_get_expirations_breakdown_html( $product );
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
					$var_name     = wp_strip_all_tags( $variation->get_name() );
					$price_html_v = $variation->get_price_html();
					$manage_v     = $variation->managing_stock();
					$stock_v      = $manage_v ? wc_stock_amount( $variation->get_stock_quantity() ) : __( '—', 'yith-point-of-sale-for-woocommerce' );
					echo '<tr class="yith-pos-variation-row">';
					echo '<td class="column-toggle"><button type="button" class="button-link yith-pos-toggle-stock" aria-expanded="false" aria-controls="var-stock-' . esc_attr( $variation->get_id() ) . '" title="Toggle expirations">🕒</button></td>';
					echo '<td class="column-id">' . esc_html( $variation->get_id() ) . '</td>';
					echo '<td class="column-name">' . esc_html( $var_name ) . '</td>';
					echo '<td class="column-stock">' . esc_html( $stock_v ) . '</td>';
					echo '<td class="column-price">' . wp_kses_post( $price_html_v ) . '</td>';
					echo '</tr>';

					// Variation expirations breakdown row.
					$variation_breakdown_html = yith_pos_get_expirations_breakdown_html( $variation );
					if ( $variation_breakdown_html ) {
						echo '<tr id="var-stock-' . esc_attr( $variation->get_id() ) . '" class="yith-pos-variation-stock-breakdown level-2" style="display:none"><td></td><td colspan="4">' . $variation_breakdown_html . '</td></tr>';
					}
				}
				echo '</tbody></table>';
				echo '</td></tr>';
			}
		}
	}
}
// Helper to render expirations table (date + qty) with actions
if ( ! function_exists( 'yith_pos_get_expirations_breakdown_html' ) ) {
    function yith_pos_get_expirations_breakdown_html( $product ) {
        if ( ! $product instanceof WC_Product ) {
            return '';
        }
        $entries = $product->get_meta( '_yith_pos_expirations' );
        $entries = is_array( $entries ) ? $entries : array();
        usort( $entries, function( $a, $b ) { return strcmp( (string) ( $a['date'] ?? '' ), (string) ( $b['date'] ?? '' ) ); } );

        ob_start();
        $product_id = $product->get_id();
        $nonce      = wp_create_nonce( 'yith_pos_exp_nonce' );
        echo '<table class="widefat fixed striped yith-pos-exp-table" data-product-id="' . esc_attr( $product_id ) . '">';
        echo '<thead><tr>';
        echo '<th style="width:30%">' . esc_html__( 'Expiration date', 'yith-point-of-sale-for-woocommerce' ) . '</th>';
        echo '<th style="width:20%">' . esc_html__( 'Code', 'yith-point-of-sale-for-woocommerce' ) . '</th>';
        echo '<th style="width:20%">' . esc_html__( 'Quantity', 'yith-point-of-sale-for-woocommerce' ) . '</th>';
        echo '<th style="width:30%">' . esc_html__( 'Actions', 'yith-point-of-sale-for-woocommerce' ) . '</th>';
        echo '</tr></thead><tbody>';
        foreach ( $entries as $e ) {
            $row_id = (int) ( $e['id'] ?? 0 );
            $qty    = max( 0, (int) ( $e['qty'] ?? 0 ) );
            $date   = esc_attr( (string) ( $e['date'] ?? '' ) );
            $code   = esc_attr( (string) ( $e['code'] ?? '' ) );
            echo '<tr class="yith-pos-exp-row" data-exp-id="' . esc_attr( $row_id ) . '">';
            echo '<td><input type="date" class="yith-pos-exp-date" value="' . $date . '" /></td>';
            echo '<td><input type="text" class="regular-text yith-pos-exp-code" value="' . $code . '" /></td>';
            echo '<td><input type="number" min="0" step="1" class="small-text yith-pos-exp-qty" value="' . esc_attr( $qty ) . '" /></td>';
            echo '<td>';
            echo '<button type="button" class="button yith-pos-exp-save" data-nonce="' . esc_attr( $nonce ) . '" data-product-id="' . esc_attr( $product_id ) . '" data-exp-id="' . esc_attr( $row_id ) . '">' . esc_html__( 'Save', 'yith-point-of-sale-for-woocommerce' ) . '</button>';
            echo '<button type="button" class="button yith-pos-exp-delete" data-nonce="' . esc_attr( $nonce ) . '" data-product-id="' . esc_attr( $product_id ) . '" data-exp-id="' . esc_attr( $row_id ) . '" style="margin-left:6px;">' . esc_html__( 'Delete', 'yith-point-of-sale-for-woocommerce' ) . '</button>';
            echo '<span class="yith-pos-save-feedback" aria-live="polite" style="margin-left:8px;"></span>';
            echo '</td>';
            echo '</tr>';
        }
        echo '<tr class="yith-pos-exp-add-row">';
        echo '<td colspan="3">';
        echo '<div class="yith-pos-exp-add-bar">';
        echo '<button type="button" class="button yith-pos-exp-add-trigger">' . esc_html__( 'Add expiration', 'yith-point-of-sale-for-woocommerce' ) . '</button>';
        echo '<span class="yith-pos-exp-add-form" style="display:none;margin-left:8px;">';
        echo '<input type="date" class="yith-pos-exp-add-date" /> ';
        echo '<input type="text" class="regular-text yith-pos-exp-add-code" placeholder="' . esc_attr__( 'Code', 'yith-point-of-sale-for-woocommerce' ) . '" /> ';
        echo '<input type="number" min="0" step="1" class="small-text yith-pos-exp-add-qty" placeholder="0" /> ';
        echo '<button type="button" class="button button-primary yith-pos-exp-add-confirm" data-product-id="' . esc_attr( $product_id ) . '" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Add', 'yith-point-of-sale-for-woocommerce' ) . '</button>';
        echo '</span>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
        echo '</tbody></table>';

        return (string) ob_get_clean();
    }
}
?>
<div class="wrap yith-pos-stock-wrap">
	<?php if ( ! empty( $expired ) ) : ?>
		<div class="notice notice-error"><p><strong><?php echo esc_html( 'Articles expirés détectés' ); ?></strong></p>
			<ul style="margin-left:18px;">
				<?php foreach ( $expired as $it ) : ?>
					<li><?php echo esc_html( sprintf( '%s (ID %d) — %s : %s, %s : %d, %s : %d', $it['product'], $it['product_id'], 'Code', $it['code'], 'Qté', $it['qty'], 'Jours', $it['days'] ) ); ?> — <?php echo esc_html( sprintf( 'Date de péremption : %s', $it['date'] ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $near ) ) : ?>
		<div class="notice notice-warning"><p><strong><?php echo esc_html( 'Produits proches de la date de péremption' ); ?></strong> <?php echo esc_html( sprintf( 'Dans %d jours', $warning_days ) ); ?></p>
			<ul style="margin-left:18px;">
				<?php foreach ( $near as $it ) : ?>
					<li><?php echo esc_html( sprintf( '%s (ID %d) — %s : %s, %s : %d, %s : %d', $it['product'], $it['product_id'], 'Code', $it['code'], 'Qté', $it['qty'], 'Jours', $it['days'] ) ); ?> — <?php echo esc_html( sprintf( 'Date de péremption : %s', $it['date'] ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
	<div style="float:right;">
		<button type="button" class="button button-primary" id="yith-pos-open-ie-modal"><?php echo esc_html( 'Importer & Exporter' ); ?></button>
	</div>
	<h2><?php echo esc_html__( 'Expirations', 'yith-point-of-sale-for-woocommerce' ); ?></h2>
	<p class="description"><?php echo esc_html__( 'Temporary duplicate of Stock panel. We will add perishable lot and expiration tracking here.', 'yith-point-of-sale-for-woocommerce' ); ?></p>
	<div class="yith-pos-stock-searchbar">
		<input type="search" id="yith-pos-stock-search" class="regular-text" placeholder="<?php echo esc_attr__( 'Rechercher des produits… (ID ou Nom)', 'yith-point-of-sale-for-woocommerce' ); ?>" />
	</div>
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
				yith_pos_render_exp_row( $product );
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
/* Reuse the stock panel styles */
.yith-pos-stock-searchbar { margin: 10px 0 12px; display:flex; justify-content:flex-start; }
.yith-pos-stock-searchbar input { max-width: 420px; }
.yith-pos-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.4); display: none; z-index: 100000; }
.yith-pos-modal { position: fixed; top: 10vh; left: 50%; transform: translateX(-50%); width: min(720px, 92vw); background: #fff; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,.2); display: none; z-index: 100001; }
.yith-pos-modal header { display:flex; align-items:center; justify-content: space-between; padding: 14px 16px; border-bottom: 1px solid #e5e7eb; }
.yith-pos-modal .content { padding: 16px; }
.yith-pos-modal .actions { padding: 12px 16px; border-top: 1px solid #e5e7eb; display:flex; justify-content: flex-end; gap:8px; }
.yith-pos-modal .row { display:flex; gap:16px; align-items:center; margin-bottom: 12px; }
.yith-pos-modal .row form { display:inline-flex; gap:8px; align-items:center; }
.yith-pos-modal .muted { color:#6b7280; font-size: 12px; }
.yith-pos-modal .close { background: transparent; border: 0; font-size: 20px; line-height: 1; cursor: pointer; }
.yith-pos-stock-table .button-link { text-decoration: none; font-size: 14px; }
.yith-pos-stock-table .column-toggle { white-space: nowrap; }

.yith-pos-stock-row td,
.yith-pos-variation-row td,
.yith-pos-stock-breakdown td,
.yith-pos-variation-stock-breakdown td { background: #ffffff; }

.is-expanded > td { background: #f3f4f6; }
.is-expanded + .yith-pos-stock-breakdown.level-1 td { background: #f3f4f6; }
.yith-pos-stock-row.is-expanded + .yith-pos-variation-container td { background: #f3f4f6; }
.yith-pos-variation-row.is-expanded + .yith-pos-variation-stock-breakdown.level-2 td { background: #f3f4f6; }

.yith-pos-save-feedback { margin-left: 8px; color: #1a7f37; opacity: 0; transition: opacity .2s ease-in-out; font-weight: 600; }
.yith-pos-save-feedback.show { opacity: 1; }

.yith-pos-add-stock-row td { background: #e6f4ea; }
.yith-pos-add-stock-bar .yith-pos-add-stock-trigger { background: #1a7f37; border-color: #146c2e; color: #fff; }
.yith-pos-add-stock-bar .yith-pos-add-stock-trigger:hover { background: #146c2e; }
</style>
<script>
(function(){
    document.addEventListener('click', function(e){
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
            if(row){
                if(expanded){ row.classList.remove('is-expanded'); }
                else { row.classList.add('is-expanded'); }
            }
            return;
        }

        var btnStock = e.target.closest('.yith-pos-toggle-stock');
        if(btnStock){
            var targetId = btnStock.getAttribute('aria-controls');
            if(!targetId){return;}
            var target = document.getElementById(targetId);
            if(!target){return;}
            var expanded = btnStock.getAttribute('aria-expanded') === 'true';
            btnStock.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            target.style.display = expanded ? 'none' : '';

            var ownerRow = btnStock.closest('tr');
            if(ownerRow){
                if(expanded){ ownerRow.classList.remove('is-expanded'); }
                else { ownerRow.classList.add('is-expanded'); }
            }
            return;
        }
    });
})();
</script>

<script>
(function(){
  function showIeModal(show){
    var overlay = document.getElementById('yith-pos-ie-overlay');
    var modal   = document.getElementById('yith-pos-ie-modal');
    if(!overlay || !modal) return;
    overlay.style.display = show ? 'block' : 'none';
    modal.style.display   = show ? 'block' : 'none';
  }
  document.addEventListener('click', function(e){
    if(e.target && e.target.id === 'yith-pos-open-ie-modal'){
      showIeModal(true);
      return;
    }
    if(e.target && (e.target.id === 'yith-pos-ie-close' || e.target.id === 'yith-pos-ie-close-2')){
      showIeModal(false);
      return;
    }
    if(e.target && e.target.id === 'yith-pos-ie-overlay'){
      showIeModal(false);
      return;
    }
  });
})();
</script>

<script>
(function(){
    // Toggle add form
    document.addEventListener('click', function(e){
        var trigger = e.target.closest('.yith-pos-exp-add-trigger');
        if(trigger){
            var wrapper = trigger.closest('.yith-pos-exp-add-bar');
            var form = wrapper && wrapper.querySelector('.yith-pos-exp-add-form');
            if(form){ form.style.display = form.style.display === 'none' ? '' : 'none'; }
            return;
        }

        // Add expiration
        var addBtn = e.target.closest('.yith-pos-exp-add-confirm');
        if(addBtn){
            var table = addBtn.closest('.yith-pos-exp-table');
            var pid = addBtn.getAttribute('data-product-id');
            var nonce = addBtn.getAttribute('data-nonce');
            var dateInput = table && table.querySelector('.yith-pos-exp-add-date');
            var codeInput = table && table.querySelector('.yith-pos-exp-add-code');
            var qtyInput = table && table.querySelector('.yith-pos-exp-add-qty');
            var date = dateInput ? dateInput.value : '';
            var code = codeInput ? codeInput.value : '';
            var qty = qtyInput ? (qtyInput.value||'0') : '0';
            if(!pid || !date || !code){ alert('Missing data'); return; }
            var form = new FormData();
            form.append('action', 'yith_pos_add_expiration');
            form.append('nonce', nonce);
            form.append('product_id', pid);
            form.append('date', date);
            form.append('code', code);
            form.append('qty', qty);
            addBtn.disabled = true;
            fetch(ajaxurl, { method:'POST', body: form })
            .then(r=>r.json())
            .then(function(data){
                addBtn.disabled = false;
                if(!data || !data.success){ alert((data&&data.data&&data.data.message)||'Error'); return; }
                // Insert new row, keep table sorted by date
                var tbody = table.querySelector('tbody');
                var addRow = table.querySelector('.yith-pos-exp-add-row');
                var tr = document.createElement('tr');
                tr.className = 'yith-pos-exp-row';
                tr.setAttribute('data-exp-id', data.data.id);
                tr.innerHTML = '<td><input type="date" class="yith-pos-exp-date" value="'+date+'" /></td>'+
                               '<td><input type="text" class="regular-text yith-pos-exp-code" value="'+code+'" /></td>'+
                               '<td><input type="number" min="0" step="1" class="small-text yith-pos-exp-qty" value="'+(parseInt(qty,10)||0)+'" /></td>'+
                               '<td>'+
                               '<button type="button" class="button yith-pos-exp-save" data-nonce="'+nonce+'" data-product-id="'+pid+'" data-exp-id="'+data.data.id+'">'+(window.yith_pos_i18n_save||'Save')+'</button>'+
                               '<button type="button" class="button yith-pos-exp-delete" data-nonce="'+nonce+'" data-product-id="'+pid+'" data-exp-id="'+data.data.id+'" style="margin-left:6px;">'+(window.yith_pos_i18n_delete||'Delete')+'</button>'+
                               '<span class="yith-pos-save-feedback" aria-live="polite" style="margin-left:8px;"></span>'+
                               '</td>';
                // place new row before add row, then re-sort
                if(addRow){ tbody.insertBefore(tr, addRow); }
                // simple client-side sort by date ascending
                var rows = [].slice.call(tbody.querySelectorAll('.yith-pos-exp-row'));
                rows.sort(function(a,b){
                    var da=(a.querySelector('.yith-pos-exp-date')||{}).value||'';
                    var db=(b.querySelector('.yith-pos-exp-date')||{}).value||'';
                    return da.localeCompare(db);
                });
                rows.forEach(function(r){ tbody.insertBefore(r, addRow); });
                // clear inputs
                if(dateInput) dateInput.value='';
                if(codeInput) codeInput.value='';
                if(qtyInput) qtyInput.value='';
            })
            .catch(function(){ addBtn.disabled=false; alert('Error'); });
            return;
        }

        // Save expiration
        var saveBtn = e.target.closest('.yith-pos-exp-save');
        if(saveBtn){
            var row = saveBtn.closest('.yith-pos-exp-row');
            var table = saveBtn.closest('.yith-pos-exp-table');
            var pid = saveBtn.getAttribute('data-product-id');
            var nonce = saveBtn.getAttribute('data-nonce');
            var expId = saveBtn.getAttribute('data-exp-id');
            var date = row && row.querySelector('.yith-pos-exp-date') ? row.querySelector('.yith-pos-exp-date').value : '';
            var code = row && row.querySelector('.yith-pos-exp-code') ? row.querySelector('.yith-pos-exp-code').value : '';
            var qty = row && row.querySelector('.yith-pos-exp-qty') ? row.querySelector('.yith-pos-exp-qty').value : '0';
            if(!pid || !expId || !date || !code){ alert('Missing data'); return; }
            var form = new FormData();
            form.append('action', 'yith_pos_update_expiration');
            form.append('nonce', nonce);
            form.append('product_id', pid);
            form.append('id', expId);
            form.append('date', date);
            form.append('code', code);
            form.append('qty', qty);
            saveBtn.disabled = true;
            fetch(ajaxurl, { method:'POST', body: form })
            .then(r=>r.json())
            .then(function(data){
                saveBtn.disabled = false;
                if(!data || !data.success){ alert((data&&data.data&&data.data.message)||'Error'); return; }
                // feedback and sort table again
                var fb = row.querySelector('.yith-pos-save-feedback');
                if(fb){ fb.textContent = (window.yith_pos_i18n_saved||'Saved'); fb.classList.add('show'); setTimeout(function(){ fb.classList.remove('show'); }, 1200); }
                var tbody = table.querySelector('tbody');
                var addRow = table.querySelector('.yith-pos-exp-add-row');
                var rows = [].slice.call(tbody.querySelectorAll('.yith-pos-exp-row'));
                rows.sort(function(a,b){
                    var da=(a.querySelector('.yith-pos-exp-date')||{}).value||'';
                    var db=(b.querySelector('.yith-pos-exp-date')||{}).value||'';
                    return da.localeCompare(db);
                });
                rows.forEach(function(r){ tbody.insertBefore(r, addRow); });
            })
            .catch(function(){ saveBtn.disabled=false; alert('Error'); });
            return;
        }

        // Delete expiration
        var delBtn = e.target.closest('.yith-pos-exp-delete');
        if(delBtn){
            if(!confirm('Delete this expiration?')){ return; }
            var row = delBtn.closest('.yith-pos-exp-row');
            var pid = delBtn.getAttribute('data-product-id');
            var nonce = delBtn.getAttribute('data-nonce');
            var expId = delBtn.getAttribute('data-exp-id');
            var form = new FormData();
            form.append('action', 'yith_pos_delete_expiration');
            form.append('nonce', nonce);
            form.append('product_id', pid);
            form.append('id', expId);
            delBtn.disabled = true;
            fetch(ajaxurl, { method:'POST', body: form })
            .then(r=>r.json())
            .then(function(data){
                delBtn.disabled = false;
                if(!data || !data.success){ alert((data&&data.data&&data.data.message)||'Error'); return; }
                if(row){ row.remove(); }
            })
            .catch(function(){ delBtn.disabled=false; alert('Error'); });
            return;
        }
    });
})();
</script>

<!-- Import/Export Expirations Modal -->
<div class="yith-pos-modal-overlay" id="yith-pos-ie-overlay"></div>
<div class="yith-pos-modal" id="yith-pos-ie-modal" role="dialog" aria-modal="true" aria-labelledby="yith-pos-ie-title" aria-hidden="true">
  <header>
    <h2 id="yith-pos-ie-title"><?php echo esc_html( 'Import / Export des péremptions' ); ?></h2>
    <button type="button" class="close" id="yith-pos-ie-close" aria-label="Fermer">×</button>
  </header>
  <div class="content">
    <div class="row">
      <strong><?php echo esc_html( 'Exporter (CSV)' ); ?></strong>
      <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=yith_pos_export_expirations' ), 'yith_pos_exp_export' ) ); ?>"><?php echo esc_html( 'Télécharger le CSV des péremptions' ); ?></a>
    </div>
    <div class="row">
      <strong><?php echo esc_html( 'Importer (CSV)' ); ?></strong>
      <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php?action=yith_pos_import_expirations' ) ); ?>">
        <?php wp_nonce_field( 'yith_pos_exp_import' ); ?>
        <input type="file" name="yith_pos_exp_file" accept=".csv" required />
        <button type="submit" class="button button-primary"><?php echo esc_html( 'Importer et mettre à jour' ); ?></button>
      </form>
    </div>
    <p class="muted"><?php echo esc_html( 'Colonnes CSV : product_id, variation_id, sku, name, product_type, code, date (YYYY-MM-DD), qty' ); ?></p>
  </div>
  <div class="actions">
    <button type="button" class="button" id="yith-pos-ie-close-2"><?php echo esc_html( 'Fermer' ); ?></button>
  </div>
</div>

<script>
(function(){
  var input = document.getElementById('yith-pos-stock-search');
  if(!input) return;
  function norm(s){ return (s||'').toString().toLowerCase(); }
  function text(el){ return (el && (el.textContent||el.innerText)||'').trim(); }
  function filter(){
    var q = norm(input.value);
    var table = document.querySelector('.yith-pos-stock-table');
    if(!table) return;
    var rows = table.querySelectorAll('tbody > tr.yith-pos-stock-row');
    rows.forEach(function(row){
      var pidCell  = row.querySelector('.column-id');
      var nameCell = row.querySelector('.column-name');
      var idTxt    = norm(text(pidCell));
      var nameTxt  = norm(text(nameCell));
      var pid      = row.getAttribute('data-product-id');
      var matchSelf = !q || idTxt.indexOf(q) !== -1 || nameTxt.indexOf(q) !== -1;

      // Check variations under this product row if not directly matched
      var matchVar = false;
      var varContainer = document.getElementById('variations-' + pid);
      if(!matchSelf && varContainer){
        var vrows = varContainer.querySelectorAll('.yith-pos-variation-row');
        vrows.forEach(function(vr){
          var vidCell  = vr.querySelector('.column-id');
          var vnameCell= vr.querySelector('.column-name');
          var vidTxt   = norm(text(vidCell));
          var vnmTxt   = norm(text(vnameCell));
          if(vidTxt.indexOf(q) !== -1 || vnmTxt.indexOf(q) !== -1){ matchVar = true; }
        });
      }

      var show = matchSelf || matchVar;
      row.style.display = show ? '' : 'none';
      // Hide containers if parent hidden
      var stockRow = document.getElementById('stock-' + pid);
      if(stockRow && !show){ stockRow.style.display = 'none'; }
      if(varContainer && !show){ varContainer.style.display = 'none'; }
    });
  }
  input.addEventListener('input', filter);
})();
</script>
