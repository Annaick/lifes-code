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
