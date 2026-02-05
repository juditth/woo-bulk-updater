<?php
/**
 * Price Updater Class
 * Handles the logic for updating product prices
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WC_Bulk_Price_Updater
{

    /**
     * Get products by category and optional price filter
     *
     * @param int $category_id Category ID
     * @param string $old_price Optional old price to filter by
     * @return array Array of product IDs
     */
    public function get_products_by_category($category_id, $old_price = '')
    {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ),
            ),
        );

        $query = new WP_Query($args);
        $product_ids = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product_id = get_the_ID();
                $product = wc_get_product($product_id);

                if (!$product) {
                    continue;
                }

                // If old price is specified, filter by it
                if ($old_price !== '') {
                    $current_price = $product->get_regular_price();

                    // For variable products, check variations
                    if ($product->is_type('variable')) {
                        $variations = $product->get_available_variations();
                        $has_matching_price = false;

                        foreach ($variations as $variation) {
                            $variation_obj = wc_get_product($variation['variation_id']);
                            if ($variation_obj && $variation_obj->get_regular_price() == $old_price) {
                                $has_matching_price = true;
                                break;
                            }
                        }

                        if ($has_matching_price) {
                            $product_ids[] = $product_id;
                        }
                    } else {
                        // Simple product
                        if ($current_price == $old_price) {
                            $product_ids[] = $product_id;
                        }
                    }
                } else {
                    // No price filter, add all products
                    $product_ids[] = $product_id;
                }
            }
            wp_reset_postdata();
        }

        return $product_ids;
    }

    /**
     * Get preview of changes
     *
     * @param int $category_id Category ID
     * @param string $old_price Old price (optional)
     * @param string $new_price New price
     * @return array Array of products with preview data
     */
    public function get_preview($category_id, $old_price, $new_price)
    {
        $product_ids = $this->get_products_by_category($category_id, $old_price);
        $preview = array();

        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);

            if (!$product) {
                continue;
            }

            $product_data = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'type' => $product->get_type(),
                'changes' => array(),
            );

            if ($product->is_type('variable')) {
                // Variable product
                $variations = $product->get_available_variations();

                foreach ($variations as $variation) {
                    $variation_obj = wc_get_product($variation['variation_id']);
                    if (!$variation_obj) {
                        continue;
                    }

                    $current_price = $variation_obj->get_regular_price();

                    // Only include if no old_price filter or if it matches
                    if ($old_price === '' || $current_price == $old_price) {
                        $product_data['changes'][] = array(
                            'variation_id' => $variation['variation_id'],
                            'variation_name' => implode(', ', $variation['attributes']),
                            'old_price' => $current_price,
                            'new_price' => $new_price,
                        );
                    }
                }
            } else {
                // Simple product
                $current_price = $product->get_regular_price();

                if ($old_price === '' || $current_price == $old_price) {
                    $product_data['changes'][] = array(
                        'variation_id' => null,
                        'variation_name' => 'Jednoduchý produkt',
                        'old_price' => $current_price,
                        'new_price' => $new_price,
                    );
                }
            }

            // Only add to preview if there are changes
            if (!empty($product_data['changes'])) {
                $preview[] = $product_data;
            }
        }

        return $preview;
    }

    /**
     * Update product prices
     *
     * @param int $category_id Category ID
     * @param string $old_price Old price (optional)
     * @param string $new_price New price
     * @return array Results with success count and errors
     */
    public function update_prices($category_id, $old_price, $new_price)
    {
        $product_ids = $this->get_products_by_category($category_id, $old_price);
        $results = array(
            'success' => 0,
            'errors' => array(),
            'updated_products' => array(),
        );

        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);

            if (!$product) {
                $results['errors'][] = sprintf(__('Produkt ID %d nebyl nalezen', 'woo-bulk-price-editor'), $product_id);
                continue;
            }

            try {
                if ($product->is_type('variable')) {
                    // Update variable product variations
                    $variations = $product->get_available_variations();
                    $updated_variations = 0;

                    foreach ($variations as $variation) {
                        $variation_obj = wc_get_product($variation['variation_id']);
                        if (!$variation_obj) {
                            continue;
                        }

                        $current_price = $variation_obj->get_regular_price();

                        // Update if no old_price filter or if it matches
                        if ($old_price === '' || $current_price == $old_price) {
                            $variation_obj->set_regular_price($new_price);
                            $variation_obj->save();
                            $updated_variations++;
                        }
                    }

                    if ($updated_variations > 0) {
                        $results['success'] += $updated_variations;
                        $results['updated_products'][] = array(
                            'id' => $product_id,
                            'name' => $product->get_name(),
                            'variations' => $updated_variations,
                        );
                    }
                } else {
                    // Update simple product
                    $current_price = $product->get_regular_price();

                    if ($old_price === '' || $current_price == $old_price) {
                        $product->set_regular_price($new_price);
                        $product->save();
                        $results['success']++;
                        $results['updated_products'][] = array(
                            'id' => $product_id,
                            'name' => $product->get_name(),
                            'variations' => 0,
                        );
                    }
                }

                // Clear product cache
                wc_delete_product_transients($product_id);

            } catch (Exception $e) {
                $results['errors'][] = sprintf(
                    __('Chyba při aktualizaci produktu %s: %s', 'woo-bulk-price-editor'),
                    $product->get_name(),
                    $e->getMessage()
                );
            }
        }


        return $results;
    }

    /**
     * Update prices for specific selected products/variations
     *
     * @param array $changes_map Array of product_id => array of variation_ids
     * @param string $new_price New price
     * @return array Results with success count and errors
     */
    /**
     * Update prices and descriptions for specific selected products/variations
     *
     * @param array $changes_map Array of product_id => array of variation_ids
     * @param string $new_price New price (optional)
     * @param string $new_short_description New short description (optional)
     * @param string $new_description New description (optional)
     * @return array Results with success count and errors
     */
    public function update_prices_selective($changes_map, $new_price, $new_short_description = '', $new_description = '')
    {
        $results = array(
            'success' => 0,
            'errors' => array(),
            'updated_products' => array(),
        );

        $batch_count = 0;
        foreach ($changes_map as $product_id => $variation_ids) {
            $product = wc_get_product($product_id);

            // Clear object cache every 50 products to prevent memory issues
            if (++$batch_count % 50 === 0) {
                wp_cache_flush();
            }

            if (!$product) {
                $results['errors'][] = sprintf(__('Produkt ID %d nebyl nalezen', 'woo-bulk-price-editor'), $product_id);
                continue;
            }

            try {
                if ($product->is_type('variable')) {
                    // 1. Update Parent Product Descriptions (if provided)
                    $parent_updated = false;
                    if ($new_short_description !== '') {
                        $product->set_short_description($new_short_description);
                        $parent_updated = true;
                    }
                    if ($new_description !== '') {
                        $product->set_description($new_description);
                        $parent_updated = true;
                    }

                    if ($parent_updated) {
                        $product->save();
                    }

                    // 2. Update Variations (Price only)
                    $updated_variations = 0;

                    foreach ($variation_ids as $variation_id) {
                        if ($variation_id == '0') {
                            continue; // Skip if it's not a variation
                        }

                        $variation_obj = wc_get_product($variation_id);
                        if (!$variation_obj) {
                            continue;
                        }

                        $variation_changed = false;

                        if ($new_price !== '') {
                            $variation_obj->set_regular_price($new_price);
                            $variation_changed = true;
                        }

                        // Note: Variations usually inherit description, so we don't set it on variation level 
                        // unless specifically requested, but here we set it on Parent per user request.

                        if ($variation_changed) {
                            $variation_obj->save();
                            $updated_variations++;
                        }
                    }

                    if ($updated_variations > 0 || $parent_updated) {
                        $results['success'] += ($updated_variations > 0 ? $updated_variations : 1);
                        $results['updated_products'][] = array(
                            'id' => $product_id,
                            'name' => $product->get_name(),
                            'variations' => $updated_variations,
                        );
                    }
                } else {
                    // Update simple product
                    if ($new_price !== '') {
                        $product->set_regular_price($new_price);
                    }

                    if ($new_short_description !== '') {
                        $product->set_short_description($new_short_description);
                    }

                    if ($new_description !== '') {
                        $product->set_description($new_description);
                    }

                    $product->save();
                    $results['success']++;
                    $results['updated_products'][] = array(
                        'id' => $product_id,
                        'name' => $product->get_name(),
                        'variations' => 0,
                    );
                }

                // Clear product cache
                wc_delete_product_transients($product_id);

            } catch (Exception $e) {
                $results['errors'][] = sprintf(
                    __('Chyba při aktualizaci produktu %s: %s', 'woo-bulk-price-editor'),
                    $product->get_name(),
                    $e->getMessage()
                );
            }
        }

        return $results;
    }
}
