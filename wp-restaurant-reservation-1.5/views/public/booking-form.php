<?php
/**
 * Public Booking Form View
 * 
 * Customer-facing reservation booking form
 * 
 * @package RestaurantReservations
 * @subpackage Views/Public
 * @version 1.4.0
 * @since 1.0.0
 * @author Your Name
 * 
 * @var array $settings Restaurant settings for form validation
 * @var array $message Success/error message data
 * @var string $theme Form theme variant
 * 
 * Form Fields:
 * - Customer information (name, email, phone)
 * - Reservation details (date, time, party size)
 * - Special requests
 * - CAPTCHA integration (optional)
 * 
 * Features:
 * - Real-time validation
 * - Responsive design
 * - Multiple theme support
 * - Accessibility compliant
 */

if (!defined('ABSPATH')) exit;


<div class="rrs-booking-form">
    <h2>🍽️ Reserve Your Table</h2>
    
    <?php if (!empty($message)): ?>
        <div style="background: <?php echo $message['type'] === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $message['type'] === 'success' ? '#155724' : '#721c24'; ?>; padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: center;">
            <?php if ($message['type'] === 'success'): ?>
                <div style="font-size: 2em; margin-bottom: 10px;">✅</div>
                <h3><?php echo esc_html($message['message']); ?></h3>
                <?php if (isset($message['reservation_code'])): ?>
                    <p><strong>Confirmation Code:</strong> <?php echo esc_html($message['reservation_code']); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <div style="font-size: 2em; margin-bottom: 10px;">❌</div>
                <p><?php echo esc_html($message['message']); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <form method="post">
        <?php wp_nonce_field('rrs_booking', 'rrs_nonce'); ?>
        <input type="hidden" name="rrs_submit" value="1">
        
        <div class="rrs-form-row">
            <div>
                <label for="customer_name">Full Name *</label>
                <input type="text" id="customer_name" name="customer_name" required placeholder="Enter your full name">
            </div>
            <div>
                <label for="customer_email">Email Address *</label>
                <input type="email" id="customer_email" name="customer_email" required placeholder="your@email.com">
            </div>
        </div>
        
        <div class="rrs-form-row">
            <div>
                <label for="customer_phone">Phone Number *</label>
                <input type="tel" id="customer_phone" name="customer_phone" required placeholder="(123) 456-7890">
            </div>
            <div>
                <label for="party_size">Party Size *</label>
                <select id="party_size" name="party_size" required>
                    <option value="">Select party size</option>
                    <?php for($i = 1; $i <= ($settings['max_party_size'] ?: 12); $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $i == 1 ? 'Guest' : 'Guests'; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        
        <div class="rrs-form-row">
            <div>
                <label for="reservation_date">Preferred Date *</label>
                <input type="date" id="reservation_date" name="reservation_date" required min="<?php echo date('Y-m-d', strtotime('+2 hours')); ?>" max="<?php echo date('Y-m-d', strtotime('+60 days')); ?>">
            </div>
            <div>
                <label for="reservation_time">Preferred Time *</label>
                <select id="reservation_time" name="reservation_time" required>
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
                </select>
            </div>
        </div>
        
        <div class="rrs-form-full">
            <label for="special_requests">Special Requests</label>
            <textarea id="special_requests" name="special_requests" rows="4" placeholder="Any dietary requirements, celebrations, or special needs..."></textarea>
        </div>
        
        <button type="submit" class="rrs-submit-btn">🍽️ Submit Reservation Request</button>
    </form>
</div>
