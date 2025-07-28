<?php
/**
 * Tables Model with Time Slot Management - v1.5.1
 */

if (!defined('ABSPATH')) exit;

class YRR_Tables_Model {
    private $tables_table;
    private $reservations_table;
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tables_table = $wpdb->prefix . 'yrr_tables';
        $this->reservations_table = $wpdb->prefix . 'yrr_reservations';
    }
    
    public function get_all_tables() {
        return $this->wpdb->get_results("SELECT * FROM {$this->tables_table} ORDER BY table_number");
    }
    
    public function get_available_tables($date, $time, $party_size = 1) {
        // Get tables that can accommodate the party size
        $suitable_tables = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->tables_table} 
             WHERE capacity >= %d AND status = 'available' 
             ORDER BY capacity ASC",
            $party_size
        ));
        
        // Check which tables are not booked at the requested time
        $available_tables = array();
        
        foreach ($suitable_tables as $table) {
            if ($this->is_table_available($table->id, $date, $time)) {
                $available_tables[] = $table;
            }
        }
        
        return $available_tables;
    }
    
    public function is_table_available($table_id, $date, $time) {
        // Check if table is booked within 2 hours of requested time
        $start_time = date('H:i:s', strtotime($time . ' -1 hour'));
        $end_time = date('H:i:s', strtotime($time . ' +1 hour'));
        
        $conflicts = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->reservations_table} 
             WHERE table_id = %d 
             AND reservation_date = %s 
             AND reservation_time BETWEEN %s AND %s 
             AND status IN ('confirmed', 'pending')",
            $table_id, $date, $start_time, $end_time
        ));
        
        return $conflicts == 0;
    }
    
    public function get_table_schedule($table_id, $date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT r.*, t.table_number, t.capacity 
             FROM {$this->reservations_table} r 
             JOIN {$this->tables_table} t ON r.table_id = t.id 
             WHERE r.table_id = %d AND r.reservation_date = %s 
             AND r.status IN ('confirmed', 'pending') 
             ORDER BY r.reservation_time",
            $table_id, $date
        ));
    }
    
    public function get_all_tables_schedule($date) {
        $tables = $this->get_all_tables();
        $schedule = array();
        
        foreach ($tables as $table) {
            $schedule[$table->id] = array(
                'table' => $table,
                'bookings' => $this->get_table_schedule($table->id, $date)
            );
        }
        
        return $schedule;
    }
    
    public function create_table($data) {
        return $this->wpdb->insert($this->tables_table, $data);
    }
    
    public function update_table($id, $data) {
        return $this->wpdb->update($this->tables_table, $data, array('id' => $id));
    }
    
    public function delete_table($id) {
        return $this->wpdb->delete($this->tables_table, array('id' => $id));
    }
    
    public function get_time_slots($table_id = null, $date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        // Generate time slots from 10 AM to 10 PM with 30-minute intervals
        $slots = array();
        $start_time = strtotime('10:00');
        $end_time = strtotime('22:00');
        
        for ($time = $start_time; $time <= $end_time; $time += 1800) { // 30 minutes = 1800 seconds
            $time_slot = date('H:i', $time);
            $formatted_time = date('g:i A', $time);
            
            $slot_data = array(
                'time' => $time_slot,
                'formatted_time' => $formatted_time,
                'available' => true
            );
            
            // If table_id is provided, check availability for that specific table
            if ($table_id) {
                $slot_data['available'] = $this->is_table_available($table_id, $date, $time_slot);
            }
            
            $slots[] = $slot_data;
        }
        
        return $slots;
    }
}
?>
