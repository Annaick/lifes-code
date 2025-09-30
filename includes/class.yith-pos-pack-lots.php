<?php
/**
 * Pack/Lot support integrated with multi-inventory.
 */

defined( 'YITH_POS' ) || exit;

if ( ! class_exists( 'YITH_POS_Pack_Lots' ) ) {
    class YITH_POS_Pack_Lots {
        use YITH_POS_Singleton_Trait;

        const META_KEY = '_yith_pos_pack_lot'; // string like "123:2,456:1"

        private function __construct() {
            // Admin fields for simple products.
            add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'render_admin_field_simple' ) );
            add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_admin_field_simple' ), 20, 1 );

            // Admin fields for variations.
            add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'render_admin_field_variation' ), 20, 3 );
            add_action( 'woocommerce_save_product_variation', array( $this, 'save_admin_field_variation' ), 20, 2 );

            // Availability based on components for POS REST responses.
            add_filter( 'yith_pos_parse_product_stock_check', array( $this, 'filter_parsed_stock_for_pos' ), 10, 4 );

            // Website catalog visibility/stock status.
            add_filter( 'woocommerce_is_purchasable', array( $this, 'filter_is_purchasable_site' ), 20, 2 );
            add_filter( 'woocommerce_product_is_in_stock', array( $this, 'filter_is_in_stock_site' ), 20, 2 );

            // Reduce/restore order stock for website orders (POS handled by POS stock manager hook below via action).
            add_action( 'woocommerce_reduce_order_stock', array( $this, 'reduce_component_stock_for_packs' ), 20 );
            add_action( 'woocommerce_restore_order_stock', array( $this, 'restore_component_stock_for_packs' ), 20 );

            // Hook into POS flows: after POS reduces product stock, also reduce components if the product is a pack for that store.
            add_action( 'yith_pos_reduce_order_stock', array( $this, 'reduce_components_for_pos_order' ) );
            add_action( 'yith_pos_restore_order_stock', array( $this, 'restore_components_for_pos_order' ) );

            // Display components in cart and order emails/PDFs.
            add_filter( 'woocommerce_get_item_data', array( $this, 'add_cart_item_lot_info' ), 10, 2 );
            add_action( 'woocommerce_order_item_meta_end', array( $this, 'render_lot_in_order_item' ), 10, 4 );
            add_filter( 'woocommerce_display_item_meta', array( $this, 'append_lot_to_display_item_meta' ), 10, 3 );
            add_action( 'wpo_wcpdf_after_item_meta', array( $this, 'render_lot_in_pdf' ), 10, 3 );
        }

        // ---------- Admin UI ----------
        public function render_admin_field_simple() {
            global $product_object;
            if ( ! $product_object instanceof WC_Product ) {
                return;
            }
            echo '<div class="options_group">';
            woocommerce_wp_text_input( array(
                'id'          => self::META_KEY,
                'value'       => $product_object->get_meta( self::META_KEY, true ),
                'label'       => __( 'Pack/Lot components', 'yith-point-of-sale-for-woocommerce' ),
                'description' => __( 'Format: id:qty,id2:qty2. Laissez vide si ce produit n\'est pas un lot.', 'yith-point-of-sale-for-woocommerce' ),
                'desc_tip'    => true,
            ) );
            echo '</div>';
        }

        public function save_admin_field_simple( $product ) {
            if ( ! $product instanceof WC_Product ) {
                return;
            }
            // phpcs:disable WordPress.Security.NonceVerification.Missing
            if ( isset( $_POST[ self::META_KEY ] ) ) {
                $value = sanitize_text_field( wp_unslash( $_POST[ self::META_KEY ] ) );
                $product->update_meta_data( self::META_KEY, $value );
            }
            // phpcs:enable
        }

        public function render_admin_field_variation( $loop, $variation_data, $variation ) {
            $value = get_post_meta( $variation->ID, self::META_KEY, true );
            woocommerce_wp_text_input( array(
                'id'          => self::META_KEY . "_{$loop}",
                'label'       => __( 'Pack/Lot components', 'yith-point-of-sale-for-woocommerce' ),
                'description' => __( 'Format: id:qty,id2:qty2 (can use product or variation IDs). Leave empty if not a pack.', 'yith-point-of-sale-for-woocommerce' ),
                'value'       => $value,
                'desc_tip'    => true,
            ) );
        }

        public function save_admin_field_variation( $variation_id, $i ) {
            // phpcs:disable WordPress.Security.NonceVerification.Missing
            $key = self::META_KEY . '_' . $i;
            if ( isset( $_POST[ $key ] ) ) {
                update_post_meta( $variation_id, self::META_KEY, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
            }
            // phpcs:enable
        }

        // ---------- Availability logic ----------
        private function parse_components( $raw ) {
            $components = array();
            if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
                return $components;
            }
            $parts = explode( ',', $raw );
            foreach ( $parts as $p ) {
                $p = trim( $p );
                if ( '' === $p || false === strpos( $p, ':' ) ) {
                    continue;
                }
                list( $id, $qty ) = array_map( 'trim', explode( ':', $p, 2 ) );
                $id  = absint( $id );
                $qty = max( 1, absint( $qty ) );
                if ( $id > 0 && $qty > 0 ) {
                    $components[] = array( 'id' => $id, 'qty' => $qty );
                }
            }
            return $components;
        }

        private function get_meta_for_product( WC_Product $product ) {
            return $product->is_type( 'variation' )
                ? get_post_meta( $product->get_id(), self::META_KEY, true )
                : $product->get_meta( self::META_KEY, true );
        }

        private function get_store_context_for_request() {
            // POS REST requests pass yith_pos_store; website has none.
            $store_id = null;
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
            if ( isset( $_REQUEST['yith_pos_store'] ) ) {
                $store_id = absint( wp_unslash( $_REQUEST['yith_pos_store'] ) );
            }
            // phpcs:enable
            return $store_id ?: null;
        }

        private function get_available_units_from_components( array $components, $store_id = null ) {
            if ( empty( $components ) ) {
                return PHP_INT_MAX;
            }
            $min_sets = PHP_INT_MAX;
            foreach ( $components as $c ) {
                $component = wc_get_product( $c['id'] );
                if ( ! $component ) {
                    $min_sets = 0;
                    break;
                }

                // Determine stock considering multistock when store_id provided.
                $available = null;
                if ( $store_id ) {
                    if ( function_exists( 'yith_pos_stock_management' ) ) {
                        $sm        = yith_pos_stock_management();
                        $stock_amt = $sm->get_stock_amount( $component, $store_id );
                        if ( false !== $stock_amt ) {
                            $available = max( 0, intval( $stock_amt ) );
                        }
                    }
                }
                if ( null === $available ) {
                    $available = $component->managing_stock() ? max( 0, intval( $component->get_stock_quantity() ) ) : PHP_INT_MAX;
                }

                $sets = ( 0 === $available ) ? 0 : intdiv( $available, max( 1, $c['qty'] ) );
                $min_sets = min( $min_sets, $sets );
                if ( 0 === $min_sets ) {
                    break;
                }
            }
            return $min_sets;
        }

        public function filter_parsed_stock_for_pos( $allowed, $product, $multistock_condition, $store_id ) {
            $raw = $this->get_meta_for_product( $product );
            $components = $this->parse_components( $raw );
            if ( empty( $components ) ) {
                return $allowed;
            }

            $available_sets = $this->get_available_units_from_components( $components, $store_id );
            if ( 0 === $available_sets ) {
                $product->set_stock_status( $product->backorders_allowed() ? 'onbackorder' : 'outofstock' );
                $product->set_stock_quantity( 0 );
            } else {
                $product->set_manage_stock( true );
                $product->set_stock_quantity( $available_sets );
                $product->set_stock_status( 'instock' );
            }
            return true;
        }

        public function filter_is_purchasable_site( $purchasable, $product ) {
            $raw = $this->get_meta_for_product( $product );
            $components = $this->parse_components( $raw );
            if ( empty( $components ) ) {
                return $purchasable;
            }
            $available_sets = $this->get_available_units_from_components( $components, null );
            return $purchasable && ( $available_sets > 0 );
        }

        public function filter_is_in_stock_site( $in_stock, $product ) {
            $raw = $this->get_meta_for_product( $product );
            $components = $this->parse_components( $raw );
            if ( empty( $components ) ) {
                return $in_stock;
            }
            $available_sets = $this->get_available_units_from_components( $components, null );
            return $available_sets > 0;
        }

        // ---------- Stock reduction (website) ----------
        public function reduce_component_stock_for_packs( $order ) {
            $this->change_component_stock_for_order( $order, 'decrease', null );
        }

        public function restore_component_stock_for_packs( $order ) {
            $this->change_component_stock_for_order( $order, 'increase', null );
        }

        // ---------- Stock reduction (POS with store) ----------
        public function reduce_components_for_pos_order( $order ) {
            $store_id = $order->get_meta( '_yith_pos_store' );
            if ( $store_id ) {
                $this->change_component_stock_for_order( $order, 'decrease', absint( $store_id ) );
            }
        }

        public function restore_components_for_pos_order( $order ) {
            $store_id = $order->get_meta( '_yith_pos_store' );
            if ( $store_id ) {
                $this->change_component_stock_for_order( $order, 'increase', absint( $store_id ) );
            }
        }

        private function change_component_stock_for_order( $order, $operation, $store_id = null ) {
            if ( ! $order instanceof WC_Order ) {
                return;
            }
            foreach ( $order->get_items() as $item ) {
                if ( ! $item->is_type( 'line_item' ) ) {
                    continue;
                }
                /** @var WC_Order_Item_Product $item */
                $product = $item->get_product();
                if ( ! $product ) {
                    continue;
                }
                $raw        = $this->get_meta_for_product( $product );
                $components = $this->parse_components( $raw );
                if ( empty( $components ) ) {
                    continue;
                }
                $qty_sets = (int) $item->get_quantity();
                foreach ( $components as $c ) {
                    $component = wc_get_product( $c['id'] );
                    if ( ! $component ) {
                        continue;
                    }

                    $delta = wc_stock_amount( $c['qty'] * $qty_sets );

                    if ( $store_id && function_exists( 'yith_pos_stock_management' ) ) {
                        $sm          = yith_pos_stock_management();
                        $stock_amt   = $sm->get_stock_amount( $component, $store_id );
                        if ( false !== $stock_amt ) {
                            $new_stock = ( 'decrease' === $operation )
                                ? $sm->update_product_stock( $component, $delta, $store_id, $stock_amt, 'decrease' )
                                : $sm->update_product_stock( $component, $delta, $store_id, $stock_amt, 'increase' );
                            continue;
                        }
                    }
                    // Fallback to general stock.
                    wc_update_product_stock( $component, $delta, ( 'decrease' === $operation ) ? 'decrease' : 'increase' );
                }
            }
        }

        // ---------- Cart and emails rendering ----------
        public function add_cart_item_lot_info( $item_data, $cart_item ) {
            $product = isset( $cart_item['data'] ) && $cart_item['data'] instanceof WC_Product ? $cart_item['data'] : null;
            if ( ! $product ) {
                return $item_data;
            }
            $raw        = $this->get_meta_for_product( $product );
            $components = $this->parse_components( $raw );
            if ( empty( $components ) ) {
                return $item_data;
            }
            $lines = array();
            foreach ( $components as $c ) {
                $p = wc_get_product( $c['id'] );
                if ( $p ) {
                    $lines[] = $c['qty'] . 'x ' . $p->get_name();
                }
            }
            if ( ! empty( $lines ) ) {
                $item_data[] = array(
                    'name'  => __( 'Contenu du lot', 'yith-point-of-sale-for-woocommerce' ),
                    'value' => implode( ', ', $lines ),
                );
            }
            return $item_data;
        }

        public function render_lot_in_order_item( $item_id, $item, $order, $plain_text ) {
            if ( ! method_exists( $item, 'get_product' ) ) {
                return;
            }
            $product = $item->get_product();
            if ( ! $product ) {
                return;
            }
            $raw        = $this->get_meta_for_product( $product );
            $components = $this->parse_components( $raw );
            if ( empty( $components ) ) {
                return;
            }
            $lines = array();
            foreach ( $components as $c ) {
                $p = wc_get_product( $c['id'] );
                if ( $p ) {
                    $lines[] = $c['qty'] . 'x ' . $p->get_name();
                }
            }
            if ( empty( $lines ) ) {
                return;
            }
            if ( $plain_text ) {
                echo "\n" . __( 'Contenu du lot:', 'yith-point-of-sale-for-woocommerce' ) . ' ' . implode( ', ', $lines );
            } else {
                echo '<div style="margin-top: 5px; padding: 5px 0; border-top: 1px solid #ddd;">';
                echo '<small><strong>' . esc_html__( 'Contenu du lot:', 'yith-point-of-sale-for-woocommerce' ) . '</strong></small><br />';
                echo '<small style="color:#666;">' . esc_html( implode( ', ', $lines ) ) . '</small>';
                echo '</div>';
            }
        }

        public function append_lot_to_display_item_meta( $html, $item, $args ) {
            if ( ! method_exists( $item, 'get_product' ) ) {
                return $html;
            }
            $product = $item->get_product();
            if ( ! $product ) {
                return $html;
            }
            $raw        = $this->get_meta_for_product( $product );
            $components = $this->parse_components( $raw );
            if ( empty( $components ) ) {
                return $html;
            }
            $lines = array();
            foreach ( $components as $c ) {
                $p = wc_get_product( $c['id'] );
                if ( $p ) {
                    $lines[] = $c['qty'] . 'x ' . $p->get_name();
                }
            }
            if ( empty( $lines ) ) {
                return $html;
            }
            $html .= '<div class="lot-content" style="margin-top:8px;padding-top:5px;border-top:1px solid #eee;">';
            $html .= '<strong style="font-size:0.9em;">' . esc_html__( 'Contenu du lot:', 'yith-point-of-sale-for-woocommerce' ) . '</strong><br />';
            $html .= '<span style="font-size:0.85em;color:#666;">' . esc_html( implode( ', ', $lines ) ) . '</span>';
            $html .= '</div>';
            return $html;
        }

        public function render_lot_in_pdf( $template_type, $item, $order ) {
            if ( ! method_exists( $item, 'get_product' ) ) {
                return;
            }
            $product = $item->get_product();
            if ( ! $product ) {
                return;
            }
            $raw        = $this->get_meta_for_product( $product );
            $components = $this->parse_components( $raw );
            if ( empty( $components ) ) {
                return;
            }
            $lines = array();
            foreach ( $components as $c ) {
                $p = wc_get_product( $c['id'] );
                if ( $p ) {
                    $lines[] = $c['qty'] . 'x ' . $p->get_name();
                }
            }
            if ( empty( $lines ) ) {
                return;
            }
            echo '<div style="margin-top:3px;font-size:11px;color:#666;">';
            echo '<strong>' . esc_html__( 'Contenu du lot:', 'yith-point-of-sale-for-woocommerce' ) . '</strong> ';
            echo esc_html( implode( ', ', $lines ) );
            echo '</div>';
        }
    }
}

if ( ! function_exists( 'yith_pos_pack_lots' ) ) {
    function yith_pos_pack_lots() {
        return YITH_POS_Pack_Lots::get_instance();
    }
}


