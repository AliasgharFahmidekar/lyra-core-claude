---
name: woocommerce-elementor-pages
description: Rules for building WooCommerce cart, checkout, thank-you and product-related designs with Lyra Core widgets without breaking WooCommerce (field names, nonces, payment, order-received, HPOS, editor demo data). Use when a design includes cart, checkout, order success, mini-cart, product cards or any shop UI.
---

# WooCommerce pages

**Principle.** Widgets are views over WooCommerce state. Woo owns cart, validation, order creation, gateways and emails. If a design
needs extra data (delivery slot, free-shipping bar), store it as cart/order meta through documented hooks; never build a parallel order system.
Use `Lyra_Woo` (`is_active()`, `cart()`, `price()`, `use_demo_data()`); guard every call, `WC()->cart` can be null in admin/REST/editor.

## Registration
After building pages: admin screen "Assign as WooCommerce cart/checkout page" (recipe key `woo_page`). It replaces the page Woo uses,
so say so. `is_cart()` / `is_checkout()` rely on those ids (or shortcodes).

## Cart
Read via `WC()->cart`. Quantity/remove/coupon: either Woo's own cart form + `update_cart`, or AJAX handlers calling `set_quantity()`,
`remove_cart_item()`, `apply_coupon()`, `remove_coupon()` with nonce + `calculate_totals()`, returning fresh fragments and triggering `wc_fragment_refresh`.
Prices through `Lyra_Woo::price()` or `wc_price()`; never assume the currency.

## Checkout (keep Woo's contract or `checkout.js` breaks)
- One `<form name="checkout" method="post" class="checkout woocommerce-checkout" action="…" enctype="multipart/form-data">`.
- Fields rendered from `WC()->checkout()->get_checkout_fields()` with Woo's names (`billing_first_name`, `billing_phone`, `billing_address_1`,
  `billing_state`, `billing_city`, `billing_postcode`, `order_comments`, `shipping_*`, `terms`, `createaccount`).
- Shipping radios `shipping_method[0]`, payment radios `payment_method` inside `.wc_payment_methods`, gateway fields from `payment_fields()`.
- Submit button `#place_order`; nonce `wp_nonce_field('woocommerce-process_checkout','woocommerce-process-checkout-nonce')`.
- Totals refresh through the `woocommerce_update_order_review_fragments` filter / keep `.woocommerce-checkout-review-order-table`.
- Extra fields: `woocommerce_checkout_fields`, validate in `woocommerce_checkout_process`, save with `$order->update_meta_data()` in
  `woocommerce_checkout_create_order` (HPOS-safe; compat is already declared by `Lyra_Plugin::declare_compat`).

## Multi-step designs: decide first
**A (recommended):** one checkout page, step panels inside the single `form.checkout`, stepper widget + JS show/hide. **B:** separate pages
sharing the WC session (more code, more breakage). Cart is always its own page. Tell the user which one you chose.

## Thank-you
Served at `checkout-url/order-received/{id}/?key=wc_order_xxx`. Either thank-you widgets on the checkout page shown only when
`is_wc_endpoint_url('order-received')`, or a separate page + `woocommerce_get_checkout_order_received_url` filter. Load with
`wc_get_order()`, verify the `key` (or ownership) before showing personal data; status UI from `$order->get_status()`/`needs_payment()`.

## Editor demo data
In the Elementor editor with an empty cart/no order return fixed sample data (`Lyra_Woo::use_demo_data()`); never on the live front end.

## Say explicitly what was NOT tested
add to cart, qty change, coupon, shipping change refresh, every gateway, required-field errors, place order, thank-you paid/failed,
guest vs logged-in, mobile sticky bar, RTL. These need a test order on the user's site.
