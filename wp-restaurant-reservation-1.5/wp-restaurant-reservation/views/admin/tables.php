<?php
if (!defined('ABSPATH')) exit;

// Helper function for safe property access
function yrr_get_property_table($object, $property, $default = '') {
    return (property_exists($object, $property) && !empty($object->$property)) ? $object->$property : $default;
}
?>

<div class="wrap">
    <div style="max-width: 1400px; margin: 20px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #28a745;">
            <h1 style="font-size: 2.5rem; color: #2c3e50; margin: 0;">🍽️ Tables Management</h1>
            <p style="color: #6c757d; margin: 10px 0 0 0;">Manage restaurant tables, capacity, and seating arrangements</p>
        </div>
        
        <!-- Success Messages -->
        <?php if (isset($_GET['message'])): ?>
            <div style="padding: 15px; margin: 20px 0; border-radius: 8px; border: 2px solid; <?php
                switch($_GET['message']) {
                    case 'table_added':
                        echo 'background: #d4edda; color: #155724; border-color: #28a745;';
                        $msg = '✅ Table added successfully!';
                        break;
                    case 'table_updated':
                        echo 'background: #cce7ff; color: #004085; border-color: #007cba;';
                        $msg = '✅ Table updated successfully!';
                        break;
                    case 'table_deleted':
                        echo 'background: #f8d7da; color: #721c24; border-color: #dc3545;';
                        $msg = '🗑️ Table deleted successfully!';
                        break;
                    default:
                        echo 'background: #f8d7da; color: #721c24; border-color: #dc3545;';
                        $msg = '❌ An error occurred.';
                }
            ?>">
                <h4 style="margin: 0;"><?php echo $msg; ?></h4>
            </div>
        <?php endif; ?>
        
        <!-- Add New Table Form -->
        <div style="background: #e8f5e8; padding: 30px; border-radius: 15px; margin-bottom: 30px; border: 3px solid #28a745;">
            <h3 style="margin: 0 0 25px 0; color: #28a745;">➕ Add New Table</h3>
            
            <form method="post" action="">
                <?php wp_nonce_field('yrr_table_action', 'table_nonce'); ?>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">🏷️ Table Number *</label>
                        <input type="text" name="table_number" required maxlength="20" placeholder="e.g., T1, Table-5"
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 1.1rem; box-sizing: border-box;">
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Unique identifier for the table</small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">👥 Capacity *</label>
                        <input type="number" name="capacity" required min="1" max="20" value="4"
                               style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; text-align: center; font-size: 1.3rem; font-weight: bold; box-sizing: border-box;">
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Maximum number of guests</small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">📍 Location</label>
                        <select name="location" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 1.1rem; box-sizing: border-box;">
                            <option value="Center">Center Area</option>
                            <option value="Window">Window Side</option>
                            <option value="Private">Private Section</option>
                            <option value="VIP">VIP Area</option>
                            <option value="Outdoor">Outdoor Seating</option>
                            <option value="Bar">Bar Area</option>
                        </select>
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Table location in restaurant</small>
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50;">🎨 Table Type</label>
                        <select name="table_type" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 1.1rem; box-sizing: border-box;">
                            <option value="standard">Standard Table</option>
                            <option value="booth">Booth Seating</option>
                            <option value="high_top">High Top Table</option>
                            <option value="round">Round Table</option>
                            <option value="square">Square Table</option>
                            <option value="rectangular">Rectangular Table</option>
                        </select>
                        <small style="color: #6c757d; display: block; margin-top: 5px;">Type of table/seating</small>
                    </div>
                </div>
                
                <div style="text-align: center; padding-top: 20px; border-top: 2px solid #28a745;">
                    <button type="submit" name="add_table" value="1"
                            style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 15px 40px; border-radius: 10px; font-size: 1.2rem; font-weight: bold; cursor: pointer;">
                        🍽️ Add Table
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Current Tables List -->
        <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px;">
                <h3 style="margin: 0; font-size: 1.8rem;">📋 Current Tables (<?php echo is_array($tables) ? count($tables) : 0; ?> total)</h3>
            </div>
            
            <div style="padding: 20px;">
                <?php if (!empty($tables) && is_array($tables)): ?>
                    
                    <!-- Tables Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                        <?php foreach ($tables as $table): ?>
                            <?php if (!is_object($table)) continue; ?>
                            
                            <div style="background: white; border: 2px solid #e9ecef; border-radius: 12px; padding: 20px; position: relative; <?php echo yrr_get_property_table($table, 'status') === 'available' ? 'border-color: #28a745;' : 'border-color: #dc3545;'; ?>">
                                
                                <!-- Status Badge -->
                                <div style="position: absolute; top: -10px; right: 15px; background: <?php echo yrr_get_property_table($table, 'status') === 'available' ? '#28a745' : '#dc3545'; ?>; color: white; padding: 5px 12px; border-radius: 15px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase;">
                                    <?php echo esc_html(yrr_get_property_table($table, 'status', 'available')); ?>
                                </div>
                                
                                <!-- Table Info -->
                                <div style="text-align: center; margin-bottom: 15px;">
                                    <h4 style="margin: 0 0 10px 0; font-size: 1.5rem; color: #2c3e50;">
                                        🍽️ <?php echo esc_html(yrr_get_property_table($table, 'table_number', 'Unknown')); ?>
                                    </h4>
                                    <div style="background: #007cba; color: white; padding: 8px 15px; border-radius: 20px; display: inline-block; font-weight: bold; margin-bottom: 10px;">
                                        👥 <?php echo intval(yrr_get_property_table($table, 'capacity', 1)); ?> guests
                                    </div>
                                </div>
                                
                                <!-- Table Details -->
                                <div style="margin-bottom: 15px;">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.9rem;">
                                        <div>
                                            <strong>📍 Location:</strong><br>
                                            <?php echo esc_html(yrr_get_property_table($table, 'location', 'Not set')); ?>
                                        </div>
                                        <div>
                                            <strong>🎨 Type:</strong><br>
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', yrr_get_property_table($table, 'table_type', 'standard')))); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div style="display: flex; gap: 8px; justify-content: center;">
                                    <button onclick="editTable(<?php echo htmlspecialchars(json_encode($table)); ?>)" 
                                            style="background: #17a2b8; color: white; border: none; padding: 8px 12px; border-radius: 5px; font-size: 0.8rem; font-weight: bold; cursor: pointer;">
                                        ✏️ Edit
                                    </button>
                                    
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=yrr-tables&delete_table=' . yrr_get_property_table($table, 'id')), 'yrr_table_action'); ?>" 
                                       onclick="return confirm('Delete this table permanently? This cannot be undone.')" 
                                       style="background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 5px; font-size: 0.8rem; font-weight: bold;">
                                        🗑️ Delete
                                    </a>
                                </div>
                            </div>
                            
                        <?php endforeach; ?>
                    </div>
                    
                <?php else: ?>
                    
                    <!-- No Tables State -->
                    <div style="text-align: center; padding: 60px 20px; color: #6c757d;">
                        <div style="font-size: 4rem; margin-bottom: 20px; opacity: 0.3;">🍽️</div>
                        <h3 style="margin: 0 0 15px 0;">No Tables Created</h3>
                        <p>Add your first table to start managing seating capacity and reservations.</p>
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
            <a href="<?php echo admin_url('admin.php?page=yrr-hours'); ?>" 
               style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold;">
                ⏰ Operating Hours
            </a>
        </div>
    </div>
