<?php
/**
 * Tables Model - Yenolx Restaurant Reservation System v1.5.1
 * Manages restaurant tables with reservation connections
 * Protected against class redeclaration
 */

if (!defined('ABSPATH')) exit;

// Prevent duplicate class declaration
if (class_exists('YRR_Tables_Model')) {
    return;
}

class YRR_Tables_Model {
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'yrr_tables';
    }
    
    /**
     * ✅ GET ALL TABLES
     */
    public function get_all() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY table_number ASC"
        );
    }
    
    /**
     * ✅ GET SINGLE TABLE BY ID
     */
    public function get_by_id($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d", $id
        ));
    }
    
    /**
     * ✅ CREATE NEW TABLE
     */
    public function create($data) {
        $insert_data = array(
            'table_number' => sanitize_text_field($data['table_number']),
            'capacity' => intval($data['capacity']),
            'location' => sanitize_text_field($data['location'] ?? ''),
            'table_type' => sanitize_text_field($data['table_type'] ?? 'standard'),
            'status' => 'available',
            'position_x' => intval($data['position_x'] ?? 0),
            'position_y' => intval($data['position_y'] ?? 0)
        );
        
        $result = $this->wpdb->insert($this->table_name, $insert_data);
        return $result ? $this->wpdb->insert_id : false;
    }
    
    /**
     * ✅ UPDATE EXISTING TABLE
     */
    public function update($id, $data) {
        $update_data = array();
        
        if (isset($data['table_number'])) $update_data['table_number'] = sanitize_text_field($data['table_number']);
        if (isset($data['capacity'])) $update_data['capacity'] = intval($data['capacity']);
        if (isset($data['location'])) $update_data['location'] = sanitize_text_field($data['location']);
        if (isset($data['table_type'])) $update_data['table_type'] = sanitize_text_field($data['table_type']);
        if (isset($data['status'])) $update_data['status'] = sanitize_text_field($data['status']);
        if (isset($data['position_x'])) $update_data['position_x'] = intval($data['position_x']);
        if (isset($data['position_y'])) $update_data['position_y'] = intval($data['position_y']);
        
        return $this->wpdb->update(
            $this->table_name, 
            $update_data, 
            array('id' => $id)
        );
    }
    
    /**
     * ✅ DELETE TABLE
     */
    public function delete($id) {
        // Check if table is assigned to any active reservations
        $active_reservations = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}yrr_reservations 
             WHERE table_id = %d AND status IN ('confirmed', 'pending')",
            $id
        ));
        
        if ($active_reservations > 0) {
            return false; // Cannot delete table with active reservations
        }
        
        return $this->wpdb->delete($this->table_name, array('id' => $id));
    }
    
    /**
     * ✅ GET AVAILABLE TABLES FOR SPECIFIC DATE/TIME SLOT
     * This is key for Admin to assign tables to reservations
     */
    public function get_available_for_slot($date, $time_slot_id, $party_size, $exclude_reservation_id = null) {
        $where_clause = "r.reservation_date = %s AND r.time_slot_id = %d AND r.status IN ('confirmed', 'pending')";
        $params = array($date, $time_slot_id);
        
        if ($exclude_reservation_id) {
            $where_clause .= " AND r.id != %d";
            $params[] = $exclude_reservation_id;
        }
        
        return $this->wpdb->get_results($this->wpdb->prepare("
            SELECT t.*, 
                   (CASE WHEN r.table_id IS NOT NULL THEN 'occupied' ELSE 'available' END) as availability_status
            FROM {$this->table_name} t
            LEFT JOIN {$this->wpdb->prefix}yrr_reservations r ON t.id = r.table_id AND {$where_clause}
            WHERE t.status = 'available' 
            AND t.capacity >= %d
            AND r.table_id IS NULL
            ORDER BY t.capacity ASC, t.table_number ASC
        ", array_merge($params, array($party_size))));
    }
    
    /**
     * ✅ GET AVAILABLE TABLES FOR SPECIFIC TIME (without time slot ID)
     */
    public function get_available_for_time($date, $time, $party_size, $exclude_reservation_id = null) {
        $where_clause = "r.reservation_date = %s AND r.reservation_time = %s AND r.status IN ('confirmed', 'pending')";
        $params = array($date, $time);
        
        if ($exclude_reservation_id) {
            $where_clause .= " AND r.id != %d";
            $params[] = $exclude_reservation_id;
        }
        
        return $this->wpdb->get_results($this->wpdb->prepare("
            SELECT t.*, 
                   (CASE WHEN r.table_id IS NOT NULL THEN 'occupied' ELSE 'available' END) as availability_status
            FROM {$this->table_name} t
            LEFT JOIN {$this->wpdb->prefix}yrr_reservations r ON t.id = r.table_id AND {$where_clause}
            WHERE t.status = 'available' 
            AND t.capacity >= %d
            AND r.table_id IS NULL
            ORDER BY t.capacity ASC, t.table_number ASC
        ", array_merge($params, array($party_size))));
    }
    
    /**
     * ✅ GET TABLES BY CAPACITY RANGE
     */
    public function get_by_capacity($min_capacity, $max_capacity = null) {
        $where_clause = "capacity >= %d";
        $params = array($min_capacity);
        
        if ($max_capacity) {
            $where_clause .= " AND capacity <= %d";
            $params[] = $max_capacity;
        }
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$where_clause} AND status = 'available' ORDER BY capacity ASC",
            $params
        ));
    }
    
    /**
     * ✅ GET TABLES BY TYPE
     */
    public function get_by_type($table_type) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE table_type = %s AND status = 'available' ORDER BY table_number ASC",
            $table_type
        ));
    }
    
    /**
     * ✅ GET TABLES BY LOCATION
     */
    public function get_by_location($location) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE location = %s AND status = 'available' ORDER BY table_number ASC",
            $location
        ));
    }
    
    /**
     * ✅ GET TABLE OCCUPANCY FOR DATE
     */
    public function get_occupancy_for_date($date) {
        return $this->wpdb->get_results($this->wpdb->prepare("
            SELECT t.*, 
                   r.reservation_code,
                   r.customer_name,
                   r.reservation_time,
                   r.party_size,
                   r.status as reservation_status,
                   ts.slot_name as time_slot_name
            FROM {$this->table_name} t
            LEFT JOIN {$this->wpdb->prefix}yrr_reservations r ON t.id = r.table_id 
                AND r.reservation_date = %s 
                AND r.status IN ('confirmed', 'pending')
            LEFT JOIN {$this->wpdb->prefix}yrr_time_slots ts ON r.time_slot_id = ts.id
            ORDER BY t.table_number ASC, r.reservation_time ASC
        ", $date));
    }
    
    /**
     * ✅ GET TABLE STATISTICS
     */
    public function get_statistics() {
        $stats = array();
        
        // Total tables
        $stats['total_tables'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        // Available tables
        $stats['available_tables'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'available'");
        
        // Tables by capacity
        $stats['capacity_breakdown'] = $this->wpdb->get_results("
            SELECT capacity, COUNT(*) as count 
            FROM {$this->table_name} 
            WHERE status = 'available' 
            GROUP BY capacity 
            ORDER BY capacity ASC
        ");
        
        // Tables by type
        $stats['type_breakdown'] = $this->wpdb->get_results("
            SELECT table_type, COUNT(*) as count 
            FROM {$this->table_name} 
            WHERE status = 'available' 
            GROUP BY table_type 
            ORDER BY table_type ASC
        ");
        
        // Tables by location
        $stats['location_breakdown'] = $this->wpdb->get_results("
            SELECT location, COUNT(*) as count 
            FROM {$this->table_name} 
            WHERE status = 'available' 
            GROUP BY location 
            ORDER BY location ASC
        ");
        
        return $stats;
    }
    
    /**
     * ✅ CHECK IF TABLE NUMBER EXISTS
     */
    public function table_number_exists($table_number, $exclude_id = null) {
        $where_clause = "table_number = %s";
        $params = array($table_number);
        
        if ($exclude_id) {
            $where_clause .= " AND id != %d";
            $params[] = $exclude_id;
        }
        
        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}",
            $params
        ));
        
        return $count > 0;
    }
    
    /**
     * ✅ ASSIGN TABLE TO RESERVATION
     */
    public function assign_to_reservation($table_id, $reservation_id) {
        // Check if table is available
        $table = $this->get_by_id($table_id);
        if (!$table || $table->status !== 'available') {
            return false;
        }
        
        // Update reservation with table assignment
        return $this->wpdb->update(
            $this->wpdb->prefix . 'yrr_reservations',
            array('table_id' => $table_id),
            array('id' => $reservation_id)
        );
    }
    
    /**
     * ✅ UNASSIGN TABLE FROM RESERVATION
     */
    public function unassign_from_reservation($reservation_id) {
        return $this->wpdb->update(
            $this->wpdb->prefix . 'yrr_reservations',
            array('table_id' => null),
            array('id' => $reservation_id)
        );
    }
    
    /**
     * ✅ GET OPTIMAL TABLE FOR PARTY SIZE
     * Returns the smallest available table that can accommodate the party
     */
    public function get_optimal_table($party_size, $date, $time_slot_id = null, $time = null) {
        if ($time_slot_id) {
            $available_tables = $this->get_available_for_slot($date, $time_slot_id, $party_size);
        } else {
            $available_tables = $this->get_available_for_time($date, $time, $party_size);
        }
        
        if (empty($available_tables)) {
            return null;
        }
        
        // Return the smallest suitable table (first in the ordered result)
        return $available_tables[0];
    }
    
    /**
     * ✅ BULK UPDATE TABLE STATUS
     */
    public function bulk_update_status($table_ids, $status) {
        if (empty($table_ids) || !in_array($status, array('available', 'maintenance', 'reserved'))) {
            return false;
        }
        
        $table_ids_str = implode(',', array_map('intval', $table_ids));
        
        return $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->table_name} SET status = %s WHERE id IN ({$table_ids_str})",
            $status
        ));
    }
    
    /**
     * ✅ GET AVAILABLE TABLE TYPES
     */
    public function get_available_types() {
        $results = $this->wpdb->get_results("
            SELECT DISTINCT table_type 
            FROM {$this->table_name} 
            WHERE status = 'available' 
            ORDER BY table_type ASC
        ");
        
        $types = array();
        foreach ($results as $result) {
            $types[] = $result->table_type;
        }
        
        return $types;
    }
    
    /**
     * ✅ GET AVAILABLE LOCATIONS
     */
    public function get_available_locations() {
        $results = $this->wpdb->get_results("
            SELECT DISTINCT location 
            FROM {$this->table_name} 
            WHERE status = 'available' AND location != '' 
            ORDER BY location ASC
        ");
        
        $locations = array();
        foreach ($results as $result) {
            $locations[] = $result->location;
        }
        
        return $locations;
    }
    
    /**
     * ✅ VALIDATE TABLE DATA
     */
    public function validate_table_data($data, $exclude_id = null) {
        $errors = array();
        
        // Validate table number
        if (empty($data['table_number'])) {
            $errors[] = 'Table number is required';
        } elseif ($this->table_number_exists($data['table_number'], $exclude_id)) {
            $errors[] = 'Table number already exists';
        }
        
        // Validate capacity
        if (!isset($data['capacity']) || intval($data['capacity']) < 1) {
            $errors[] = 'Capacity must be at least 1';
        } elseif (intval($data['capacity']) > 50) {
            $errors[] = 'Capacity cannot exceed 50';
        }
        
        // Validate table type
        $valid_types = array('standard', 'vip', 'outdoor', 'private', 'bar', 'intimate', 'family', 'romantic');
        if (isset($data['table_type']) && !in_array($data['table_type'], $valid_types)) {
            $errors[] = 'Invalid table type';
        }
        
        return $errors;
    }
}
