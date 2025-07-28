<?php
/**
 * Public Class
 * Handles all public-facing functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class RRS_Public {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles_scripts'));
        add_action('init', array($this, 'init_public_hooks'));
        add_shortcode('restaurant_hours', array($this, 'display_opening_hours'));
        add_shortcode('restaurant_contact', array($this, 'display_contact_info'));
        add_shortcode('reservation_lookup', array($this, 'reservation_lookup_form'));
        add_action('wp_ajax_nopriv_lookup_reservation', array($this, 'ajax_lookup_reservation'));
        add_action('wp_ajax_lookup_reservation', array($this, 'ajax_lookup_reservation'));
        add_action('wp_ajax_nopriv_cancel_reservation', array($this, 'ajax_cancel_reservation'));
        add_action('wp_ajax_cancel_reservation', array($this, 'ajax_cancel_reservation'));
        add_action('wp_head', array($this, 'add_structured_data'));
        add_filter('wp_title', array($this, 'modify_reservation_page_title'), 10, 2);
        add_action('wp_footer', array($this, 'add_schema_markup'));
    }
    
    public function init_public_hooks() {
        // Handle reservation confirmation links from emails
        if (isset($_GET['rrs_action']) && isset($_GET['reservation_code'])) {
            $this->handle_reservation_actions();
        }
        
        // Add custom CSS classes to body
        add_filter('body_class', array($this, 'add_body_classes'));
        
        // Register custom post types if needed
        $this->register_custom_post_types();
    }
    
    public function enqueue_styles_scripts() {
        // Only enqueue on pages that need it
        if ($this->should_enqueue_assets()) {
            wp_enqueue_style('rrs-public-style', RRS_PLUGIN_URL . 'public/css/public.css', array(), RRS_VERSION);
            wp_enqueue_script('rrs-public-script', RRS_PLUGIN_URL . 'public/js/public.js', array('jquery'), RRS_VERSION, true);
            
            // Localize script for AJAX
            wp_localize_script('rrs-public-script', 'rrs_public', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('rrs_public_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'restaurant-reservation'),
                    'error' => __('An error occurred. Please try again.', 'restaurant-reservation'),
                    'confirm_cancel' => __('Are you sure you want to cancel this reservation?', 'restaurant-reservation'),
                    'cancellation_success' => __('Your reservation has been cancelled successfully.', 'restaurant-reservation'),
                    'reservation_not_found' => __('Reservation not found. Please check your confirmation code.', 'restaurant-reservation'),
                    'invalid_code' => __('Please enter a valid reservation code.', 'restaurant-reservation')
                )
            ));
        }
    }
    
    private function should_enqueue_assets() {
        global $post;
        
        // Enqueue if shortcodes are present
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'restaurant_booking_form') ||
            has_shortcode($post->post_content, 'restaurant_hours') ||
            has_shortcode($post->post_content, 'restaurant_contact') ||
            has_shortcode($post->post_content, 'reservation_lookup')
        )) {
            return true;
        }
        
        // Enqueue on specific pages
        if (is_page() && $post) {
            $reservation_pages = array('reservations', 'book-table', 'reserve', 'booking');
            if (in_array($post->post_name, $reservation_pages)) {
                return true;
            }
        }
        
        return false;
    }
    
    public function display_opening_hours($atts) {
        $atts = shortcode_atts(array(
            'style' => 'default',
            'show_closed_days' => 'true',
            'format' => '12hour'
        ), $atts);
        
        $settings = RRS_Database_Manager::get_settings();
        $opening_hours = isset($settings['opening_hours']) ? $settings['opening_hours'] : array();
        
        if (empty($opening_hours)) {
            return '<p>' . __('Opening hours not available.', 'restaurant-reservation') . '</p>';
        }
        
        $days_order = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        $day_names = array(
            'monday' => __('Monday', 'restaurant-reservation'),
            'tuesday' => __('Tuesday', 'restaurant-reservation'),
            'wednesday' => __('Wednesday', 'restaurant-reservation'),
            'thursday' => __('Thursday', 'restaurant-reservation'),
            'friday' => __('Friday', 'restaurant-reservation'),
            'saturday' => __('Saturday', 'restaurant-reservation'),
            'sunday' => __('Sunday', 'restaurant-reservation')
        );
        
        ob_start();
        ?>
        <div class="rrs-opening-hours rrs-style-<?php echo esc_attr($atts['style']); ?>">
            <h3 class="rrs-hours-title"><?php _e('Opening Hours', 'restaurant-reservation'); ?></h3>
            <div class="rrs-hours-list">
                <?php foreach ($days_order as $day): ?>
                    <?php 
                    $day_hours = isset($opening_hours[$day]) ? $opening_hours[$day] : null;
                    $is_closed = empty($day_hours['open']) || empty($day_hours['close']);
                    
                    if ($is_closed && $atts['show_closed_days'] === 'false') {
                        continue;
                    }
                    ?>
                    <div class="rrs-hours-day <?php echo $is_closed ? 'closed' : 'open'; ?> <?php echo date('l') === ucfirst($day) ? 'today' : ''; ?>">
                        <span class="rrs-day-name"><?php echo $day_names[$day]; ?></span>
                        <span class="rrs-day-hours">
                            <?php if ($is_closed): ?>
                                <span class="closed-text"><?php _e('Closed', 'restaurant-reservation'); ?></span>
                            <?php else: ?>
                                <?php
                                $open_time = $atts['format'] === '24hour' ? 
                                    $day_hours['open'] : 
                                    date('g:i A', strtotime($day_hours['open']));
                                $close_time = $atts['format'] === '24hour' ? 
                                    $day_hours['close'] : 
                                    date('g:i A', strtotime($day_hours['close']));
                                ?>
                                <span class="open-time"><?php echo $open_time; ?></span>
                                <span class="time-separator"> - </span>
                                <span class="close-time"><?php echo $close_time; ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="rrs-hours-note">
                <p><small><?php _e('Hours may vary during holidays. Please call to confirm.', 'restaurant-reservation'); ?></small></p>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    public function display_contact_info($atts) {
        $atts = shortcode_atts(array(
            'show_phone' => 'true',
            'show_email' => 'true',
            'show_address' => 'true',
            'show_map' => 'false',
            'style' => 'default'
        ), $atts);
        
        ob_start();
        ?>
        <div class="rrs-contact-info rrs-style-<?php echo esc_attr($atts['style']); ?>">
            <h3 class="rrs-contact-title"><?php _e('Contact Information', 'restaurant-reservation'); ?></h3>
            
            <div class="rrs-contact-details">
                <?php if ($atts['show_phone'] === 'true'): ?>
                    <div class="rrs-contact-item rrs-phone">
                        <span class="rrs-contact-icon">📞</span>
                        <div class="rrs-contact-content">
                            <strong><?php _e('Phone', 'restaurant-reservation'); ?></strong>
                            <a href="tel:+1234567890">(123) 456-7890</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_email'] === 'true'): ?>
                    <div class="rrs-contact-item rrs-email">
                        <span class="rrs-contact-icon">✉️</span>
                        <div class="rrs-contact-content">
                            <strong><?php _e('Email', 'restaurant-reservation'); ?></strong>
                            <a href="mailto:info@restaurant.com">info@restaurant.com</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($atts['show_address'] === 'true'): ?>
                    <div class="rrs-contact-item rrs-address">
                        <span class="rrs-contact-icon">📍</span>
                        <div class="rrs-contact-content">
                            <strong><?php _e('Address', 'restaurant-reservation'); ?></strong>
                            <address>
                                123 Restaurant Street<br>
                                Food District, City 12345<br>
                                Country
                            </address>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($atts['show_map'] === 'true'): ?>
                <div class="rrs-map-container">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3024.123456789!2d-74.00597968459418!3d40.71278997933024!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDDCsDQyJzQ2LjAiTiA3NMKwMDAnMjEuNSJX!5e0!3m2!1sen!2sus!4v1234567890123!5m2!1sen!2sus"
                        width="100%" 
                        height="300" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    public function reservation_lookup_form($atts) {
        $atts = shortcode_atts(array(
            'style' => 'default',
            'show_instructions' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <div class="rrs-lookup-form rrs-style-<?php echo esc_attr($atts['style']); ?>">
            <h3 class="rrs-lookup-title"><?php _e('Find Your Reservation', 'restaurant-reservation'); ?></h3>
            
            <?php if ($atts['show_instructions'] === 'true'): ?>
                <p class="rrs-lookup-instructions">
                    <?php _e('Enter your reservation code to view, modify, or cancel your reservation.', 'restaurant-reservation'); ?>
                </p>
            <?php endif; ?>
            
            <form id="rrs-lookup-form" class="rrs-lookup-form-fields">
                <div class="rrs-form-group">
                    <label for="reservation_code"><?php _e('Reservation Code', 'restaurant-reservation'); ?></label>
                    <input type="text" 
                           id="reservation_code" 
                           name="reservation_code" 
                           placeholder="<?php _e('e.g., RES-20250726-ABC123', 'restaurant-reservation'); ?>" 
                           required
                           pattern="[A-Z]{3}-[0-9]{8}-[A-Z0-9]{6}"
                           title="<?php _e('Please enter a valid reservation code', 'restaurant-reservation'); ?>">
                </div>
                
                <div class="rrs-form-group">
                    <label for="customer_email"><?php _e('Email Address', 'restaurant-reservation'); ?></label>
                    <input type="email" 
                           id="customer_email" 
                           name="customer_email" 
                           placeholder="<?php _e('your@email.com', 'restaurant-reservation'); ?>" 
                           required>
                </div>
                
                <button type="submit" class="rrs-btn rrs-btn-primary">
                    <span class="lookup-text"><?php _e('Find Reservation', 'restaurant-reservation'); ?></span>
                    <span class="loading-text" style="display: none;"><?php _e('Searching...', 'restaurant-reservation'); ?></span>
                </button>
            </form>
            
            <div id="rrs-lookup-results" style="display: none;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#rrs-lookup-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $submitBtn = $form.find('button[type="submit"]');
                var $results = $('#rrs-lookup-results');
                
                // Show loading state
                $submitBtn.find('.lookup-text').hide();
                $submitBtn.find('.loading-text').show();
                $submitBtn.prop('disabled', true);
                
                $.ajax({
                    url: rrs_public.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'lookup_reservation',
                        reservation_code: $('#reservation_code').val(),
                        customer_email: $('#customer_email').val(),
                        nonce: rrs_public.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $results.html(response.data.html).slideDown();
                            $form.slideUp();
                        } else {
                            alert(response.data || rrs_public.strings.reservation_not_found);
                        }
                    },
                    error: function() {
                        alert(rrs_public.strings.error);
                    },
                    complete: function() {
                        // Reset loading state
                        $submitBtn.find('.lookup-text').show();
                        $submitBtn.find('.loading-text').hide();
                        $submitBtn.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    public function ajax_lookup_reservation() {
        check_ajax_referer('rrs_public_nonce', 'nonce');
        
        $reservation_code = sanitize_text_field($_POST['reservation_code']);
        $customer_email = sanitize_email($_POST['customer_email']);
        
        if (empty($reservation_code) || empty($customer_email)) {
            wp_send_json_error(__('Please fill in all fields.', 'restaurant-reservation'));
        }
        
        global $wpdb;
        
        $reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rrs_reservations 
             WHERE reservation_code = %s AND customer_email = %s",
            $reservation_code, $customer_email
        ));
        
        if (!$reservation) {
            wp_send_json_error(__('Reservation not found. Please check your code and email address.', 'restaurant-reservation'));
        }
        
        // Generate HTML for reservation details
        ob_start();
        ?>
        <div class="rrs-reservation-details">
            <h4><?php _e('Reservation Found', 'restaurant-reservation'); ?></h4>
            
            <div class="rrs-reservation-info">
                <div class="rrs-info-row">
                    <span class="rrs-info-label"><?php _e('Confirmation Code:', 'restaurant-reservation'); ?></span>
                    <span class="rrs-info-value"><strong><?php echo esc_html($reservation->reservation_code); ?></strong></span>
                </div>
                
                <div class="rrs-info-row">
                    <span class="rrs-info-label"><?php _e('Name:', 'restaurant-reservation'); ?></span>
                    <span class="rrs-info-value"><?php echo esc_html($reservation->customer_name); ?></span>
                </div>
                
                <div class="rrs-info-row">
                    <span class="rrs-info-label"><?php _e('Date & Time:', 'restaurant-reservation'); ?></span>
                    <span class="rrs-info-value">
                        <?php echo date('F j, Y \a\t g:i A', strtotime($reservation->reservation_date . ' ' . $reservation->reservation_time)); ?>
                    </span>
                </div>
                
                <div class="rrs-info-row">
                    <span class="rrs-info-label"><?php _e('Party Size:', 'restaurant-reservation'); ?></span>
                    <span class="rrs-info-value"><?php echo $reservation->party_size; ?> <?php echo _n('guest', 'guests', $reservation->party_size, 'restaurant-reservation'); ?></span>
                </div>
                
                <div class="rrs-info-row">
                    <span class="rrs-info-label"><?php _e('Status:', 'restaurant-reservation'); ?></span>
                    <span class="rrs-info-value">
                        <span class="rrs-status rrs-status-<?php echo esc_attr($reservation->status); ?>">
                            <?php echo ucfirst($reservation->status); ?>
                        </span>
                    </span>
                </div>
                
                <?php if (!empty($reservation->special_requests)): ?>
                    <div class="rrs-info-row">
                        <span class="rrs-info-label"><?php _e('Special Requests:', 'restaurant-reservation'); ?></span>
                        <span class="rrs-info-value"><?php echo esc_html($reservation->special_requests); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (in_array($reservation->status, ['pending', 'confirmed'])): ?>
                <div class="rrs-reservation-actions">
                    <?php if (strtotime($reservation->reservation_date . ' ' . $reservation->reservation_time) > strtotime('+2 hours')): ?>
                        <button type="button" 
                                class="rrs-btn rrs-btn-secondary rrs-cancel-reservation" 
                                data-reservation-id="<?php echo $reservation->id; ?>"
                                data-reservation-code="<?php echo esc_attr($reservation->reservation_code); ?>">
                            <?php _e('Cancel Reservation', 'restaurant-reservation'); ?>
                        </button>
                    <?php else: ?>
                        <p class="rrs-cancellation-notice">
                            <?php _e('This reservation cannot be cancelled online as it\'s less than 2 hours away. Please call us directly.', 'restaurant-reservation'); ?>
                        </p>
                    <?php endif; ?>
                    
                    <a href="tel:+1234567890" class="rrs-btn rrs-btn-primary">
                        <?php _e('Call Restaurant', 'restaurant-reservation'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="rrs-lookup-again">
                <button type="button" class="rrs-btn rrs-btn-link" onclick="jQuery('#rrs-lookup-form').slideDown(); jQuery('#rrs-lookup-results').slideUp();">
                    <?php _e('Look up another reservation', 'restaurant-reservation'); ?>
                </button>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.rrs-cancel-reservation').on('click', function() {
                if (!confirm(rrs_public.strings.confirm_cancel)) {
                    return;
                }
                
                var $btn = $(this);
                var reservationId = $btn.data('reservation-id');
                var reservationCode = $btn.data('reservation-code');
                
                $btn.prop('disabled', true).text('<?php _e('Cancelling...', 'restaurant-reservation'); ?>');
                
                $.ajax({
                    url: rrs_public.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'cancel_reservation',
                        reservation_id: reservationId,
                        reservation_code: reservationCode,
                        nonce: rrs_public.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.rrs-reservation-details').html('<div class="rrs-cancellation-success"><h4><?php _e('Reservation Cancelled', 'restaurant-reservation'); ?></h4><p>' + rrs_public.strings.cancellation_success + '</p></div>');
                        } else {
                            alert(response.data || rrs_public.strings.error);
                            $btn.prop('disabled', false).text('<?php _e('Cancel Reservation', 'restaurant-reservation'); ?>');
                        }
                    },
                    error: function() {
                        alert(rrs_public.strings.error);
                        $btn.prop('disabled', false).text('<?php _e('Cancel Reservation', 'restaurant-reservation'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
        
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    public function ajax_cancel_reservation() {
        check_ajax_referer('rrs_public_nonce', 'nonce');
        
        $reservation_id = intval($_POST['reservation_id']);
        $reservation_code = sanitize_text_field($_POST['reservation_code']);
        
        global $wpdb;
        
        // Verify the reservation exists and belongs to the user
        $reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rrs_reservations 
             WHERE id = %d AND reservation_code = %s",
            $reservation_id, $reservation_code
        ));
        
        if (!$reservation) {
            wp_send_json_error(__('Reservation not found.', 'restaurant-reservation'));
        }
        
        // Check if cancellation is allowed (at least 2 hours in advance)
        $reservation_datetime = strtotime($reservation->reservation_date . ' ' . $reservation->reservation_time);
        if ($reservation_datetime <= strtotime('+2 hours')) {
            wp_send_json_error(__('Cancellations must be made at least 2 hours in advance.', 'restaurant-reservation'));
        }
        
        // Cancel the reservation
        $reservation_model = new RRS_Reservation();
        $result = $reservation_model->cancel_reservation($reservation_id, 'Cancelled by customer online');
        
        if ($result) {
            // Send cancellation notification
            do_action('rrs_reservation_cancelled_by_customer', $reservation_id, $reservation);
            
            wp_send_json_success(__('Your reservation has been cancelled successfully.', 'restaurant-reservation'));
        } else {
            wp_send_json_error(__('Failed to cancel reservation. Please try again.', 'restaurant-reservation'));
        }
    }
    
    private function handle_reservation_actions() {
        $action = sanitize_text_field($_GET['rrs_action']);
        $reservation_code = sanitize_text_field($_GET['reservation_code']);
        
        switch ($action) {
            case 'confirm':
                $this->handle_email_confirmation($reservation_code);
                break;
                
            case 'cancel':
                $this->handle_email_cancellation($reservation_code);
                break;
                
            case 'view':
                $this->handle_reservation_view($reservation_code);
                break;
        }
    }
    
    private function handle_email_confirmation($reservation_code) {
        global $wpdb;
        
        $reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rrs_reservations WHERE reservation_code = %s",
            $reservation_code
        ));
        
        if ($reservation && $reservation->status === 'pending') {
            $reservation_model = new RRS_Reservation();
            $result = $reservation_model->update_status($reservation->id, 'confirmed');
            
            if ($result) {
                wp_redirect(add_query_arg('confirmation_success', '1', home_url()));
                exit;
            }
        }
        
        wp_redirect(add_query_arg('confirmation_error', '1', home_url()));
        exit;
    }
    
    private function handle_email_cancellation($reservation_code) {
        // Similar to handle_email_confirmation but for cancellation
        global $wpdb;
        
        $reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rrs_reservations WHERE reservation_code = %s",
            $reservation_code
        ));
        
        if ($reservation && in_array($reservation->status, ['pending', 'confirmed'])) {
            $reservation_model = new RRS_Reservation();
            $result = $reservation_model->cancel_reservation($reservation->id, 'Cancelled via email link');
            
            if ($result) {
                wp_redirect(add_query_arg('cancellation_success', '1', home_url()));
                exit;
            }
        }
        
        wp_redirect(add_query_arg('cancellation_error', '1', home_url()));
        exit;
    }
    
    private function register_custom_post_types() {
        // Register menu items post type (optional)
        register_post_type('rrs_menu_item', array(
            'labels' => array(
                'name' => __('Menu Items', 'restaurant-reservation'),
                'singular_name' => __('Menu Item', 'restaurant-reservation'),
            ),
            'public' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'taxonomies' => array('rrs_menu_category'),
        ));
        
        // Register menu categories taxonomy
        register_taxonomy('rrs_menu_category', 'rrs_menu_item', array(
            'labels' => array(
                'name' => __('Menu Categories', 'restaurant-reservation'),
                'singular_name' => __('Menu Category', 'restaurant-reservation'),
            ),
            'hierarchical' => true,
            'show_in_rest' => true,
        ));
    }
    
    public function add_body_classes($classes) {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'restaurant_booking_form')) {
            $classes[] = 'has-reservation-form';
        }
        
        return $classes;
    }
    
    public function add_structured_data() {
        if ($this->should_add_structured_data()) {
            $settings = RRS_Database_Manager::get_settings();
            $opening_hours = isset($settings['opening_hours']) ? $settings['opening_hours'] : array();
            
            $structured_data = array(
                '@context' => 'https://schema.org',
                '@type' => 'Restaurant',
                'name' => get_bloginfo('name'),
                'url' => home_url(),
                'telephone' => '+1234567890', // This should come from settings
                'email' => 'info@restaurant.com', // This should come from settings
                'address' => array(
                    '@type' => 'PostalAddress',
                    'streetAddress' => '123 Restaurant Street',
                    'addressLocality' => 'City',
                    'postalCode' => '12345',
                    'addressCountry' => 'US'
                ),
                'openingHours' => $this->format_opening_hours_for_schema($opening_hours),
                'acceptsReservations' => 'True',
                'servesCuisine' => 'International', // This should come from settings
                'priceRange' => '$$', // This should come from settings
            );
            
            echo '<script type="application/ld+json">' . json_encode($structured_data, JSON_UNESCAPED_SLASHES) . '</script>';
        }
    }
    
    private function should_add_structured_data() {
        global $post;
        
        return is_front_page() || 
               (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'restaurant_booking_form')) ||
               is_page(array('contact', 'about', 'menu'));
    }
    
    private function format_opening_hours_for_schema($opening_hours) {
        if (empty($opening_hours)) {
            return array();
        }
        
        $schema_hours = array();
        $day_mapping = array(
            'monday' => 'Mo',
            'tuesday' => 'Tu', 
            'wednesday' => 'We',
            'thursday' => 'Th',
            'friday' => 'Fr',
            'saturday' => 'Sa',
            'sunday' => 'Su'
        );
        
        foreach ($opening_hours as $day => $hours) {
            if (!empty($hours['open']) && !empty($hours['close'])) {
                $schema_hours[] = $day_mapping[$day] . ' ' . $hours['open'] . '-' . $hours['close'];
            }
        }
        
        return $schema_hours;
    }
    
    public function modify_reservation_page_title($title, $sep) {
        if (isset($_GET['confirmation_success'])) {
            return __('Reservation Confirmed', 'restaurant-reservation') . ' ' . $sep . ' ' . get_bloginfo('name');
        }
        
        if (isset($_GET['cancellation_success'])) {
            return __('Reservation Cancelled', 'restaurant-reservation') . ' ' . $sep . ' ' . get_bloginfo('name');
        }
        
        return $title;
    }
    
    public function add_schema_markup() {
        // Add additional schema markup in footer if needed
        if (is_page() && has_shortcode(get_post()->post_content, 'restaurant_booking_form')) {
            ?>
            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "WebPage",
                "name": "<?php echo get_the_title(); ?>",
                "description": "Make a reservation at <?php echo get_bloginfo('name'); ?>",
                "url": "<?php echo get_permalink(); ?>",
                "mainEntity": {
                    "@type": "Restaurant",
                    "name": "<?php echo get_bloginfo('name'); ?>",
                    "acceptsReservations": "True"
                }
            }
            </script>
            <?php
        }
    }
}
