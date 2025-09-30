# Shipping to Store Feature - Documentation

## Overview

This feature allows you to assign WooCommerce shipping methods to specific store inventories. When a customer selects a shipping method assigned to a store, the system will:

1. **Validate stock availability** - Only show the shipping method if all products have sufficient stock in the assigned store
2. **Deduct stock from the correct store** - When an order is placed, stock is deducted from the assigned store's inventory
3. **Restore stock to the correct store** - When an order is cancelled or refunded, stock is restored to the correct store

## How It Works

### 1. Multi-Store & Multi-Inventory System

The plugin supports:
- **Multiple stores** - Each store can be configured independently
- **Multi-inventory per product** - Each product can have separate stock levels for each store
- **Pack/Lot feature** - Products can be configured to deduct stock from other component products

### 2. Shipping Method Assignment

#### Setup Instructions

1. Navigate to **WooCommerce → Settings → Shipping**
2. Select a shipping zone
3. Click on a shipping method (e.g., "Flat Rate", "Local Pickup", "Free Shipping")
4. You'll see a new field: **"Assigned Store"**
5. Select the store you want to assign to this shipping method
6. Save changes

#### Supported Shipping Methods

The feature works with:
- Flat Rate
- Free Shipping
- Local Pickup
- Any other standard WooCommerce shipping method

### 3. Stock Validation on Checkout

When a customer is on the checkout page:

1. The system checks all products in the cart
2. For each shipping method with an assigned store:
   - If a product has **multi-stock enabled**: Check if the assigned store has sufficient stock
   - If stock is insufficient: Hide the shipping method
   - If stock is sufficient: Show the shipping method
3. If a product doesn't have multi-stock enabled, the shipping method is available (uses general stock)

### 4. Stock Deduction on Order Completion

When an order is placed with a store-assigned shipping method:

1. The system stores the shipping method's assigned store ID in order meta: `_yith_pos_shipping_store`
2. Stock is deducted from the assigned store's inventory (not the default/general stock)
3. Order items are marked with metadata to track where stock was reduced:
   - `_reduced_stock` - Total quantity reduced
   - `_yith_pos_reduced_stock_by_store` - Store ID where stock was reduced
   - `_yith_pos_reduced_stock_by_store_qty` - Quantity reduced from store

### 5. Stock Restoration

When an order is cancelled, refunded, or restored:

1. The system checks the order meta `_yith_pos_shipping_store`
2. If present, stock is restored to the correct store inventory
3. The restoration follows the same logic as the reduction, ensuring consistency

## Technical Details

### Key Files

- **`includes/class.yith-pos-shipping-to-store.php`** - Main feature class
- **`includes/class.yith-pos.php`** - Updated to load the new class
- **`includes/class.yith-pos-stock-management.php`** - Existing multi-stock manager (used by the feature)

### Hooks Used

#### Filters
- `woocommerce_shipping_instance_form_fields_flat_rate` - Add store field to flat rate settings
- `woocommerce_shipping_instance_form_fields_free_shipping` - Add store field to free shipping settings
- `woocommerce_shipping_instance_form_fields_local_pickup` - Add store field to local pickup settings
- `woocommerce_package_rates` - Filter available shipping methods based on stock
- `woocommerce_can_reduce_order_stock` - Handle custom stock reduction
- `woocommerce_can_restore_order_stock` - Handle custom stock restoration
- `woocommerce_can_restock_refunded_items` - Handle refund stock restoration

#### Actions
- `woocommerce_checkout_order_processed` - Store shipping method's store info in order meta

### Database Schema

#### Shipping Method Meta
- `_yith_pos_assigned_store_id` - Stores the assigned store ID for each shipping method instance

#### Order Meta
- `_yith_pos_shipping_store` - Stores which store's inventory should be used for this order

