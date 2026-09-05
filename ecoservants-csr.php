<?php
/*
Plugin Name: EcoServants CSR
Description: A plugin for environmental volunteers to submit Community Site Reports (CSR) after cleanup events.
Version: 1.2.0
By: EcoServants - Thanks to our developers
Author URI: https://ecoservantsproject.org
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('ECOSERVANTS_CSR_VERSION')) {
    define('ECOSERVANTS_CSR_VERSION', '1.2.0');
}

// Register custom post type for CSR Reports with locked-down capabilities
function ecoservants_register_csr_post_type() {
    register_post_type('csr_report', [
        'labels' => [
            'name'          => __('CSR Reports'),
            'singular_name' => __('CSR Report'),
            'add_new'       => __('Add New Report'),
            'add_new_item'  => __('Add New CSR Report'),
            'edit_item'     => __('Edit CSR Report'),
            'new_item'      => __('New CSR Report'),
            'view_item'     => __('View CSR Report'),
            'all_items'     => __('All CSR Reports'),
        ],

        // Keep CSR reports from being publicly browsable
        'public'              => false,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'has_archive'         => false,
        'rewrite'             => false,
        'query_var'           => false,

        // Admin UI controlled by capabilities
        'show_ui'      => true,
        'show_in_menu' => true,
        'show_in_rest' => false,

        'supports'  => ['title'],
        'menu_icon' => 'dashicons-clipboard',

        // Custom caps
        'capability_type' => ['csr_report', 'csr_reports'],
        'map_meta_cap'    => true,
    ]);
}
add_action('init', 'ecoservants_register_csr_post_type');

// Grant CSR caps to administrators on activation
function ecoservants_csr_add_caps() {
    $role = get_role('administrator');
    if (!$role) {
        return;
    }

    $caps = [
        'read_csr_report',
        'read_private_csr_reports',
        'edit_csr_report',
        'edit_csr_reports',
        'edit_others_csr_reports',
        'publish_csr_reports',
        'delete_csr_report',
        'delete_csr_reports',
        'delete_others_csr_reports',
        // additional caps for published/private csr_report objects
        'edit_published_csr_reports',
        'delete_published_csr_reports',
        'delete_private_csr_reports',
        'edit_private_csr_reports',
    ];

    foreach ($caps as $cap) {
        $role->add_cap($cap);
    }
}
register_activation_hook(__FILE__, 'ecoservants_csr_add_caps');

// Lightweight JSON endpoint for Top Impact Metrics
// Example (top only): https://esp-university.earth/csr/?top_impact_json=1&limit=4&min=0
// Example (all metrics): https://esp-university.earth/csr/?top_impact_json=1&all=1
add_action('init', function () {
    if (!isset($_GET['top_impact_json'])) {
        return;
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'GET' && $method !== 'OPTIONS') {
        status_header(405);
        exit;
    }

    // --- CORS: allow EcoServants domains (add others if needed) ---
    $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = [
        'https://ecoservantsproject.org',
        'https://www.ecoservantsproject.org',
        'https://esp-university.earth',
    ];

    // Preflight
    if ($method === 'OPTIONS') {
        if (in_array($origin, $allowed, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        exit;
    }

    if (in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    header('Vary: Origin');
    nocache_headers();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Content-Type: application/json; charset=' . get_option('blog_charset'));

    // Allow simple tuning via query string
    $limit = isset($_GET['limit']) ? max(1, (int) $_GET['limit']) : 6;
    $min   = isset($_GET['min'])   ? (float) $_GET['min']         : 0.0;
    $all   = isset($_GET['all'])   ? (bool) $_GET['all']          : false;

    $data        = ecoservants_calculate_totals();
    $totals      = $data['totals'];
    $item_counts = $data['item_counts'];

    $metric_defs = [
        'plastic_waste'             => ['label' => 'Plastic Waste',                              'unit' => 'lbs'],
        'paper_waste'               => ['label' => 'Paper Waste',                                'unit' => 'lbs'],
        'metal_waste'               => ['label' => 'Metal Waste',                                'unit' => 'lbs'],
        'glass_waste'               => ['label' => 'Glass Waste',                                'unit' => 'lbs'],
        'food_waste'                => ['label' => 'Food Waste',                                 'unit' => 'lbs'],
        'cigarette_litter'          => ['label' => 'Cigarette Litter',                           'unit' => 'lbs'],
        'textiles'                  => ['label' => 'Textiles',                                   'unit' => 'lbs'],
        'medical_waste'             => ['label' => 'Medical Waste',                              'unit' => 'lbs'],
        'sanitary_products'         => ['label' => 'Sanitary Products',                          'unit' => 'lbs'],
        'fishing_gear'              => ['label' => 'Fishing Gear',                               'unit' => 'lbs'],
        'styrofoam_hazardous_waste' => ['label' => 'Styrofoam & Hazardous Waste',                'unit' => 'lbs'],
        'miscellaneous'             => ['label' => 'Miscellaneous',                              'unit' => 'lbs'],
        'derelict_items'            => ['label' => 'Derelict Items',                             'unit' => 'lbs'],
        'unsorted_litter'           => ['label' => 'Unsorted Litter',                            'unit' => 'lbs'],
        'trees_planted'             => ['label' => 'Trees Planted',                              'unit' => ''],
        'invasive_species_removed'  => ['label' => 'Invasive Species Removed (sq ft)',           'unit' => ''],
        'invasive_species_weight'   => ['label' => 'Weight of Invasive Species Collected',       'unit' => 'lbs'],
        'volunteers_involved'       => ['label' => 'Volunteers Involved',                        'unit' => ''],
        'bags_collected'            => ['label' => 'Number of Unsorted Bags Collected',          'unit' => ''],
    ];

    // Build list of metrics from totals
    $metrics = [];
    foreach ($metric_defs as $key => $def) {
        $value = isset($totals[$key]) ? (float) $totals[$key] : 0.0;
        if (!$all && $value <= $min) {
            continue;
        }
        $formatted = is_numeric($value)
            ? number_format($value, ($def['unit'] === 'lbs' ? 1 : 0))
            : $value;

        $metrics[] = [
            'key'             => $key,
            'label'           => $def['label'],
            'value'           => $value,
            'value_formatted' => $formatted,
            'unit'            => $def['unit'],
        ];
    }

    // Sort + limit only for "top" view
    if (!$all) {
        usort($metrics, function ($a, $b) {
            if ($a['value'] === $b['value']) return 0;
            return ($a['value'] < $b['value']) ? 1 : -1;
        });
        $metrics = array_slice($metrics, 0, $limit);
    }

    // Optionally include item counts
    $include_counts = isset($_GET['include_counts']) ? (bool) $_GET['include_counts'] : false;
    $counts = null;
    if ($include_counts) {
        // Map counts to labels for convenience
        $count_labels = [
            'plastic_items'      => 'Plastic Items',
            'paper_items'        => 'Paper Items',
            'metal_items'        => 'Metal Items',
            'glass_items'        => 'Glass Items',
            'food_items'         => 'Food Items',
            'cigarette_items'    => 'Cigarette Items',
            'textiles_items'     => 'Textiles Items',
            'medical_items'      => 'Medical Items',
            'sanitary_items'     => 'Sanitary Items',
            'fishing_items'      => 'Fishing Gear Items',
            'styrofoam_items'    => 'Styrofoam Items',
            'miscellaneous_items'=> 'Miscellaneous Items',
            'derelict_items'     => 'Derelict Items',
        ];
        $counts = [];
        foreach ($item_counts as $k => $v) {
            $counts[] = [
                'key'   => $k,
                'label' => $count_labels[$k] ?? ucwords(str_replace('_', ' ', $k)),
                'value' => (int) $v,
                'unit'  => '',
            ];
        }
    }

    $response = [
        'metrics' => $metrics,
    ];

    if ($all) {
        $response['totals'] = $totals;
    }
    if ($include_counts) {
        $response['item_counts'] = $counts;
    }

    wp_send_json($response);
});

/**
 * Lightweight JSON endpoint for Wall of Fame
 */
