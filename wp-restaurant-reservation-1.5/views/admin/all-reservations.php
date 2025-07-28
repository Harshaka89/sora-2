<!-- ✅ COMPLETE PAGINATION CONTROLS (ICONS + TEXT) -->
<?php if ($total_pages > 1): ?>
    <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 30px; border-radius: 15px; border: 3px solid #007cba; margin-top: 30px;">
        <h3 style="margin: 0 0 25px 0; color: #007cba; font-size: 1.4rem; text-align: center;">
            📄 Page Navigation - <?php echo $current_page; ?> of <?php echo $total_pages; ?>
        </h3>
        
        <!-- ✅ PAGINATION BUTTONS WITH ICONS AND TEXT -->
        <div style="display: flex; justify-content: center; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 25px;">
            
            <!-- ✅ FIRST PAGE -->
            <?php if ($current_page > 1): ?>
                <a href="<?php echo esc_url(add_query_arg(array('paged' => 1, 'search' => $search, 'status' => $status_filter, 'date' => $date_filter), admin_url('admin.php?page=yrr-all-reservations'))); ?>" 
                   style="background: #007cba; color: white; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; border: 2px solid #007cba;" 
                   onmouseover="this.style.background='#0056b3'; this.style.transform='translateY(-2px)'" 
                   onmouseout="this.style.background='#007cba'; this.style.transform='translateY(0)'">
                    <span style="font-size: 1.2rem;">⏮️</span>
                    <span>First</span>
                </a>
            <?php endif; ?>
            
            <!-- ✅ PREVIOUS PAGE -->
            <?php if ($current_page > 1): ?>
                <a href="<?php echo esc_url(add_query_arg(array('paged' => $current_page - 1, 'search' => $search, 'status' => $status_filter, 'date' => $date_filter), admin_url('admin.php?page=yrr-all-reservations'))); ?>" 
                   style="background: #6c757d; color: white; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; border: 2px solid #6c757d;"
                   onmouseover="this.style.background='#545b62'; this.style.transform='translateY(-2px)'" 
                   onmouseout="this.style.background='#6c757d'; this.style.transform='translateY(0)'">
                    <span style="font-size: 1.2rem;">⬅️</span>
                    <span>Previous</span>
                </a>
            <?php endif; ?>
            
            <!-- ✅ PAGE NUMBERS WITH ENHANCED DISPLAY -->
            <?php
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            // Show ellipsis if needed
            if ($start_page > 1) {
                echo '<a href="' . esc_url(add_query_arg(array('paged' => 1, 'search' => $search, 'status' => $status_filter, 'date' => $date_filter), admin_url('admin.php?page=yrr-all-reservations'))) . '" style="background: #e9ecef; color: #495057; padding: 12px 16px; border-radius: 8px; text-decoration: none; font-weight: bold; border: 2px solid #dee2e6;">1</a>';
                if ($start_page > 2) {
                    echo '<span style="color: #6c757d; font-weight: bold; padding: 12px 8px;">...</span>';
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <?php if ($i == $current_page): ?>
                    <span style="background: #28a745; color: white; padding: 12px 20px; border-radius: 8px; font-weight: bold; font-size: 1.2rem; border: 3px solid #1e7e34; display: flex; align-items: center; gap: 5px; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);">
                        <span style="font-size: 1.1rem;">📍</span>
                        <span><?php echo $i; ?></span>
                    </span>
                <?php else: ?>
                    <a href="<?php echo esc_url(add_query_arg(array('paged' => $i, 'search' => $search, 'status' => $status_filter, 'date' => $date_filter), admin_url('admin.php?page=yrr-all-reservations'))); ?>" 
                       style="background: #e9ecef; color: #495057; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; border: 2px solid #dee2e6; transition: all 0.3s ease; display: flex; align-items: center; gap: 5px;"
                       onmouseover="this.style.background='#007cba'; this.style.color='white'; this.style.transform='translateY(-2px)'" 
                       onmouseout="this.style.background='#e9ecef'; this.style.color='#495057'; this.style.transform='translateY(0)'">
                        <span style="font-size: 1rem;">📄</span>
                        <span><?php echo $i; ?></span>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php
            // Show ellipsis and last page if needed
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span style="color: #6c757d; font-weight: bold; padding: 12px 8px;">...</span>';
                }
                echo '<a href="' . esc_url(add_query_arg(array('paged' => $total_pages, 'search' => $search, 'status' => $status_filter, 'date' => $date_filter), admin_url('admin.php?page=yrr-all-reservations'))) . '" style="background: #e9ecef; color: #495057; padding: 12px 16px; border-radius: 8px; text-decoration: none; font-weight: bold; border: 2px solid #dee2e6;">' . $total_pages . '</a>';
            }
            ?>
            
            <!-- ✅ NEXT PAGE -->
            <?php if ($current_page < $total_pages): ?>
                <a href="<?php echo esc_url(add_query_arg(array('paged' => $current_page + 1, 'search' => $search, 'status' => $status_filter, 'date' => $date_filter), admin_url('admin.php?page=yrr-all-reservations'))); ?>" 
                   style="background: #6c757d; color: white; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; border: 2px solid #6c757d;"
                   onmouseover="this.style.background='#545b62'; this.style.transform='translateY(-2px)'" 
                   onmouseout="this.style.background='#6c757d'; this.style.transform='translateY(0)'">
                    <span>Next</span>
                    <span style="font-size: 1.2rem;">➡️</span>
                </a>
            <?php endif; ?>
            
            <!-- ✅ LAST PAGE -->
            <?php if ($current_page < $total_pages): ?>
                <a href="<?php echo esc_url(add_query_arg(array('paged' => $total_pages, 'search' => $search, 'status' => $status_filter, 'date' => $date_filter), admin_url('admin.php?page=yrr-all-reservations'))); ?>" 
                   style="background: #007cba; color: white; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease; border: 2px solid #007cba;"
                   onmouseover="this.style.background='#0056b3'; this.style.transform='translateY(-2px)'" 
                   onmouseout="this.style.background='#007cba'; this.style.transform='translateY(0)'">
                    <span>Last</span>
                    <span style="font-size: 1.2rem;">⏭️</span>
                </a>
            <?php endif; ?>
        </div>
        
        <!-- ✅ DETAILED PAGINATION INFO -->
        <div style="background: white; padding: 25px; border-radius: 12px; border: 2px solid #007cba; text-align: center;">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                <div style="background: #e3f2fd; padding: 15px; border-radius: 8px;">
                    <div style="font-size: 1.8rem; font-weight: bold; color: #1976d2; margin-bottom: 5px;"><?php echo $current_page; ?></div>
                    <div style="color: #1976d2; font-weight: bold;">Current Page</div>
                </div>
                <div style="background: #f3e5f5; padding: 15px; border-radius: 8px;">
                    <div style="font-size: 1.8rem; font-weight: bold; color: #7b1fa2; margin-bottom: 5px;"><?php echo $total_pages; ?></div>
                    <div style="color: #7b1fa2; font-weight: bold;">Total Pages</div>
                </div>
                <div style="background: #e8f5e8; padding: 15px; border-radius: 8px;">
                    <div style="font-size: 1.8rem; font-weight: bold; color: #388e3c; margin-bottom: 5px;"><?php echo number_format($total_reservations); ?></div>
                    <div style="color: #388e3c; font-weight: bold;">Total Records</div>
                </div>
            </div>
            
            <div style="color: #6c757d; font-size: 1.1rem; font-weight: bold;">
                📊 Showing reservations <strong><?php echo $showing_from; ?>-<?php echo $showing_to; ?></strong> of <strong><?php echo number_format($total_reservations); ?></strong> 
                | <strong><?php echo $per_page; ?></strong> per page
            </div>
        </div>
        
        <!-- ✅ QUICK JUMP TO PAGE -->
        <div style="background: #fff3cd; padding: 20px; border-radius: 10px; margin-top: 20px; text-align: center;">
            <label style="font-weight: bold; color: #856404; margin-right: 10px;">🔍 Quick Jump to Page:</label>
            <select onchange="if(this.value) window.location.href='<?php echo admin_url('admin.php?page=yrr-all-reservations&paged='); ?>' + this.value + '<?php echo '&search=' . urlencode($search) . '&status=' . urlencode($status_filter) . '&date=' . urlencode($date_filter); ?>'" 
                    style="padding: 8px 15px; border: 2px solid #ffc107; border-radius: 5px; font-weight: bold;">
                <option value="">Select Page...</option>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php selected($current_page, $i); ?>>
                        Page <?php echo $i; ?> <?php echo $i == $current_page ? '(Current)' : ''; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
    
<?php else: ?>
    <!-- ✅ SINGLE PAGE INFO -->
    <div style="background: #e3f2fd; padding: 25px; border-radius: 15px; text-align: center; border: 2px solid #1976d2; margin-top: 30px;">
        <h3 style="color: #1976d2; margin: 0 0 15px 0;">📄 Single Page Display</h3>
        <div style="color: #1976d2; font-weight: bold; font-size: 1.2rem;">
            All <strong><?php echo number_format($total_reservations); ?></strong> reservations are displayed on this page
        </div>
        <div style="color: #6c757d; margin-top: 10px;">
            No pagination needed - showing all results
        </div>
    </div>
<?php endif; ?>