#### Order Item Meta (per line item)
- `_reduced_stock` - Total quantity of stock reduced
- `_yith_pos_reduced_stock_by_store` - Store ID from which stock was reduced
- `_yith_pos_reduced_stock_by_store_qty` - Quantity reduced from the store
- `_yith_pos_reduced_stock_by_general` - Quantity reduced from general stock (fallback)

## Example Use Cases

### Use Case 1: Click and Collect for Specific Boutique

**Setup:**
1. Create a shipping method: "Click and Collect: Boutique A"
2. Assign it to "Boutique A" store
3. Customer adds products to cart
4. On checkout, "Click and Collect: Boutique A" only appears if Boutique A has enough stock
5. Order is placed → Stock deducted from Boutique A inventory

### Use Case 2: Multiple Store Locations

**Setup:**
1. Create shipping method: "Local Pickup: Downtown Store" → Assign to Downtown Store
2. Create shipping method: "Local Pickup: Mall Store" → Assign to Mall Store
3. Customers see only the pickup options where stock is available
4. Each order deducts from the correct store's inventory

### Use Case 3: Mixed Inventory

**Product Configuration:**
- Product A: Multi-stock enabled with stock in Store 1 and Store 2
- Product B: Multi-stock NOT enabled (uses general stock only)

**Result:**
- Shipping methods assigned to Store 1 or Store 2 will be available
- Product A stock checked in assigned store
- Product B uses general stock (not validated per store)

## Important Notes

### Compatibility with POS Orders

- This feature only affects **website orders** (WooCommerce checkout)
- **POS orders** are handled by the existing POS stock management system
- POS orders are automatically detected and skipped by this feature

### Compatibility with Pack/Lot Feature

- The pack/lot feature continues to work as expected
- Component products are tracked separately
- Stock deduction happens for both the pack product and its components

### Fallback Behavior

If a product with multi-stock enabled doesn't have stock defined for the assigned store:
- The shipping method will be hidden (not available)
- This prevents orders from being placed when stock is unavailable

If a product doesn't have multi-stock enabled:
- The shipping method remains available
- General/default stock is used

## Testing Checklist

- [ ] Configure a product with multi-stock for multiple stores
- [ ] Create a shipping method and assign it to a specific store
- [ ] Add product to cart and verify shipping method appears only when store has stock
- [ ] Place an order and verify stock is deducted from the correct store
- [ ] Cancel/refund the order and verify stock is restored to the correct store
- [ ] Test with products that don't have multi-stock enabled
- [ ] Test with pack/lot products
- [ ] Verify POS orders are not affected

## Troubleshooting

### Shipping method not showing up

**Possible causes:**
1. The assigned store doesn't have sufficient stock
2. Multi-stock is not enabled on the product
3. Multi-stock is enabled but no stock is defined for the assigned store

**Solution:** 
- Check product inventory settings
- Verify multi-stock is enabled and configured for the store
- Ensure stock quantity is sufficient for the cart items

### Stock not deducting from the correct store

**Possible causes:**
1. The shipping method doesn't have a store assigned
2. WooCommerce stock management is disabled

**Solution:**
- Go to shipping method settings and verify store assignment
- Ensure WooCommerce → Settings → Products → Inventory → "Manage stock" is enabled
- Check order meta for `_yith_pos_shipping_store` to verify store was recorded

### Stock restoration issues

**Possible causes:**
1. Order was created as a POS order (handled differently)
2. Stock reduction was not properly tracked

**Solution:**
- Check order item meta for `_yith_pos_reduced_stock_by_store` or `_yith_pos_reduced_stock_by_general`
- Verify the order has `_yith_pos_shipping_store` meta

## Support

For issues or questions, check:
1. WooCommerce logs (WooCommerce → Status → Logs)
2. Order notes (each order shows stock reduction/restoration notes)
3. Product meta data in the database

## Version

- **Feature Version:** 1.0.0
- **Compatible with:** Lifes-code POS Plugin v1.0.0+
- **Requires:** WooCommerce 9.6+
