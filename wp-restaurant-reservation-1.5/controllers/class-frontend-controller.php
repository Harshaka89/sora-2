<?php
if (!defined('ABSPATH')) exit;

// Prevent duplicate class declaration
if (class_exists('YRR_Frontend_Controller')) {
    return;
}

class YRR_Frontend_Controller {
    private $reservation_model;
    private $settings_model;
    private $tables_model;
    private $time_slots_model;
    
    public function __construct() {
        $this->reservation_model = new YRR_Reservation_Model();
        $this->settings_model = new YRR_Settings_Model();
        $this->tables_model = new YRR_Tables_Model();
        $this->time_slots_model = new YRR_Time_Slots_Model();
        
        // Register AJAX handlers
        add_action('wp_ajax_yrr_get_available_slots', array($this, 'ajax_get_available_slots'));
        add_action('wp_ajax_nopriv_yrr_get_available_slots', array($this, 'ajax_get_available_slots'));
        
        add_action('wp_ajax_yrr_create_reservation', array($this, 'ajax_create_reservation'));
        add_action('wp_ajax_nopriv_yrr_create_reservation', array($this, 'ajax_create_reservation'));
        
        add_action('wp_ajax_yrr_validate_coupon', array($this, 'ajax_validate_coupon'));
        add_action('wp_ajax_nopriv_yrr_validate_coupon', array($this, 'ajax_validate_coupon'));
    }
    