add_action('init', function () {
    if (!isset($_GET['wof_json'])) { return; }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'GET' && $method !== 'OPTIONS') {
        status_header(405);
        exit;
    }

    // --- Allowed origins (edit if you add more) ---
    $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = [
    'https://ecoservantsproject.org',
    'https://www.ecoservantsproject.org',
    'https://esp-university.earth',
];

    // --- CORS preflight (OPTIONS) ---
    if ($method === 'OPTIONS') {
        if (in_array($origin, $allowed, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        exit;
    }

    // --- Actual response headers ---
    if (in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    header('Vary: Origin');
    nocache_headers();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Content-Type: application/json; charset=' . get_option('blog_charset'));

    // Query latest CSR reports
    $q = new WP_Query([
        'post_type'      => 'csr_report',
        'posts_per_page' => 50,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
        'fields'         => 'ids',
    ]);

    // Helper to build photo URLs
    $photo_urls = function ($post_id) {
        $ids_csv = get_post_meta($post_id, 'csr_photos', true);
        if (!$ids_csv) return [];
        $ids = array_filter(array_map('trim', explode(',', $ids_csv)));
        $out = [];
        foreach ($ids as $aid) {
            $thumb = wp_get_attachment_image_url((int) $aid, 'medium');
            $full  = wp_get_attachment_url((int) $aid);
            if ($thumb || $full) {
                $out[] = ['thumb' => $thumb ?: $full, 'full' => $full ?: $thumb];
            }
        }
        return $out;
    };

    // Build payload
    $items = [];
    foreach ($q->posts as $pid) {
        $name     = get_post_meta($pid, 'csr_name', true) ?: 'EcoServants Volunteer';
        $location = get_post_meta($pid, 'csr_location', true) ?: '';

        $cats = [
            'plastic_waste_weight','paper_waste_weight','metal_waste_weight','glass_waste_weight',
            'food_waste_weight','cigarette_litter_weight','textiles_weight','medical_waste_weight',
            'sanitary_products_weight','fishing_gear_weight','styrofoam_hazardous_waste_weight',
            'miscellaneous_weight','derelict_items_weight'
        ];

        $total = (float) get_post_meta($pid, 'csr_unsorted_litter_weight', true);
        foreach ($cats as $ck) {
            $total += (float) get_post_meta($pid, 'csr_' . $ck, true);
        }

        $items[] = [
            'name'     => $name,
            'location' => $location,
            'metric'   => number_format($total, 2) . ' lbs',
            'photos'   => $photo_urls($pid),
            // 'permalink' intentionally omitted for privacy
            'date'     => get_the_date('c', $pid),
        ];
    }

    shuffle($items);
    wp_send_json(['items' => $items]);
});

/**
 * Helper function to retrieve standardized SVG icon markup for a CSR category.
 *
 * @param string $category_key Category key or alias (e.g. 'plastic', 'plastic_waste', 'styrofoam_hazardous', etc.)
 * @param array  $args         Optional settings: 'class', 'size', 'aria_hidden', 'alt'
 * @return string SVG HTML markup
 */
function csr_get_category_icon_svg( $category_key, $args = [] ) {
    static $svg_cache = [];

    $key_map = [
        'unsorted_litter'           => 'unsorted-litter',
        'unsorted'                  => 'unsorted-litter',
        'plastic'                   => 'plastic',
        'plastic_waste'             => 'plastic',
        'paper'                     => 'paper',
        'paper_waste'               => 'paper',
        'food'                      => 'food',
        'food_waste'                => 'food',
        'metal'                     => 'metal',
        'metal_waste'               => 'metal',
        'glass'                     => 'glass',
        'glass_waste'               => 'glass',
        'cigarette'                 => 'cigarette',
        'cigarette_litter'          => 'cigarette',
        'textiles'                  => 'textiles',
        'medical'                   => 'medical',
        'medical_waste'             => 'medical',
        'sanitary'                  => 'sanitary',
        'sanitary_products'         => 'sanitary',
        'fishing'                   => 'fishing',
        'fishing_gear'              => 'fishing',
        'styrofoam_hazardous'       => 'styrofoam-hazardous',
        'styrofoam_hazardous_waste' => 'styrofoam-hazardous',
        'miscellaneous'             => 'miscellaneous',
        'derelict'                  => 'derelict',
        'derelict_items'            => 'derelict',
        'invasive'                  => 'invasive-species',
        'invasive_species'          => 'invasive-species',
        'invasive_species_removed'  => 'invasive-species',
        'invasive_species_weight'   => 'invasive-species',
        'invasive-species'          => 'invasive-species',
    ];

    $slug = isset($key_map[$category_key]) ? $key_map[$category_key] : sanitize_title($category_key);
    $icon_path = plugin_dir_path(__FILE__) . 'assets/icons/' . $slug . '.svg';

    if (!isset($svg_cache[$slug])) {
        if (file_exists($icon_path)) {
            $svg_cache[$slug] = file_get_contents($icon_path);
        } else {
            $svg_cache[$slug] = '';
        }
    }

    $svg = $svg_cache[$slug];

    if (empty($svg)) {
        return '';
    }

    $class       = isset($args['class']) ? esc_attr($args['class']) : 'csr-category-icon-svg';
    $size        = isset($args['size']) ? (int) $args['size'] : 0;
    $aria_hidden = isset($args['aria_hidden']) ? (bool) $args['aria_hidden'] : true;
    $alt         = isset($args['alt']) ? esc_attr($args['alt']) : '';

    if ($class !== 'csr-category-icon-svg') {
        $svg = preg_replace('/class="[^"]*"/', '', $svg);
        $svg = str_replace('<svg ', '<svg class="' . $class . '" ', $svg);
    } else {
        $svg = str_replace('<svg ', '<svg class="csr-category-icon-svg" ', $svg);
    }

    if ($size > 0) {
        $svg = preg_replace('/width="[^"]*"/', 'width="' . $size . '"', $svg);
        $svg = preg_replace('/height="[^"]*"/', 'height="' . $size . '"', $svg);
    }

    if ($aria_hidden) {
        $svg = str_replace('<svg ', '<svg aria-hidden="true" role="img" ', $svg);
    } else if ($alt !== '') {
        $svg = str_replace('<svg ', '<svg role="img" aria-label="' . $alt . '" ', $svg);
    }

    return $svg;
}

/**
 * Helper function to return all major category icon SVGs mapped by key.
 *
 * @return array
 */
function csr_get_all_category_icons() {
    $categories = [
        'unsorted_litter', 'plastic', 'paper', 'food', 'metal', 'glass',
        'cigarette', 'textiles', 'medical', 'sanitary', 'fishing',
        'styrofoam_hazardous', 'miscellaneous', 'derelict', 'invasive_species'
    ];
    $icons = [];
    foreach ($categories as $cat) {
        $icons[$cat] = csr_get_category_icon_svg($cat);
    }
    return $icons;
}

// Enqueue scripts and styles
function ecoservants_enqueue_scripts() {
    $version = defined('ECOSERVANTS_CSR_VERSION') ? ECOSERVANTS_CSR_VERSION : '1.1.8-category-icons';
    wp_enqueue_style('ecoservants-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), $version);
    wp_enqueue_script('ecoservants-carousel', plugin_dir_url(__FILE__) . 'assets/js/carousel.js', array(), $version, true);
    wp_enqueue_script('csr-guided-wrapper', plugin_dir_url(__FILE__) . 'assets/js/csr-guided-wrapper.js', array(), $version, true);
    wp_enqueue_script('csr-category-cards', plugin_dir_url(__FILE__) . 'assets/js/csr-category-cards.js', array(), $version, true);
    wp_enqueue_script('csr-wall-modal', plugin_dir_url(__FILE__) . 'assets/js/csr-wall-modal.js', array(), $version, true);
}
add_action('wp_enqueue_scripts', 'ecoservants_enqueue_scripts');

// Enqueue admin styles, icon assets, and review console stylesheet
function ecoservants_admin_enqueue_scripts($hook) {
    global $post_type;
    $is_csr_report = ($post_type === 'csr_report');
    $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
    $is_csr_page = ($page && (strpos($page, 'csr') !== false || in_array($page, ['total-impact-metrics', 'csr-yearly-export'], true)));

    if ($is_csr_report || $is_csr_page) {
        $version = defined('ECOSERVANTS_CSR_VERSION') ? ECOSERVANTS_CSR_VERSION : '1.2.0';
        wp_enqueue_style('ecoservants-admin-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), $version);
        wp_enqueue_style('ecoservants-admin-review-css', plugin_dir_url(__FILE__) . 'assets/css/admin-review.css', array('ecoservants-admin-style'), $version);
    }
}
add_action('admin_enqueue_scripts', 'ecoservants_admin_enqueue_scripts');

// Include form template and handler
include plugin_dir_path(__FILE__) . 'form-handler.php';

// Include public CSR impact summary shortcode (Issue #22)
include plugin_dir_path(__FILE__) . 'csr-impact-summary.php';

// Register shortcode for the form
function ecoservants_csr_form_shortcode() {
    if (function_exists('ecoservants_enqueue_scripts')) { ecoservants_enqueue_scripts(); }
    $submitted = isset($_GET['submitted']) && sanitize_text_field(wp_unslash($_GET['submitted'])) === 'true';
    $report_id = isset($_GET['csr_report_id']) ? absint($_GET['csr_report_id']) : 0;

    ob_start();

    if ($submitted) {
        ?>
        <section class="csr-submission-complete" aria-labelledby="csr-submission-complete-title">
            <div class="csr-submission-complete__icon" aria-hidden="true">✓</div>
            <p class="csr-submission-complete__kicker">Community Site Report Recorded</p>
            <h2 id="csr-submission-complete-title">Thank you for making an environmental impact.</h2>
            <p>Your report has been submitted successfully and added to the EcoServants® Community Site Report record<?php echo $report_id ? ' as report #' . esc_html($report_id) : ''; ?>.</p>
            <p class="csr-submission-complete__note">Your contribution may now appear below as part of the EcoServants® Wall of Fame.</p>
            <div class="csr-submission-complete__actions">
                <a class="csr-wall-action csr-wall-action--primary" href="#csr-completion-wall">View the Wall of Fame</a>
                <a class="csr-wall-action csr-wall-action--secondary" href="<?php echo esc_url(add_query_arg('csr_new', '1', remove_query_arg(['submitted', 'csr_report_id', 'csr_error']))); ?>">Submit Another Report</a>
            </div>
        </section>

        <section id="csr-completion-wall" class="csr-completion-wall" aria-labelledby="csr-completion-wall-title">
            <div class="csr-completion-wall__heading">
                <p>Community Recognition</p>
                <h2 id="csr-completion-wall-title">EcoServants® Wall of Fame</h2>
                <span>Celebrating volunteers, teams, and organizations turning environmental responsibility into action.</span>
            </div>
            <?php echo ecoservants_wall_of_fame_shortcode(['limit' => 50]); ?>
        </section>
        <?php
        return ob_get_clean();
    }

    include plugin_dir_path(__FILE__) . 'form-template.php';
    ?>
    <div class="csr-wall-link-row">
        <button type="button" class="csr-wall-link" data-csr-wall-open aria-haspopup="dialog" aria-controls="csr-wall-modal">
            View the EcoServants® Wall of Fame
        </button>
    </div>

    <div id="csr-wall-modal" class="csr-wall-modal" aria-hidden="true">
        <div class="csr-wall-modal__backdrop" data-csr-wall-close></div>
        <div class="csr-wall-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="csr-wall-modal-title">
            <button type="button" class="csr-wall-modal__close" data-csr-wall-close aria-label="Close Wall of Fame">×</button>
            <div class="csr-wall-modal__heading">
                <p>Community Recognition</p>
                <h2 id="csr-wall-modal-title">EcoServants® Wall of Fame</h2>
                <span>See how volunteers and partners are creating measurable environmental impact.</span>
            </div>
            <div class="csr-wall-modal__content">
                <?php echo ecoservants_wall_of_fame_shortcode(['limit' => 50]); ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('csr_form', 'ecoservants_csr_form_shortcode');

// Register shortcode for the Wall of Fame
function ecoservants_wall_of_fame_shortcode($atts = []) {
    if (function_exists('ecoservants_enqueue_scripts')) { ecoservants_enqueue_scripts(); }
    $atts = shortcode_atts(
        [
            'limit' => 50,
        ],
        $atts,
        'wall_of_fame'
    );

    $limit = (int) $atts['limit'];
    if ($limit < 1) {
        $limit = 1;
    } elseif ($limit > 200) {
        $limit = 200;
    }

    $args = array(
        'post_type'      => 'csr_report',
        'posts_per_page' => $limit,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    $query = new WP_Query($args);
    ob_start();
    if ($query->have_posts()) {
        echo '<div class="wall-of-fame">';
        echo '<h2>Wall of Fame</h2>';
        echo '<div class="wall-of-fame-container">';
        echo '<div class="wall-of-fame-carousel">'; // Add a scrollable container
        while ($query->have_posts()) {
            $query->the_post();
            $name = get_post_meta(get_the_ID(), 'csr_name', true);
            $location = get_post_meta(get_the_ID(), 'csr_location', true);
            $photos = get_post_meta(get_the_ID(), 'csr_photos', true);
            $photos_array = $photos ? explode(',', $photos) : [];
            $trees_planted = intval(get_post_meta(get_the_ID(), 'csr_trees_planted', true));
            $invasive_species_removed = intval(get_post_meta(get_the_ID(), 'csr_invasive_species_removed', true));
            $invasive_species_weight = floatval(get_post_meta(get_the_ID(), 'csr_invasive_species_weight', true));
            $unsorted_litter_weight = floatval(get_post_meta(get_the_ID(), 'csr_unsorted_litter_weight', true));
            $unsorted_litter_subcategories_count = json_decode(get_post_meta(get_the_ID(), 'csr_unsorted_litter_subcategories_count', true), true);
            $bags_collected = isset($unsorted_litter_subcategories_count['Number of Unsorted Bags Collected']) ? intval($unsorted_litter_subcategories_count['Number of Unsorted Bags Collected']) : 0;

            echo '<div class="wall-of-fame-entry">';
            echo '<div class="card">';
            echo '<h3>' . esc_html($name) . '</h3>';
            echo '<p class="location">Location: ' . esc_html($location) . '</p>';

            // Display Habitat Restoration metrics if available
            if ($trees_planted > 0) {
                echo '<p class="total-weight">Trees Planted: <span>' . esc_html(number_format($trees_planted)) . '</span></p>';
            }
            if ($invasive_species_removed > 0) {
                echo '<p class="total-weight">Square Feet of Invasive Species Removed: <span>' . esc_html(number_format($invasive_species_removed)) . '</span></p>';
            }
            if ($invasive_species_weight > 0) {
                echo '<p class="total-weight">Weight of Invasive Species Collected: <span>' . esc_html(number_format($invasive_species_weight, 2)) . ' lbs</span></p>';
            }
            if ($unsorted_litter_weight > 0) {
                echo '<p class="total-weight">Unsorted Litter: <span>' . esc_html(number_format($unsorted_litter_weight, 2)) . ' lbs</span></p>';
            }
            if ($bags_collected > 0) {
                echo '<p class="total-weight">Number of Unsorted Bags Collected: <span>' . esc_html(number_format($bags_collected)) . '</span></p>';
            }

            // Calculate and display total litter weight using canonical meta keys
            $total_weight = 0;
            $weight_meta_keys = [
                'csr_plastic_waste_weight',
                'csr_paper_waste_weight',
                'csr_metal_waste_weight',
                'csr_glass_waste_weight',
                'csr_food_waste_weight',
                'csr_cigarette_litter_weight',
                'csr_textiles_weight',
                'csr_medical_waste_weight',
                'csr_sanitary_products_weight',
                'csr_fishing_gear_weight',
                'csr_styrofoam_hazardous_waste_weight',
                'csr_miscellaneous_weight',
                'csr_derelict_items_weight',
            ];
            foreach ($weight_meta_keys as $meta_key) {
                $weight = get_post_meta(get_the_ID(), $meta_key, true);
                if ($weight !== false && $weight !== '') {
                    $total_weight += floatval($weight);
                }
            }
            if ($total_weight > 0) {
                echo '<p class="total-weight">Total Sorted Litter Picked Up: <span>' . esc_html(number_format($total_weight, 2)) . ' lbs</span></p>';
            }

            if (!empty($photos_array)) {
                echo '<div class="photos">';
                foreach ($photos_array as $photo_id) {
                    $photo_url = wp_get_attachment_image_url($photo_id, 'thumbnail'); // Retrieve thumbnail size
                    $full_url = wp_get_attachment_url($photo_id); // Full size
                    if ($photo_url && $full_url) {
                        echo '<img src="' . esc_url($photo_url) . '" alt="Uploaded Photo" class="wall-of-fame-photo" data-full="' . esc_url($full_url) . '">';
                    }
                }
                echo '</div>';
            }
            echo '<p class="highlight">Thank you for making a difference!</p>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>'; // Close carousel container
        echo '</div>'; // Close container
    } else {
        echo '<p>No entries found.</p>';
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('wall_of_fame', 'ecoservants_wall_of_fame_shortcode');

// Add a custom endpoint for the Wall of Fame iframe (auto-resizes via postMessage)
function ecoservants_wall_of_fame_iframe_endpoint() {
    if (!isset($_GET['wall_of_fame_iframe'])) return;

    header('Content-Type: text/html; charset=' . get_option('blog_charset'));
    ?>
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="<?php echo esc_attr(get_option('blog_charset')); ?>">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link rel="stylesheet" href="<?php echo esc_url(plugin_dir_url(__FILE__) . 'assets/css/style.css'); ?>">
      <style> body{margin:0;padding:16px;background:#fff}.wall-of-fame{max-width:1200px;margin:0 auto} </style>
    </head>
    <body>
      <?php echo do_shortcode('[wall_of_fame]'); ?>
      <script>
        (function () {
          var allowedOrigins = [[
            'https://ecoservantsproject.org',
            'https://www.ecoservantsproject.org',
            'https://esp-university.earth'
          ], 'https://shop.ecoservantsproject.org'];

          function getTargetOrigin() {
            var ref = document.referrer || '';
            for (var i = 0; i < allowedOrigins.length; i++) {
              if (ref.indexOf(allowedOrigins[i]) === 0) {
                return allowedOrigins[i];
              }
            }
            return null;
          }

          function sendHeight(){
            var origin = getTargetOrigin();
            if (!origin) return;
            var h = document.documentElement.scrollHeight || document.body.scrollHeight || 700;
            parent.postMessage({type:'wofHeight',height:h}, origin);
          }
          window.addEventListener('load', sendHeight);
          try {
            var ro = new ResizeObserver(sendHeight);
            ro.observe(document.documentElement); ro.observe(document.body);
          } catch(e) { setInterval(sendHeight, 800); }
          document.addEventListener('readystatechange', sendHeight);
          document.addEventListener('DOMContentLoaded', sendHeight);
        })();
      </script>
    </body>
    </html>
    <?php
    exit;
}
add_action('init', 'ecoservants_wall_of_fame_iframe_endpoint');

// Add a custom endpoint for the CSR form iframe
function ecoservants_csr_form_iframe_endpoint() {
    if (isset($_GET['csr_form_iframe'])) {
        $version = defined('ECOSERVANTS_CSR_VERSION') ? ECOSERVANTS_CSR_VERSION : '1.1.8-category-icons';
        $css_url = plugin_dir_url(__FILE__) . 'assets/css/style.css?v=' . urlencode($version);
        $carousel_url = plugin_dir_url(__FILE__) . 'assets/js/carousel.js?v=' . urlencode($version);
        $wrapper_url = plugin_dir_url(__FILE__) . 'assets/js/csr-guided-wrapper.js?v=' . urlencode($version);
        $cards_url = plugin_dir_url(__FILE__) . 'assets/js/csr-category-cards.js?v=' . urlencode($version);
        $modal_url = plugin_dir_url(__FILE__) . 'assets/js/csr-wall-modal.js?v=' . urlencode($version);

        header('Content-Type: text/html; charset=' . get_option('blog_charset', 'UTF-8'));
        echo '<!DOCTYPE html><html><head>';
        echo '<meta charset="' . esc_attr(get_option('blog_charset', 'UTF-8')) . '">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<link rel="stylesheet" href="' . esc_url($css_url) . '">';
        echo '</head><body>';
        echo do_shortcode('[csr_form]');
        echo '<script src="' . esc_url($carousel_url) . '"></script>';
        echo '<script src="' . esc_url($wrapper_url) . '"></script>';
        echo '<script src="' . esc_url($cards_url) . '"></script>';
        echo '<script src="' . esc_url($modal_url) . '"></script>';
        echo '</body></html>';
        exit;
    }
}

// Add a custom endpoint for the Total Impact Metrics iframe
function ecoservants_total_impact_iframe_endpoint() {
    if (isset($_GET['total_impact_iframe'])) {
        header('Content-Type: text/html');
        echo '<!DOCTYPE html><html><head>';
        echo '<link rel="stylesheet" href="' . plugin_dir_url(__FILE__) . 'assets/css/style.css">';
        echo '</head><body>';
        echo do_shortcode('[total_impact]');
        echo '</body></html>';
        exit;
    }
}

add_action('init', 'ecoservants_csr_form_iframe_endpoint');
add_action('init', 'ecoservants_total_impact_iframe_endpoint');

// Add a custom endpoint for the Top Impact Metrics iframe
function ecoservants_top_impact_iframe_endpoint() {
    if ( ! isset($_GET['top_impact_iframe']) ) {
        return;
    }

    header('Content-Type: text/html; charset=' . get_option('blog_charset'));
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="<?php echo esc_attr(get_option('blog_charset')); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="<?php echo esc_url( plugin_dir_url(__FILE__) . 'assets/css/style.css' ); ?>">
        <style>
            body{margin:0;padding:16px;background:#fff}
            .total-impact-metrics{max-width:1200px;margin:0 auto}
        </style>
    </head>
    <body>
        <?php echo do_shortcode('[top_impact]'); ?>
        <script>
        (function () {
            var allowedOrigins = [[
                'https://ecoservantsproject.org',
                'https://www.ecoservantsproject.org',
                'https://esp-university.earth'
            ], 'https://shop.ecoservantsproject.org'];

            function getTargetOrigin() {
                var ref = document.referrer || '';
                for (var i = 0; i < allowedOrigins.length; i++) {
                    if (ref.indexOf(allowedOrigins[i]) === 0) {
                        return allowedOrigins[i];
                    }
                }
                return null;
            }

            function sendHeight() {
                var origin = getTargetOrigin();
                if (!origin) return;
                var h = document.documentElement.scrollHeight || document.body.scrollHeight || 700;
                parent.postMessage({ type: 'topImpactHeight', height: h }, origin);
            }
            window.addEventListener('load', sendHeight);
            try {
                var ro = new ResizeObserver(sendHeight);
                ro.observe(document.documentElement);
                ro.observe(document.body);
            } catch(e) {
                setInterval(sendHeight, 800);
            }
            document.addEventListener('readystatechange', sendHeight);
            document.addEventListener('DOMContentLoaded', sendHeight);
        })();
        </script>
    </body>
    </html>
    <?php
    exit;
}
add_action('init', 'ecoservants_top_impact_iframe_endpoint');

// Calculate total waste weight (lbs) for a single CSR report post
function ecoservants_get_post_total_weight($post_id) {
    $weight_keys = [
        'csr_plastic_waste_weight',
        'csr_paper_waste_weight',
        'csr_metal_waste_weight',
        'csr_glass_waste_weight',
        'csr_food_waste_weight',
        'csr_cigarette_litter_weight',
        'csr_textiles_weight',
        'csr_medical_waste_weight',
        'csr_sanitary_products_weight',
        'csr_fishing_gear_weight',
        'csr_styrofoam_hazardous_waste_weight',
        'csr_miscellaneous_weight',
        'csr_derelict_items_weight',
        'csr_unsorted_litter_weight',
    ];
    $total = 0.0;
    foreach ($weight_keys as $key) {
        $val = get_post_meta($post_id, $key, true);
        if (is_numeric($val)) {
            $total += (float) $val;
        }
    }
    return $total;
}

// Data Quality & Audit Evaluation Helper
function ecoservants_audit_csr_report($post_id) {
    $issues = [];
    $name = (string) get_post_meta($post_id, 'csr_name', true);
    $email = (string) get_post_meta($post_id, 'csr_email', true);
    $date = (string) get_post_meta($post_id, 'csr_date', true);
    $location = (string) get_post_meta($post_id, 'csr_location', true);
    $reporter_type = (string) get_post_meta($post_id, 'csr_reporter_type', true);
    $organization = (string) get_post_meta($post_id, 'csr_organization_name', true);
    $team = (string) get_post_meta($post_id, 'csr_team_name', true);
    $volunteers = (int) get_post_meta($post_id, 'csr_volunteers_involved', true);
    $photos = (string) get_post_meta($post_id, 'csr_photos', true);
    $photos_count = $photos ? count(array_filter(array_map('trim', explode(',', $photos)))) : 0;
    $total_weight = ecoservants_get_post_total_weight($post_id);

    // Missing basic info
    if (empty($name)) {
        $issues[] = ['type' => 'warning', 'message' => 'Missing reporter name'];
    }
    if (empty($email)) {
        $issues[] = ['type' => 'warning', 'message' => 'Missing reporter email'];
    }
    if (empty($location)) {
        $issues[] = ['type' => 'warning', 'message' => 'Missing cleanup location'];
    }
    if (empty($date)) {
        $issues[] = ['type' => 'warning', 'message' => 'Missing report date'];
    }

    // Entity naming check
    if ($reporter_type === 'organization' && empty($organization)) {
        $issues[] = ['type' => 'warning', 'message' => 'Organization report missing organization name'];
    }
    if ($reporter_type === 'team' && empty($team)) {
        $issues[] = ['type' => 'warning', 'message' => 'Team report missing team name'];
    }

    // High threshold anomaly checks
    if ($total_weight > 500) {
        $issues[] = ['type' => 'info', 'message' => 'High reported total weight (' . number_format($total_weight, 1) . ' lbs)'];
    }
    if ($volunteers > 100) {
        $issues[] = ['type' => 'info', 'message' => 'Large volunteer count (' . $volunteers . ' volunteers)'];
    }

    // Photo check
    if (($total_weight > 50 || $volunteers > 5) && $photos_count === 0) {
        $issues[] = ['type' => 'info', 'message' => 'No photos attached for large report (>50 lbs or >5 volunteers)'];
    }

    return $issues;
}

// Admin List Table Columns for CSR Reports
function ecoservants_csr_report_columns($columns) {
    $new_columns = [];
    $new_columns['cb']                  = $columns['cb'] ?? '<input type="checkbox" />';
    $new_columns['title']               = __('Report Title', 'ecoservants-csr');
    $new_columns['csr_reporter_entity'] = __('Reporter & Entity', 'ecoservants-csr');
    $new_columns['csr_event_ref']       = __('Event & Reference', 'ecoservants-csr');
    $new_columns['csr_location_date']   = __('Location & Date', 'ecoservants-csr');
    $new_columns['csr_total_weight']    = __('Total Weight (lbs)', 'ecoservants-csr');
    $new_columns['csr_volunteers']      = __('Volunteers', 'ecoservants-csr');
    $new_columns['csr_photos_count']    = __('Photos', 'ecoservants-csr');
    $new_columns['csr_quality_audit']   = __('Quality Audit', 'ecoservants-csr');
    return $new_columns;
}
add_filter('manage_csr_report_posts_columns', 'ecoservants_csr_report_columns');

// Render Custom Admin Column Content
function ecoservants_csr_report_custom_column($column, $post_id) {
    switch ($column) {
        case 'csr_reporter_entity':
            $name          = (string) get_post_meta($post_id, 'csr_name', true);
            $email         = (string) get_post_meta($post_id, 'csr_email', true);
            $type          = (string) get_post_meta($post_id, 'csr_reporter_type', true) ?: 'individual';
            $org           = (string) get_post_meta($post_id, 'csr_organization_name', true);
            $team          = (string) get_post_meta($post_id, 'csr_team_name', true);

            echo '<strong>' . esc_html($name ?: '—') . '</strong>';
            if ($email) {
                echo '<br><span style="color:#64748b; font-size:12px;">' . esc_html($email) . '</span>';
            }
            echo '<br><span class="csr-admin-badge csr-badge-' . esc_attr($type) . '">' . esc_html(ucfirst($type)) . '</span>';
            if ($type === 'organization' && $org) {
                echo ' <span style="font-size:11px; color:#334155;">(' . esc_html($org) . ')</span>';
            } elseif ($type === 'team' && $team) {
                echo ' <span style="font-size:11px; color:#334155;">(' . esc_html($team) . ')</span>';
            }
            break;

        case 'csr_event_ref':
            $event = (string) get_post_meta($post_id, 'csr_event_name', true);
            $ref   = (string) get_post_meta($post_id, 'csr_internal_reference', true);
            if ($event) {
                echo '<strong>' . esc_html($event) . '</strong>';
            }
            if ($ref) {
                echo ($event ? '<br>' : '') . '<code style="font-size:11px;">Ref: ' . esc_html($ref) . '</code>';
            }
            if (!$event && !$ref) {
                echo '—';
            }
            break;

        case 'csr_location_date':
            $location = (string) get_post_meta($post_id, 'csr_location', true);
            $date     = (string) get_post_meta($post_id, 'csr_date', true);
            echo esc_html($location ?: '—');
            if ($date) {
                echo '<br><span style="color:#64748b; font-size:12px;">' . esc_html($date) . '</span>';
            }
            break;

        case 'csr_total_weight':
            $weight = ecoservants_get_post_total_weight($post_id);
            echo '<strong>' . esc_html(number_format($weight, 1)) . ' lbs</strong>';
            break;

        case 'csr_volunteers':
            $volunteers = (int) get_post_meta($post_id, 'csr_volunteers_involved', true);
            echo esc_html($volunteers > 0 ? $volunteers : '—');
            break;

        case 'csr_photos_count':
            $photos = (string) get_post_meta($post_id, 'csr_photos', true);
            $count  = $photos ? count(array_filter(array_map('trim', explode(',', $photos)))) : 0;
            if ($count > 0) {
                echo '📷 <strong>' . esc_html($count) . '</strong>';
            } else {
                echo '<span style="color:#94a3b8;">0</span>';
            }
            break;

        case 'csr_quality_audit':
            $audit = ecoservants_audit_csr_report($post_id);
            if (empty($audit)) {
                echo '<span class="csr-audit-badge audit-clean">✔ Verified</span>';
            } else {
                $has_warning = false;
                foreach ($audit as $item) {
                    if ($item['type'] === 'warning') {
                        $has_warning = true;
                        break;
                    }
                }
                if ($has_warning) {
                    echo '<span class="csr-audit-badge audit-warning" title="' . esc_attr(implode(' | ', array_column($audit, 'message'))) . '">⚠️ Notes (' . count($audit) . ')</span>';
                } else {
                    echo '<span class="csr-audit-badge audit-info" title="' . esc_attr(implode(' | ', array_column($audit, 'message'))) . '">ℹ️ Info (' . count($audit) . ')</span>';
                }
            }
            break;
    }
}
add_action('manage_csr_report_posts_custom_column', 'ecoservants_csr_report_custom_column', 10, 2);

// Make Admin Columns Sortable
function ecoservants_csr_report_sortable_columns($columns) {
    $columns['csr_location_date'] = 'csr_date';
    $columns['csr_volunteers']    = 'csr_volunteers';
    return $columns;
}
add_filter('manage_edit-csr_report_sortable_columns', 'ecoservants_csr_report_sortable_columns');

// Filter by Reporter Type in List View
function ecoservants_csr_report_admin_filters($post_type) {
    if ($post_type !== 'csr_report') {
        return;
    }
    $current = isset($_GET['csr_reporter_type_filter']) ? sanitize_key($_GET['csr_reporter_type_filter']) : '';
    ?>
    <select name="csr_reporter_type_filter">
        <option value=""><?php esc_html_e('All Reporter Types', 'ecoservants-csr'); ?></option>
        <option value="individual" <?php selected($current, 'individual'); ?>><?php esc_html_e('Individual', 'ecoservants-csr'); ?></option>
        <option value="team" <?php selected($current, 'team'); ?>><?php esc_html_e('Team', 'ecoservants-csr'); ?></option>
        <option value="organization" <?php selected($current, 'organization'); ?>><?php esc_html_e('Organization', 'ecoservants-csr'); ?></option>
    </select>
    <?php
}
add_action('restrict_manage_posts', 'ecoservants_csr_report_admin_filters');

// Filter and Sort Query callback for CSR reports list view
function ecoservants_csr_report_filter_query($query) {
    global $pagenow;
    if (is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'csr_report') {
        if (!empty($_GET['csr_reporter_type_filter'])) {
            $filter_type = sanitize_key($_GET['csr_reporter_type_filter']);
            $meta_query  = $query->get('meta_query');
            $meta_query  = is_array($meta_query) ? $meta_query : [];
            $meta_query[] = [
                'key'     => 'csr_reporter_type',
                'value'   => $filter_type,
                'compare' => '=',
            ];
            $query->set('meta_query', $meta_query);
        }

        // Handle sortable column orderby mappings
        $orderby = $query->get('orderby');
        if ($orderby === 'csr_date') {
            $query->set('meta_key', 'csr_date');
            $query->set('orderby', 'meta_value');
        } elseif ($orderby === 'csr_volunteers') {
            $query->set('meta_key', 'csr_volunteers_involved');
            $query->set('orderby', 'meta_value_num');
        }
    }
}
add_action('pre_get_posts', 'ecoservants_csr_report_filter_query');

// Register meta boxes for CSR Reports
function ecoservants_register_meta_boxes() {
    add_meta_box('csr_report_details', 'CSR Report Details & Review Console', 'ecoservants_display_meta_boxes', 'csr_report', 'normal', 'high');
}
add_action('add_meta_boxes', 'ecoservants_register_meta_boxes');

// Register CSR meta with show_in_rest=false globally (covers REST-only contexts too)
add_action('init', function () {
    $meta_keys = [
        // Core identity & metadata
        'csr_name',
        'csr_email',
        'csr_date',
        'csr_location',
        'csr_reporter_type',
        'csr_organization_name',
        'csr_team_name',
        'csr_event_name',
        'csr_internal_reference',
        'csr_notes',
        'csr_photos',

        // Weights
        'csr_plastic_waste_weight',
        'csr_paper_waste_weight',
        'csr_metal_waste_weight',
        'csr_glass_waste_weight',
        'csr_food_waste_weight',
        'csr_cigarette_litter_weight',
        'csr_textiles_weight',
        'csr_medical_waste_weight',
        'csr_sanitary_products_weight',
        'csr_fishing_gear_weight',
        'csr_styrofoam_hazardous_waste_weight',
        'csr_miscellaneous_weight',
        'csr_derelict_items_weight',
        'csr_unsorted_litter_weight',
        'csr_invasive_species_weight',

        // Habitat / extras
        'csr_trees_planted',
        'csr_invasive_species_removed',
        'csr_invasive_species_names',
        'csr_volunteers_involved',
        'csr_native_plants_seeded',
        'csr_native_plants_seeded_other',
        'csr_erosion_control_methods',
        'csr_erosion_control_notes',

        // Subcategories
        'csr_plastic_subcategories',
        'csr_paper_subcategories',
        'csr_metal_subcategories',
        'csr_glass_subcategories',
        'csr_food_subcategories',
        'csr_cigarette_subcategories',
        'csr_textiles_subcategories',
        'csr_medical_subcategories',
        'csr_sanitary_subcategories',
        'csr_fishing_subcategories',
        'csr_styrofoam_subcategories',
        'csr_hazardous_subcategories',
        'csr_miscellaneous_subcategories',
        'csr_derelict_subcategories',
        'csr_unsorted_litter_subcategories',

        // Subcategory counts (JSON)
        'csr_plastic_subcategories_count',
        'csr_paper_subcategories_count',
        'csr_food_subcategories_count',
        'csr_metal_subcategories_count',
        'csr_glass_subcategories_count',
        'csr_cigarette_subcategories_count',
        'csr_textiles_subcategories_count',
        'csr_medical_subcategories_count',
        'csr_sanitary_subcategories_count',
        'csr_fishing_subcategories_count',
        'csr_styrofoam_subcategories_count',
        'csr_hazardous_subcategories_count',
        'csr_miscellaneous_subcategories_count',
        'csr_derelict_subcategories_count',
        'csr_unsorted_litter_subcategories_count',
    ];

    foreach ($meta_keys as $key) {
        register_meta('post', $key, [
            'type'         => 'string',
            'single'       => true,
            'show_in_rest' => false,
        ]);
    }
});

// Revamped Display Meta Boxes — CSR Admin Review & Management Console
function ecoservants_display_meta_boxes($post) {
    // Identity & Entity Info
    $name          = (string) get_post_meta($post->ID, 'csr_name', true);
    $email         = (string) get_post_meta($post->ID, 'csr_email', true);
    $date          = (string) get_post_meta($post->ID, 'csr_date', true);
    $location      = (string) get_post_meta($post->ID, 'csr_location', true);
    $reporter_type = (string) get_post_meta($post->ID, 'csr_reporter_type', true) ?: 'individual';
    $org_name      = (string) get_post_meta($post->ID, 'csr_organization_name', true);
    $team_name     = (string) get_post_meta($post->ID, 'csr_team_name', true);
    $event_name    = (string) get_post_meta($post->ID, 'csr_event_name', true);
    $internal_ref  = (string) get_post_meta($post->ID, 'csr_internal_reference', true);
    $notes         = (string) get_post_meta($post->ID, 'csr_notes', true);

    // Media
    $photos       = (string) get_post_meta($post->ID, 'csr_photos', true);
    $photos_array = $photos ? array_filter(array_map('trim', explode(',', $photos))) : [];

    // Habitat restoration fields
    $trees_planted            = (int) get_post_meta($post->ID, 'csr_trees_planted', true);
    $invasive_species_removed = (int) get_post_meta($post->ID, 'csr_invasive_species_removed', true);
    $invasive_species_names   = (string) get_post_meta($post->ID, 'csr_invasive_species_names', true);
    $invasive_species_weight  = (float) get_post_meta($post->ID, 'csr_invasive_species_weight', true);
    $volunteers_involved      = (int) get_post_meta($post->ID, 'csr_volunteers_involved', true);

    $native_plants_seeded       = (string) get_post_meta($post->ID, 'csr_native_plants_seeded', true);
    $native_plants_seeded_other = (string) get_post_meta($post->ID, 'csr_native_plants_seeded_other', true);

    $erosion_control_methods = (string) get_post_meta($post->ID, 'csr_erosion_control_methods', true);
    $erosion_control_notes   = (string) get_post_meta($post->ID, 'csr_erosion_control_notes', true);

    // Calculated metrics
    $total_weight  = ecoservants_get_post_total_weight($post->ID);
    $audit_issues  = ecoservants_audit_csr_report($post->ID);

    // Unsorted litter
    $unsorted_litter_weight = (string) get_post_meta($post->ID, 'csr_unsorted_litter_weight', true);
    $unsorted_litter_subcategories = (string) get_post_meta($post->ID, 'csr_unsorted_litter_subcategories', true);
    $unsorted_litter_subcategories_array = $unsorted_litter_subcategories
        ? array_map('trim', explode(',', $unsorted_litter_subcategories))
        : [];
    $unsorted_litter_subcategories_count = json_decode(
        (string) get_post_meta($post->ID, 'csr_unsorted_litter_subcategories_count', true),
        true
    );
    if (!is_array($unsorted_litter_subcategories_count)) {
        $unsorted_litter_subcategories_count = [];
    }

    // Build subcategory counts map
    $counts_map = [
        'csr_plastic_subcategories_count'       => json_decode((string) get_post_meta($post->ID, 'csr_plastic_subcategories_count', true), true),
        'csr_paper_subcategories_count'         => json_decode((string) get_post_meta($post->ID, 'csr_paper_subcategories_count', true), true),
        'csr_food_subcategories_count'          => json_decode((string) get_post_meta($post->ID, 'csr_food_subcategories_count', true), true),
        'csr_metal_subcategories_count'         => json_decode((string) get_post_meta($post->ID, 'csr_metal_subcategories_count', true), true),
        'csr_glass_subcategories_count'         => json_decode((string) get_post_meta($post->ID, 'csr_glass_subcategories_count', true), true),
        'csr_cigarette_subcategories_count'     => json_decode((string) get_post_meta($post->ID, 'csr_cigarette_subcategories_count', true), true),
        'csr_textiles_subcategories_count'      => json_decode((string) get_post_meta($post->ID, 'csr_textiles_subcategories_count', true), true),
        'csr_medical_subcategories_count'       => json_decode((string) get_post_meta($post->ID, 'csr_medical_subcategories_count', true), true),
        'csr_sanitary_subcategories_count'      => json_decode((string) get_post_meta($post->ID, 'csr_sanitary_subcategories_count', true), true),
        'csr_fishing_subcategories_count'       => json_decode((string) get_post_meta($post->ID, 'csr_fishing_subcategories_count', true), true),
        'csr_styrofoam_subcategories_count'     => json_decode((string) get_post_meta($post->ID, 'csr_styrofoam_subcategories_count', true), true),
        'csr_hazardous_subcategories_count'     => json_decode((string) get_post_meta($post->ID, 'csr_hazardous_subcategories_count', true), true),
        'csr_miscellaneous_subcategories_count' => json_decode((string) get_post_meta($post->ID, 'csr_miscellaneous_subcategories_count', true), true),
        'csr_derelict_subcategories_count'      => json_decode((string) get_post_meta($post->ID, 'csr_derelict_subcategories_count', true), true),
    ];
    foreach ($counts_map as $k => $v) {
        if (!is_array($v)) {
            $counts_map[$k] = [];
        }
    }

    // Category definitions
    $categories = [
        'plastic' => [
            'label'                 => 'Plastic Waste',
            'weight_meta'           => 'csr_plastic_waste_weight',
            'subcategories_meta'    => 'csr_plastic_subcategories',
            'subcategories_count_meta' => 'csr_plastic_subcategories_count',
            'subcategories'         => [
                'Plastic bottles', 'Bottle caps', 'Straws & stirrers', 'Plastic bags', 'Food wrappers',
                'Plastic utensils', 'Cups & lids', 'Six-pack rings', 'Microplastics', 'Toys',
                'Containers (non-food)', 'Hard plastics', 'Packaging materials', 'Fishing nets (plastic-based)'
            ]
        ],
        'paper' => [
            'label'                 => 'Paper Waste',
            'weight_meta'           => 'csr_paper_waste_weight',
            'subcategories_meta'    => 'csr_paper_subcategories',
            'subcategories_count_meta' => 'csr_paper_subcategories_count',
            'subcategories'         => [
                'Newspapers', 'Magazines', 'Flyers / brochures', 'Food packaging (paper-based)', 'Cardboard',
                'Paper bags', 'Napkins / tissues', 'Notebooks / loose paper', 'Cigarette packs (paper-based)',
                'Receipts', 'Coffee cups (paper with lining)'
            ]
        ],
        'food' => [
            'label'                 => 'Food Waste',
            'weight_meta'           => 'csr_food_waste_weight',
            'subcategories_meta'    => 'csr_food_subcategories',
            'subcategories_count_meta' => 'csr_food_subcategories_count',
            'subcategories'         => [
                'Fruit peels', 'Vegetable scraps', 'Meat scraps', 'Fish scraps', 'Egg shells'
            ]
        ],
        'metal' => [
            'label'                 => 'Metal Waste',
            'weight_meta'           => 'csr_metal_waste_weight',
            'subcategories_meta'    => 'csr_metal_subcategories',
            'subcategories_count_meta' => 'csr_metal_subcategories_count',
            'subcategories'         => [
                'Aluminum cans', 'Metal bottle caps', 'Metal lids', 'Metal utensils', 'Metal containers',
                'Metal scraps', 'Metal wires', 'Metal tools', 'Metal toys', 'Metal furniture', 'Metal appliances'
            ]
        ],
        'glass' => [
            'label'                 => 'Glass Waste',
            'weight_meta'           => 'csr_glass_waste_weight',
            'subcategories_meta'    => 'csr_glass_subcategories',
            'subcategories_count_meta' => 'csr_glass_subcategories_count',
            'subcategories'         => [
                'Glass bottles', 'Glass jars', 'Glass containers', 'Glass fragments', 'Glass cups',
                'Glass plates', 'Glass utensils', 'Glass toys', 'Glass furniture', 'Glass appliances'
            ]
        ],
        'cigarette' => [
            'label'                 => 'Cigarette Litter',
            'weight_meta'           => 'csr_cigarette_litter_weight',
            'subcategories_meta'    => 'csr_cigarette_subcategories',
            'subcategories_count_meta' => 'csr_cigarette_subcategories_count',
            'subcategories'         => [
                'Cigarette butts', 'Cigarette packs', 'Cigarette filters', 'Cigarette wrappers', 'Cigarette lighters'
            ]
        ],
        'textiles' => [
            'label'                 => 'Textiles',
            'weight_meta'           => 'csr_textiles_weight',
            'subcategories_meta'    => 'csr_textiles_subcategories',
            'subcategories_count_meta' => 'csr_textiles_subcategories_count',
            'subcategories'         => [
                'Clothing', 'Shoes', 'Bags', 'Hats', 'Scarves', 'Gloves', 'Socks', 'Underwear', 'Bedding', 'Towels', 'Curtains'
            ]
        ],
        'medical' => [
            'label'                 => 'Medical Waste',
            'weight_meta'           => 'csr_medical_waste_weight',
            'subcategories_meta'    => 'csr_medical_subcategories',
            'subcategories_count_meta' => 'csr_medical_subcategories_count',
            'subcategories'         => [
                'Syringes', 'Medicine bottles', 'Medicine packaging', 'Bandages', 'Gloves', 'Masks',
                'Medical tools', 'Medical containers', 'Medical waste bags'
            ]
        ],
        'sanitary' => [
            'label'                 => 'Sanitary Products',
            'weight_meta'           => 'csr_sanitary_products_weight',
            'subcategories_meta'    => 'csr_sanitary_subcategories',
            'subcategories_count_meta' => 'csr_sanitary_subcategories_count',
            'subcategories'         => [
                'Sanitary pads', 'Tampons', 'Diapers', 'Wet wipes', 'Cotton swabs', 'Toilet paper', 'Tissues'
            ]
        ],
        'fishing' => [
            'label'                 => 'Fishing Gear',
            'weight_meta'           => 'csr_fishing_gear_weight',
            'subcategories_meta'    => 'csr_fishing_subcategories',
            'subcategories_count_meta' => 'csr_fishing_subcategories_count',
            'subcategories'         => [
                'Fishing nets', 'Fishing lines', 'Fishing hooks', 'Fishing lures', 'Fishing weights',
                'Fishing floats', 'Fishing rods', 'Fishing reels', 'Fishing bait containers'
            ]
        ],
        'styrofoam_hazardous' => [
            'label'                 => 'Styrofoam & Hazardous Waste',
            'weight_meta'           => 'csr_styrofoam_hazardous_waste_weight',
            'subcategories_meta'    => 'csr_styrofoam_subcategories',
            'subcategories_count_meta' => 'csr_styrofoam_subcategories_count',
            'subcategories'         => [
                'Styrofoam cups', 'Styrofoam plates', 'Styrofoam containers', 'Styrofoam packaging', 'Styrofoam fragments'
            ],
            'hazardous_subcategories_meta' => 'csr_hazardous_subcategories',
            'hazardous_subcategories'      => [
                'Batteries', 'Paint cans', 'Chemical containers', 'Oil containers', 'Pesticide containers',
                'Cleaning product containers', 'Medical waste', 'Electronic waste'
            ]
        ],
        'miscellaneous' => [
            'label'                 => 'Miscellaneous',
            'weight_meta'           => 'csr_miscellaneous_weight',
            'subcategories_meta'    => 'csr_miscellaneous_subcategories',
            'subcategories_count_meta' => 'csr_miscellaneous_subcategories_count',
            'subcategories'         => [
                'Rubber items', 'Wood items', 'Ceramic items', 'Leather items', 'Electronic items', 'Miscellaneous fragments'
            ]
        ],
        'derelict' => [
            'label'                 => 'Derelict Items',
            'weight_meta'           => 'csr_derelict_items_weight',
            'subcategories_meta'    => 'csr_derelict_subcategories',
            'subcategories_count_meta' => 'csr_derelict_subcategories_count',
            'subcategories'         => [
                'Derelict fishing gear', 'Derelict boats', 'Derelict vehicles', 'Derelict furniture',
                'Derelict appliances', 'Derelict building materials', 'Derelict tools', 'Derelict toys'
            ]
        ]
    ];

    wp_nonce_field('csr_report_details_nonce', 'csr_report_details_nonce_field');
    ?>

    <!-- 1. Executive Summary Hero Bar -->
    <div class="csr-admin-hero-bar">
        <div class="csr-admin-metric-card">
            <div class="csr-admin-metric-value">
                <span class="csr-admin-badge csr-badge-<?php echo esc_attr($reporter_type); ?>">
                    <?php echo esc_html(ucfirst($reporter_type)); ?>
                </span>
            </div>
            <div class="csr-admin-metric-label">Reporter Type</div>
        </div>
        <div class="csr-admin-metric-card">
            <div class="csr-admin-metric-value"><?php echo esc_html(number_format($total_weight, 1)); ?> <span style="font-size:12px; font-weight:normal;">lbs</span></div>
            <div class="csr-admin-metric-label">Total Waste Collected</div>
        </div>
        <div class="csr-admin-metric-card">
            <div class="csr-admin-metric-value"><?php echo esc_html($volunteers_involved); ?></div>
            <div class="csr-admin-metric-label">Volunteers Involved</div>
        </div>
        <div class="csr-admin-metric-card">
            <div class="csr-admin-metric-value">📷 <?php echo esc_html(count($photos_array)); ?></div>
            <div class="csr-admin-metric-label">Photos Uploaded</div>
        </div>
        <div class="csr-admin-metric-card">
            <div class="csr-admin-metric-value">
                <?php if (empty($audit_issues)): ?>
                    <span class="csr-audit-badge audit-clean">✔ Clean</span>
                <?php else: ?>
                    <span class="csr-audit-badge audit-warning">⚠️ Notes (<?php echo count($audit_issues); ?>)</span>
                <?php endif; ?>
            </div>
            <div class="csr-admin-metric-label">Audit Status</div>
        </div>
    </div>

    <!-- 2. Data Quality Audit Panel -->
    <div class="csr-admin-audit-panel <?php echo !empty($audit_issues) ? 'has-warnings' : 'is-clean'; ?>">
        <div class="csr-admin-audit-title">
            <?php if (!empty($audit_issues)): ?>
                <span>⚠️ Data Quality & Verification Notes (<?php echo count($audit_issues); ?>)</span>
            <?php else: ?>
                <span>✔ Data Quality Check Passed</span>
            <?php endif; ?>
        </div>
        <?php if (!empty($audit_issues)): ?>
            <ul class="csr-admin-audit-list">
                <?php foreach ($audit_issues as $issue): ?>
                    <li>
                        <strong>[<?php echo esc_html(strtoupper($issue['type'])); ?>]</strong>
                        <?php echo esc_html($issue['message']); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="margin:0; font-size:12px; color:#166534;">
                This CSR report contains all essential contact details, location metadata, and reasonable weight metrics.
            </p>
        <?php endif; ?>
    </div>

    <!-- 3. Section: Reporter & Entity Information -->
    <div class="csr-admin-review-section">
        <div class="csr-admin-section-header">
            <h3>👤 Reporter & Organization Information</h3>
        </div>
        <div class="csr-admin-grid-2">
            <div class="csr-admin-info-group">
                <div class="csr-admin-info-label">Reporter Details</div>
                <p style="margin: 4px 0;">
                    <label for="csr_name"><strong>Name:</strong></label>
                    <input type="text" id="csr_name" name="csr_name" value="<?php echo esc_attr($name); ?>" style="width:100%;" required>
                </p>
                <p style="margin: 4px 0;">
                    <label for="csr_email"><strong>Email:</strong></label>
                    <input type="email" id="csr_email" name="csr_email" value="<?php echo esc_attr($email); ?>" style="width:100%;" required>
                </p>
                <p style="margin: 4px 0;">
                    <label for="csr_reporter_type"><strong>Reporter Type:</strong></label>
                    <select id="csr_reporter_type" name="csr_reporter_type" style="width:100%;">
                        <option value="individual" <?php selected($reporter_type, 'individual'); ?>>Individual</option>
                        <option value="team" <?php selected($reporter_type, 'team'); ?>>Team</option>
                        <option value="organization" <?php selected($reporter_type, 'organization'); ?>>Organization</option>
                    </select>
                </p>
                <p style="margin: 4px 0;">
                    <label for="csr_organization_name"><strong>Organization Name:</strong></label>
                    <input type="text" id="csr_organization_name" name="csr_organization_name" value="<?php echo esc_attr($org_name); ?>" style="width:100%;" placeholder="e.g. Acme Corp">
                </p>
                <p style="margin: 4px 0;">
                    <label for="csr_team_name"><strong>Team Name:</strong></label>
                    <input type="text" id="csr_team_name" name="csr_team_name" value="<?php echo esc_attr($team_name); ?>" style="width:100%;" placeholder="e.g. Green Team A">
                </p>
            </div>

            <div class="csr-admin-info-group">
                <div class="csr-admin-info-label">Event & Location Reference</div>
                <p style="margin: 4px 0;">
                    <label for="csr_event_name"><strong>Event Name:</strong></label>
                    <input type="text" id="csr_event_name" name="csr_event_name" value="<?php echo esc_attr($event_name); ?>" style="width:100%;" placeholder="e.g. Earth Day Beach Cleanup">
                </p>
                <p style="margin: 4px 0;">
                    <label for="csr_internal_reference"><strong>Internal Reference / Code:</strong></label>
                    <input type="text" id="csr_internal_reference" name="csr_internal_reference" value="<?php echo esc_attr($internal_ref); ?>" style="width:100%;" placeholder="e.g. REF-2026-001">
                </p>
                <p style="margin: 4px 0;">
                    <label for="csr_date"><strong>Reporting Date:</strong></label>
                    <input type="date" id="csr_date" name="csr_date" value="<?php echo esc_attr($date); ?>" style="width:100%;" required>
                </p>
                <p style="margin: 4px 0;">
                    <label for="csr_location"><strong>Cleanup Location:</strong></label>
                    <input type="text" id="csr_location" name="csr_location" value="<?php echo esc_attr($location); ?>" style="width:100%;" required>
                </p>
            </div>
        </div>
    </div>

    <!-- 4. Section: Waste Categories Breakdown & Review -->
    <div class="csr-admin-review-section">
        <div class="csr-admin-section-header">
            <h3>♻️ Waste Categories & Subcategories Breakdown</h3>
            <button type="button" class="csr-admin-toggle-btn" onclick="toggleRawCategories(this)">
                ✏️ Edit All Categories / Raw Inputs
            </button>
        </div>

        <!-- Active / Reported Categories Summary Cards -->
        <?php
        $has_reported_waste = false;
        ?>
        <div class="csr-admin-category-grid">
            <?php
            // Check unsorted litter
            if ((float) $unsorted_litter_weight > 0 || !empty($unsorted_litter_subcategories_array)):
                $has_reported_waste = true;
            ?>
                <div class="csr-admin-active-card">
                    <div class="csr-admin-active-card-header">
                        <span><span class="csr-admin-category-icon" aria-hidden="true"><?php echo csr_get_category_icon_svg('unsorted_litter', ['size' => 16]); ?></span> Unsorted Litter</span>
                        <span class="csr-admin-active-card-weight"><?php echo esc_html(number_format((float)$unsorted_litter_weight, 1)); ?> lbs</span>
                    </div>
                    <?php if (!empty($unsorted_litter_subcategories_array)): ?>
                        <ul class="csr-admin-subcategory-list">
                            <?php foreach ($unsorted_litter_subcategories_array as $subcat): ?>
                                <li>
                                    <span><?php echo esc_html($subcat); ?></span>
                                    <strong><?php echo esc_html($unsorted_litter_subcategories_count[$subcat] ?? 0); ?> items</strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php
            foreach ($categories as $cat_key => $cat_def):
                $w = (float) get_post_meta($post->ID, $cat_def['weight_meta'], true);
                $subs = (string) get_post_meta($post->ID, $cat_def['subcategories_meta'], true);
                $subs_arr = $subs ? array_map('trim', explode(',', $subs)) : [];
                $count_key = $cat_def['subcategories_count_meta'] ?? null;
                $counts = ($count_key && isset($counts_map[$count_key])) ? $counts_map[$count_key] : [];

                if ($w > 0 || !empty($subs_arr)):
                    $has_reported_waste = true;
                ?>
                    <div class="csr-admin-active-card">
                        <div class="csr-admin-active-card-header">
                            <span><span class="csr-admin-category-icon" aria-hidden="true"><?php echo csr_get_category_icon_svg($cat_key, ['size' => 16]); ?></span> <?php echo esc_html($cat_def['label']); ?></span>
                            <span class="csr-admin-active-card-weight"><?php echo esc_html(number_format($w, 1)); ?> lbs</span>
                        </div>
                        <?php if (!empty($subs_arr)): ?>
                            <ul class="csr-admin-subcategory-list">
                                <?php foreach ($subs_arr as $sc): ?>
                                    <li>
                                        <span><?php echo esc_html($sc); ?></span>
                                        <strong><?php echo esc_html($counts[$sc] ?? 0); ?> items</strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php
                endif;
            endforeach;
            ?>
        </div>

        <?php if (!$has_reported_waste): ?>
            <p style="font-size:13px; color:#64748b; font-style:italic;">No active waste category weight or subcategories recorded for this report.</p>
        <?php endif; ?>

        <!-- Full Edit Raw Category Accordion -->
        <div id="csr_raw_categories_panel" class="csr-admin-raw-fields" style="display:none;">
            <h4>Raw Category Inputs & Edit Console</h4>
            <p>
                <label for="csr_unsorted_litter_weight"><span class="csr-admin-category-icon" aria-hidden="true"><?php echo csr_get_category_icon_svg('unsorted_litter', ['size' => 18]); ?></span> Unsorted Litter (lbs):</label>
                <input type="number" id="csr_unsorted_litter_weight" name="csr_unsorted_litter_weight" value="<?php echo esc_attr($unsorted_litter_weight); ?>" step="0.01" min="0">
                <fieldset>
                    <legend>Unsorted Litter Subcategories:</legend>
                    <label>
                        <input type="checkbox" name="csr_unsorted_litter_subcategories[]" value="Number of Unsorted Bags Collected" <?php echo in_array('Number of Unsorted Bags Collected', $unsorted_litter_subcategories_array, true) ? 'checked' : ''; ?>>
                        Number of Unsorted Bags Collected
                        <input type="number" name="csr_unsorted_litter_subcategories_count[Number of Unsorted Bags Collected]" value="<?php echo esc_attr($unsorted_litter_subcategories_count['Number of Unsorted Bags Collected'] ?? 0); ?>" step="1" min="0">
                    </label>
                </fieldset>
            </p>

            <?php foreach ($categories as $key => $category): 
                $weight = (string) get_post_meta($post->ID, $category['weight_meta'], true);
                $subcategories = (string) get_post_meta($post->ID, $category['subcategories_meta'], true);
                $subcategories_array = $subcategories ? array_map('trim', explode(',', $subcategories)) : [];
                $count_meta_key       = $category['subcategories_count_meta'] ?? null;
                $counts_for_category  = ($count_meta_key && isset($counts_map[$count_meta_key])) ? $counts_map[$count_meta_key] : [];
            ?>
                <p style="border-top:1px solid #f1f5f9; padding-top:10px;">
                    <label for="<?php echo esc_attr($category['weight_meta']); ?>"><span class="csr-admin-category-icon" aria-hidden="true"><?php echo csr_get_category_icon_svg($key, ['size' => 18]); ?></span> <?php echo esc_html($category['label']); ?> (lbs):</label>
                    <input type="number" id="<?php echo esc_attr($category['weight_meta']); ?>" name="<?php echo esc_attr($category['weight_meta']); ?>" step="0.01" value="<?php echo esc_attr($weight); ?>">
                    <fieldset>
                        <legend><?php echo esc_html($category['label']); ?> Subcategories:</legend>
                        <?php foreach ($category['subcategories'] as $subcategory): ?>
                            <?php
                            $checked   = in_array($subcategory, $subcategories_array, true) ? 'checked' : '';
                            $count_val = ($count_meta_key && isset($counts_for_category[$subcategory])) ? (int) $counts_for_category[$subcategory] : 0;
                            ?>
                            <label style="display:inline-block; margin-right:15px; margin-bottom:5px;">
                                <input type="checkbox" name="<?php echo esc_attr($category['subcategories_meta']); ?>[]" value="<?php echo esc_attr($subcategory); ?>" <?php echo $checked; ?>>
                                <?php echo esc_html($subcategory); ?>
                                <?php if ($count_meta_key): ?>
                                    <input
                                        type="number"
                                        name="<?php echo esc_attr($count_meta_key); ?>[<?php echo esc_attr($subcategory); ?>]"
                                        value="<?php echo esc_attr($count_val); ?>"
                                        step="1"
                                        min="0"
                                        style="width:60px;"
                                    >
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <?php if (isset($category['hazardous_subcategories'])): 
                        $hazardous_subcategories = (string) get_post_meta($post->ID, $category['hazardous_subcategories_meta'], true);
                        $hazardous_subcategories_array = $hazardous_subcategories ? array_map('trim', explode(',', $hazardous_subcategories)) : [];
                        $hazardous_counts = $counts_map['csr_hazardous_subcategories_count'] ?? [];
                    ?>
                        <fieldset>
                            <legend>Hazardous Subcategories:</legend>
                            <?php foreach ($category['hazardous_subcategories'] as $hazardous_subcategory): 
                                $haz_checked = in_array($hazardous_subcategory, $hazardous_subcategories_array, true) ? 'checked' : '';
                                $haz_count   = isset($hazardous_counts[$hazardous_subcategory]) ? (int) $hazardous_counts[$hazardous_subcategory] : 0;
                            ?>
                                <label style="display:inline-block; margin-right:15px; margin-bottom:5px;">
                                    <input type="checkbox" name="<?php echo esc_attr($category['hazardous_subcategories_meta']); ?>[]" value="<?php echo esc_attr($hazardous_subcategory); ?>" <?php echo $haz_checked; ?>>
                                    <?php echo esc_html($hazardous_subcategory); ?>
                                    <input
                                        type="number"
                                        name="csr_hazardous_subcategories_count[<?php echo esc_attr($hazardous_subcategory); ?>]"
                                        value="<?php echo esc_attr($haz_count); ?>"
                                        step="1"
                                        min="0"
                                        style="width:60px;"
                                    >
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    <?php endif; ?>
                </p>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 5. Section: Habitat Restoration & Environmental Metrics -->
    <div class="csr-admin-review-section">
        <div class="csr-admin-section-header">
            <h3>🌱 Habitat Restoration & Volunteer Metrics</h3>
        </div>
        <div class="csr-admin-grid-2">
            <div class="csr-admin-info-group">
                <p style="margin:4px 0;">
                    <label for="csr_volunteers_involved"><strong>Volunteers Involved:</strong></label>
                    <input type="number" id="csr_volunteers_involved" name="csr_volunteers_involved" value="<?php echo esc_attr($volunteers_involved); ?>" step="1" min="0" style="width:100%;">
                </p>
                <p style="margin:4px 0;">
                    <label for="csr_trees_planted"><strong>Trees Planted:</strong></label>
                    <input type="number" id="csr_trees_planted" name="csr_trees_planted" value="<?php echo esc_attr($trees_planted); ?>" step="1" min="0" style="width:100%;">
                </p>
                <p style="margin:4px 0;">
                    <label for="csr_native_plants_seeded"><strong>Native Plant Species Seeded:</strong></label>
                    <select id="csr_native_plants_seeded" name="csr_native_plants_seeded" style="width:100%;">
                        <option value="" <?php selected($native_plants_seeded, ''); ?>>Select a species</option>
                        <option value="Milkweed" <?php selected($native_plants_seeded, 'Milkweed'); ?>>Milkweed</option>
                        <option value="Oak" <?php selected($native_plants_seeded, 'Oak'); ?>>Oak</option>
                        <option value="Pine" <?php selected($native_plants_seeded, 'Pine'); ?>>Pine</option>
                        <option value="Other" <?php selected($native_plants_seeded, 'Other'); ?>>Other</option>
                    </select>
                    <input type="text" id="csr_native_plants_seeded_other" name="csr_native_plants_seeded_other" value="<?php echo esc_attr($native_plants_seeded_other); ?>" placeholder="Specify if 'Other'" style="width:100%; margin-top:4px;">
                </p>
            </div>

            <div class="csr-admin-info-group">
                <p style="margin:4px 0;">
                    <label for="csr_invasive_species_removed"><strong>Invasive Species Area Removed (sq ft):</strong></label>
                    <input type="number" id="csr_invasive_species_removed" name="csr_invasive_species_removed" value="<?php echo esc_attr($invasive_species_removed); ?>" step="1" min="0" style="width:100%;">
                </p>
                <p style="margin:4px 0;">
                    <label for="csr_invasive_species_names"><strong>Invasive Species Names:</strong></label>
                    <input type="text" id="csr_invasive_species_names" name="csr_invasive_species_names" value="<?php echo esc_attr($invasive_species_names); ?>" placeholder="e.g. Kudzu, English Ivy" style="width:100%;">
                </p>
                <p style="margin:4px 0;">
                    <label for="csr_invasive_species_weight"><strong>Invasive Species Weight (lbs):</strong></label>
                    <input type="number" id="csr_invasive_species_weight" name="csr_invasive_species_weight" value="<?php echo esc_attr($invasive_species_weight); ?>" step="0.01" min="0" style="width:100%;">
                </p>
            </div>
        </div>

        <div style="margin-top:12px;">
            <fieldset>
                <legend><strong>Erosion Control Methods Used:</strong></legend>
                <?php $erosion_methods = $erosion_control_methods ? array_map('trim', explode(',', $erosion_control_methods)) : []; ?>
                <label style="margin-right:15px;"><input type="checkbox" name="csr_erosion_control_methods[]" value="Mulching" <?php checked(in_array('Mulching', $erosion_methods, true)); ?>> Mulching</label>
                <label style="margin-right:15px;"><input type="checkbox" name="csr_erosion_control_methods[]" value="Terracing" <?php checked(in_array('Terracing', $erosion_methods, true)); ?>> Terracing</label>
                <label style="margin-right:15px;"><input type="checkbox" name="csr_erosion_control_methods[]" value="Planting ground cover" <?php checked(in_array('Planting ground cover', $erosion_methods, true)); ?>> Planting ground cover</label>
                <label style="margin-right:15px;"><input type="checkbox" name="csr_erosion_control_methods[]" value="Other" <?php checked(in_array('Other', $erosion_methods, true)); ?>> Other</label>
                <textarea name="csr_erosion_control_notes" rows="2" placeholder="Erosion control notes" style="width:100%; margin-top:5px;"><?php echo esc_textarea($erosion_control_notes); ?></textarea>
            </fieldset>
        </div>
    </div>

    <!-- 6. Section: Attached Photos Review Gallery -->
    <div class="csr-admin-review-section">
        <div class="csr-admin-section-header">
            <h3>📷 Attached Field Photos (<?php echo count($photos_array); ?>)</h3>
        </div>
        <?php if (!empty($photos_array)): ?>
            <div class="csr-admin-photo-gallery">
                <?php foreach ($photos_array as $photo_id): ?>
                    <?php
                    $photo_url  = wp_get_attachment_url($photo_id);
                    $edit_url   = get_edit_post_link($photo_id);
                    $meta       = wp_get_attachment_metadata($photo_id);
                    $file_name  = get_the_title($photo_id);
                    ?>
                    <?php if ($photo_url): ?>
                        <div class="csr-admin-photo-item">
                            <a href="<?php echo esc_url($photo_url); ?>" target="_blank" title="View Full Image">
                                <img src="<?php echo esc_url($photo_url); ?>" alt="Uploaded CSR Photo">
                            </a>
                            <div class="photo-meta" title="<?php echo esc_attr($file_name); ?>">ID #<?php echo esc_html($photo_id); ?></div>
                            <a href="<?php echo esc_url($photo_url); ?>" target="_blank">🔍 View Full</a>
                            <?php if ($edit_url): ?>
                                | <a href="<?php echo esc_url($edit_url); ?>" target="_blank">✏️ Edit Media</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="font-size:13px; color:#64748b; margin:0;">No photos were uploaded with this submission.</p>
        <?php endif; ?>

        <p style="margin-top:12px; margin-bottom:0;">
            <label for="csr_photos"><strong>Raw Attachment IDs (comma-separated):</strong></label>
            <input type="text" id="csr_photos" name="csr_photos" value="<?php echo esc_attr($photos); ?>" style="width:100%; font-family:monospace; font-size:12px;">
        </p>
    </div>

    <!-- 7. Section: Reporter Notes -->
    <div class="csr-admin-review-section">
        <div class="csr-admin-section-header">
            <h3>📝 Reporter Notes & Comments</h3>
        </div>
        <p style="margin:0;">
            <textarea id="csr_notes" name="csr_notes" rows="4" style="width: 100%; font-size:13px;" placeholder="No notes submitted with this report."><?php echo esc_textarea($notes); ?></textarea>
        </p>
    </div>

    <!-- JavaScript Toggle Handler -->
    <script>
    function toggleRawCategories(btn) {
        var panel = document.getElementById('csr_raw_categories_panel');
        if (panel.style.display === 'none') {
            panel.style.display = 'block';
            if (btn) btn.innerText = '🔒 Hide Raw Category Edit Console';
        } else {
            panel.style.display = 'none';
            if (btn) btn.innerText = '✏️ Edit All Categories / Raw Inputs';
        }
    }
    </script>
    <?php
}

// Save Meta Box Data Handler
function ecoservants_save_meta_boxes($post_id) {
    if (!isset($_POST['csr_report_details_nonce_field']) || !wp_verify_nonce($_POST['csr_report_details_nonce_field'], 'csr_report_details_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = [
        // Core Identity & Entity
        'csr_name',
        'csr_email',
        'csr_date',
        'csr_location',
        'csr_reporter_type',
        'csr_organization_name',
        'csr_team_name',
        'csr_event_name',
        'csr_internal_reference',
        'csr_notes',

        // Waste weights
        'csr_plastic_waste_weight',
        'csr_paper_waste_weight',
        'csr_metal_waste_weight',
        'csr_glass_waste_weight',
        'csr_food_waste_weight',
        'csr_cigarette_litter_weight',
        'csr_textiles_weight',
        'csr_medical_waste_weight',
        'csr_sanitary_products_weight',
        'csr_fishing_gear_weight',
        'csr_styrofoam_hazardous_waste_weight',
        'csr_miscellaneous_weight',
        'csr_derelict_items_weight',
        'csr_unsorted_litter_weight',

        // Habitat / restoration
        'csr_trees_planted',
        'csr_invasive_species_removed',
        'csr_invasive_species_names',
        'csr_invasive_species_weight',
        'csr_volunteers_involved',
        'csr_native_plants_seeded',
        'csr_native_plants_seeded_other',
        'csr_erosion_control_notes',

        // Media
        'csr_photos',
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    $subcategories_fields = [
        'csr_plastic_subcategories', 'csr_paper_subcategories', 'csr_metal_subcategories', 'csr_glass_subcategories', 'csr_food_subcategories',
        'csr_cigarette_subcategories', 'csr_textiles_subcategories', 'csr_medical_subcategories', 'csr_sanitary_subcategories', 'csr_fishing_subcategories',
        'csr_styrofoam_subcategories', 'csr_hazardous_subcategories', 'csr_miscellaneous_subcategories', 'csr_derelict_subcategories', 'csr_unsorted_litter_subcategories'
    ];

    foreach ($subcategories_fields as $field) {
        if (isset($_POST[$field]) && is_array($_POST[$field])) {
            $subcategories = implode(',', array_map('sanitize_text_field', $_POST[$field]));
            update_post_meta($post_id, $field, $subcategories);
        }
    }

    // Save erosion control methods (checkbox array)
    if (isset($_POST['csr_erosion_control_methods']) && is_array($_POST['csr_erosion_control_methods'])) {
        $methods = array_map('sanitize_text_field', $_POST['csr_erosion_control_methods']);
        update_post_meta($post_id, 'csr_erosion_control_methods', implode(',', $methods));
    } else {
        delete_post_meta($post_id, 'csr_erosion_control_methods');
    }

    $subcategories_count_fields = [
        'csr_plastic_subcategories_count', 'csr_paper_subcategories_count', 'csr_food_subcategories_count', 'csr_metal_subcategories_count',
        'csr_glass_subcategories_count', 'csr_cigarette_subcategories_count', 'csr_textiles_subcategories_count', 'csr_medical_subcategories_count', 'csr_sanitary_subcategories_count',
        'csr_fishing_subcategories_count', 'csr_styrofoam_subcategories_count', 'csr_hazardous_subcategories_count', 'csr_miscellaneous_subcategories_count',
        'csr_derelict_subcategories_count', 'csr_unsorted_litter_subcategories_count'
    ];

    foreach ($subcategories_count_fields as $field) {
        if (isset($_POST[$field]) && is_array($_POST[$field])) {
            $raw = (array) $_POST[$field];
            $clean = [];
            foreach ($raw as $key => $value) {
                $clean_key = sanitize_text_field($key);
                if ($clean_key === '') {
                    continue;
                }
                $clean[$clean_key] = intval($value);
            }
            update_post_meta($post_id, $field, wp_json_encode($clean));
        }
    }
}
add_action('save_post', 'ecoservants_save_meta_boxes');


// Function to calculate total impact metrics, including item counts
function ecoservants_calculate_totals() {
    $args = [
    'post_type'      => 'csr_report',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'no_found_rows'  => true,
    'fields'         => 'ids',
];
    $query = new WP_Query($args);

if (!empty($query->posts)) {
    update_meta_cache('post', $query->posts);
}

// Base volunteer count to seed cumulative totals
$base_volunteers = 1452;



    $totals = [
        'plastic_waste' => 0,
        'paper_waste' => 0,
        'metal_waste' => 0,
        'glass_waste' => 0,
        'food_waste' => 0,
        'cigarette_litter' => 0,
        'textiles' => 0,
        'medical_waste' => 0,
        'sanitary_products' => 0,
        'fishing_gear' => 0,
        'styrofoam_hazardous_waste' => 0,
        'miscellaneous' => 0,
        'derelict_items' => 0,
        'trees_planted' => 0,
        'invasive_species_removed' => 0,
        'volunteers_involved' => 0,
        'invasive_species_weight' => 0,
        'unsorted_litter' => 0,
        'bags_collected' => 0,
    ];

    $item_counts = [
        'plastic_items' => 0,
        'paper_items' => 0,
        'metal_items' => 0,
        'glass_items' => 0,
        'food_items' => 0,
        'cigarette_items' => 0,
        'textiles_items' => 0,
        'medical_items' => 0,
        'sanitary_items' => 0,
        'fishing_items' => 0,
        'styrofoam_items' => 0,
        'miscellaneous_items' => 0,
        'derelict_items' => 0,
    ];

    // Explicit mapping of logical keys → meta keys for weight metrics
    $weight_meta_map = [
        'plastic_waste'             => 'csr_plastic_waste_weight',
        'paper_waste'               => 'csr_paper_waste_weight',
        'metal_waste'               => 'csr_metal_waste_weight',
        'glass_waste'               => 'csr_glass_waste_weight',
        'food_waste'                => 'csr_food_waste_weight',
        'cigarette_litter'          => 'csr_cigarette_litter_weight',
        'textiles'                  => 'csr_textiles_weight',
        'medical_waste'             => 'csr_medical_waste_weight',
        'sanitary_products'         => 'csr_sanitary_products_weight',
        'fishing_gear'              => 'csr_fishing_gear_weight',
        'styrofoam_hazardous_waste' => 'csr_styrofoam_hazardous_waste_weight',
        'miscellaneous'             => 'csr_miscellaneous_weight',
        'derelict_items'            => 'csr_derelict_items_weight',
        'unsorted_litter'           => 'csr_unsorted_litter_weight',
        'invasive_species_weight'   => 'csr_invasive_species_weight',
    ];


    if (!empty($query->posts)) {
    foreach ($query->posts as $post_id) {

            // Aggregate weight metrics
            foreach ($weight_meta_map as $key => $meta_key) {
                $raw = get_post_meta($post_id, $meta_key, true);
                if ($raw !== '' && $raw !== null) {
                    $totals[$key] += floatval($raw);
                }
            }

            // Aggregate item counts from JSON subcategory counts
            foreach ($item_counts as $key => $value) {
                $meta_key = 'csr_' . str_replace('_items', '_subcategories_count', $key);
                $counts = json_decode(get_post_meta($post_id, $meta_key, true), true) ?: [];
                $item_counts[$key] += array_sum(array_map('intval', $counts));
            }

            $totals['trees_planted']            += intval(get_post_meta($post_id, 'csr_trees_planted', true));
            $totals['invasive_species_removed'] += intval(get_post_meta($post_id, 'csr_invasive_species_removed', true));
            $totals['volunteers_involved']      += intval(get_post_meta($post_id, 'csr_volunteers_involved', true));

            // Bags collected from unsorted litter JSON
            $bags_count = json_decode(get_post_meta($post_id, 'csr_unsorted_litter_subcategories_count', true), true);
            if (is_array($bags_count) && isset($bags_count['Number of Unsorted Bags Collected'])) {
                $totals['bags_collected'] += intval($bags_count['Number of Unsorted Bags Collected']);
            }
        }
    }

    // Add base volunteers to cumulative total
    $totals['volunteers_involved'] += $base_volunteers;

    return ['totals' => $totals, 'item_counts' => $item_counts];
}

// Compute yearly totals for a given calendar year (based on csr_date)
function ecoservants_get_yearly_totals($year) {
    $year = intval($year);
    if ($year <= 0) {
        return [
            'year'         => $year,
            'report_count' => 0,
            'totals'       => [],
            'item_counts'  => [],
        ];
    }

    $start_date = sprintf('%04d-01-01', $year);
    $end_date   = sprintf('%04d-12-31', $year);

    $args = [
        'post_type'      => 'csr_report',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
        'meta_query'     => [
            [
                'key'     => 'csr_date',
                'value'   => [$start_date, $end_date],
                'compare' => 'BETWEEN',
                'type'    => 'DATE',
            ],
        ],
        'fields' => 'ids',
    ];

    $query = new WP_Query($args);
    if (!empty($query->posts)) {
    update_meta_cache('post', $query->posts);
    }
    $totals = [
        'plastic_waste' => 0,
        'paper_waste' => 0,
        'metal_waste' => 0,
        'glass_waste' => 0,
        'food_waste' => 0,
        'cigarette_litter' => 0,
        'textiles' => 0,
        'medical_waste' => 0,
        'sanitary_products' => 0,
        'fishing_gear' => 0,
        'styrofoam_hazardous_waste' => 0,
        'miscellaneous' => 0,
        'derelict_items' => 0,
        'trees_planted' => 0,
        'invasive_species_removed' => 0,
        'volunteers_involved' => 0,
        'invasive_species_weight' => 0,
        'unsorted_litter' => 0,
        'bags_collected' => 0,
    ];

    $item_counts = [
        'plastic_items' => 0,
        'paper_items' => 0,
        'metal_items' => 0,
        'glass_items' => 0,
        'food_items' => 0,
        'cigarette_items' => 0,
        'textiles_items' => 0,
        'medical_items' => 0,
        'sanitary_items' => 0,
        'fishing_items' => 0,
        'styrofoam_items' => 0,
        'miscellaneous_items' => 0,
        'derelict_items' => 0,
    ];

    $weight_meta_map = [
        'plastic_waste'             => 'csr_plastic_waste_weight',
        'paper_waste'               => 'csr_paper_waste_weight',
        'metal_waste'               => 'csr_metal_waste_weight',
        'glass_waste'               => 'csr_glass_waste_weight',
        'food_waste'                => 'csr_food_waste_weight',
        'cigarette_litter'          => 'csr_cigarette_litter_weight',
        'textiles'                  => 'csr_textiles_weight',
        'medical_waste'             => 'csr_medical_waste_weight',
        'sanitary_products'         => 'csr_sanitary_products_weight',
        'fishing_gear'              => 'csr_fishing_gear_weight',
        'styrofoam_hazardous_waste' => 'csr_styrofoam_hazardous_waste_weight',
        'miscellaneous'             => 'csr_miscellaneous_weight',
        'derelict_items'            => 'csr_derelict_items_weight',
        'unsorted_litter'           => 'csr_unsorted_litter_weight',
        'invasive_species_weight'   => 'csr_invasive_species_weight',
    ];

    $report_count = 0;


    if (!empty($query->posts)) {
    foreach ($query->posts as $post_id) {
            $cleanup_date = get_post_meta($post_id, 'csr_date', true);
            // Ignore missing or malformed dates
            if (!is_string($cleanup_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $cleanup_date)) {
                continue;
            }
            $cleanup_year = intval(substr($cleanup_date, 0, 4));
            if ($cleanup_year !== $year) {
                continue;
            }

            $report_count++;

            foreach ($weight_meta_map as $key => $meta_key) {
                $raw = get_post_meta($post_id, $meta_key, true);
                if ($raw !== '' && $raw !== null) {
                    $totals[$key] += floatval($raw);
                }
            }

            foreach ($item_counts as $key => $value) {
                $meta_key = 'csr_' . str_replace('_items', '_subcategories_count', $key);
                $counts = json_decode(get_post_meta($post_id, $meta_key, true), true) ?: [];
                $item_counts[$key] += array_sum(array_map('intval', $counts));
            }

            $totals['trees_planted']            += intval(get_post_meta($post_id, 'csr_trees_planted', true));
            $totals['invasive_species_removed'] += intval(get_post_meta($post_id, 'csr_invasive_species_removed', true));
            $totals['volunteers_involved']      += intval(get_post_meta($post_id, 'csr_volunteers_involved', true));

            $bags_count = json_decode(get_post_meta($post_id, 'csr_unsorted_litter_subcategories_count', true), true);
            if (is_array($bags_count) && isset($bags_count['Number of Unsorted Bags Collected'])) {
                $totals['bags_collected'] += intval($bags_count['Number of Unsorted Bags Collected']);
            }
        }
    }

    wp_reset_postdata();

    return [
        'year'         => $year,
        'report_count' => $report_count,
        'totals'       => $totals,
        'item_counts'  => $item_counts,
    ];
}

// Shortcode to display total impact metrics on the front end
function ecoservants_display_totals_shortcode() {
    $data = ecoservants_calculate_totals();
    $totals = $data['totals'];
    $item_counts = $data['item_counts'];

    // Define all possible metrics for circular display
    $all_circular_metrics = [
        [
            'label' => 'Plastic Waste',
            'value' => $totals['plastic_waste'],
            'unit' => 'lbs',
        ],
        [
            'label' => 'Paper Waste',
            'value' => $totals['paper_waste'],
            'unit' => 'lbs',
        ],
        [
            'label' => 'Metal Waste',
            'value' => $totals['metal_waste'],
            'unit' => 'lbs',
        ],
        [
            'label' => 'Glass Waste',
            'value' => $totals['glass_waste'],
            'unit' => 'lbs',
        ],
        [
            'label' => 'Food Waste',
            'value' => $totals['food_waste'],
            'unit' => 'lbs',
        ],
        [
            'label' => 'Cigarette Litter',
            'value' => $totals['cigarette_litter'],
            'unit' => 'lbs',
        ],
        [
            'label' => 'Textiles',
            'value' => $totals['textiles'],
            'unit' => 'lbs',
        ],
        [
            'label' => 'Medical Waste',
            'value' => $totals['medical_waste'],
            'unit' => 'lbs',
        ],
        [
            'label' => 'Sanitary Products',
            'value' => $totals['sanitary_products'],
            'unit' => 'lbs',
        ],
        [
            'label' => 'Fishing Gear',
            'value' => $totals['fishing_gear'],
            'unit' => 'lbs',
        ],
        [
            'label' => 'Styrofoam & Hazardous Waste',
            'value' => $totals['styrofoam_hazardous_waste'],
            'unit' => 'lbs',
        ],
        [
            'label' => 'Miscellaneous',
            'value' => $totals['miscellaneous'],
            'unit' => 'lbs',
        ],
        [
            'label' => 'Derelict Items',
            'value' => $totals['derelict_items'],
            'unit' => 'lbs',
        ],
        [
            'label' => 'Trees Planted',
            'value' => $totals['trees_planted'],
            'unit' => '',
        ],
        [
            'label' => 'Invasive Species Removed (sq ft)',
            'value' => $totals['invasive_species_removed'],
            'unit' => '',
        ],
        [
            'label' => 'Weight of Invasive Species Collected',
            'value' => $totals['invasive_species_weight'],
            'unit' => 'lbs',
        ],
        [
            'label' => 'Volunteers Involved',
            'value' => $totals['volunteers_involved'],
            'unit' => '',
        ],
        [
            'label' => 'Number of Unsorted Bags Collected',
            'value' => $totals['bags_collected'],
            'unit' => '',
        ],
    ];

    // Shuffle and pick 6 random metrics
    $circular_metrics = $all_circular_metrics;
    shuffle($circular_metrics);
    $circular_metrics = array_slice($circular_metrics, 0, 6);

    ob_start();
    ?>
    <div class="total-impact-metrics">
        <h2>Total Impact Metrics</h2>
        <div class="csr-circular-metrics">
            <?php foreach ($circular_metrics as $m): 
                $percent = 75; // You can make this dynamic if you want
                $circ = 2 * pi() * 44;
                $offset = $circ * (1 - $percent / 100);
                $value = is_numeric($m['value']) ? number_format($m['value'], ($m['unit'] === 'lbs' ? 1 : 0)) : $m['value'];
            ?>
                <div class="csr-circular-metric" title="<?php echo esc_attr($m['label']); ?>">
                    <div class="csr-circular-svg-wrap">
                        <svg class="csr-circular-svg" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="44" fill="none" stroke="#d7dff2" stroke-width="7"/>
                            <circle
                                cx="50" cy="50" r="44"
                                fill="none"
                                stroke="#243b7e"
                                stroke-width="7"
                                stroke-linecap="round"
                                stroke-dasharray="<?php echo $circ; ?>"
                                stroke-dashoffset="<?php echo $offset; ?>"
                                style="transition: stroke-dashoffset 0.6s;"
                            />
                        </svg>
                        <div class="csr-circular-value">
                            <?php echo esc_html($value); ?><?php if ($m['unit']) echo '<span class="csr-circular-unit">' . esc_html($m['unit']) . '</span>'; ?>
                        </div>
                    </div>
                    <div class="csr-circular-label"><?php echo esc_html($m['label']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="#" class="csr-view-all-btn" id="csr-view-all-btn" aria-expanded="false">View All Metrics</a>
        <div id="csr-all-metrics" style="display:none; margin-top:30px;">
            <ul>
                <?php foreach ($totals as $key => $value): ?>
                    <?php if (!in_array($key, ['trees_planted', 'invasive_species_removed', 'invasive_species_weight', 'volunteers_involved', 'bags_collected'])): ?>
                        <li><?php echo ucwords(str_replace('_', ' ', $key)); ?>: <span><?php echo number_format($value, 2); ?> lbs</span></li>
                    <?php endif; ?>
                <?php endforeach; ?>
                <li>Number of Unsorted Bags Collected: <span><?php echo number_format($totals['bags_collected']); ?></span></li>
            </ul>
            <h3>Restoration Metrics</h3>
            <ul>
                <li>Trees Planted: <span><?php echo number_format($totals['trees_planted']); ?></span></li>
                <li>Square Feet of Invasive Species Removed: <span><?php echo number_format($totals['invasive_species_removed']); ?></span></li>
                <li>Weight of Invasive Species Collected: <span><?php echo number_format($totals['invasive_species_weight'], 2); ?> lbs</span></li>
                <li>Volunteers Involved: <span><?php echo number_format($totals['volunteers_involved']); ?></span></li>
            </ul>
            <h3>Item Counts</h3>
            <ul>
                <?php foreach ($item_counts as $key => $value): ?>
                    <li><?php echo ucwords(str_replace('_', ' ', $key)); ?>: <span><?php echo number_format($value); ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <script>
    // Toggle "View All" metrics
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('csr-view-all-btn');
        var allMetrics = document.getElementById('csr-all-metrics');
        if (btn && allMetrics) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var expanded = btn.getAttribute('aria-expanded') === 'true';
                allMetrics.style.display = expanded ? 'none' : 'block';
                btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                btn.textContent = expanded ? 'View All Metrics' : 'Hide Metrics';
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('total_impact', 'ecoservants_display_totals_shortcode');

// Add admin page to view total impact metrics
function ecoservants_add_admin_page() {
    add_menu_page(
        'Total Impact Metrics',
        'Impact Metrics',
        'manage_options',
        'total-impact-metrics',
        'ecoservants_display_totals_admin',
        'dashicons-chart-bar',
        20
    );

    // Yearly Export submenu under Impact Metrics
    add_submenu_page(
        'total-impact-metrics',
        'Yearly Export',
        'Yearly Export',
        'manage_options',
        'csr-yearly-export',
        'ecoservants_yearly_export_page'
    );
}
add_action('admin_menu', 'ecoservants_add_admin_page');

// Determine min and max CSR years based on csr_date meta
function ecoservants_get_csr_year_range() {
    $current_year = (int) current_time('Y');
    $min_year     = $current_year;

    $q = new WP_Query([
        'post_type'      => 'csr_report',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'no_found_rows'  => true,
        'meta_key'       => 'csr_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => 'csr_date',
                'compare' => 'EXISTS',
            ],
        ],
    ]);

    if (!empty($q->posts)) {
        update_meta_cache('post', $q->posts);
        foreach ($q->posts as $post_id) {
            $date = (string) get_post_meta($post_id, 'csr_date', true);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $year = (int) substr($date, 0, 4);
                if ($year > 0) {
                    $min_year = min($min_year, $year);
                }
                break; // earliest valid date found
            }
        }
    }
    wp_reset_postdata();

    return [
        'min' => $min_year,
        'max' => $current_year,
    ];
}

// Render Yearly Export admin page
function ecoservants_yearly_export_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'ecoservants-csr'));
    }

    $range = ecoservants_get_csr_year_range();
    $min   = (int) $range['min'];
    $max   = (int) $range['max'];

    if ($min <= 0 || $min > $max) {
        $min = $max;
    }

    $current_year = (int) current_time('Y');
    $selected_year = isset($_GET['year']) ? max($min, min($max, intval($_GET['year']))) : $current_year;
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Yearly CSR Export', 'ecoservants-csr'); ?></h1>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('ecoservants_yearly_export', 'ecoservants_yearly_export_nonce'); ?>
            <input type="hidden" name="action" value="export_yearly_csr">
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="csr_export_year"><?php esc_html_e('Year', 'ecoservants-csr'); ?></label>
                                       </th>
                    <td>
                        <select name="year" id="csr_export_year">
                            <?php for ($y = $max; $y >= $min; $y--) : ?>
                                <option value="<?php echo esc_attr($y); ?>" <?php selected($selected_year, $y); ?>>
                                    <?php echo esc_html($y); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Download CSV', 'ecoservants-csr')); ?>
        </form>
    </div>
    <?php
}

// Display total impact metrics on the admin page
function ecoservants_display_totals_admin() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $data = ecoservants_calculate_totals();
    $totals = $data['totals'];
    $item_counts = $data['item_counts'];
    ?>
    <div class="wrap">
        <h1>Total Impact Metrics</h1>
        <table class="widefat fixed" style="max-width: 600px;">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Total Weight (lbs)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($totals as $key => $value): ?>
                    <?php if (!in_array($key, ['trees_planted', 'invasive_species_removed', 'invasive_species_weight', 'volunteers_involved', 'bags_collected'])): ?>
                        <tr>
                            <td><?php echo ucwords(str_replace('_', ' ', $key)); ?></td>
                            <td><?php echo number_format($value, 2); ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                <tr>
                    <td>Number of Unsorted Bags Collected</td>
                    <td><?php echo number_format($totals['bags_collected']); ?></td>
                </tr>
            </tbody>
        </table>
        <h2>Habitat Restoration Metrics</h2>
        <table class="widefat fixed" style="max-width: 600px;">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Trees Planted</td>
                    <td><?php echo number_format($totals['trees_planted']); ?></td>
                </tr>
                <tr>
                    <td>Square Feet of Invasive Species Removed</td>
                    <td><?php echo number_format($totals['invasive_species_removed']); ?></td>
                </tr>
                <tr>
                    <td>Weight of Invasive Species Collected (lbs)</td>
                    <td><?php echo number_format($totals['invasive_species_weight'], 2); ?></td>
                </tr>
                <tr>
                    <td>Volunteers Involved</td>
                    <td><?php echo number_format($totals['volunteers_involved']); ?></td>
                </tr>
            </tbody>
        </table>
        <h2>Item Counts</h2>
        <table class="widefat fixed" style="max-width: 600px;">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Total Items</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($item_counts as $key => $value): ?>
                    <tr>
                        <td><?php echo ucwords(str_replace('_', ' ', $key)); ?></td>
                        <td><?php echo number_format($value); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Shortcode to display only the highest impact metrics
function ecoservants_display_top_metrics_shortcode( $atts = [] ) {
    // Attributes: [top_impact limit="6" min="0"]
    $atts = shortcode_atts([
        'limit' => 6,   // how many metrics to show
        'min'   => 0,   // minimum value required to be included
    ], $atts, 'top_impact');

    $limit = max(1, (int) $atts['limit']);
    $min   = (float) $atts['min'];

    $data   = ecoservants_calculate_totals();
    $totals = $data['totals'];

    // Define the metrics we want to rank by size
    $metric_defs = [
        [
            'key'   => 'plastic_waste',
            'label' => 'Plastic Waste',
            'unit'  => 'lbs',
        ],
        [
            'key'   => 'paper_waste',
            'label' => 'Paper Waste',
            'unit'  => 'lbs',
        ],
        [
            'key'   => 'metal_waste',
            'label' => 'Metal Waste',
            'unit'  => 'lbs',
        ],
        [
            'key'   => 'glass_waste',
            'label' => 'Glass Waste',
            'unit'  => 'lbs',
        ],
        [
            'key'   => 'food_waste',
            'label' => 'Food Waste',
            'unit'  => 'lbs',
        ],
        [
            'key'   => 'cigarette_litter',
            'label' => 'Cigarette Litter',
            'unit'  => 'lbs',
        ],
        [
            'key'   => 'textiles',
            'label' => 'Textiles',
            'unit'  => 'lbs',
        ],
        [
            'key'   => 'medical_waste',
            'label' => 'Medical Waste',
            'unit'  => 'lbs',
        ],
        [
            'key'   => 'sanitary_products',
            'label' => 'Sanitary Products',
            'unit'  => 'lbs',
        ],
        [
            'key'   => 'fishing_gear',
            'label' => 'Fishing Gear',
            'unit'  => 'lbs',
        ],
        [
            'key'   => 'styrofoam_hazardous_waste',
            'label' => 'Styrofoam & Hazardous Waste',
            'unit'  => 'lbs',
        ],
        [
            'key'   => 'miscellaneous',
            'label' => 'Miscellaneous',
            'unit'  => 'lbs',
        ],
        [
            'key'   => 'derelict_items',
            'label' => 'Derelict Items',
            'unit'  => 'lbs',
        ],
        [
            'key'   => 'trees_planted',
            'label' => 'Trees Planted',
            'unit'  => '',
        ],
        [
            'key'   => 'invasive_species_removed',
            'label' => 'Invasive Species Removed (sq ft)',
            'unit'  => '',
        ],
        [
            'key'   => 'invasive_species_weight',
            'label' => 'Weight of Invasive Species Collected',
            'unit'  => 'lbs',
        ],
        [
            'key'   => 'volunteers_involved',
            'label' => 'Volunteers Involved',
            'unit'  => '',
        ],
        [
            'key'   => 'bags_collected',
            'label' => 'Number of Unsorted Bags Collected',
            'unit'  => '',
        ],
    ];

    // Build a flat list with actual values
    $metrics = [];
    foreach ( $metric_defs as $def ) {
        $key   = $def['key'];
        $value = isset($totals[$key]) ? (float) $totals[$key] : 0;

        if ( $value <= $min ) {
            continue; // skip zero or below-min metrics
        }

        $metrics[] = [
            'label' => $def['label'],
            'value' => $value,
            'unit'  => $def['unit'],
        ];
    }

    // Sort highest-first
    usort($metrics, function($a, $b) {
        if ($a['value'] === $b['value']) return 0;
        return ($a['value'] < $b['value']) ? 1 : -1;
    });

    // Slice to requested limit
    $metrics = array_slice($metrics, 0, $limit);

    if (empty($metrics)) {
        return '<p>No impact metrics recorded yet.</p>';
    }

    ob_start();
    ?>
    <div class="total-impact-metrics csr-top-impact-metrics">
        <h2>Top Impact Metrics</h2>
        <div class="csr-circular-metrics">
            <?php foreach ($metrics as $m): 
                // same circular style as [total_impact]
                $percent = 75; // static visual for now; can be made dynamic
                $circ    = 2 * pi() * 44;
                $offset  = $circ * (1 - $percent / 100);
                $value   = is_numeric($m['value'])
                    ? number_format($m['value'], ($m['unit'] === 'lbs' ? 1 : 0))
                    : $m['value'];
            ?>
                <div class="csr-circular-metric" title="<?php echo esc_attr($m['label']); ?>">
                    <div class="csr-circular-svg-wrap">
                        <svg class="csr-circular-svg" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="44" fill="none" stroke="#d7dff2" stroke-width="7"></circle>
                            <circle
                                cx="50" cy="50" r="44"
                                fill="none"
                                stroke="#243b7e"
                                stroke-width="7"
                                stroke-linecap="round"
                                stroke-dasharray="<?php echo $circ; ?>"
                                stroke-dashoffset="<?php echo $offset; ?>"
                                style="transition: stroke-dashoffset 0.6s;"
                            ></circle>
                        </svg>
                        <div class="csr-circular-value">
                            <?php echo esc_html($value); ?>
                            <?php if ($m['unit']) : ?>
                                <span class="csr-circular-unit"><?php echo esc_html($m['unit']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="csr-circular-label"><?php echo esc_html($m['label']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('top_impact', 'ecoservants_display_top_metrics_shortcode');

// Add "Export" action for single CSR reports (uses POST with nonce)
function ecoservants_add_export_link($actions, $post) {
    if ($post->post_type !== 'csr_report') {
        return $actions;
    }
    if (!current_user_can('manage_options')) {
        return $actions;
    }

    $url   = admin_url('admin-post.php');
    $nonce = wp_create_nonce('ecoservants_single_export');

    $form  = '<form method="post" action="' . esc_url($url) . '" style="display:inline;">';
    $form .= '<input type="hidden" name="action" value="export_single_csr" />';
    $form .= '<input type="hidden" name="post_id" value="' . esc_attr($post->ID) . '" />';
    $form .= '<input type="hidden" name="ecoservants_single_export_nonce" value="' . esc_attr($nonce) . '" />';
    $form .= '<button type="submit" class="button-link">' . esc_html__('Export', 'ecoservants-csr') . '</button>';
    $form .= '</form>';

    $actions['export'] = $form;
    return $actions;
}
add_filter('post_row_actions', 'ecoservants_add_export_link', 10, 2);

// Handle single CSR report export (streams CSV)
function ecoservants_export_single_csr() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to export this report.', 'ecoservants-csr'));
    }

    if (
        !isset($_POST['ecoservants_single_export_nonce']) ||
        !wp_verify_nonce($_POST['ecoservants_single_export_nonce'], 'ecoservants_single_export')
    ) {
        wp_die(esc_html__('Invalid export request.', 'ecoservants-csr'));
    }

    if (!isset($_POST['post_id'])) {
        wp_die(esc_html__('Missing report ID.', 'ecoservants-csr'));
    }

    $post_id = intval($_POST['post_id']);
    if ($post_id <= 0) {
        wp_die(esc_html__('Invalid report ID.', 'ecoservants-csr'));
    }

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'csr_report') {
        wp_die(esc_html__('Invalid CSR report.', 'ecoservants-csr'));
    }

    nocache_headers();

    $filename = 'csr_report_' . $post_id . '_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=' . get_option('blog_charset'));
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    if ($output === false) {
        wp_die(esc_html__('Failed to open output stream.', 'ecoservants-csr'));
    }

    // CSV headers in stable order
    $headers = [
        'Name', 'Email', 'Date', 'Location', 'Plastic Waste (lbs)', 'Paper Waste (lbs)',
        'Metal Waste (lbs)', 'Glass Waste (lbs)', 'Food Waste (lbs)', 'Cigarette Litter (lbs)',
        'Textiles (lbs)', 'Medical Waste (lbs)', 'Sanitary Products (lbs)', 'Fishing Gear (lbs)',
        'Styrofoam & Hazardous Waste (lbs)', 'Miscellaneous (lbs)', 'Derelict Items (lbs)',
        'Trees Planted', 'Invasive Species Removed (sq ft)', 'Invasive Species Weight (lbs)',
        'Volunteers Involved', 'Notes', 'Unsorted Litter (lbs)', 'Number of Unsorted Bags Collected',
    ];
    fputcsv($output, $headers);

    $unsorted_litter_subcategories_count = json_decode(
        get_post_meta($post_id, 'csr_unsorted_litter_subcategories_count', true),
        true
    );
    $bags_collected = 0;
    if (is_array($unsorted_litter_subcategories_count) && isset($unsorted_litter_subcategories_count['Number of Unsorted Bags Collected'])) {
        $bags_collected = intval($unsorted_litter_subcategories_count['Number of Unsorted Bags Collected']);
    }

    $data = [
        get_post_meta($post_id, 'csr_name', true),
        get_post_meta($post_id, 'csr_email', true),
        get_post_meta($post_id, 'csr_date', true),
        get_post_meta($post_id, 'csr_location', true),
        get_post_meta($post_id, 'csr_plastic_waste_weight', true),
        get_post_meta($post_id, 'csr_paper_waste_weight', true),
        get_post_meta($post_id, 'csr_metal_waste_weight', true),
        get_post_meta($post_id, 'csr_glass_waste_weight', true),
        get_post_meta($post_id, 'csr_food_waste_weight', true),
        get_post_meta($post_id, 'csr_cigarette_litter_weight', true),
        get_post_meta($post_id, 'csr_textiles_weight', true),
        get_post_meta($post_id, 'csr_medical_waste_weight', true),
        get_post_meta($post_id, 'csr_sanitary_products_weight', true),
        get_post_meta($post_id, 'csr_fishing_gear_weight', true),
        get_post_meta($post_id, 'csr_styrofoam_hazardous_waste_weight', true),
        get_post_meta($post_id, 'csr_miscellaneous_weight', true),
        get_post_meta($post_id, 'csr_derelict_items_weight', true),
        get_post_meta($post_id, 'csr_trees_planted', true),
        get_post_meta($post_id, 'csr_invasive_species_removed', true),
        get_post_meta($post_id, 'csr_invasive_species_weight', true),
        get_post_meta($post_id, 'csr_volunteers_involved', true),
        get_post_meta($post_id, 'csr_notes', true),
        get_post_meta($post_id, 'csr_unsorted_litter_weight', true),
        $bags_collected,
    ];
    fputcsv($output, $data);

    fclose($output);
    exit;
}
add_action('admin_post_export_single_csr', 'ecoservants_export_single_csr');

// Yearly CSR export handler (streams CSV)
function ecoservants_export_yearly_csr() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to export data.', 'ecoservants-csr'));
    }

    if (
        !isset($_POST['ecoservants_yearly_export_nonce']) ||
        !wp_verify_nonce($_POST['ecoservants_yearly_export_nonce'], 'ecoservants_yearly_export')
    ) {
        wp_die(esc_html__('Invalid export request.', 'ecoservants-csr'));
    }

    if (!isset($_POST['year'])) {
        wp_die(esc_html__('Year is required.', 'ecoservants-csr'));
    }

    $year = intval($_POST['year']);
    if ($year <= 0) {
        wp_die(esc_html__('Invalid year.', 'ecoservants-csr'));
    }

    $result      = ecoservants_get_yearly_totals($year);
    $totals      = isset($result['totals']) ? $result['totals'] : [];
    $item_counts = isset($result['item_counts']) ? $result['item_counts'] : [];
    $report_count = isset($result['report_count']) ? intval($result['report_count']) : 0;

    $filename = sprintf('csr_yearly_export_%d_%s.csv', $year, date('Y-m-d_H-i-s'));

    nocache_headers();
    header('Content-Type: text/csv; charset=' . get_option('blog_charset'));
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    if ($output === false) {
        wp_die(esc_html__('Failed to open output stream.', 'ecoservants-csr'));
    }

    // Stable header order
    $headers = [
        'Year',
        'Report Count',
        'Plastic Waste (lbs)',
        'Paper Waste (lbs)',
        'Metal Waste (lbs)',
        'Glass Waste (lbs)',
        'Food Waste (lbs)',
        'Cigarette Litter (lbs)',
        'Textiles (lbs)',
        'Medical Waste (lbs)',
        'Sanitary Products (lbs)',
        'Fishing Gear (lbs)',
        'Styrofoam & Hazardous Waste (lbs)',
        'Miscellaneous (lbs)',
        'Derelict Items (lbs)',
        'Unsorted Litter (lbs)',
        'Trees Planted',
        'Invasive Species Removed (sq ft)',
        'Invasive Species Weight (lbs)',
        'Volunteers Involved',
        'Number of Unsorted Bags Collected',
        'Plastic Items',
        'Paper Items',
        'Metal Items',
        'Glass Items',
        'Food Items',
        'Cigarette Items',
        'Textiles Items',
        'Medical Items',
        'Sanitary Items',
        'Fishing Items',
        'Styrofoam Items',
        'Miscellaneous Items',
        'Derelict Items (count)',
    ];
    fputcsv($output, $headers);

    $row = [];

    $row[] = $year;
    $row[] = $report_count;

    $row[] = isset($totals['plastic_waste']) ? floatval($totals['plastic_waste']) : 0;
    $row[] = isset($totals['paper_waste']) ? floatval($totals['paper_waste']) : 0;
    $row[] = isset($totals['metal_waste']) ? floatval($totals['metal_waste']) : 0;
    $row[] = isset($totals['glass_waste']) ? floatval($totals['glass_waste']) : 0;
    $row[] = isset($totals['food_waste']) ? floatval($totals['food_waste']) : 0;
    $row[] = isset($totals['cigarette_litter']) ? floatval($totals['cigarette_litter']) : 0;
    $row[] = isset($totals['textiles']) ? floatval($totals['textiles']) : 0;
    $row[] = isset($totals['medical_waste']) ? floatval($totals['medical_waste']) : 0;
    $row[] = isset($totals['sanitary_products']) ? floatval($totals['sanitary_products']) : 0;
    $row[] = isset($totals['fishing_gear']) ? floatval($totals['fishing_gear']) : 0;
    $row[] = isset($totals['styrofoam_hazardous_waste']) ? floatval($totals['styrofoam_hazardous_waste']) : 0;
    $row[] = isset($totals['miscellaneous']) ? floatval($totals['miscellaneous']) : 0;
    $row[] = isset($totals['derelict_items']) ? floatval($totals['derelict_items']) : 0;
    $row[] = isset($totals['unsorted_litter']) ? floatval($totals['unsorted_litter']) : 0;

    $row[] = isset($totals['trees_planted']) ? intval($totals['trees_planted']) : 0;
    $row[] = isset($totals['invasive_species_removed']) ? intval($totals['invasive_species_removed']) : 0;
    $row[] = isset($totals['invasive_species_weight']) ? floatval($totals['invasive_species_weight']) : 0;
    $row[] = isset($totals['volunteers_involved']) ? intval($totals['volunteers_involved']) : 0;
    $row[] = isset($totals['bags_collected']) ? intval($totals['bags_collected']) : 0;

    $row[] = isset($item_counts['plastic_items']) ? intval($item_counts['plastic_items']) : 0;
    $row[] = isset($item_counts['paper_items']) ? intval($item_counts['paper_items']) : 0;
    $row[] = isset($item_counts['metal_items']) ? intval($item_counts['metal_items']) : 0;
    $row[] = isset($item_counts['glass_items']) ? intval($item_counts['glass_items']) : 0;
    $row[] = isset($item_counts['food_items']) ? intval($item_counts['food_items']) : 0;
    $row[] = isset($item_counts['cigarette_items']) ? intval($item_counts['cigarette_items']) : 0;
    $row[] = isset($item_counts['textiles_items']) ? intval($item_counts['textiles_items']) : 0;
    $row[] = isset($item_counts['medical_items']) ? intval($item_counts['medical_items']) : 0;
    $row[] = isset($item_counts['sanitary_items']) ? intval($item_counts['sanitary_items']) : 0;
    $row[] = isset($item_counts['fishing_items']) ? intval($item_counts['fishing_items']) : 0;
    $row[] = isset($item_counts['styrofoam_items']) ? intval($item_counts['styrofoam_items']) : 0;
    $row[] = isset($item_counts['miscellaneous_items']) ? intval($item_counts['miscellaneous_items']) : 0;
    $row[] = isset($item_counts['derelict_items']) ? intval($item_counts['derelict_items']) : 0;

    fputcsv($output, $row);
    fclose($output);
    exit;
}
add_action('admin_post_export_yearly_csr', 'ecoservants_export_yearly_csr');

// AJAX handler for lazy loading Wall of Fame entries
function ecoservants_load_wall_of_fame() {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $args = array(
        'post_type' => 'csr_report',
        'posts_per_page' => 10,
        'paged' => $page,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
    );
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $name = get_post_meta(get_the_ID(), 'csr_name', true);
            $location = get_post_meta(get_the_ID(), 'csr_location', true);
            $photos = get_post_meta(get_the_ID(), 'csr_photos', true);
            $photos_array = $photos ? explode(',', $photos) : [];
            $trees_planted = intval(get_post_meta(get_the_ID(), 'csr_trees_planted', true));
            $invasive_species_removed = intval(get_post_meta(get_the_ID(), 'csr_invasive_species_removed', true));
            $invasive_species_weight = floatval(get_post_meta(get_the_ID(), 'csr_invasive_species_weight', true));
            $unsorted_litter_weight = floatval(get_post_meta(get_the_ID(), 'csr_unsorted_litter_weight', true));
            $unsorted_litter_subcategories_count = json_decode(get_post_meta(get_the_ID(), 'csr_unsorted_litter_subcategories_count', true), true);
            $bags_collected = isset($unsorted_litter_subcategories_count['Number of Unsorted Bags Collected']) ? intval($unsorted_litter_subcategories_count['Number of Unsorted Bags Collected']) : 0;

            echo '<div class="wall-of-fame-entry">';
            echo '<div class="card">';
            echo '<h3>' . esc_html($name) . '</h3>';
            echo '<p class="location">Location: ' . esc_html($location) . '</p>';

            // Display Habitat Restoration metrics if available
            if ($trees_planted > 0) {
                echo '<p class="total-weight">Trees Planted: <span>' . esc_html(number_format($trees_planted)) . '</span></p>';
            }
            if ($invasive_species_removed > 0) {
                echo '<p class="total-weight">Square Feet of Invasive Species Removed: <span>' . esc_html(number_format($invasive_species_removed)) . '</span></p>';
            }
            if ($invasive_species_weight > 0) {
                echo '<p class="total-weight">Weight of Invasive Species Collected: <span>' . esc_html(number_format($invasive_species_weight, 2)) . ' lbs</span></p>';
            }
            if ($unsorted_litter_weight > 0) {
                echo '<p class="total-weight">Unsorted Litter: <span>' . esc_html(number_format($unsorted_litter_weight, 2)) . ' lbs</span></p>';
            }
            if ($bags_collected > 0) {
                echo '<p class="total-weight">Number of Unsorted Bags Collected: <span>' . esc_html(number_format($bags_collected)) . '</span></p>';
            }

            // Calculate and display total litter weight using canonical meta keys
            $total_weight = 0;
            $weight_meta_keys = [
                'csr_plastic_waste_weight',
                'csr_paper_waste_weight',
                'csr_metal_waste_weight',
                'csr_glass_waste_weight',
                'csr_food_waste_weight',
                'csr_cigarette_litter_weight',
                'csr_textiles_weight',
                'csr_medical_waste_weight',
                'csr_sanitary_products_weight',
                'csr_fishing_gear_weight',
                'csr_styrofoam_hazardous_waste_weight',
                'csr_miscellaneous_weight',
                'csr_derelict_items_weight',
            ];
            foreach ($weight_meta_keys as $meta_key) {
                $weight = get_post_meta(get_the_ID(), $meta_key, true);
                if ($weight !== false && $weight !== '') {
                    $total_weight += floatval($weight);
                }
            }
            if ($total_weight > 0) {
                echo '<p class="total-weight">Total Sorted Litter Picked Up: <span>' . esc_html(number_format($total_weight, 2)) . ' lbs</span></p>';
            }

            if (!empty($photos_array)) {
                echo '<div class="photos">';
                foreach ($photos_array as $photo_id) {
                    $photo_url = wp_get_attachment_image_url($photo_id, 'thumbnail'); // Retrieve thumbnail size
                    $full_url = wp_get_attachment_url($photo_id); // Full size
                    if ($photo_url && $full_url) {
                        echo '<img src="' . esc_url($photo_url) . '" alt="Uploaded Photo" class="wall-of-fame-photo" data-full="' . esc_url($full_url) . '">';
                    }
                }
                echo '</div>';
            }
            echo '<p class="highlight">Thank you for making a difference!</p>';
            echo '</div>';
            echo '</div>';
        }
    }
    wp_reset_postdata();
    wp_die();
}
add_action('wp_ajax_load_wall_of_fame', 'ecoservants_load_wall_of_fame');
add_action('wp_ajax_nopriv_load_wall_of_fame', 'ecoservants_load_wall_of_fame');
?>
