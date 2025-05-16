const toggleBtn = document.getElementById('toggleSidebar');
            const sidebar = document.getElementById('sidebar');
            const chevronIcon = document.getElementById('chevronIcon');
            const menuItems = document.querySelectorAll('.menu-item');

            // Function to toggle sidebar
            function toggleSidebar() {
                sidebar.classList.toggle('collapsed');
                chevronIcon.classList.toggle('rotate-180');
                
                // Add transition class to menu items
                menuItems.forEach(item => {
                    item.style.transition = 'all 0.3s ease';
                });
            }

            // Toggle button click handler
            toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent event from bubbling up
                toggleSidebar();
            });

            // Sidebar click handler
            sidebar.addEventListener('click', (e) => {
                // Don't toggle if clicking on menu items or their children
                if (e.target.closest('.menu-item')) {
                    return;
                }
                // Don't toggle if clicking on the toggle button
                if (e.target.closest('#toggleSidebar')) {
                    return;
                }
                // Don't toggle if clicking on the logo or logo text
                if (e.target.closest('#logo') || e.target.closest('#logo-text')) {
                    return;
                }
                toggleSidebar();
            });