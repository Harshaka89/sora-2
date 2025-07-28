<?php
// admin/partials/tables-management.php
class RRS_Tables_Management {
    
    public function tables_management_page() {
        $tables = $this->get_all_tables();
        $locations = $this->get_locations();
        
        if (isset($_POST['save_table'])) {
            $this->handle_save_table();
        }
        
        include RRS_PLUGIN_DIR . 'admin/partials/tables-management.php';
    }
    
    private function get_all_tables() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}rrs_tables 
            ORDER BY location_id, name
        ");
    }
    
    public function handle_save_table() {
        if (!wp_verify_nonce($_POST['table_nonce'], 'save_table')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        
        $table_data = array(
            'name' => sanitize_text_field($_POST['table_name']),
            'capacity_min' => intval($_POST['capacity_min']),
            'capacity_max' => intval($_POST['capacity_max']),
            'x_position' => intval($_POST['x_position']),
            'y_position' => intval($_POST['y_position']),
            'table_shape' => sanitize_text_field($_POST['table_shape']),
            'location_id' => intval($_POST['location_id']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'can_combine' => isset($_POST['can_combine']) ? 1 : 0
        );
        
        if (isset($_POST['table_id']) && !empty($_POST['table_id'])) {
            // Update existing table
            $table_id = intval($_POST['table_id']);
            $result = $wpdb->update(
                $wpdb->prefix . 'rrs_tables',
                $table_data,
                array('id' => $table_id)
            );
            
            $message = $result !== false ? __('Table updated successfully!', 'restaurant-reservation') : __('Failed to update table.', 'restaurant-reservation');
        } else {
            // Create new table
            $result = $wpdb->insert($wpdb->prefix . 'rrs_tables', $table_data);
            $message = $result !== false ? __('Table created successfully!', 'restaurant-reservation') : __('Failed to create table.', 'restaurant-reservation');
        }
        
        add_action('admin_notices', function() use ($message, $result) {
            $class = $result !== false ? 'notice-success' : 'notice-error';
            echo "<div class='notice {$class} is-dismissible'><p>{$message}</p></div>";
        });
    }
}
?>

<div class="wrap rrs-admin-wrap">
    <h1 class="wp-heading-inline"><?php _e('Table Management', 'restaurant-reservation'); ?></h1>
    <button class="page-title-action" id="add-new-table"><?php _e('Add New Table', 'restaurant-reservation'); ?></button>
    
    <!-- Tables List -->
    <div class="rrs-tables-container">
        <div class="rrs-tables-grid">
            <?php if (!empty($tables)): ?>
                <?php foreach ($tables as $table): ?>
                <div class="rrs-table-card" data-table-id="<?php echo $table->id; ?>">
                    <div class="rrs-table-header">
                        <h3><?php echo esc_html($table->name); ?></h3>
                        <div class="rrs-table-status">
                            <?php if ($table->is_active): ?>
                                <span class="status-active"><?php _e('Active', 'restaurant-reservation'); ?></span>
                            <?php else: ?>
                                <span class="status-inactive"><?php _e('Inactive', 'restaurant-reservation'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="rrs-table-details">
                        <div class="rrs-table-capacity">
                            <strong><?php _e('Capacity:', 'restaurant-reservation'); ?></strong>
                            <?php echo $table->capacity_min; ?>-<?php echo $table->capacity_max; ?> guests
                        </div>
                        
                        <div class="rrs-table-shape">
                            <strong><?php _e('Shape:', 'restaurant-reservation'); ?></strong>
                            <?php echo ucfirst($table->table_shape); ?>
                        </div>
                        
                        <div class="rrs-table-position">
                            <strong><?php _e('Position:', 'restaurant-reservation'); ?></strong>
                            X: <?php echo $table->x_position; ?>, Y: <?php echo $table->y_position; ?>
                        </div>
                        
                        <?php if ($table->can_combine): ?>
                        <div class="rrs-table-combine">
                            <span class="dashicons dashicons-networking"></span>
                            <?php _e('Can combine with other tables', 'restaurant-reservation'); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="rrs-table-actions">
                        <button class="button edit-table" data-table-id="<?php echo $table->id; ?>">
                            <?php _e('Edit', 'restaurant-reservation'); ?>
                        </button>
                        <button class="button view-reservations" data-table-id="<?php echo $table->id; ?>">
                            <?php _e('Reservations', 'restaurant-reservation'); ?>
                        </button>
                        <button class="button button-link-delete delete-table" data-table-id="<?php echo $table->id; ?>">
                            <?php _e('Delete', 'restaurant-reservation'); ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="rrs-no-tables">
                    <p><?php _e('No tables found. Create your first table to get started.', 'restaurant-reservation'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bulk Actions -->
    <div class="rrs-bulk-actions">
        <select id="bulk-action-selector">
            <option value=""><?php _e('Bulk Actions', 'restaurant-reservation'); ?></option>
            <option value="activate"><?php _e('Activate', 'restaurant-reservation'); ?></option>
            <option value="deactivate"><?php _e('Deactivate', 'restaurant-reservation'); ?></option>
            <option value="delete"><?php _e('Delete', 'restaurant-reservation'); ?></option>
        </select>
        <button class="button" id="apply-bulk-action"><?php _e('Apply', 'restaurant-reservation'); ?></button>
    </div>
</div>

<!-- Table Editor Modal -->
<div id="table-editor-modal" class="rrs-modal" style="display: none;">
    <div class="rrs-modal-content rrs-modal-large">
        <div class="rrs-modal-header">
            <h3 id="table-editor-title"><?php _e('Add New Table', 'restaurant-reservation'); ?></h3>
            <button class="rrs-modal-close">&times;</button>
        </div>
        
        <form id="table-editor-form" class="rrs-modal-body">
            <?php wp_nonce_field('save_table', 'table_nonce'); ?>
            <input type="hidden" id="table_id" name="table_id">
            
            <div class="rrs-form-row">
                <div class="rrs-form-group">
                    <label for="table_name"><?php _e('Table Name', 'restaurant-reservation'); ?></label>
                    <input type="text" id="table_name" name="table_name" required>
                </div>
                
                <div class="rrs-form-group">
                    <label for="table_shape"><?php _e('Table Shape', 'restaurant-reservation'); ?></label>
                    <select id="table_shape" name="table_shape">
                        <option value="square"><?php _e('Square', 'restaurant-reservation'); ?></option>
                        <option value="rectangle"><?php _e('Rectangle', 'restaurant-reservation'); ?></option>
                        <option value="round"><?php _e('Round', 'restaurant-reservation'); ?></option>
                        <option value="oval"><?php _e('Oval', 'restaurant-reservation'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="rrs-form-row">
                <div class="rrs-form-group">
                    <label for="capacity_min"><?php _e('Minimum Capacity', 'restaurant-reservation'); ?></label>
                    <input type="number" id="capacity_min" name="capacity_min" min="1" max="20" required>
                </div>
                
                <div class="rrs-form-group">
                    <label for="capacity_max"><?php _e('Maximum Capacity', 'restaurant-reservation'); ?></label>
                    <input type="number" id="capacity_max" name="capacity_max" min="1" max="20" required>
                </div>
            </div>
            
            <div class="rrs-form-row">
                <div class="rrs-form-group">
                    <label for="x_position"><?php _e('X Position', 'restaurant-reservation'); ?></label>
                    <input type="number" id="x_position" name="x_position" min="0">
                </div>
                
                <div class="rrs-form-group">
                    <label for="y_position"><?php _e('Y Position', 'restaurant-reservation'); ?></label>
                    <input type="number" id="y_position" name="y_position" min="0">
                </div>
            </div>
            
            <div class="rrs-form-row">
                <div class="rrs-form-group">
                    <label for="location_id"><?php _e('Location', 'restaurant-reservation'); ?></label>
                    <select id="location_id" name="location_id">
                        <option value="1"><?php _e('Main Dining Room', 'restaurant-reservation'); ?></option>
                        <option value="2"><?php _e('Private Dining', 'restaurant-reservation'); ?></option>
                        <option value="3"><?php _e('Outdoor Seating', 'restaurant-reservation'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="rrs-form-row">
                <div class="rrs-form-group">
                    <label class="rrs-checkbox-label">
                        <input type="checkbox" id="is_active" name="is_active" checked>
                        <span class="checkmark"></span>
                        <?php _e('Table is active and available for booking', 'restaurant-reservation'); ?>
                    </label>
                </div>
                
                <div class="rrs-form-group">
                    <label class="rrs-checkbox-label">
                        <input type="checkbox" id="can_combine" name="can_combine">
                        <span class="checkmark"></span>
                        <?php _e('Can be combined with adjacent tables', 'restaurant-reservation'); ?>
                    </label>
                </div>
            </div>
        </form>
        
        <div class="rrs-modal-footer">
            <button type="button" class="button" onclick="rrsCloseModal('table-editor-modal')">
                <?php _e('Cancel', 'restaurant-reservation'); ?>
            </button>
            <button type="submit" form="table-editor-form" class="button button-primary">
                <?php _e('Save Table', 'restaurant-reservation'); ?>
            </button>
        </div>
    </div>
</div>
