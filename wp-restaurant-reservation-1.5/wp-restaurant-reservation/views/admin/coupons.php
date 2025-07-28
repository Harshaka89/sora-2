<?php
if (!defined('ABSPATH')) exit;

// Helper function for safe property access
function yrr_get_property_coupon($object, $property, $default = '') {
    return (property_exists($object, $property) && !empty($object->$property)) ? $object->$property : $default;
}
?>

<div class="wrap">
    <div style="max-width: 1400px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #ffc107;">
            <h1 style="font-size: 2.5rem; color: #2c3e50; margin: 0;">🎫 Discount Coupons Management</h1>
            <p style="color: #6c757d; margin: 10px 0 0 0;">Create and manage discount coupons for customer reservations</p>
        </div>
        
        <!-- Success Messages -->
        <?php if (isset($_GET['message'])): ?>
            <div style="padding: 15px; margin: 20px 0; border-radius: 8px; border: 2px solid; <?php
                switch($_GET['message']) {
                    case 'coupon_created':
                        echo 'background: #d4edda; color: #155724; border-color: #28a745;';
                        $msg = '✅ Coupon created successfully!';
                        break;
                    case 'coupon_updated':
                        echo 'background: #cce7ff; color: #004085; border-color: #007cba;';
                        $msg = '✅ Coupon updated successfully!';
                        break;
                    case 'coupon_deleted':
                        echo 'background: #f8d7da; color: #721c24; border-color: #dc3545;';
                        $msg = '🗑️ Coupon deleted successfully!';
                        break;
                    default:
                        echo 'background: #f8d7da; color: #721c24; border-color: #dc3545;';
                        $msg = '❌ An error occurred.';
                }
            ?>">
                <h4 style="margin: 0;"><?php echo $msg; ?></h4>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: white; padding: 25px; border-radius: 15px; text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 10px;">🎫</div>
                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 5px;"><?php echo intval($statistics['total'] ?? 0); ?></div>
                <div style="font-size: 1.1rem; opacity: 0.9;">Total Coupons</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 25px; border-radius: 15px; text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 10px;">✅</div>
                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 5px;"><?php echo intval($statistics['active'] ?? 0); ?></div>
                <div style="font-size: 1.1rem; opacity: 0.9;">Active Coupons</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; padding: 25px; border-radius: 15px; text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 10px;">📊</div>
                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 5px;"><?php echo intval($statistics['used'] ?? 0); ?></div>
                <div style="font-size: 1.1rem; opacity: 0.9;">Times Used</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 25px; border-radius: 15px; text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 10px;">⏰</div>
                <div style="font-size: 2.5rem; font-weight: bold; margin-bottom: 5px;"><?php echo intval($statistics['expired'] ?? 0); ?></div>
                <div style="font-size: 1.1rem; opacity: 0.9;">Expired</div>
            </div>
        </div>
        
        <!-- Create New Coupon Form -->
        <div style="background: #f8f9fa; padding: 30px; border-radius: 15px; margin-bottom: 30px; border: 3px solid #e9ecef;">
            <h3 style="margin: 0 0 25px 0; color: #2c3e50;">➕ Create New Discount Coupon</h3>
            
            <form method="post" action="">
                <?php wp_nonce_field('yrr_coupon_action', 'coupon_nonce'); ?>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">🎫 Coupon Code *</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" name="coupon_code" id="coupon_code" required maxlength="50"
                                   style="flex: 1; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-family: monospace; text-transform: uppercase;">
                            <button type="button" onclick="generateCouponCode()" 
                                    style="background: #007cba; color: white; border: none; padding: 12px 16px; border-radius: 8px; cursor: pointer;">
                                🎲 Generate
                            </button>
                        </div>
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Unique code customers will use</small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">📝 Coupon Name *</label>
                        <input type="text" name="coupon_name" required maxlength="100"
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Descriptive name for internal use</small>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">💰 Discount Type *</label>
                        <select name="discount_type" id="discount_type" onchange="updateDiscountLabel()" required
                                style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                            <option value="percentage">Percentage Discount (%)</option>
                            <option value="fixed">Fixed Amount Discount ($)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">
                            <span id="discount_label">💸 Discount Percentage *</span>
                        </label>
                        <input type="number" name="discount_value" min="0" step="0.01" required
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; text-align: center; font-size: 1.1rem; box-sizing: border-box;">
                        <small style="color: #6c757d; display: block; margin-top: 5px;">
                            <span id="discount_help">Enter percentage (e.g., 20 for 20%)</span>
                        </small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">📊 Minimum Order Amount</label>
                        <input type="number" name="min_order_amount" min="0" step="0.01" value="0"
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; text-align: center; font-size: 1.1rem; box-sizing: border-box;">
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Minimum amount required to use coupon</small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">🔒 Maximum Discount</label>
                        <input type="number" name="max_discount_amount" min="0" step="0.01"
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; text-align: center; font-size: 1.1rem; box-sizing: border-box;">
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Maximum discount amount (optional)</small>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">🔢 Usage Limit</label>
                        <input type="number" name="usage_limit" min="1"
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; text-align: center; font-size: 1.1rem; box-sizing: border-box;">
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Total number of times this coupon can be used (optional)</small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">⏰ Valid Until</label>
                        <input type="datetime-local" name="valid_until"
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Expiry date and time (optional)</small>
                    </div>
                </div>
                
                <div style="text-align: center; padding-top: 20px; border-top: 2px solid #e9ecef;">
                    <button type="submit" name="create_coupon" value="1"
                            style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: white; border: none; padding: 15px 40px; border-radius: 10px; font-size: 1.2rem; font-weight: bold; cursor: pointer;">
                        🎫 Create Discount Coupon
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Existing Coupons List -->
        <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px;">
                <h3 style="margin: 0; font-size: 1.8rem;">📋 All Discount Coupons</h3>
            </div>
            
            <div style="padding: 20px;">
                <?php if (!empty($coupons) && is_array($coupons)): ?>
                    
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                    <th style="padding: 15px; text-align: left; font-weight: bold; color: #2c3e50;">Coupon Code</th>
                                    <th style="padding: 15px; text-align: left; font-weight: bold; color: #2c3e50;">Name</th>
                                    <th style="padding: 15px; text-align: center; font-weight: bold; color: #2c3e50;">Discount</th>
                                    <th style="padding: 15px; text-align: center; font-weight: bold; color: #2c3e50;">Usage</th>
                                    <th style="padding: 15px; text-align: center; font-weight: bold; color: #2c3e50;">Status</th>
                                    <th style="padding: 15px; text-align: center; font-weight: bold; color: #2c3e50;">Valid Until</th>
                                    <th style="padding: 15px; text-align: center; font-weight: bold; color: #2c3e50;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coupons as $index => $coupon): ?>
                                    <?php if (!is_object($coupon)) continue; ?>
                                    
                                    <tr style="<?php echo $index % 2 == 0 ? 'background: #f8f9fa;' : 'background: white;'; ?> border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 15px; font-family: monospace; font-weight: bold; font-size: 1.1rem; color: #007cba;">
                                            <?php echo esc_html(yrr_get_property_coupon($coupon, 'coupon_code', 'N/A')); ?>
                                        </td>
                                        
                                        <td style="padding: 15px;">
                                            <div style="font-weight: bold; margin-bottom: 5px;">
                                                <?php echo esc_html(yrr_get_property_coupon($coupon, 'coupon_name', 'Unnamed')); ?>
                                            </div>
                                            <?php 
                                            $min_order = floatval(yrr_get_property_coupon($coupon, 'min_order_amount', 0));
                                            if ($min_order > 0): ?>
                                                <small style="color: #6c757d;">Min: $<?php echo number_format($min_order, 2); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td style="padding: 15px; text-align: center;">
                                            <?php 
                                            $discount_type = yrr_get_property_coupon($coupon, 'discount_type', 'percentage');
                                            $discount_value = floatval(yrr_get_property_coupon($coupon, 'discount_value', 0));
                                            
                                            if ($discount_type === 'percentage') {
                                                echo '<span style="background: #28a745; color: white; padding: 5px 10px; border-radius: 15px; font-weight: bold;">' . $discount_value . '%</span>';
                                            } else {
                                                echo '<span style="background: #007cba; color: white; padding: 5px 10px; border-radius: 15px; font-weight: bold;">$' . number_format($discount_value, 2) . '</span>';
                                            }
                                            ?>
                                        </td>
                                        
                                        <td style="padding: 15px; text-align: center;">
                                            <?php 
                                            $usage_count = intval(yrr_get_property_coupon($coupon, 'usage_count', 0));
                                            $usage_limit = intval(yrr_get_property_coupon($coupon, 'usage_limit', 0));
                                            
                                            echo '<span style="font-weight: bold;">' . $usage_count . '</span>';
                                            if ($usage_limit > 0) {
                                                echo ' / ' . $usage_limit;
                                            }
                                            ?>
                                        </td>
                                        
                                        <td style="padding: 15px; text-align: center;">
                                            <?php 
                                            $is_active = intval(yrr_get_property_coupon($coupon, 'is_active', 1));
                                            $valid_until = yrr_get_property_coupon($coupon, 'valid_until');
                                            $is_expired = $valid_until && strtotime($valid_until) < time();
                                            
                                            if (!$is_active) {
                                                echo '<span style="background: #6c757d; color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: bold;">INACTIVE</span>';
                                            } elseif ($is_expired) {
                                                echo '<span style="background: #dc3545; color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: bold;">EXPIRED</span>';
                                            } else {
                                                echo '<span style="background: #28a745; color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: bold;">ACTIVE</span>';
                                            }
                                            ?>
                                        </td>
                                        
                                        <td style="padding: 15px; text-align: center; font-size: 0.9rem;">
                                            <?php 
                                            if ($valid_until) {
                                                echo date('M j, Y', strtotime($valid_until));
                                            } else {
                                                echo '<span style="color: #28a745; font-weight: bold;">No Expiry</span>';
                                            }
                                            ?>
                                        </td>
                                        
                                        <td style="padding: 15px; text-align: center;">
                                            <div style="display: flex; gap: 5px; justify-content: center; flex-wrap: wrap;">
                                                <button onclick="editCoupon(<?php echo htmlspecialchars(json_encode($coupon)); ?>)" 
                                                        style="background: #17a2b8; color: white; border: none; padding: 6px 10px; border-radius: 5px; font-size: 0.8rem; font-weight: bold; cursor: pointer;">
                                                    ✏️ Edit
                                                </button>
                                                
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=yrr-coupons&delete_coupon=' . yrr_get_property_coupon($coupon, 'id')), 'yrr_coupon_action'); ?>" 
                                                   onclick="return confirm('Delete this coupon permanently?')" 
                                                   style="background: #dc3545; color: white; padding: 6px 10px; text-decoration: none; border-radius: 5px; font-size: 0.8rem; font-weight: bold;">
                                                    🗑️ Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php else: ?>
                    
                    <!-- No Coupons State -->
                    <div style="text-align: center; padding: 60px 20px; color: #6c757d;">
                        <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.3;">🎫</div>
                        <h3 style="margin: 0 0 15px 0;">No Discount Coupons Created</h3>
                        <p>Create your first discount coupon to start offering promotions to customers.</p>
                    </div>
                    
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Navigation -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="<?php echo admin_url('admin.php?page=yenolx-reservations'); ?>" 
               style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold; margin-right: 15px;">
                📊 Dashboard
            </a>
            <a href="<?php echo admin_url('admin.php?page=yrr-settings'); ?>" 
               style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold;">
                ⚙️ Settings
            </a>
        </div>
    </div>
