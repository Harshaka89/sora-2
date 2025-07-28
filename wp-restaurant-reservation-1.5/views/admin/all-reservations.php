<?php
// ✅ FORCE PAGINATION DISPLAY FOR TESTING
$force_show_pagination = true; // Set to false after testing
$test_total_pages = 5; // For testing purposes

// Use test values if forcing pagination
if ($force_show_pagination) {
    $display_total_pages = $test_total_pages;
    $display_current_page = $current_page ?? 1;
} else {
    $display_total_pages = $total_pages ?? 1;
    $display_current_page = $current_page ?? 1;
}
?>

<!-- ✅ ALWAYS SHOW PAGINATION SECTION (FOR TESTING) -->
<div style="background: linear-gradient(135deg, #007cba 0%, #0056b3 100%); color: white; padding: 30px; border-radius: 15px; margin: 30px 0; box-shadow: 0 10px 30px rgba(0,123,186,0.3);">
    <div style="text-align: center; margin-bottom: 25px;">
        <h2 style="margin: 0; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; gap: 10px;">
            <span style="font-size: 2rem;">📄</span>
            <span>Pagination Controls</span>
        </h2>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">
            Page <?php echo $display_current_page; ?> of <?php echo $display_total_pages; ?> 
            | <?php echo number_format($total_reservations ?? 0); ?> total reservations
        </p>
    </div>
    
    <!-- ✅ PAGINATION BUTTONS WITH LARGE ICONS -->
    <div style="display: flex; justify-content: center; align-items: center; gap: 15px; flex-wrap: wrap;">
        
        <!-- First Page Button -->
        <a href="<?php echo admin_url('admin.php?page=yrr-all-reservations&paged=1'); ?>" 
           style="background: rgba(255,255,255,0.2); color: white; padding: 15px 20px; border-radius: 12px; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 8px; border: 2px solid rgba(255,255,255,0.3); transition: all 0.3s ease; backdrop-filter: blur(10px);"
           onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='translateY(-3px)'"
           onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(0)'">
            <span style="font-size: 1.5rem;">⏮️</span>
            <span style="font-size: 1.1rem;">First</span>
        </a>
        
        <!-- Previous Page Button -->
        <a href="<?php echo admin_url('admin.php?page=yrr-all-reservations&paged=' . max(1, $display_current_page - 1)); ?>" 
           style="background: rgba(255,255,255,0.2); color: white; padding: 15px 20px; border-radius: 12px; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 8px; border: 2px solid rgba(255,255,255,0.3); transition: all 0.3s ease; backdrop-filter: blur(10px);"
           onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='translateY(-3px)'"
           onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(0)'">
            <span style="font-size: 1.5rem;">⬅️</span>
            <span style="font-size: 1.1rem;">Previous</span>
        </a>
        
        <!-- Page Numbers with Icons -->
        <?php for ($i = 1; $i <= $display_total_pages; $i++): ?>
            <?php if ($i == $display_current_page): ?>
                <!-- Current Page -->
                <span style="background: #28a745; color: white; padding: 15px 20px; border-radius: 12px; font-weight: bold; display: flex; align-items: center; gap: 8px; border: 3px solid #fff; box-shadow: 0 5px 20px rgba(40,167,69,0.4); animation: pulse 2s infinite;">
                    <span style="font-size: 1.5rem;">📍</span>
                    <span style="font-size: 1.2rem;"><?php echo $i; ?></span>
                </span>
            <?php else: ?>
                <!-- Other Pages -->
                <a href="<?php echo admin_url('admin.php?page=yrr-all-reservations&paged=' . $i); ?>" 
                   style="background: rgba(255,255,255,0.15); color: white; padding: 15px 20px; border-radius: 12px; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 8px; border: 2px solid rgba(255,255,255,0.3); transition: all 0.3s ease; backdrop-filter: blur(10px);"
                   onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='scale(1.1)'"
                   onmouseout="this.style.background='rgba(255,255,255,0.15)'; this.style.transform='scale(1)'">
                    <span style="font-size: 1.3rem;">📄</span>
                    <span style="font-size: 1.1rem;"><?php echo $i; ?></span>
                </a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <!-- Next Page Button -->
        <a href="<?php echo admin_url('admin.php?page=yrr-all-reservations&paged=' . min($display_total_pages, $display_current_page + 1)); ?>" 
           style="background: rgba(255,255,255,0.2); color: white; padding: 15px 20px; border-radius: 12px; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 8px; border: 2px solid rgba(255,255,255,0.3); transition: all 0.3s ease; backdrop-filter: blur(10px);"
           onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='translateY(-3px)'"
           onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(0)'">
            <span style="font-size: 1.1rem;">Next</span>
            <span style="font-size: 1.5rem;">➡️</span>
        </a>
        
        <!-- Last Page Button -->
        <a href="<?php echo admin_url('admin.php?page=yrr-all-reservations&paged=' . $display_total_pages); ?>" 
           style="background: rgba(255,255,255,0.2); color: white; padding: 15px 20px; border-radius: 12px; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 8px; border: 2px solid rgba(255,255,255,0.3); transition: all 0.3s ease; backdrop-filter: blur(10px);"
           onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='translateY(-3px)'"
           onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(0)'">
            <span style="font-size: 1.1rem;">Last</span>
            <span style="font-size: 1.5rem;">⏭️</span>
        </a>
    </div>
    
    <!-- ✅ PAGINATION STATS -->
    <div style="margin-top: 25px; text-align: center; background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; backdrop-filter: blur(10px);">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                <span style="font-size: 1.5rem;">📊</span>
                <span style="font-weight: bold;">Total: <?php echo number_format($total_reservations ?? 0); ?></span>
            </div>
            <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                <span style="font-size: 1.5rem;">📋</span>
                <span style="font-weight: bold;">Per Page: <?php echo $per_page ?? 10; ?></span>
            </div>
            <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                <span style="font-size: 1.5rem;">🎯</span>
                <span style="font-weight: bold;">Showing: <?php echo ($showing_from ?? 0) . '-' . ($showing_to ?? 0); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- ✅ CSS ANIMATIONS -->
<style>
@keyframes pulse {
    0% { box-shadow: 0 5px 20px rgba(40,167,69,0.4); }
    50% { box-shadow: 0 8px 30px rgba(40,167,69,0.8); }
    100% { box-shadow: 0 5px 20px rgba(40,167,69,0.4); }
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    div[style*="display: flex; justify-content: center; align-items: center; gap: 15px; flex-wrap: wrap;"] {
        gap: 8px !important;
    }
    
    div[style*="display: flex; justify-content: center; align-items: center; gap: 15px; flex-wrap: wrap;"] a,
    div[style*="display: flex; justify-content: center; align-items: center; gap: 15px; flex-wrap: wrap;"] span {
        padding: 10px 15px !important;
        font-size: 0.9rem !important;
    }
    
    div[style*="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));"] {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
    }
}
</style>