</div>

<!-- Edit Table Modal -->
<div id="editTableModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e9ecef;">
            <h3 style="margin: 0;">✏️ Edit Table</h3>
            <button onclick="closeTableModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6c757d;">×</button>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('yrr_table_action', 'table_nonce'); ?>
            <input type="hidden" id="edit_table_id" name="table_id">
            <input type="hidden" name="update_table" value="1">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">🏷️ Table Number *</label>
                    <input type="text" id="edit_table_number" name="table_number" required
                           style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">👥 Capacity *</label>
                    <input type="number" id="edit_capacity" name="capacity" min="1" max="20" required
                           style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; text-align: center; box-sizing: border-box;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">📍 Location</label>
                    <select id="edit_location" name="location" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                        <option value="Center">Center Area</option>
                        <option value="Window">Window Side</option>
                        <option value="Private">Private Section</option>
                        <option value="VIP">VIP Area</option>
                        <option value="Outdoor">Outdoor Seating</option>
                        <option value="Bar">Bar Area</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">🎨 Table Type</label>
                    <select id="edit_table_type" name="table_type" style="width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; box-sizing: border-box;">
                        <option value="standard">Standard Table</option>
                        <option value="booth">Booth Seating</option>
                        <option value="high_top">High Top Table</option>
                        <option value="round">Round Table</option>
                        <option value="square">Square Table</option>
                        <option value="rectangular">Rectangular Table</option>
                    </select>
                </div>
            </div>
            
            <div style="text-align: right; padding-top: 20px; border-top: 2px solid #e9ecef;">
                <button type="button" onclick="closeTableModal()" style="background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 8px; margin-right: 15px; cursor: pointer; font-weight: bold;">Cancel</button>
                <button type="submit" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: bold;">💾 Update Table</button>
            </div>
        </form>
    </div>
</div>

<script>
function editTable(table) {
    document.getElementById('edit_table_id').value = table.id || '';
    document.getElementById('edit_table_number').value = table.table_number || '';
    document.getElementById('edit_capacity').value = table.capacity || '4';
    document.getElementById('edit_location').value = table.location || 'Center';
    document.getElementById('edit_table_type').value = table.table_type || 'standard';
    
    document.getElementById('editTableModal').style.display = 'flex';
}

function closeTableModal() {
    document.getElementById('editTableModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('editTableModal').addEventListener('click', function(e) {
    if (e.target === this) closeTableModal();
});
</script>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr))"] {
        grid-template-columns: 1fr !important;
    }
    
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}

button:hover, a[style*="background:"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}
</style>
