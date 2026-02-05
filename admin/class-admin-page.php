<?php
/**
 * Admin Page Class
 * Handles the admin interface for bulk price editing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WC_Bulk_Price_Editor_Admin
{

    /**
     * Price updater instance
     */
    private $price_updater;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->price_updater = new WC_Bulk_Price_Updater();

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_wc_bulk_price_preview', array($this, 'ajax_preview'));
        add_action('wp_ajax_wc_bulk_price_update', array($this, 'ajax_update'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            __('Hromadná úprava cen', 'woo-bulk-price-editor'),
            __('Hromadná úprava cen', 'woo-bulk-price-editor'),
            'manage_woocommerce',
            'wc-bulk-price-editor',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook)
    {
        if ($hook !== 'woocommerce_page_wc-bulk-price-editor') {
            return;
        }

        if (function_exists('wc_enqueue_js')) {
            wp_enqueue_script('wc-enhanced-select');
            wp_enqueue_style('woocommerce_admin_styles');
        }

        wp_enqueue_style(
            'wc-bulk-price-editor-admin',
            WC_BULK_PRICE_EDITOR_PLUGIN_URL . 'admin/css/admin-style.css',
            array(),
            WC_BULK_PRICE_EDITOR_VERSION
        );

        wp_enqueue_script(
            'wc-bulk-price-editor-admin',
            WC_BULK_PRICE_EDITOR_PLUGIN_URL . 'admin/js/admin-script.js',
            array('jquery', 'wc-enhanced-select'),
            WC_BULK_PRICE_EDITOR_VERSION,
            true
        );

        wp_localize_script('wc-bulk-price-editor-admin', 'wcBulkPriceEditor', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_bulk_price_editor_nonce'),
            'strings' => array(
                'confirm_update' => __('Opravdu chcete změnit ceny u vybraných produktů? Tato akce je nevratná.', 'woo-bulk-price-editor'),
                'loading' => __('Načítání...', 'woo-bulk-price-editor'),
                'error' => __('Došlo k chybě. Zkuste to prosím znovu.', 'woo-bulk-price-editor'),
            ),
        ));
    }

    /**
     * Render admin page
     */
    public function render_admin_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Nemáte oprávnění k přístupu na tuto stránku.', 'woo-bulk-price-editor'));
        }

        // Get all product categories
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));

        ?>
        <div class="wrap wc-bulk-price-editor">
            <h1>
                <?php _e('Hromadná úprava cen produktů', 'woo-bulk-price-editor'); ?>
            </h1>

            <div class="wc-bulk-price-editor-container">
                <div class="wc-bulk-price-editor-form">
                    <form id="wc-bulk-price-form">
                        <?php wp_nonce_field('wc_bulk_price_editor_nonce', 'wc_bulk_price_nonce'); ?>

                        <div class="wc-bulk-editor-grid">
                            <!-- Section 1: Filter -->
                            <div class="wc-bulk-editor-section">
                                <h3><?php _e('1. Filtrování produktů (KDE)', 'woo-bulk-price-editor'); ?></h3>

                                <div class="form-row">
                                    <label for="category_id"><?php _e('Kategorie', 'woo-bulk-price-editor'); ?></label>
                                    <select name="category_id" id="category_id" class="wc-category-select" required>
                                        <option value=""><?php _e('Vyhledat kategorii...', 'woo-bulk-price-editor'); ?></option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo esc_attr($category->term_id); ?>">
                                                <?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">
                                        <?php _e('Začněte psát pro vyhledání kategorie.', 'woo-bulk-price-editor'); ?>
                                    </p>
                                </div>

                                <div class="form-row">
                                    <label
                                        for="old_price"><?php _e('Původní cena (volitelné)', 'woo-bulk-price-editor'); ?></label>
                                    <input type="number" name="old_price" id="old_price" step="0.01" min="0">
                                    <p class="description">
                                        <?php _e('Vyplňte pouze pokud chcete filtrovat podle konkrétní ceny.', 'woo-bulk-price-editor'); ?>
                                    </p>
                                </div>

                                <div class="form-row" style="margin-top: 30px;">
                                    <button type="button" id="preview-changes" class="button button-primary button-large"
                                        style="width: 100%;">
                                        <?php _e('🔍 Vyhledat produkty', 'woo-bulk-price-editor'); ?>
                                    </button>
                                </div>
                            </div>

                            <!-- Section 2: Values -->
                            <div class="wc-bulk-editor-section">
                                <h3><?php _e('2. Nové hodnoty (CO)', 'woo-bulk-price-editor'); ?></h3>

                                <div class="form-row">
                                    <label for="new_price"><?php _e('Nová cena', 'woo-bulk-price-editor'); ?></label>
                                    <input type="number" name="new_price" id="new_price" step="0.01" min="0"
                                        placeholder="<?php _e('Ponechat prázdné pro beze změny', 'woo-bulk-price-editor'); ?>">
                                </div>

                                <div class="form-row">
                                    <label for="new_short_description"><?php _e('Krátký popis', 'woo-bulk-price-editor'); ?>
                                        <small>(jen pro jednoduché produkty)</small></label>
                                    <textarea name="new_short_description" id="new_short_description" rows="5"
                                        placeholder="<?php _e('Ponechat prázdné pro beze změny', 'woo-bulk-price-editor'); ?>"></textarea>
                                    <p class="description">
                                        <?php _e('Ponechat prázdné pro beze změny.', 'woo-bulk-price-editor'); ?>
                                    </p>
                                </div>

                                <div class="form-row">
                                    <label for="new_description"><?php _e('Hlavní popis', 'woo-bulk-price-editor'); ?></label>
                                    <?php
                                    wp_editor('', 'new_description', array(
                                        'textarea_name' => 'new_description',
                                        'textarea_rows' => 10,
                                        'media_buttons' => true,
                                    ));
                                    ?>
                                    <p class="description">
                                        <?php _e('Ponechat prázdné pro beze změny.', 'woo-bulk-price-editor'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <p class="submit" style="text-align: right; border-top: 1px solid #ccc; padding-top: 20px;">
                            <button type="button" id="apply-changes" class="button button-primary button-hero" disabled>
                                <?php _e('Aplikovat změny na vybrané produkty', 'woo-bulk-price-editor'); ?>
                            </button>
                        </p>
                    </form>
                </div>

                <div id="preview-container" class="wc-bulk-price-preview" style="display: none;">
                    <h2>
                        <?php _e('Náhled změn', 'woo-bulk-price-editor'); ?>
                    </h2>
                    <div id="preview-content"></div>
                </div>

                <div id="results-container" class="wc-bulk-price-results" style="display: none;">
                    <h2>
                        <?php _e('Výsledky', 'woo-bulk-price-editor'); ?>
                    </h2>
                    <div id="results-content"></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for preview
     */
    public function ajax_preview()
    {
        check_ajax_referer('wc_bulk_price_editor_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Nemáte oprávnění.', 'woo-bulk-price-editor')));
        }

        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $old_price = isset($_POST['old_price']) ? sanitize_text_field($_POST['old_price']) : '';
        $new_price = isset($_POST['new_price']) ? sanitize_text_field($_POST['new_price']) : '';

        // Check if just using as filter or search
        if (!$category_id) {
            wp_send_json_error(array('message' => __('Vyberte alespoň kategorii.', 'woo-bulk-price-editor')));
        }

        // For preview, we just list products based on filter, new_price is optional for preview context
        // But if user wants to see "New Price", they should fill it. 
        // Logic: Preview just shows what will be affected.

        $preview = $this->price_updater->get_preview($category_id, $old_price, $new_price);

        if (empty($preview)) {
            wp_send_json_error(array('message' => __('Nebyly nalezeny žádné produkty odpovídající kritériím.', 'woo-bulk-price-editor')));
        }

        ob_start();
        $this->render_preview_table($preview);
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
            'count' => count($preview),
        ));
    }

    /**
     * AJAX handler for update
     */
    public function ajax_update()
    {
        check_ajax_referer('wc_bulk_price_editor_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Nemáte oprávnění.', 'woo-bulk-price-editor')));
        }

        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $old_price = isset($_POST['old_price']) ? sanitize_text_field($_POST['old_price']) : '';
        $new_price = isset($_POST['new_price']) ? sanitize_text_field($_POST['new_price']) : '';
        $new_short_description = isset($_POST['new_short_description']) ? wp_kses_post($_POST['new_short_description']) : '';
        $new_description = isset($_POST['new_description']) ? wp_kses_post($_POST['new_description']) : '';
        $selected_changes = isset($_POST['selected_changes']) ? $_POST['selected_changes'] : array();

        if (!$category_id || empty($selected_changes) || (!$new_price && !$new_short_description && !$new_description)) {
            wp_send_json_error(array('message' => __('Vyplňte alespoň jednu hodnotu ke změně a vyberte produkty.', 'woo-bulk-price-editor')));
        }

        // Parse selected changes into array of product_id => variation_ids
        $changes_map = array();
        foreach ($selected_changes as $change) {
            list($product_id, $variation_id) = explode(':', $change);
            if (!isset($changes_map[$product_id])) {
                $changes_map[$product_id] = array();
            }
            $changes_map[$product_id][] = $variation_id;
        }

        $results = $this->price_updater->update_prices_selective($changes_map, $new_price, $new_short_description, $new_description);

        ob_start();
        $this->render_results($results);
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
            'success_count' => $results['success'],
        ));
    }

    /**
     * Render preview table
     */
    private function render_preview_table($preview)
    {
        $total_changes = 0;
        foreach ($preview as $product) {
            $total_changes += count($product['changes']);
        }
        ?>
        <div class="notice notice-info">
            <p>
                <strong><?php printf(__('Nalezeno %d produktů s celkem %d změnami cen.', 'woo-bulk-price-editor'), count($preview), $total_changes); ?></strong>
            </p>
            <p>
                <?php _e('Odškrtněte produkty/varianty, které NECHCETE změnit. Výchozí jsou všechny vybrané.', 'woo-bulk-price-editor'); ?>
            </p>
        </div>

        <?php if ($total_changes > 100): ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php _e('Upozornění:', 'woo-bulk-price-editor'); ?></strong>
                    <?php printf(__('Budete měnit %d cen. Aktualizace může trvat několik minut. Prosím neopouštějte stránku během zpracování.', 'woo-bulk-price-editor'), $total_changes); ?>
                </p>
            </div>
        <?php endif; ?>

        <p>
            <label>
                <input type="checkbox" id="select-all-changes" checked>
                <strong><?php _e('Vybrat všechny změny', 'woo-bulk-price-editor'); ?></strong>
            </label>
        </p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" class="select-all-checkbox" checked>
                    </th>
                    <th><?php _e('Produkt', 'woo-bulk-price-editor'); ?></th>
                    <th><?php _e('Typ', 'woo-bulk-price-editor'); ?></th>
                    <th><?php _e('Varianta', 'woo-bulk-price-editor'); ?></th>
                    <th><?php _e('Původní cena', 'woo-bulk-price-editor'); ?></th>
                    <th><?php _e('Nová cena', 'woo-bulk-price-editor'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($preview as $product): ?>
                    <?php foreach ($product['changes'] as $index => $change): ?>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" class="change-checkbox" name="selected_changes[]"
                                    value="<?php echo esc_attr($product['id'] . ':' . ($change['variation_id'] ?? '0')); ?>"
                                    data-product-id="<?php echo esc_attr($product['id']); ?>"
                                    data-variation-id="<?php echo esc_attr($change['variation_id'] ?? '0'); ?>" checked>
                            </th>
                            <?php if ($index === 0): ?>
                                <td rowspan="<?php echo count($product['changes']); ?>">
                                    <strong><a href="<?php echo get_edit_post_link($product['id']); ?>"
                                            target="_blank"><?php echo esc_html($product['name']); ?></a></strong><br>
                                    <small>ID: <?php echo $product['id']; ?></small>
                                </td>
                                <td rowspan="<?php echo count($product['changes']); ?>">
                                    <?php echo $product['type'] === 'variable' ? __('Variantní', 'woo-bulk-price-editor') : __('Jednoduchý', 'woo-bulk-price-editor'); ?>
                                </td>
                            <?php endif; ?>
                            <td><?php echo esc_html($change['variation_name']); ?></td>
                            <td><?php echo wc_price($change['old_price']); ?></td>
                            <td><strong><?php echo wc_price($change['new_price']); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render results
     */
    private function render_results($results)
    {
        if ($results['success'] > 0) {
            ?>
            <div class="notice notice-success">
                <p>
                    <strong>
                        <?php printf(__('Úspěšně aktualizováno %d cen!', 'woo-bulk-price-editor'), $results['success']); ?>
                    </strong>
                </p>
            </div>

            <h3>
                <?php _e('Aktualizované produkty:', 'woo-bulk-price-editor'); ?>
            </h3>
            <ul>
                <?php foreach ($results['updated_products'] as $product): ?>
                    <li>
                        <strong>
                            <a href="<?php echo get_edit_post_link($product['id']); ?>"
                                target="_blank"><?php echo esc_html($product['name']); ?></a>
                        </strong> (ID:
                        <?php echo $product['id']; ?>)
                        <?php if ($product['variations'] > 0): ?>
                            -
                            <?php printf(__('%d variant', 'woo-bulk-price-editor'), $product['variations']); ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }

        if (!empty($results['errors'])) {
            ?>
            <div class="notice notice-error">
                <p><strong>
                        <?php _e('Chyby:', 'woo-bulk-price-editor'); ?>
                    </strong></p>
                <ul>
                    <?php foreach ($results['errors'] as $error): ?>
                        <li>
                            <?php echo esc_html($error); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php
        }
    }
}
