<?php
class RRS_GDPR_Manager {
  public function __construct() {
    add_filter('wp_privacy_personal_data_exporters', [$this,'register_exporter']);
    add_filter('wp_privacy_personal_data_erasers',  [$this,'register_eraser']);
    add_action('rrs_reservation_created',         [$this,'log_consent'], 10, 2);
    add_action('rrs_daily_retention_cleanup',     [$this,'run_data_retention']);
  }

  // 1) DATA EXPORTER
  public function register_exporter($exporters) {
    $exporters['rrs-reservation'] = [
      'exporter_friendly_name' => __('RRS Reservations & Customers','restaurant-reservation'),
      'callback'               => [$this,'export_user_data']
    ];
    return $exporters;
  }

  public function export_user_data($email_address, $page_size, $page) {
    global $wpdb;
    $data = [];
    // Reservations
    $reservations = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM {$wpdb->prefix}rrs_reservations WHERE customer_email = %s",
      $email_address
    ), ARRAY_A);
    foreach($reservations as $r) {
      $data[] = [
        'group_id'    => 'rrs_reservations',
        'group_label' => __('Your Reservations','restaurant-reservation'),
        'item_id'     => "reservation-{$r['id']}",
        'data'        => [
          ['name'=>__('Date','restaurant-reservation'), 'value'=>$r['reservation_date']],
          ['name'=>__('Time','restaurant-reservation'), 'value'=>$r['reservation_time']],
          ['name'=>__('Party Size','restaurant-reservation'), 'value'=>$r['party_size']],
        ]
      ];
    }
    // Customer profile
    $customer = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$wpdb->prefix}rrs_customers WHERE email = %s",
      $email_address
    ), ARRAY_A);
    if ($customer) {
      $data[] = [
        'group_id'=>'rrs_customer_profile',
        'group_label'=>__('Customer Profile','restaurant-reservation'),
        'item_id'=>"customer-{$customer['id']}",
        'data'=>[
          ['name'=>__('Name','restaurant-reservation'),'value'=>$customer['name']],
          ['name'=>__('Phone','restaurant-reservation'),'value'=>$customer['phone']],
        ]
      ];
    }
    return [
      'data'           => $data,
      'done'           => true
    ];
  }

  // 2) DATA ERASER
  public function register_eraser($erasers) {
    $erasers['rrs-reservation'] = [
      'eraser_friendly_name'=>__('RRS Personal Data Eraser','restaurant-reservation'),
      'callback'=>[$this,'erase_user_data']
    ];
    return $erasers;
  }
  public function erase_user_data($email_address, $page_size, $page) {
    global $wpdb;
    // Anonymize reservations
    $wpdb->query($wpdb->prepare(
      "UPDATE {$wpdb->prefix}rrs_reservations
       SET customer_name='ANONYMIZED', customer_email='', customer_phone=''
       WHERE customer_email=%s",
       $email_address
    ));
    // Delete customer record
    $wpdb->delete($wpdb->prefix.'rrs_customers',['email'=>$email_address]);
    return ['items_removed'=>true,'done'=>true];
  }

  // 3) GDPR Consent Logging
  public function log_consent($reservation_id, $data) {
    if (!empty($data['gdpr_consent'])) {
      global $wpdb;
      $wpdb->update(
        "{$wpdb->prefix}rrs_reservations",
        ['gdpr_consent'=>1],
        ['id'=>$reservation_id],
        ['%d'],['%d']
      );
    }
  }

  // 4) Data Retention Cleanup (schedule daily)
  public function run_data_retention() {
    global $wpdb;
    $retention_days = intval(get_option('rrs_data_retention_days',30));
    $cutoff = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
    // Anonymize old records
    $wpdb->query($wpdb->prepare(
      "UPDATE {$wpdb->prefix}rrs_reservations
       SET customer_name='ANONYMIZED', customer_email='', customer_phone=''
       WHERE created_at < %s",
       $cutoff
    ));
  }
}
new RRS_GDPR_Manager();

// Schedule retention on activation
add_action('plugins_loaded', function(){
  if (!wp_next_scheduled('rrs_daily_retention_cleanup'))
    wp_schedule_event(strtotime('tomorrow 01:00'),'daily','rrs_daily_retention_cleanup');
});