    /**
     * ✅ MAIN BOOKING FORM SHORTCODE
     */
    public function display_booking_form($atts) {
        $atts = shortcode_atts(array(
            'style' => 'modern',
            'show_title' => 'true',
            'title' => 'Make a Reservation'
        ), $atts);
        
        // Enqueue styles and scripts
        $this->enqueue_frontend_assets();
        
        ob_start();
        ?>
        <div class="yrr-booking-container" id="yrr-booking-form">
            <?php if ($atts['show_title'] === 'true'): ?>
                <div class="yrr-booking-header">
                    <h2 class="yrr-booking-title"><?php echo esc_html($atts['title']); ?></h2>
                    <p class="yrr-booking-subtitle">Reserve your table and enjoy an exceptional dining experience</p>
                </div>
            <?php endif; ?>
            
            <div class="yrr-booking-form-wrapper">
                <form id="yrr-reservation-form" class="yrr-booking-form" method="post">
                    <?php wp_nonce_field('yrr_create_reservation', 'yrr_reservation_nonce'); ?>
                    
                    <!-- Step 1: Date & Party Size -->
                    <div class="yrr-form-step yrr-step-1 active">
                        <div class="yrr-step-header">
                            <span class="yrr-step-number">1</span>
                            <h3>When & How Many?</h3>
                        </div>
                        
                        <div class="yrr-form-row">
                            <div class="yrr-form-group">
                                <label for="reservation_date">📅 Select Date</label>
                                <input type="date" id="reservation_date" name="reservation_date" required 
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       max="<?php echo date('Y-m-d', strtotime('+60 days')); ?>">
                            </div>
                            <div class="yrr-form-group">
                                <label for="party_size">👥 Party Size</label>
                                <select id="party_size" name="party_size" required>
                                    <option value="">Select guests</option>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>">
                                            <?php echo $i; ?> <?php echo $i === 1 ? 'Guest' : 'Guests'; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <button type="button" class="yrr-btn yrr-btn-primary yrr-next-step" data-next="2">
                            Check Availability <span class="yrr-arrow">→</span>
                        </button>
                    </div>
                    
                    <!-- Step 2: Time Selection -->
                    <div class="yrr-form-step yrr-step-2">
                        <div class="yrr-step-header">
                            <span class="yrr-step-number">2</span>
                            <h3>Choose Your Time</h3>
                        </div>
                        
                        <div id="yrr-time-slots-container" class="yrr-time-slots">
                            <div class="yrr-loading">
                                <div class="yrr-spinner"></div>
                                <p>Finding available times...</p>
                            </div>
                        </div>
                        
                        <div class="yrr-form-navigation">
                            <button type="button" class="yrr-btn yrr-btn-secondary yrr-prev-step" data-prev="1">
                                ← Back
                            </button>
                            <button type="button" class="yrr-btn yrr-btn-primary yrr-next-step" data-next="3" disabled>
                                Continue <span class="yrr-arrow">→</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 3: Customer Information -->
                    <div class="yrr-form-step yrr-step-3">
                        <div class="yrr-step-header">
                            <span class="yrr-step-number">3</span>
                            <h3>Your Information</h3>
                        </div>
                        
                        <div class="yrr-form-row">
                            <div class="yrr-form-group">
                                <label for="customer_name">👤 Full Name *</label>
                                <input type="text" id="customer_name" name="customer_name" required 
                                       placeholder="Enter your full name">
                            </div>
                            <div class="yrr-form-group">
                                <label for="customer_email">📧 Email Address *</label>
                                <input type="email" id="customer_email" name="customer_email" required 
                                       placeholder="your@email.com">
                            </div>
                        </div>
                        
                        <div class="yrr-form-row">
                            <div class="yrr-form-group">
                                <label for="customer_phone">📱 Phone Number *</label>
                                <input type="tel" id="customer_phone" name="customer_phone" required 
                                       placeholder="+1 (555) 123-4567">
                            </div>
                            <div class="yrr-form-group">
                                <label for="coupon_code">🎫 Coupon Code (Optional)</label>
                                <div class="yrr-coupon-input">
                                    <input type="text" id="coupon_code" name="coupon_code" 
                                           placeholder="Enter coupon code">
                                    <button type="button" id="yrr-validate-coupon" class="yrr-btn-small">
                                        Apply
                                    </button>
                                </div>
                                <div id="yrr-coupon-message" class="yrr-coupon-message"></div>
                            </div>
                        </div>
                        
                        <div class="yrr-form-group">
                            <label for="special_requests">💭 Special Requests (Optional)</label>
                            <textarea id="special_requests" name="special_requests" rows="3" 
                                      placeholder="Any dietary restrictions, allergies, or special occasions?"></textarea>
                        </div>
                        
                        <div class="yrr-form-navigation">
                            <button type="button" class="yrr-btn yrr-btn-secondary yrr-prev-step" data-prev="2">
                                ← Back
                            </button>
                            <button type="button" class="yrr-btn yrr-btn-primary yrr-next-step" data-next="4">
                                Review Booking <span class="yrr-arrow">→</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 4: Confirmation -->
                    <div class="yrr-form-step yrr-step-4">
                        <div class="yrr-step-header">
                            <span class="yrr-step-number">4</span>
                            <h3>Confirm Your Reservation</h3>
                        </div>
                        
                        <div class="yrr-booking-summary">
                            <div class="yrr-summary-card">
                                <h4>📋 Booking Summary</h4>
                                <div class="yrr-summary-details">
                                    <div class="yrr-summary-item">
                                        <span class="yrr-label">Date:</span>
                                        <span class="yrr-value" id="summary-date">-</span>
                                    </div>
                                    <div class="yrr-summary-item">
                                        <span class="yrr-label">Time:</span>
                                        <span class="yrr-value" id="summary-time">-</span>
                                    </div>
                                    <div class="yrr-summary-item">
                                        <span class="yrr-label">Guests:</span>
                                        <span class="yrr-value" id="summary-guests">-</span>
                                    </div>
                                    <div class="yrr-summary-item">
                                        <span class="yrr-label">Name:</span>
                                        <span class="yrr-value" id="summary-name">-</span>
                                    </div>
                                    <div class="yrr-summary-item">
                                        <span class="yrr-label">Email:</span>
                                        <span class="yrr-value" id="summary-email">-</span>
                                    </div>
                                    <div class="yrr-summary-item">
                                        <span class="yrr-label">Phone:</span>
                                        <span class="yrr-value" id="summary-phone">-</span>
                                    </div>
                                </div>
                                
                                <div id="yrr-coupon-applied" class="yrr-coupon-applied" style="display: none;">
                                    <div class="yrr-discount-info">
                                        <h5>🎉 Discount Applied!</h5>
                                        <p id="yrr-discount-details"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="yrr-form-navigation">
                            <button type="button" class="yrr-btn yrr-btn-secondary yrr-prev-step" data-prev="3">
                                ← Back
                            </button>
                            <button type="submit" class="yrr-btn yrr-btn-success yrr-submit-btn">
                                <span class="yrr-btn-text">Confirm Reservation</span>
                                <span class="yrr-btn-spinner" style="display: none;">
                                    <div class="yrr-spinner-small"></div>
                                </span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Hidden fields -->
                    <input type="hidden" id="selected_time_slot_id" name="time_slot_id" value="">
                    <input type="hidden" id="selected_time_slot_time" name="selected_time" value="">
                    <input type="hidden" id="coupon_validated" name="coupon_validated" value="0">
                    <input type="hidden" id="discount_amount" name="discount_amount" value="0">
                </form>
                
                <!-- Success Message -->
                <div id="yrr-booking-success" class="yrr-booking-result yrr-success" style="display: none;">
                    <div class="yrr-result-icon">✅</div>
                    <h3>Reservation Confirmed!</h3>
                    <p>Thank you! Your reservation has been successfully created.</p>
                    <div class="yrr-confirmation-details">
                        <p><strong>Confirmation Code:</strong> <span id="confirmation-code"></span></p>
                        <p>A confirmation email has been sent to your email address.</p>
                    </div>
                    <button type="button" class="yrr-btn yrr-btn-primary" onclick="location.reload()">
                        Make Another Reservation
                    </button>
                </div>
                
                <!-- Error Message -->
                <div id="yrr-booking-error" class="yrr-booking-result yrr-error" style="display: none;">
                    <div class="yrr-result-icon">❌</div>
                    <h3>Booking Failed</h3>
                    <p id="error-message">Something went wrong. Please try again.</p>
                    <button type="button" class="yrr-btn yrr-btn-primary" onclick="location.reload()">
                        Try Again
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * ✅ AJAX: GET AVAILABLE TIME SLOTS
     */
    public function ajax_get_available_slots() {
        check_ajax_referer('yrr_get_slots', 'nonce');
        
        $date = sanitize_text_field($_POST['date']);
        $party_size = intval($_POST['party_size']);
        
        if (empty($date) || $party_size < 1) {
            wp_send_json_error('Invalid date or party size');
        }
        
        // Get active time slots
        $time_slots = $this->time_slots_model->get_active_slots();
        $available_slots = array();
        
        foreach ($time_slots as $slot) {
            // Check if slot has available tables for this party size
            $available_tables = $this->tables_model->get_available_for_slot($date, $slot->id, $party_size);
            
            if (!empty($available_tables)) {
                $available_slots[] = array(
                    'id' => $slot->id,
                    'time' => $slot->slot_time,
                    'name' => $slot->slot_name,
                    'available_tables' => count($available_tables)
                );
            }
        }
        
        wp_send_json_success(array(
            'slots' => $available_slots,
            'date' => date('F j, Y', strtotime($date))
        ));
    }
    
    /**
     * ✅ AJAX: CREATE RESERVATION
     */
    public function ajax_create_reservation() {
        check_ajax_referer('yrr_create_reservation', 'nonce');
        
        // Validate required fields
        $required_fields = array('customer_name', 'customer_email', 'customer_phone', 'reservation_date', 'time_slot_id', 'party_size');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error('Missing required field: ' . $field);
            }
        }
        