</div>

<!-- Edit Coupon Modal -->
<div id="editCouponModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
            <h3 style="margin: 0;">✏️ Edit Discount Coupon</h3>
            <button onclick="closeCouponModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6c757d;">×</button>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('yrr_coupon_action', 'coupon_nonce'); ?>
            <input type="hidden" id="edit_coupon_id" name="coupon_id">
            <input type="hidden" name="update_coupon" value="1">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">🎫 Coupon Code</label>
                    <input type="text" id="edit_coupon_code" disabled
                           style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; background: #f8f9fa; font-family: monospace; box-sizing: border-box;">
                    <small style="color: #6c757d; display: block; margin-top: 5px;">Coupon code cannot be changed</small>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">📝 Coupon Name *</label>
                    <input type="text" id="edit_coupon_name" name="coupon_name" required
                           style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">💰 Discount Type</label>
                    <select id="edit_discount_type" name="discount_type" required
                            style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Amount ($)</option>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">💸 Discount Value *</label>
                    <input type="number" id="edit_discount_value" name="discount_value" min="0" step="0.01" required
                           style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; text-align: center; box-sizing: border-box;">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">📊 Min Order Amount</label>
                    <input type="number" id="edit_min_order_amount" name="min_order_amount" min="0" step="0.01"
                           style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; text-align: center; box-sizing: border-box;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">🔒 Max Discount</label>
                    <input type="number" id="edit_max_discount_amount" name="max_discount_amount" min="0" step="0.01"
                           style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; text-align: center; box-sizing: border-box;">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">🔢 Usage Limit</label>
                    <input type="number" id="edit_usage_limit" name="usage_limit" min="1"
                           style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; text-align: center; box-sizing: border-box;">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">⏰ Valid Until</label>
                    <input type="datetime-local" id="edit_valid_until" name="valid_until"
                           style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 10px; font-weight: bold; cursor: pointer;">
                    <input type="checkbox" id="edit_is_active" name="is_active" style="transform: scale(1.5);">
                    <span>✅ Coupon is Active</span>
                </label>
            </div>
            
            <div style="text-align: right; padding-top: 20px; border-top: 2px solid #e9ecef;">
                <button type="button" onclick="closeCouponModal()" 
                        style="background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 8px; margin-right: 15px; cursor: pointer; font-weight: bold;">
                    Cancel
                </button>
                <button type="submit" 
                        style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: bold;">
                    💾 Update Coupon
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function generateCouponCode() {
    const prefixes = ['SAVE', 'DISC', 'OFF', 'DEAL', 'PROMO'];
    const prefix = prefixes[Math.floor(Math.random() * prefixes.length)];
    const number = Math.floor(Math.random() * 90) + 10;
    document.getElementById('coupon_code').value = prefix + number;
}

