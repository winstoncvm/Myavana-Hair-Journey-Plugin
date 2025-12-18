<?php
// Partial: timeline-area.php
?>
            <div class="timeline-area">
                <div class="timeline-controls">
                    <div class="view-tabs">
                        <button class="tab active" onclick="switchView('calendar')">Calendar</button>
                        <button class="tab" onclick="switchView('timeline')">Timeline</button>         
                        <button class="tab" onclick="switchView('slider')">Slider</button>
                        <button class="tab" onclick="switchView('list')">List</button>
                    </div>
                    <!-- <div class="timeline-filters">
                        <select class="filter-btn">
                            <option>All Events</option>
                            <option>Goals</option>
                            <option>Entries</option>
                            <option>Routines</option>
                        </select>
                        <button class="filter-btn">🔍 Search</button>
                    </div> -->
                </div>

                <!-- Include modular view partials -->
                <?php
                $views_dir = __DIR__;
                if ( file_exists( $views_dir . '/view-calendar.php' ) ) {
                    include $views_dir . '/view-calendar.php';
                }
                if ( file_exists( $views_dir . '/view-timeline.php' ) ) {
                    include $views_dir . '/view-timeline.php';
                }
                
                if ( file_exists( $views_dir . '/view-slider.php' ) ) {
                    include $views_dir . '/view-slider.php';
                }
                if ( file_exists( $views_dir . '/view-list.php' ) ) {
                    include $views_dir . '/view-list.php';
                }
                ?>
            </div>
