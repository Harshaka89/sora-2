<?php
class RRS_Booking_Form {
    
    public function __construct() {
        add_shortcode('restaurant_booking_form', array($this, 'render_form'));
        add_action('init', array($this, 'handle_form_submission'));
    }
    
    public function handle_form_submission() {
        if (isset($_POST['rrs_action']) && $_POST['rrs_action'] === 'submit_reservation') {
            if (!wp_verify_nonce($_POST['rrs_nonce'], 'rrs_booking_form')) {
                wp_redirect(add_query_arg('reservation_status', 'error', wp_get_referer()));
                exit;
            }
            
            $this->process_reservation_data();
        }
    }
    
    public function render_form() {
        $success_message = '';
        $error_message = '';
        
        if (isset($_GET['reservation_status'])) {
            if ($_GET['reservation_status'] === 'success') {
                $success_message = 'Your reservation has been submitted successfully! We will contact you soon.';
            } elseif ($_GET['reservation_status'] === 'error') {
                $error_message = 'There was an error submitting your reservation. Please try again.';
            }
        }
        
        ob_start();
        ?>
        <div style="max-width: 600px; margin: 20px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h2 style="text-align: center; color: #333; margin-bottom: 30px;">Make a Reservation</h2>
            
            <?php if ($success_message): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #c3e6cb;">
                    <strong>Success!</strong> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #f5c6cb;">
                    <strong>Error!</strong> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" style="display: grid; gap: 20px;">
                <?php wp_nonce_field('rrs_booking_form', 'rrs_nonce'); ?>
                <input type="hidden" name="rrs_action" value="submit_reservation">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Full Name *</label>
                        <input type="text" name="customer_name" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Email Address *</label>
                        <input type="email" name="customer_email" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Phone Number *</label>
                        <input type="tel" name="customer_phone" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Party Size *</label>
                        <select name="party_size" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                            <option value="">Select party size</option>
                            <?php for($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $i == 1 ? 'Guest' : 'Guests'; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Preferred Date *</label>
                        <input type="date" name="reservation_date" required min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+60 days')); ?>" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Preferred Time *</label>
                        <select name="reservation_time" required style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                            <option value="">Select time</option>
                            <option value="17:00">5:00 PM</option>
                            <option value="17:30">5:30 PM</option>
                            <option value="18:00">6:00 PM</option>
                            <option value="18:30">6:30 PM</option>
                            <option value="19:00">7:00 PM</option>
                            <option value="19:30">7:30 PM</option>
                            <option value="20:00">8:00 PM</option>
                            <option value="20:30">8:30 PM</option>
                            <option value="21:00">9:00 PM</option>
                            <option value="21:30">9:30 PM</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Special Requests</label>
                    <textarea name="special_requests" rows="4" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; resize: vertical;" placeholder="Dietary requirements, celebrations, accessibility needs, table preferences..."></textarea>
                </div>
                
                <div>
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="gdpr_consent" required style="margin-top: 4px;">
                        <span style="font-size: 14px; color: #666;">I agree to the processing of my personal data for this reservation and consent to being contacted regarding my booking.</span>
                    </label>
                </div>
                
                <button type="submit" style="width: 100%; padding: 15px; background: #0073aa; color: white; border: none; border-radius: 5px; font-size: 18px; font-weight: bold; cursor: pointer; transition: background 0.3s;" onmouseover="this.style.background='#005a87'" onmouseout="this.style.background='#0073aa'">
                    Make Reservation
                </button>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    private function process_reservation_data() {
        $required_fields = ['customer_name', 'customer_email', 'customer_phone', 'party_size', 'reservation_date', 'reservation_time'];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_redirect(add_query_arg('reservation_status', 'error', wp_get_referer()));
                exit;
            }
        }
        
        if (empty($_POST['gdpr_consent'])) {
            wp_redirect(add_query_arg('reservation_status', 'error', wp_get_referer()));
            exit;
        }
        
        global $wpdb;
        
        $reservation_data = array(
            'reservation_code' => 'WEB-' . time() . '-' . rand(100, 999),
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => sanitize_text_field($_POST['customer_phone']),
            'party_size' => intval($_POST['party_size']),
            'reservation_date' => sanitize_text_field($_POST['reservation_date']),
            'reservation_time' => sanitize_text_field($_POST['reservation_time']),
            'special_requests' => sanitize_textarea_field($_POST['special_requests']),
            'status' => 'pending',
            'gdpr_consent' => 1
        );
        
        $result = $wpdb->insert($wpdb->prefix . 'rrs_reservations', $reservation_data);
        
        if ($result) {
            wp_redirect(add_query_arg('reservation_status', 'success', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('reservation_status', 'error', wp_get_referer()));
        }
        exit;
    }
}