        // Generate unique reservation code
        $reservation_code = 'YRR-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Get optimal table for this reservation
        $optimal_table = $this->tables_model->get_optimal_table(
            intval($_POST['party_size']),
            sanitize_text_field($_POST['reservation_date']),
            intval($_POST['time_slot_id'])
        );
        
        // Prepare reservation data
        $reservation_data = array(
            'reservation_code' => $reservation_code,
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => sanitize_text_field($_POST['customer_phone']),
            'party_size' => intval($_POST['party_size']),
            'reservation_date' => sanitize_text_field($_POST['reservation_date']),
            'time_slot_id' => intval($_POST['time_slot_id']),
            'table_id' => $optimal_table ? $optimal_table->id : null,
            'special_requests' => sanitize_textarea_field($_POST['special_requests'] ?? ''),
            'status' => 'pending',
            'coupon_code' => sanitize_text_field($_POST['coupon_code'] ?? ''),
            'original_price' => 0.00,
            'discount_amount' => floatval($_POST['discount_amount'] ?? 0),
            'final_price' => 0.00
        );
        
        // Create reservation
        $reservation_id = $this->reservation_model->create($reservation_data);
        
        if ($reservation_id) {
            // Send confirmation email
            if (function_exists('yrr_send_reservation_email_with_discount')) {
                yrr_send_reservation_email_with_discount($reservation_data);
            }
            
            wp_send_json_success(array(
                'reservation_code' => $reservation_code,
                'message' => 'Reservation created successfully!'
            ));
        } else {
            wp_send_json_error('Failed to create reservation. Please try again.');
        }
    }
    
    /**
     * ✅ AJAX: VALIDATE COUPON
     */
    public function ajax_validate_coupon() {
        check_ajax_referer('yrr_validate_coupon', 'nonce');
        
        $coupon_code = sanitize_text_field($_POST['coupon_code']);
        $party_size = intval($_POST['party_size']);
        
        if (empty($coupon_code)) {
            wp_send_json_error('Please enter a coupon code');
        }
        
        global $wpdb;
        
        // Check if coupon exists and is valid
        $coupon = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}yrr_coupons 
             WHERE coupon_code = %s 
             AND is_active = 1 
             AND (valid_until IS NULL OR valid_until > NOW())
             AND (usage_limit IS NULL OR usage_count < usage_limit)",
            $coupon_code
        ));
        
        if (!$coupon) {
            wp_send_json_error('Invalid or expired coupon code');
        }
        
        // Calculate base price
        $base_price = floatval($this->settings_model->get('base_price_per_person', '15.00')) * $party_size;
        
        // Check minimum order amount
        if ($base_price < $coupon->min_order_amount) {
            wp_send_json_error('Minimum order amount is $' . number_format($coupon->min_order_amount, 2));
        }
        
        // Calculate discount
        if ($coupon->discount_type === 'percentage') {
            $discount_amount = ($base_price * $coupon->discount_value) / 100;
        } else {
            $discount_amount = $coupon->discount_value;
        }
        
        // Apply max discount limit if set
        if ($coupon->max_discount_amount && $discount_amount > $coupon->max_discount_amount) {
            $discount_amount = $coupon->max_discount_amount;
        }
        
        $final_price = $base_price - $discount_amount;
        
        wp_send_json_success(array(
            'coupon_name' => $coupon->coupon_name,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
            'discount_amount' => $discount_amount,
            'original_price' => $base_price,
            'final_price' => $final_price,
            'currency_symbol' => $this->settings_model->get('currency_symbol', '$')
        ));
    }
    
    /**
     * ✅ ENQUEUE FRONTEND ASSETS
     */
    public function enqueue_frontend_assets() {
        // Enqueue styles
        wp_enqueue_style('yrr-frontend-style', YRR_PLUGIN_URL . 'assets/css/frontend.css', array(), YRR_VERSION);
        
        // Enqueue scripts
        wp_enqueue_script('yrr-frontend-script', YRR_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), YRR_VERSION, true);
        
        // Localize script with AJAX data
        wp_localize_script('yrr-frontend-script', 'yrr_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'get_slots_nonce' => wp_create_nonce('yrr_get_slots'),
            'create_reservation_nonce' => wp_create_nonce('yrr_create_reservation'),
            'validate_coupon_nonce' => wp_create_nonce('yrr_validate_coupon'),
            'currency_symbol' => $this->settings_model->get('currency_symbol', '$')
        ));
    }
}
