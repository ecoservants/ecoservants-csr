<?php
// Public CSR impact summary shortcode (Issue #22)
// Usage: [csr_impact_summary]  or  [csr_impact_summary year="2026" top="6"]
// Reuses ecoservants_calculate_totals() / ecoservants_get_yearly_totals().
// Renders aggregate totals only - no reporter names, emails, or metadata.

if (!defined('ABSPATH')) {
    exit;
}

// Weight categories that sum into total pounds (mirrors the weight_meta_map)
function ecoservants_summary_weight_defs() {
    return [
        ['key' => 'plastic_waste',             'label' => 'Plastic Waste'],
        ['key' => 'paper_waste',               'label' => 'Paper Waste'],
        ['key' => 'metal_waste',               'label' => 'Metal Waste'],
        ['key' => 'glass_waste',               'label' => 'Glass Waste'],
        ['key' => 'food_waste',                'label' => 'Food Waste'],
        ['key' => 'cigarette_litter',          'label' => 'Cigarette Litter'],
        ['key' => 'textiles',                  'label' => 'Textiles'],
        ['key' => 'medical_waste',             'label' => 'Medical Waste'],
        ['key' => 'sanitary_products',         'label' => 'Sanitary Products'],
        ['key' => 'fishing_gear',              'label' => 'Fishing Gear'],
        ['key' => 'styrofoam_hazardous_waste', 'label' => 'Styrofoam & Hazardous Waste'],
        ['key' => 'miscellaneous',             'label' => 'Miscellaneous'],
        ['key' => 'derelict_items',            'label' => 'Derelict Items'],
        ['key' => 'unsorted_litter',           'label' => 'Unsorted Litter'],
        ['key' => 'invasive_species_weight',   'label' => 'Weight of Invasive Species Collected'],
    ];
}

// Add up every weight category into one pounds figure
function ecoservants_summary_total_pounds($totals) {
    $pounds = 0;
    foreach (ecoservants_summary_weight_defs() as $def) {
        if (isset($totals[$def['key']])) {
            $pounds += (float) $totals[$def['key']];
        }
    }
    return $pounds;
}

// Highest categories first, sliced to $limit
function ecoservants_summary_top_categories($totals, $limit) {
    $rows = [];
    foreach (ecoservants_summary_weight_defs() as $def) {
        $value = isset($totals[$def['key']]) ? (float) $totals[$def['key']] : 0;
        if ($value > 0) {
            $rows[] = ['key' => $def['key'], 'label' => $def['label'], 'value' => $value];
        }
    }

    usort($rows, function($a, $b) {
        if ($a['value'] === $b['value']) return 0;
        return ($a['value'] < $b['value']) ? 1 : -1;
    });

    return array_slice($rows, 0, $limit);
}

// One circular metric card - same markup as [top_impact] so the CSS applies
function ecoservants_summary_render_metric($value, $unit, $label, $category_key = '') {
    $percent = 75;
    $circ    = 2 * pi() * 44;
    $offset  = $circ * (1 - $percent / 100);
    $display = is_numeric($value)
        ? number_format($value, ($unit === 'lbs' ? 1 : 0))
        : $value;
    $icon_svg = ($category_key && function_exists('csr_get_category_icon_svg'))
        ? csr_get_category_icon_svg($category_key, ['size' => 18, 'class' => 'csr-summary-category-icon-svg'])
        : '';
    ?>
    <div class="csr-circular-metric" title="<?php echo esc_attr($label); ?>">
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
                <?php echo esc_html($display); ?>
                <?php if ($unit) : ?>
                    <span class="csr-circular-unit"><?php echo esc_html($unit); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="csr-circular-label">
            <?php if ($icon_svg) : ?>
                <span class="csr-summary-category-icon" aria-hidden="true"><?php echo $icon_svg; ?></span>
            <?php endif; ?>
            <?php echo esc_html($label); ?>
        </div>
    </div>
    <?php
}

function ecoservants_impact_summary_shortcode($atts = []) {
    if (function_exists('ecoservants_enqueue_scripts')) { ecoservants_enqueue_scripts(); }
    // Attributes: [csr_impact_summary year="2026" top="4"]
    $atts = shortcode_atts([
        'year' => '',
        'top'  => 4,
    ], $atts, 'csr_impact_summary');

    $year  = trim((string) $atts['year']);
    $limit = max(0, (int) $atts['top']);

    if ($year !== '' && !preg_match('/^\d{4}$/', $year)) {
        return '<p>Please provide a four-digit year, e.g. [csr_impact_summary year="2026"].</p>';
    }

    if ($year !== '') {
        // Per-year view - yearly totals don't include the seeded base count
        $data             = ecoservants_get_yearly_totals((int) $year);
        $totals           = isset($data['totals']) ? $data['totals'] : [];
        $report_count     = isset($data['report_count']) ? (int) $data['report_count'] : 0;
        $heading          = sprintf('CSR Impact in %s', $year);
        $volunteers_label = 'Volunteers This Year';
    } else {
        // All-time view - calculate_totals() seeds a base volunteer count,
        // so this figure sits on a different basis than the per-year view
        $data             = ecoservants_calculate_totals();
        $totals           = isset($data['totals']) ? $data['totals'] : [];
        $counts           = wp_count_posts('csr_report');
        $report_count     = isset($counts->publish) ? (int) $counts->publish : 0;
        $heading          = 'CSR Impact to Date';
        $volunteers_label = 'Volunteers Involved';
    }

    $pounds     = ecoservants_summary_total_pounds($totals);
    $volunteers = isset($totals['volunteers_involved']) ? (int) $totals['volunteers_involved'] : 0;
    $trees      = isset($totals['trees_planted']) ? (int) $totals['trees_planted'] : 0;
    $bags       = isset($totals['bags_collected']) ? (int) $totals['bags_collected'] : 0;
    $invasive   = isset($totals['invasive_species_removed']) ? (int) $totals['invasive_species_removed'] : 0;

    ob_start();
    ?>
    <div class="total-impact-metrics csr-impact-summary">
        <h2><?php echo esc_html($heading); ?></h2>

        <?php if ($report_count === 0) : ?>
            <p>No Community Site Reports have been recorded for this period yet.</p>
        <?php else : ?>
            <div class="csr-circular-metrics">
                <?php
                ecoservants_summary_render_metric($report_count, '', 'Reports Submitted');
                ecoservants_summary_render_metric($pounds, 'lbs', 'Material Removed');

                if ($volunteers > 0) {
                    ecoservants_summary_render_metric($volunteers, '', $volunteers_label);
                }
                if ($bags > 0) {
                    ecoservants_summary_render_metric($bags, '', 'Number of Unsorted Bags Collected');
                }
                if ($trees > 0) {
                    ecoservants_summary_render_metric($trees, '', 'Trees Planted');
                }
                if ($invasive > 0) {
                    ecoservants_summary_render_metric($invasive, '', 'Invasive Species Removed (sq ft)');
                }
                ?>
            </div>

            <?php
            $top = $limit > 0 ? ecoservants_summary_top_categories($totals, $limit) : [];
            if (!empty($top)) :
            ?>
                <h3>Top Categories</h3>
                <div class="csr-circular-metrics">
                    <?php foreach ($top as $row) : ?>
                        <?php ecoservants_summary_render_metric($row['value'], 'lbs', $row['label'], $row['key']); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('csr_impact_summary', 'ecoservants_impact_summary_shortcode');
