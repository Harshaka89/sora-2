// ✅ YENOLX RESTAURANT RESERVATION - FRONTEND JAVASCRIPT

jQuery(document).ready(function($) {
    'use strict';
    
    let currentStep = 1;
    let selectedTimeSlot = null;
    let appliedCoupon = null;
    
    // Initialize booking form
    initBookingForm();
    
    function initBookingForm() {
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        $('#reservation_date').attr('min', today);
        
        // Bind events
        bindEvents();
        
        // Initialize first step
        showStep(1);
    }
    
    function bindEvents() {
        // Step navigation
        $('.yrr-next-step').on('click', handleNextStep);
        $('.yrr-prev-step').on('click', handlePrevStep);
        
        // Date and party size change
        $('#reservation_date, #party_size').on('change', function() {
            if ($('#reservation_date').val() && $('#party_size').val()) {
                $('.yrr-step-1 .yrr-next-step').prop('disabled', false);
            }
        });
        
        // Time slot selection
        $(document).on('click', '.yrr-time-slot', handleTimeSlotSelection);
        
        // Form field changes for summary
        $('#customer_name, #customer_email, #customer_phone').on('input', updateSummary);
        
        // Coupon validation
        $('#yrr-validate-coupon').on('click', validateCoupon);
        $('#coupon_code').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                validateCoupon();
            }
        });
        
        // Form submission
        $('#yrr-reservation-form').on('submit', handleFormSubmission);
    }
    
    function handleNextStep(e) {
        e.preventDefault();
        const nextStep = parseInt($(this).data('next'));
        
        if (validateCurrentStep()) {
            if (nextStep === 2) {
                loadAvailableTimeSlots();
            } else if (nextStep === 4) {
                updateSummary();
            }
            showStep(nextStep);
        }
    }
    
    function handlePrevStep(e) {
        e.preventDefault();
        const prevStep = parseInt($(this).data('prev'));
        showStep(prevStep);
    }
    
    function showStep(step) {
        // Hide all steps
        $('.yrr-form-step').removeClass('active');
        
        // Show target step
        $(`.yrr-step-${step}`).addClass('active');
        
        currentStep = step;
        
        // Update progress
        updateProgress(step);
        
        // Scroll to top of form
        $('html, body').animate({
            scrollTop: $('#yrr-booking-form').offset().top - 50
        }, 300);
    }
    
    function updateProgress(step) {
        $('.yrr-step-number').removeClass('active');
        for (let i = 1; i <= step; i++) {
            $(`.yrr-step-${i} .yrr-step-number`).addClass('active');
        }
    }
    
    function validateCurrentStep() {
        if (currentStep === 1) {
            const date = $('#reservation_date').val();
            const partySize = $('#party_size').val();
            
            if (!date || !partySize) {
                showNotification('Please select date and party size', 'error');
                return false;
            }
            
            // Check if date is not in the past
            if (new Date(date) < new Date().setHours(0,0,0,0)) {
                showNotification('Please select a future date', 'error');
                return false;
            }
            
            return true;
        }
        
        if (currentStep === 2) {
            if (!selectedTimeSlot) {
                showNotification('Please select a time slot', 'error');
                return false;
            }
            return true;
        }
        
        if (currentStep === 3) {
            const name = $('#customer_name').val().trim();
            const email = $('#customer_email').val().trim();
            const phone = $('#customer_phone').val().trim();
            
            if (!name || !email || !phone) {
                showNotification('Please fill in all required fields', 'error');
                return false;
            }
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showNotification('Please enter a valid email address', 'error');
                return false;
            }
            
            return true;
        }
        
        return true;
    }
    
    function loadAvailableTimeSlots() {
        const date = $('#reservation_date').val();
        const partySize = $('#party_size').val();
        
        // Show loading
        $('#yrr-time-slots-container').html(`
            <div class="yrr-loading">
                <div class="yrr-spinner"></div>
                <p>Finding available times for ${formatDate(date)}...</p>
            </div>
        `);
        
        // Make AJAX request
        $.ajax({
            url: yrr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'yrr_get_available_slots',
                nonce: yrr_ajax.get_slots_nonce,
                date: date,
                party_size: partySize
            },
            success: function(response) {
                if (response.success) {
                    displayTimeSlots(response.data.slots, response.data.date);
                } else {
                    showError('Failed to load available time slots: ' + response.data);
                }
            },
            error: function() {
                showError('Connection error. Please try again.');
            }
        });
    }
    
    function displayTimeSlots(slots, formattedDate) {
        if (slots.length === 0) {
            $('#yrr-time-slots-container').html(`
                <div class="yrr-no-slots">
                    <h4>😔 No available times</h4>
                    <p>Unfortunately, there are no available time slots for ${formattedDate}.</p>
                    <p>Please try selecting a different date.</p>
                </div>
            `);
            return;
        }
        
        let slotsHtml = `
            <div class="yrr-slots-header">
                <h4>Available times for ${formattedDate}</h4>
                <p>Select your preferred time slot</p>
            </div>
            <div class="yrr-time-slots-grid">
        `;
        
        slots.forEach(function(slot) {
            slotsHtml += `
                <div class="yrr-time-slot" data-slot-id="${slot.id}" data-slot-time="${slot.time}">
                    <div class="yrr-time-slot-time">${formatTime(slot.time)}</div>
                    <div class="yrr-time-slot-name">${slot.name}</div>
                    <div class="yrr-time-slot-availability">${slot.available_tables} tables available</div>
                </div>
            `;
        });
        
        slotsHtml += '</div>';
        
        $('#yrr-time-slots-container').html(slotsHtml);
    }
    
    function handleTimeSlotSelection(e) {
        e.preventDefault();
        
        // Remove previous selection
        $('.yrr-time-slot').removeClass('selected');
        
        // Select current slot
        $(this).addClass('selected');
        
        // Store selection
        selectedTimeSlot = {
            id: $(this).data('slot-id'),
            time: $(this).data('slot-time'),
            name: $(this).find('.yrr-time-slot-name').text()
        };
        
        // Update hidden fields
        $('#selected_time_slot_id').val(selectedTimeSlot.id);
        $('#selected_time_slot_time').val(selectedTimeSlot.time);
        
        // Enable next button
        $('.yrr-step-2 .yrr-next-step').prop('disabled', false);
        
        // Show selection feedback
        showNotification('Time slot selected: ' + formatTime(selectedTimeSlot.time), 'success');
    }
    
    function validateCoupon() {
        const couponCode = $('#coupon_code').val().trim();
        const partySize = $('#party_size').val();
        
        if (!couponCode) {
            showCouponMessage('Please enter a coupon code', 'error');
            return;
        }
        
        // Show loading
        $('#yrr-validate-coupon').prop('disabled', true).text('Validating...');
        
        $.ajax({
            url: yrr_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'yrr_validate_coupon',
                nonce: yrr_ajax.validate_coupon_nonce,
                coupon_code: couponCode,
                party_size: partySize
            },
            success: function(response) {
                if (response.success) {
                    appliedCoupon = response.data;
                    $('#coupon_validated').val('1');
                    $('#discount_amount').val(appliedCoupon.discount_amount);
                    
                    showCouponMessage(
                        `✅ ${appliedCoupon.coupon_name} applied! You save ${appliedCoupon.currency_symbol}${appliedCoupon.discount_amount.toFixed(2)}`,
                        'success'
                    );
                    
                    // Disable further edits
                    $('#coupon_code').prop('readonly', true);
                    $('#yrr-validate-coupon').text('Applied').css('background', '#27ae60');
                    
                } else {
                    showCouponMessage('❌ ' + response.data, 'error');
                    appliedCoupon = null;
                    $('#coupon_validated').val('0');
                    $('#discount_amount').val('0');
                }
            },
            error: function() {
                showCouponMessage('❌ Connection error. Please try again.', 'error');
            },
            complete: function() {
                $('#yrr-validate-coupon').prop('disabled', false).text('Apply');
            }
        });
    }
    
    function updateSummary() {
        // Update reservation details
        $('#summary-date').text(formatDate($('#reservation_date').val()));
        $('#summary-time').text(selectedTimeSlot ? formatTime(selectedTimeSlot.time) : '-');
        $('#summary-guests').text($('#party_size').val() + ' guests');
        $('#summary-name').text($('#customer_name').val());
        $('#summary-email').text($('#customer_email').val());
        $('#summary-phone').text($('#customer_phone').val());
        
        // Update coupon information
        if (appliedCoupon) {
            $('#yrr-coupon-applied').show();
            $('#yrr-discount-details').html(
                `<strong>${appliedCoupon.coupon_name}</strong><br>
                 Original: ${appliedCoupon.currency_symbol}${appliedCoupon.original_price.toFixed(2)}<br>
                 Discount: -${appliedCoupon.currency_symbol}${appliedCoupon.discount_amount.toFixed(2)}<br>
                 <strong>Final Price: ${appliedCoupon.currency_symbol}${appliedCoupon.final_price.toFixed(2)}</strong>`
            );
        } else {
            $('#yrr-coupon-applied').hide();
        }
    }
    
    function handleFormSubmission(e) {
        e.preventDefault();
        
        if (!validateCurrentStep()) {
            return;
        }
        
        // Show loading state
        const submitBtn = $('.yrr-submit-btn');
        submitBtn.addClass('loading').prop('disabled', true);
        submitBtn.find('.yrr-btn-text').hide();
        submitBtn.find('.yrr-btn-spinner').show();
        
        // Prepare form data
        const formData = $(this).serialize();
        
        $.ajax({
            url: yrr_ajax.ajax_url,
            type: 'POST',
            data: formData + '&action=yrr_create_reservation&nonce=' + yrr_ajax.create_reservation_nonce,
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.reservation_code);
                } else {
                    showError(response.data);
                }
            },
            error: function() {
                showError('Connection error. Please try again.');
            },
            complete: function() {
                // Reset button state
                submitBtn.removeClass('loading').prop('disabled', false);
                submitBtn.find('.yrr-btn-text').show();
                submitBtn.find('.yrr-btn-spinner').hide();
            }
        });
    }
    
    function showSuccess(reservationCode) {
        $('.yrr-booking-form-wrapper').hide();
        $('#confirmation-code').text(reservationCode);
        $('#yrr-booking-success').fadeIn();
    }
    
    function showError(message) {
        $('.yrr-booking-form-wrapper').hide();
        $('#error-message').text(message);
        $('#yrr-booking-error').fadeIn();
    }
    
    function showNotification(message, type) {
        // Create notification element
        const notification = $(`
            <div class="yrr-notification yrr-notification-${type}">
                ${message}
            </div>
        `);
        
        // Add to page
        $('body').append(notification);
        
        // Show and hide after delay
        setTimeout(() => notification.addClass('show'), 100);
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    function showCouponMessage(message, type) {
        $('#yrr-coupon-message')
            .removeClass('success error')
            .addClass(type)
            .html(message)
            .fadeIn();
    }
    
    // Helper functions
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    }
    
    function formatTime(timeString) {
        const [hours, minutes] = timeString.split(':');
        const date = new Date();
        date.setHours(hours, minutes);
        return date.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit', 
            hour12: true 
        });
    }
});

// ✅ NOTIFICATION STYLES (dynamically added)
if (!document.getElementById('yrr-notification-styles')) {
    const styles = `
        <style id="yrr-notification-styles">
        .yrr-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            transform: translateX(400px);
            transition: all 0.3s ease;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .yrr-notification.show {
            transform: translateX(0);
        }
        
        .yrr-notification-success {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
        }
        
        .yrr-notification-error {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        
        @media (max-width: 480px) {
            .yrr-notification {
                left: 10px;
                right: 10px;
                top: 10px;
                transform: translateY(-100px);
            }
            
            .yrr-notification.show {
                transform: translateY(0);
            }
        }
        </style>
    `;
    document.head.insertAdjacentHTML('beforeend', styles);
}
