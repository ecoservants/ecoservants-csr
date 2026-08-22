<div class="csr-form-container">
    <h2>Community Site Report</h2>
    <!-- Voice Assistant UI -->
    <div id="csr-voice-assistant" aria-live="polite" class="csr-voice-assistant">
        <button type="button" id="csr-voice-btn" aria-label="Start voice input">
            <span id="csr-voice-icon" aria-hidden="true">🎤</span> Voice Input
        </button>
        <span id="csr-voice-status" class="csr-voice-status" aria-live="polite"></span>
    </div>
    <form class="csr-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field('csr_form_nonce', 'csr_form_nonce_field'); ?>
        <input type="hidden" name="action" value="csr_form">
        <input type="hidden" name="csr_submit" value="1">
        <input type="hidden" name="csr_return_url" value="<?php echo esc_url(get_permalink()); ?>">

        <!-- Step 1: Reporter and event details -->
        <div class="csr-step" data-step="1" data-step-title="About You and Your Event">
        <div class="csr-guided-intro">
            <span class="csr-guided-kicker">Community Site Report</span>
            <h3>Tell us who is making an impact</h3>
            <p>Choose the reporting path that best fits your cleanup or restoration activity. Individual reporting is fully available today; team and organization information is collected now while expanded dashboards are being developed.</p>
        </div>

        <fieldset class="csr-reporter-path" aria-labelledby="csr-reporter-path-title">
            <legend id="csr-reporter-path-title">Who is submitting this report?</legend>
            <div class="csr-path-grid">
                <label class="csr-path-card">
                    <input type="radio" name="csr_reporter_type" value="individual" checked>
                    <span class="csr-path-icon" aria-hidden="true">👤</span>
                    <strong>Individual Volunteer</strong>
                    <small>Report your own cleanup or restoration activity.</small>
                </label>
                <label class="csr-path-card">
                    <input type="radio" name="csr_reporter_type" value="team">
                    <span class="csr-path-icon" aria-hidden="true">👥</span>
                    <strong>Volunteer Team</strong>
                    <small>Record a group, class, club, or community event.</small>
                </label>
                <label class="csr-path-card">
                    <input type="radio" name="csr_reporter_type" value="organization">
                    <span class="csr-path-icon" aria-hidden="true">🏢</span>
                    <strong>Company or Organization</strong>
                    <small>Capture participation for a partner or workplace event.</small>
                </label>
            </div>
        </fieldset>

        <div class="csr-organization-fields" hidden>
            <div class="csr-coming-soon">
                <strong>Organizational reporting preview</strong>
                <span>Team dashboards, branded summaries, goals, badges, and downloadable impact reports are planned for the full CSR experience.</span>
            </div>
            <div class="csr-two-col">
                <div>
                    <label for="csr_organization_name">Organization or Team Name:</label>
                    <input type="text" id="csr_organization_name" name="csr_organization_name" placeholder="Example: GreenWorks Volunteer Team">
                </div>
                <div>
                    <label for="csr_team_name">Department, Class, or Group:</label>
                    <input type="text" id="csr_team_name" name="csr_team_name" placeholder="Optional">
                </div>
                <div>
                    <label for="csr_event_name">Event or Campaign Name:</label>
                    <input type="text" id="csr_event_name" name="csr_event_name" placeholder="Optional">
                </div>
                <div>
                    <label for="csr_internal_reference">Internal Reference:</label>
                    <input type="text" id="csr_internal_reference" name="csr_internal_reference" placeholder="Optional event or campaign ID">
                </div>
            </div>
        </div>

        <div class="csr-two-col csr-basic-fields">
        <div>
            <label for="csr_name">Name:</label>
            <input type="text" id="csr_name" name="csr_name" required aria-label="Name">
        </div>
        <div>
            <label for="csr_email">Email:</label>
            <input type="email" id="csr_email" name="csr_email" required aria-label="Email">
        </div>
        <div>
            <label for="csr_date">Date:</label>
            <input type="date" id="csr_date" name="csr_date" required aria-label="Date">
        </div>
        <div class="csr-location-field">
            <label for="csr_location">Location:</label>
            <input type="text" id="csr_location" name="csr_location" required aria-label="Location" placeholder="City, state, park, shoreline, or site">
            <div class="csr-location-actions">
                <button type="button" id="csr-use-location" class="csr-use-location">
                    <span aria-hidden="true">📍</span> Use My Current Location
                </button>
                <span id="csr-location-status" class="csr-location-status" role="status" aria-live="polite"></span>
            </div>
            <small class="csr-location-help">You may use your device location or enter the location manually.</small>
        </div>
        </div>
        <p class="csr-save-note" role="status">Your progress is automatically saved on this device until the report is submitted.</p>
        </div>

        <!-- Step 2: major-category cards (Issue #7 wiring restored) plus
             contextual subcategory panels (Issue #8). Existing csr_* field
             names inside each detail block are unchanged. -->
        <div class="csr-step" data-step="2" data-step-title="Select What You Collected">
        <div class="csr-step-heading">
            <span class="csr-guided-kicker">Collection details</span>
            <h3>What did you collect?</h3>
            <p>Select every category that applies. Only the categories you choose will expand for weights and item counts.</p>
        </div>
        <div id="csr-category-card-grid" class="csr-category-grid csr-category-cards" aria-label="Waste and material categories">
            <label class="csr-category-card">
                <input type="checkbox" class="csr-category-toggle" data-category="unsorted_litter" aria-controls="csr-detail-unsorted_litter" aria-expanded="false">
                <span class="csr-category-icon" aria-hidden="true">🗑️</span>
                <span class="csr-category-label">Unsorted Litter</span>
            </label>
            <label class="csr-category-card">
                <input type="checkbox" class="csr-category-toggle" data-category="plastic" aria-controls="csr-detail-plastic" aria-expanded="false">
                <span class="csr-category-icon" aria-hidden="true">🧴</span>
                <span class="csr-category-label">Plastic Waste</span>
            </label>
            <label class="csr-category-card">
                <input type="checkbox" class="csr-category-toggle" data-category="paper" aria-controls="csr-detail-paper" aria-expanded="false">
                <span class="csr-category-icon" aria-hidden="true">📄</span>
                <span class="csr-category-label">Paper Waste</span>
            </label>
            <label class="csr-category-card">
                <input type="checkbox" class="csr-category-toggle" data-category="food" aria-controls="csr-detail-food" aria-expanded="false">
                <span class="csr-category-icon" aria-hidden="true">🍎</span>
                <span class="csr-category-label">Food Waste</span>
            </label>
            <label class="csr-category-card">
                <input type="checkbox" class="csr-category-toggle" data-category="metal" aria-controls="csr-detail-metal" aria-expanded="false">
                <span class="csr-category-icon" aria-hidden="true">🥫</span>
                <span class="csr-category-label">Metal Waste</span>
            </label>
            <label class="csr-category-card">
                <input type="checkbox" class="csr-category-toggle" data-category="glass" aria-controls="csr-detail-glass" aria-expanded="false">
                <span class="csr-category-icon" aria-hidden="true">🍾</span>
                <span class="csr-category-label">Glass Waste</span>
            </label>
            <label class="csr-category-card">
                <input type="checkbox" class="csr-category-toggle" data-category="cigarette" aria-controls="csr-detail-cigarette" aria-expanded="false">
                <span class="csr-category-icon" aria-hidden="true">🚬</span>
                <span class="csr-category-label">Cigarette Litter</span>
            </label>
            <label class="csr-category-card">
                <input type="checkbox" class="csr-category-toggle" data-category="textiles" aria-controls="csr-detail-textiles" aria-expanded="false">
                <span class="csr-category-icon" aria-hidden="true">👕</span>
                <span class="csr-category-label">Textiles</span>
            </label>
            <label class="csr-category-card">
                <input type="checkbox" class="csr-category-toggle" data-category="medical" aria-controls="csr-detail-medical" aria-expanded="false">
                <span class="csr-category-icon" aria-hidden="true">💊</span>
                <span class="csr-category-label">Medical Waste</span>
            </label>
            <label class="csr-category-card">
                <input type="checkbox" class="csr-category-toggle" data-category="sanitary" aria-controls="csr-detail-sanitary" aria-expanded="false">
                <span class="csr-category-icon" aria-hidden="true">🧻</span>
                <span class="csr-category-label">Sanitary Products</span>
            </label>
            <label class="csr-category-card">
                <input type="checkbox" class="csr-category-toggle" data-category="fishing" aria-controls="csr-detail-fishing" aria-expanded="false">
                <span class="csr-category-icon" aria-hidden="true">🎣</span>
                <span class="csr-category-label">Fishing Gear</span>
            </label>
            <label class="csr-category-card">
                <input type="checkbox" class="csr-category-toggle" data-category="styrofoam_hazardous" aria-controls="csr-detail-styrofoam_hazardous" aria-expanded="false">
                <span class="csr-category-icon" aria-hidden="true">☣️</span>
                <span class="csr-category-label">Styrofoam &amp; Hazardous</span>
            </label>
            <label class="csr-category-card">
                <input type="checkbox" class="csr-category-toggle" data-category="miscellaneous" aria-controls="csr-detail-miscellaneous" aria-expanded="false">
                <span class="csr-category-icon" aria-hidden="true">📦</span>
                <span class="csr-category-label">Miscellaneous</span>
            </label>
            <label class="csr-category-card">
                <input type="checkbox" class="csr-category-toggle" data-category="derelict" aria-controls="csr-detail-derelict" aria-expanded="false">
                <span class="csr-category-icon" aria-hidden="true">🚗</span>
                <span class="csr-category-label">Derelict Items</span>
            </label>
        </div>
        <p class="csr-category-cards-note">Icons are placeholders. A proper icon set is tracked in Issue #18.</p>
        <div class="csr-category-details">
        <div class="csr-category-detail-panel" id="csr-detail-unsorted_litter" data-category-detail="unsorted_litter" data-category-label="Unsorted Litter">
            <label for="csr_unsorted_litter_weight">Unsorted Litter (lbs):</label>
            <input type="number" id="csr_unsorted_litter_weight" name="csr_unsorted_litter_weight" step="0.01" min="0" aria-label="Unsorted Litter">
            <fieldset class="collapsible">
                <legend class="collapsible-header">Unsorted Litter Subcategories <span class="toggle-icon">+</span></legend>
                <div class="collapsible-content">
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_unsorted_litter_subcategories[]" value="Number of Unsorted Bags Collected"> Number of Unsorted Bags Collected
                        <input
                            type="number"
                            id="csr_unsorted_bags_count"
                            name="csr_unsorted_litter_subcategories_count[Number of Unsorted Bags Collected]"
                            step="1" min="0" placeholder="Count" aria-label="Number of Unsorted Bags Collected">
                    </label>
                </div>
            </fieldset>
        </div>
        <div class="csr-category-detail-panel" id="csr-detail-plastic" data-category-detail="plastic" data-category-label="Plastic Waste">
            <label for="csr_plastic_waste_weight">Plastic Waste (lbs):</label>
            <input type="number" id="csr_plastic_waste_weight" name="csr_plastic_waste_weight" step="0.01">
            <fieldset class="collapsible">
                <legend class="collapsible-header">Plastic Subcategories <span class="toggle-icon">+</span></legend>
                <div class="collapsible-content">
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_plastic_subcategories[]" value="Plastic bottles"> Plastic bottles
                        <input type="number" name="csr_plastic_subcategories_count[Plastic bottles]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_plastic_subcategories[]" value="Bottle caps"> Bottle caps
                        <input type="number" name="csr_plastic_subcategories_count[Bottle caps]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_plastic_subcategories[]" value="Straws & stirrers"> Straws & stirrers
                        <input type="number" name="csr_plastic_subcategories_count[Straws & stirrers]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_plastic_subcategories[]" value="Plastic bags"> Plastic bags
                        <input type="number" name="csr_plastic_subcategories_count[Plastic bags]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_plastic_subcategories[]" value="Food wrappers"> Food wrappers
                        <input type="number" name="csr_plastic_subcategories_count[Food wrappers]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_plastic_subcategories[]" value="Plastic utensils"> Plastic utensils
                        <input type="number" name="csr_plastic_subcategories_count[Plastic utensils]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_plastic_subcategories[]" value="Cups & lids"> Cups & lids
                        <input type="number" name="csr_plastic_subcategories_count[Cups & lids]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_plastic_subcategories[]" value="Six-pack rings"> Six-pack rings
                        <input type="number" name="csr_plastic_subcategories_count[Six-pack rings]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_plastic_subcategories[]" value="Microplastics"> Microplastics
                        <input type="number" name="csr_plastic_subcategories_count[Microplastics]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_plastic_subcategories[]" value="Toys"> Toys
                        <input type="number" name="csr_plastic_subcategories_count[Toys]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_plastic_subcategories[]" value="Containers (non-food)"> Containers (non-food)
                        <input type="number" name="csr_plastic_subcategories_count[Containers (non-food)]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_plastic_subcategories[]" value="Hard plastics"> Hard plastics
                        <input type="number" name="csr_plastic_subcategories_count[Hard plastics]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_plastic_subcategories[]" value="Packaging materials"> Packaging materials
                        <input type="number" name="csr_plastic_subcategories_count[Packaging materials]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_plastic_subcategories[]" value="Fishing nets (plastic-based)"> Fishing nets (plastic-based)
                        <input type="number" name="csr_plastic_subcategories_count[Fishing nets (plastic-based)]" step="1" min="0" placeholder="Count">
                    </label>
                </div>
            </fieldset>
        </div>
        <div class="csr-category-detail-panel" id="csr-detail-paper" data-category-detail="paper" data-category-label="Paper Waste">
            <label for="csr_paper_waste_weight">Paper Waste (lbs):</label>
            <input type="number" id="csr_paper_waste_weight" name="csr_paper_waste_weight" step="0.01">
            <fieldset class="collapsible">
                <legend class="collapsible-header">Paper Subcategories <span class="toggle-icon">+</span></legend>
                <div class="collapsible-content">
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_paper_subcategories[]" value="Newspapers"> Newspapers
                        <input type="number" name="csr_paper_subcategories_count[Newspapers]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_paper_subcategories[]" value="Magazines"> Magazines
                        <input type="number" name="csr_paper_subcategories_count[Magazines]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_paper_subcategories[]" value="Flyers / brochures"> Flyers / brochures
                        <input type="number" name="csr_paper_subcategories_count[Flyers / brochures]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_paper_subcategories[]" value="Food packaging (paper-based)"> Food packaging (paper-based)
                        <input type="number" name="csr_paper_subcategories_count[Food packaging (paper-based)]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_paper_subcategories[]" value="Cardboard"> Cardboard
                        <input type="number" name="csr_paper_subcategories_count[Cardboard]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_paper_subcategories[]" value="Paper bags"> Paper bags
                        <input type="number" name="csr_paper_subcategories_count[Paper bags]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_paper_subcategories[]" value="Napkins / tissues"> Napkins / tissues
                        <input type="number" name="csr_paper_subcategories_count[Napkins / tissues]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_paper_subcategories[]" value="Notebooks / loose paper"> Notebooks / loose paper
                        <input type="number" name="csr_paper_subcategories_count[Notebooks / loose paper]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_paper_subcategories[]" value="Cigarette packs (paper-based)"> Cigarette packs (paper-based)
                        <input type="number" name="csr_paper_subcategories_count[Cigarette packs (paper-based)]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_paper_subcategories[]" value="Receipts"> Receipts
                        <input type="number" name="csr_paper_subcategories_count[Receipts]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_paper_subcategories[]" value="Coffee cups (paper with lining)"> Coffee cups (paper with lining)
                        <input type="number" name="csr_paper_subcategories_count[Coffee cups (paper with lining)]" step="1" min="0" placeholder="Count">
                    </label>
                </div>
            </fieldset>
        </div>
        <div class="csr-category-detail-panel" id="csr-detail-food" data-category-detail="food" data-category-label="Food Waste">
            <label for="csr_food_waste_weight">Food Waste (lbs):</label>
            <input type="number" id="csr_food_waste_weight" name="csr_food_waste_weight" step="0.01">
            <fieldset class="collapsible">
                <legend class="collapsible-header">Food Subcategories <span class="toggle-icon">+</span></legend>
                <div class="collapsible-content">
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_food_subcategories[]" value="Fruit peels"> Fruit peels
                        <input type="number" name="csr_food_subcategories_count[Fruit peels]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_food_subcategories[]" value="Vegetable scraps"> Vegetable scraps
                        <input type="number" name="csr_food_subcategories_count[Vegetable scraps]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_food_subcategories[]" value="Meat scraps"> Meat scraps
                        <input type="number" name="csr_food_subcategories_count[Meat scraps]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_food_subcategories[]" value="Fish scraps"> Fish scraps
                        <input type="number" name="csr_food_subcategories_count[Fish scraps]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_food_subcategories[]" value="Egg shells"> Egg shells
                        <input type="number" name="csr_food_subcategories_count[Egg shells]" step="1" min="0" placeholder="Count">
                    </label>
                </div>
            </fieldset>
        </div>
        <div class="csr-category-detail-panel" id="csr-detail-metal" data-category-detail="metal" data-category-label="Metal Waste">
            <label for="csr_metal_waste_weight">Metal Waste (lbs):</label>
            <input type="number" id="csr_metal_waste_weight" name="csr_metal_waste_weight" step="0.01">
            <fieldset class="collapsible">
                <legend class="collapsible-header">Metal Subcategories <span class="toggle-icon">+</span></legend>
                <div class="collapsible-content">
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_metal_subcategories[]" value="Aluminum cans"> Aluminum cans
                        <input type="number" name="csr_metal_subcategories_count[Aluminum cans]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_metal_subcategories[]" value="Metal bottle caps"> Metal bottle caps
                        <input type="number" name="csr_metal_subcategories_count[Metal bottle caps]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_metal_subcategories[]" value="Metal lids"> Metal lids
                        <input type="number" name="csr_metal_subcategories_count[Metal lids]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_metal_subcategories[]" value="Metal utensils"> Metal utensils
                        <input type="number" name="csr_metal_subcategories_count[Metal utensils]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_metal_subcategories[]" value="Metal containers"> Metal containers
                        <input type="number" name="csr_metal_subcategories_count[Metal containers]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_metal_subcategories[]" value="Metal scraps"> Metal scraps
                        <input type="number" name="csr_metal_subcategories_count[Metal scraps]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_metal_subcategories[]" value="Metal wires"> Metal wires
                        <input type="number" name="csr_metal_subcategories_count[Metal wires]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_metal_subcategories[]" value="Metal tools"> Metal tools
                        <input type="number" name="csr_metal_subcategories_count[Metal tools]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_metal_subcategories[]" value="Metal toys"> Metal toys
                        <input type="number" name="csr_metal_subcategories_count[Metal toys]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_metal_subcategories[]" value="Metal furniture"> Metal furniture
                        <input type="number" name="csr_metal_subcategories_count[Metal furniture]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_metal_subcategories[]" value="Metal appliances"> Metal appliances
                        <input type="number" name="csr_metal_subcategories_count[Metal appliances]" step="1" min="0" placeholder="Count">
                    </label>
                </div>
            </fieldset>
        </div>
        <div class="csr-category-detail-panel" id="csr-detail-glass" data-category-detail="glass" data-category-label="Glass Waste">
            <label for="csr_glass_waste_weight">Glass Waste (lbs):</label>
            <input type="number" id="csr_glass_waste_weight" name="csr_glass_waste_weight" step="0.01">
            <fieldset class="collapsible">
                <legend class="collapsible-header">Glass Subcategories <span class="toggle-icon">+</span></legend>
                <div class="collapsible-content">
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_glass_subcategories[]" value="Glass bottles"> Glass bottles
                        <input type="number" name="csr_glass_subcategories_count[Glass bottles]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_glass_subcategories[]" value="Glass jars"> Glass jars
                        <input type="number" name="csr_glass_subcategories_count[Glass jars]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_glass_subcategories[]" value="Glass containers"> Glass containers
                        <input type="number" name="csr_glass_subcategories_count[Glass containers]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_glass_subcategories[]" value="Glass fragments"> Glass fragments
                        <input type="number" name="csr_glass_subcategories_count[Glass fragments]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_glass_subcategories[]" value="Glass cups"> Glass cups
                        <input type="number" name="csr_glass_subcategories_count[Glass cups]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_glass_subcategories[]" value="Glass plates"> Glass plates
                        <input type="number" name="csr_glass_subcategories_count[Glass plates]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_glass_subcategories[]" value="Glass utensils"> Glass utensils
                        <input type="number" name="csr_glass_subcategories_count[Glass utensils]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_glass_subcategories[]" value="Glass toys"> Glass toys
                        <input type="number" name="csr_glass_subcategories_count[Glass toys]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_glass_subcategories[]" value="Glass furniture"> Glass furniture
                        <input type="number" name="csr_glass_subcategories_count[Glass furniture]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_glass_subcategories[]" value="Glass appliances"> Glass appliances
                        <input type="number" name="csr_glass_subcategories_count[Glass appliances]" step="1" min="0" placeholder="Count">
                    </label>
                </div>
            </fieldset>
        </div>
        <div class="csr-category-detail-panel" id="csr-detail-cigarette" data-category-detail="cigarette" data-category-label="Cigarette Litter">
            <label for="csr_cigarette_litter_weight">Cigarette Litter (lbs):</label>
            <input type="number" id="csr_cigarette_litter_weight" name="csr_cigarette_litter_weight" step="0.01">
            <fieldset class="collapsible">
                <legend class="collapsible-header">Cigarette Subcategories <span class="toggle-icon">+</span></legend>
                <div class="collapsible-content">
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_cigarette_subcategories[]" value="Cigarette butts"> Cigarette butts
                        <input type="number" name="csr_cigarette_subcategories_count[Cigarette butts]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_cigarette_subcategories[]" value="Cigarette packs"> Cigarette packs
                        <input type="number" name="csr_cigarette_subcategories_count[Cigarette packs]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_cigarette_subcategories[]" value="Cigarette filters"> Cigarette filters
                        <input type="number" name="csr_cigarette_subcategories_count[Cigarette filters]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_cigarette_subcategories[]" value="Cigarette wrappers"> Cigarette wrappers
                        <input type="number" name="csr_cigarette_subcategories_count[Cigarette wrappers]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_cigarette_subcategories[]" value="Cigarette lighters"> Cigarette lighters
                        <input type="number" name="csr_cigarette_subcategories_count[Cigarette lighters]" step="1" min="0" placeholder="Count">
                    </label>
                </div>
            </fieldset>
        </div>
        <div class="csr-category-detail-panel" id="csr-detail-textiles" data-category-detail="textiles" data-category-label="Textiles">
            <label for="csr_textiles_weight">Textiles (lbs):</label>
            <input type="number" id="csr_textiles_weight" name="csr_textiles_weight" step="0.01">
            <fieldset class="collapsible">
                <legend class="collapsible-header">Textiles Subcategories <span class="toggle-icon">+</span></legend>
                <div class="collapsible-content">
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_textiles_subcategories[]" value="Clothing"> Clothing
                        <input type="number" name="csr_textiles_subcategories_count[Clothing]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_textiles_subcategories[]" value="Shoes"> Shoes
                        <input type="number" name="csr_textiles_subcategories_count[Shoes]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_textiles_subcategories[]" value="Bags"> Bags
                        <input type="number" name="csr_textiles_subcategories_count[Bags]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_textiles_subcategories[]" value="Hats"> Hats
                        <input type="number" name="csr_textiles_subcategories_count[Hats]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_textiles_subcategories[]" value="Scarves"> Scarves
                        <input type="number" name="csr_textiles_subcategories_count[Scarves]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_textiles_subcategories[]" value="Gloves"> Gloves
                        <input type="number" name="csr_textiles_subcategories_count[Gloves]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_textiles_subcategories[]" value="Socks"> Socks
                        <input type="number" name="csr_textiles_subcategories_count[Socks]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_textiles_subcategories[]" value="Underwear"> Underwear
                        <input type="number" name="csr_textiles_subcategories_count[Underwear]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_textiles_subcategories[]" value="Bedding"> Bedding
                        <input type="number" name="csr_textiles_subcategories_count[Bedding]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_textiles_subcategories[]" value="Towels"> Towels
                        <input type="number" name="csr_textiles_subcategories_count[Towels]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_textiles_subcategories[]" value="Curtains"> Curtains
                        <input type="number" name="csr_textiles_subcategories_count[Curtains]" step="1" min="0" placeholder="Count">
                    </label>
                </div>
            </fieldset>
        </div>
        <div class="csr-category-detail-panel" id="csr-detail-medical" data-category-detail="medical" data-category-label="Medical Waste">
            <label for="csr_medical_waste_weight">Medical Waste (lbs):</label>
            <input type="number" id="csr_medical_waste_weight" name="csr_medical_waste_weight" step="0.01">
            <fieldset class="collapsible">
                <legend class="collapsible-header">Medical Subcategories <span class="toggle-icon">+</span></legend>
                <div class="collapsible-content">
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_medical_subcategories[]" value="Syringes"> Syringes
                        <input type="number" name="csr_medical_subcategories_count[Syringes]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_medical_subcategories[]" value="Medicine bottles"> Medicine bottles
                        <input type="number" name="csr_medical_subcategories_count[Medicine bottles]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_medical_subcategories[]" value="Medicine packaging"> Medicine packaging
                        <input type="number" name="csr_medical_subcategories_count[Medicine packaging]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_medical_subcategories[]" value="Bandages"> Bandages
                        <input type="number" name="csr_medical_subcategories_count[Bandages]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_medical_subcategories[]" value="Gloves"> Gloves
                        <input type="number" name="csr_medical_subcategories_count[Gloves]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_medical_subcategories[]" value="Masks"> Masks
                        <input type="number" name="csr_medical_subcategories_count[Masks]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_medical_subcategories[]" value="Medical tools"> Medical tools
                        <input type="number" name="csr_medical_subcategories_count[Medical tools]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_medical_subcategories[]" value="Medical containers"> Medical containers
                        <input type="number" name="csr_medical_subcategories_count[Medical containers]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_medical_subcategories[]" value="Medical waste bags"> Medical waste bags
                        <input type="number" name="csr_medical_subcategories_count[Medical waste bags]" step="1" min="0" placeholder="Count">
                    </label>
                </div>
            </fieldset>
        </div>
        <div class="csr-category-detail-panel" id="csr-detail-sanitary" data-category-detail="sanitary" data-category-label="Sanitary Products">
            <label for="csr_sanitary_products_weight">Sanitary Products (lbs):</label>
            <input type="number" id="csr_sanitary_products_weight" name="csr_sanitary_products_weight" step="0.01">
            <fieldset class="collapsible">
                <legend class="collapsible-header">Sanitary Subcategories <span class="toggle-icon">+</span></legend>
                <div class="collapsible-content">
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_sanitary_subcategories[]" value="Sanitary pads"> Sanitary pads
                        <input type="number" name="csr_sanitary_subcategories_count[Sanitary pads]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_sanitary_subcategories[]" value="Tampons"> Tampons
                        <input type="number" name="csr_sanitary_subcategories_count[Tampons]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_sanitary_subcategories[]" value="Diapers"> Diapers
                        <input type="number" name="csr_sanitary_subcategories_count[Diapers]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_sanitary_subcategories[]" value="Wet wipes"> Wet wipes
                        <input type="number" name="csr_sanitary_subcategories_count[Wet wipes]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_sanitary_subcategories[]" value="Cotton swabs"> Cotton swabs
                        <input type="number" name="csr_sanitary_subcategories_count[Cotton swabs]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_sanitary_subcategories[]" value="Toilet paper"> Toilet paper
                        <input type="number" name="csr_sanitary_subcategories_count[Toilet paper]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_sanitary_subcategories[]" value="Tissues"> Tissues
                        <input type="number" name="csr_sanitary_subcategories_count[Tissues]" step="1" min="0" placeholder="Count">
                    </label>
                </div>
            </fieldset>
        </div>
        <div class="csr-category-detail-panel" id="csr-detail-fishing" data-category-detail="fishing" data-category-label="Fishing Gear">
            <label for="csr_fishing_gear_weight">Fishing Gear (lbs):</label>
            <input type="number" id="csr_fishing_gear_weight" name="csr_fishing_gear_weight" step="0.01">
            <fieldset class="collapsible">
                <legend class="collapsible-header">Fishing Subcategories <span class="toggle-icon">+</span></legend>
                <div class="collapsible-content">
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_fishing_subcategories[]" value="Fishing nets"> Fishing nets
                        <input type="number" name="csr_fishing_subcategories_count[Fishing nets]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_fishing_subcategories[]" value="Fishing lines"> Fishing lines
                        <input type="number" name="csr_fishing_subcategories_count[Fishing lines]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_fishing_subcategories[]" value="Fishing hooks"> Fishing hooks
                        <input type="number" name="csr_fishing_subcategories_count[Fishing hooks]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_fishing_subcategories[]" value="Fishing lures"> Fishing lures
                        <input type="number" name="csr_fishing_subcategories_count[Fishing lures]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_fishing_subcategories[]" value="Fishing weights"> Fishing weights
                        <input type="number" name="csr_fishing_subcategories_count[Fishing weights]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_fishing_subcategories[]" value="Fishing floats"> Fishing floats
                        <input type="number" name="csr_fishing_subcategories_count[Fishing floats]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_fishing_subcategories[]" value="Fishing rods"> Fishing rods
                        <input type="number" name="csr_fishing_subcategories_count[Fishing rods]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_fishing_subcategories[]" value="Fishing reels"> Fishing reels
                        <input type="number" name="csr_fishing_subcategories_count[Fishing reels]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_fishing_subcategories[]" value="Fishing bait containers"> Fishing bait containers
                        <input type="number" name="csr_fishing_subcategories_count[Fishing bait containers]" step="1" min="0" placeholder="Count">
                    </label>
                </div>
            </fieldset>
        </div>
        <div class="csr-category-detail-panel" id="csr-detail-styrofoam_hazardous" data-category-detail="styrofoam_hazardous" data-category-label="Styrofoam & Hazardous Waste">
            <label for="csr_styrofoam_hazardous_waste_weight">Styrofoam & Hazardous Waste (lbs):</label>
            <input type="number" id="csr_styrofoam_hazardous_waste_weight" name="csr_styrofoam_hazardous_waste_weight" step="0.01">
            <fieldset class="collapsible">
                <legend class="collapsible-header">Styrofoam Subcategories <span class="toggle-icon">+</span></legend>
                <div class="collapsible-content">
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_styrofoam_subcategories[]" value="Styrofoam cups"> Styrofoam cups
                        <input type="number" name="csr_styrofoam_subcategories_count[Styrofoam cups]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_styrofoam_subcategories[]" value="Styrofoam plates"> Styrofoam plates
                        <input type="number" name="csr_styrofoam_subcategories_count[Styrofoam plates]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_styrofoam_subcategories[]" value="Styrofoam containers"> Styrofoam containers
                        <input type="number" name="csr_styrofoam_subcategories_count[Styrofoam containers]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_styrofoam_subcategories[]" value="Styrofoam packaging"> Styrofoam packaging
                        <input type="number" name="csr_styrofoam_subcategories_count[Styrofoam packaging]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_styrofoam_subcategories[]" value="Styrofoam fragments"> Styrofoam fragments
                        <input type="number" name="csr_styrofoam_subcategories_count[Styrofoam fragments]" step="1" min="0" placeholder="Count">
                    </label>
                </div>
            </fieldset>
            <fieldset class="collapsible">
                <legend class="collapsible-header">Hazardous Subcategories <span class="toggle-icon">+</span></legend>
                <div class="collapsible-content">
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_hazardous_subcategories[]" value="Batteries"> Batteries
                        <input type="number" name="csr_hazardous_subcategories_count[Batteries]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_hazardous_subcategories[]" value="Paint cans"> Paint cans
                        <input type="number" name="csr_hazardous_subcategories_count[Paint cans]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_hazardous_subcategories[]" value="Chemical containers"> Chemical containers
                        <input type="number" name="csr_hazardous_subcategories_count[Chemical containers]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_hazardous_subcategories[]" value="Oil containers"> Oil containers
                        <input type="number" name="csr_hazardous_subcategories_count[Oil containers]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_hazardous_subcategories[]" value="Pesticide containers"> Pesticide containers
                        <input type="number" name="csr_hazardous_subcategories_count[Pesticide containers]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_hazardous_subcategories[]" value="Cleaning product containers"> Cleaning product containers
                        <input type="number" name="csr_hazardous_subcategories_count[Cleaning product containers]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_hazardous_subcategories[]" value="Medical waste"> Medical waste
                        <input type="number" name="csr_hazardous_subcategories_count[Medical waste]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_hazardous_subcategories[]" value="Electronic waste"> Electronic waste
                        <input type="number" name="csr_hazardous_subcategories_count[Electronic waste]" step="1" min="0" placeholder="Count">
                    </label>
                </div>
            </fieldset>
        </div>
        <div class="csr-category-detail-panel" id="csr-detail-miscellaneous" data-category-detail="miscellaneous" data-category-label="Miscellaneous">
            <label for="csr_miscellaneous_weight">Miscellaneous (lbs):</label>
            <input type="number" id="csr_miscellaneous_weight" name="csr_miscellaneous_weight" step="0.01">
            <fieldset class="collapsible">
                <legend class="collapsible-header">Miscellaneous Subcategories <span class="toggle-icon">+</span></legend>
                <div class="collapsible-content">
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_miscellaneous_subcategories[]" value="Rubber items"> Rubber items
                        <input type="number" name="csr_miscellaneous_subcategories_count[Rubber items]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_miscellaneous_subcategories[]" value="Wood items"> Wood items
                        <input type="number" name="csr_miscellaneous_subcategories_count[Wood items]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_miscellaneous_subcategories[]" value="Ceramic items"> Ceramic items
                        <input type="number" name="csr_miscellaneous_subcategories_count[Ceramic items]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_miscellaneous_subcategories[]" value="Leather items"> Leather items
                        <input type="number" name="csr_miscellaneous_subcategories_count[Leather items]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_miscellaneous_subcategories[]" value="Electronic items"> Electronic items
                        <input type="number" name="csr_miscellaneous_subcategories_count[Electronic items]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_miscellaneous_subcategories[]" value="Miscellaneous fragments"> Miscellaneous fragments
                        <input type="number" name="csr_miscellaneous_subcategories_count[Miscellaneous fragments]" step="1" min="0" placeholder="Count">
                    </label>
                </div>
            </fieldset>
        </div>
        <div class="csr-category-detail-panel" id="csr-detail-derelict" data-category-detail="derelict" data-category-label="Derelict Items">
            <label for="csr_derelict_items_weight">Derelict Items (lbs):</label>
            <input type="number" id="csr_derelict_items_weight" name="csr_derelict_items_weight" step="0.01">
            <fieldset class="collapsible">
                <legend class="collapsible-header">Derelict Subcategories <span class="toggle-icon">+</span></legend>
                <div class="collapsible-content">
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_derelict_subcategories[]" value="Derelict fishing gear"> Derelict fishing gear
                        <input type="number" name="csr_derelict_subcategories_count[Derelict fishing gear]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_derelict_subcategories[]" value="Derelict boats"> Derelict boats
                        <input type="number" name="csr_derelict_subcategories_count[Derelict boats]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_derelict_subcategories[]" value="Derelict vehicles"> Derelict vehicles
                        <input type="number" name="csr_derelict_subcategories_count[Derelict vehicles]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_derelict_subcategories[]" value="Derelict furniture"> Derelict furniture
                        <input type="number" name="csr_derelict_subcategories_count[Derelict furniture]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_derelict_subcategories[]" value="Derelict appliances"> Derelict appliances
                        <input type="number" name="csr_derelict_subcategories_count[Derelict appliances]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_derelict_subcategories[]" value="Derelict building materials"> Derelict building materials
                        <input type="number" name="csr_derelict_subcategories_count[Derelict building materials]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_derelict_subcategories[]" value="Derelict tools"> Derelict tools
                        <input type="number" name="csr_derelict_subcategories_count[Derelict tools]" step="1" min="0" placeholder="Count">
                    </label>
                    <label class="csr-subcategory-row">
                        <input type="checkbox" name="csr_derelict_subcategories[]" value="Derelict toys"> Derelict toys
                        <input type="number" name="csr_derelict_subcategories_count[Derelict toys]" step="1" min="0" placeholder="Count">
                    </label>
                </div>
            </fieldset>
        </div>
        </div>
        </div>

        <!-- Step 3: Habitat Restoration -->
        <div class="csr-step" data-step="3" data-step-title="Restoration and Participation">
        <div>
            <div class="csr-step-heading">
                <span class="csr-guided-kicker">Beyond litter removal</span>
                <h3>Habitat restoration and participation</h3>
                <p>Complete only the activities that apply. It is fine to leave unrelated fields blank.</p>
            </div>
            <div>
                <label for="csr_trees_planted">Number of Trees Planted:</label>
                <input type="number" id="csr_trees_planted" name="csr_trees_planted" step="1" min="0">
            </div>
            <div>
                <label for="csr_invasive_species_removed">Square Feet of Invasive Species Removed:</label>
                <input type="number" id="csr_invasive_species_removed" name="csr_invasive_species_removed" step="1" min="0">
            </div>
            <div>
                <label for="csr_invasive_species_names">Invasive Species Removed:</label>
                <input type="text" id="csr_invasive_species_names" name="csr_invasive_species_names" placeholder="Enter species names">
                <label for="csr_invasive_species_weight">Weight Collected (lbs):</label>
                <input type="number" id="csr_invasive_species_weight" name="csr_invasive_species_weight" step="0.01" min="0">
            </div>
            <div>
                <label for="csr_native_plants_seeded">Native Plant Species Seeded:</label>
                <select id="csr_native_plants_seeded" name="csr_native_plants_seeded">
                    <option value="">Select a species</option>
                    <option value="Milkweed">Milkweed</option>
                    <option value="Oak">Oak</option>
                    <option value="Pine">Pine</option>
                    <option value="Other">Other</option>
                </select>
                <input type="text" id="csr_native_plants_seeded_other" name="csr_native_plants_seeded_other" placeholder="Specify if 'Other'">
            </div>
            <div>
                <fieldset>
                    <legend>Erosion Control Methods Used:</legend>
                    <label>
                        <input type="checkbox" name="csr_erosion_control_methods[]" value="Mulching"> Mulching
                    </label>
                    <label>
                        <input type="checkbox" name="csr_erosion_control_methods[]" value="Terracing"> Terracing
                    </label>
                    <label>
                        <input type="checkbox" name="csr_erosion_control_methods[]" value="Planting ground cover"> Planting ground cover
                    </label>
                    <label>
                        <input type="checkbox" name="csr_erosion_control_methods[]" value="Other"> Other
                    </label>
                    <textarea name="csr_erosion_control_notes" rows="3" placeholder="Additional notes (optional)"></textarea>
                </fieldset>
            </div>
            <div>
                <label for="csr_volunteers_involved">Number of Volunteers Involved:</label>
                <input type="number" id="csr_volunteers_involved" name="csr_volunteers_involved" step="1" min="0">
            </div>
        </div>
        </div>

        <!-- Step 4 (Issue #6): Photos. csr_notes textarea intentionally not added
             here, tracked as a separate small follow-up referenced in the
             Issue #4 audit doc, out of scope for this PR. -->
        <div class="csr-step" data-step="4" data-step-title="Photos and Evidence">
        <div class="csr-step-heading">
            <span class="csr-guided-kicker">Show the impact</span>
            <h3>Add photos from the site</h3>
            <p>Photos help document the activity and may be featured in the EcoServants® Wall of Fame.</p>
        </div>
        <div>
            <label for="csr_photos">Upload Photos:</label>
            <input type="file" id="csr_photos" name="csr_photos[]" multiple accept="image/*">
            <small>You can upload multiple photos (JPEG, PNG, GIF).</small>
        </div>
        </div>

        <!-- Step 5 (Issue #6): Review and Submit. A full editable review
             summary is Issue #9's scope, this is just the final step
             containing the existing submit control. -->
        <div class="csr-step" data-step="5" data-step-title="Review and Submit">
        <div class="csr-step-heading">
            <span class="csr-guided-kicker">Final check</span>
            <h3>Review your environmental impact</h3>
            <p>Confirm the information below before submitting. Use the edit buttons to return to a section.</p>
        </div>
        <div id="csr-review-summary" class="csr-review-summary" aria-live="polite"></div>
        <div>
            <input type="hidden" name="csr_honeypot" value="">
            <button type="submit">Submit Report</button>
        </div>
        </div>
    </form>
</div>
<!-- Voice Assistant JS -->
<script src="<?php echo plugin_dir_url(__FILE__); ?>assets/js/ecoservants-csr.js"></script>
<!-- csr-guided-wrapper.js is enqueued in ecoservants-csr.php via ecoservants_enqueue_scripts(),
     not included directly here, so it loads once and only on pages where the form actually renders. -->