function updateDiscountLabel() {
    const discountType = document.getElementById('discount_type').value;
    const label = document.getElementById('discount_label');
    const help = document.getElementById('discount_help');
    
    if (discountType === 'percentage') {
        label.textContent = '💸 Discount Percentage *';
        help.textContent = 'Enter percentage (e.g., 20 for 20%)';
    } else {
        label.textContent = '💸 Discount Amount *';
        help.textContent = 'Enter fixed dollar amount (e.g., 10 for $10)';
    }
}

function editCoupon(coupon) {
    document.getElementById('edit_coupon_id').value = coupon.id || '';
    document.getElementById('edit_coupon_code').value = coupon.coupon_code || '';
    document.getElementById('edit_coupon_name').value = coupon.coupon_name || '';
    document.getElementById('edit_discount_type').value = coupon.discount_type || 'percentage';
    document.getElementById('edit_discount_value').value = coupon.discount_value || '';
    document.getElementById('edit_min_order_amount').value = coupon.min_order_amount || '';
    document.getElementById('edit_max_discount_amount').value = coupon.max_discount_amount || '';
    document.getElementById('edit_usage_limit').value = coupon.usage_limit || '';
    document.getElementById('edit_is_active').checked = coupon.is_active == '1';
    
    if (coupon.valid_until) {
        const date = new Date(coupon.valid_until);
        const localDateTime = new Date(date.getTime() - (date.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
        document.getElementById('edit_valid_until').value = localDateTime;
    }
    
    document.getElementById('editCouponModal').style.display = 'flex';
}

function closeCouponModal() {
    document.getElementById('editCouponModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('editCouponModal').addEventListener('click', function(e) {
    if (e.target === this) closeCouponModal();
});
</script>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr))"] {
        grid-template-columns: 1fr !important;
    }
    
    div[style*="grid-template-columns: 1fr 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    
    table {
        font-size: 0.8rem;
    }
}

button:hover, a[style*="background:"
