<?php
/**
 * Tables Model - Yenolx Restaurant Reservation v1.5
 */

if (!defined('ABSPATH')) exit;

class YRR_Tables_Model {
    private $table_name;
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'yrr_tables';
    }
    
    public function get_all_tables() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY table_number");
    }
    
    public function get_table($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }
    
    public function create_table($data) {
        $data['created_at'] = current_time('mysql');
        return $this->wpdb->insert($this->table_name, $data);
    }
    
    public function update_table($id, $data) {
        return $this->wpdb->update($this->table_name, $data, array('id' => $id));
    }
    
    public function delete_table($id) {
        return $this->wpdb->delete($this->table_name, array('id' => $id));
    }
    
    public function get_available_tables($date, $time, $party_size, $duration = 120) {
        $start_time = $time;
        $end_time = date('H:i:s', strtotime($time . ' +' . $duration . ' minutes'));
        
        $sql = "
            SELECT t.* FROM {$this->table_name} t
            WHERE t.capacity >= %d 
            AND t.status = 'available'
            AND t.id NOT IN (
                SELECT DISTINCT r.table_id 
                FROM {$this->wpdb->prefix}yrr_reservations r 
                WHERE r.reservation_date = %s 
                AND r.status IN ('confirmed', 'pending')
                AND r.table_id IS NOT NULL
                AND (
                    (r.reservation_time <= %s AND DATE_ADD(CONCAT(r.reservation_date, ' ', r.reservation_time), INTERVAL 120 MINUTE) > %s)
                    OR
                    (r.reservation_time < %s AND r.reservation_time >= %s)
                )
            )
            ORDER BY t.capacity ASC, t.table_number ASC
        ";
        
        return $this->wpdb->get_results($this->wpdb->prepare(
            $sql, $party_size, $date, $start_time, $date . ' ' . $start_time, $date . ' ' . $end_time, $end_time, $start_time
        ));
    }
    
    public function get_table_bookings($table_id, $date) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}yrr_reservations 
             WHERE table_id = %d AND reservation_date = %s 
             AND status IN ('confirmed', 'pending')
             ORDER BY reservation_time",
            $table_id, $date
        ));
    }
    
    public function get_tables_by_capacity($min_capacity) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE capacity >= %d AND status = 'available' 
             ORDER BY capacity ASC, table_number ASC",
            $min_capacity
        ));
    }
}
?>
