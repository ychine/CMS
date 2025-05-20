const toggleBtn = document.getElementById('toggleSidebar');
const sidebar = document.getElementById('sidebar');
const chevronIcon = document.getElementById('chevronIcon');
const menuItems = document.querySelectorAll('.menu-item');


function toggleSidebar() {
    sidebar.classList.toggle('collapsed');
    chevronIcon.classList.toggle('rotate-180');
    
    menuItems.forEach(item => {
        item.style.transition = 'all 0.3s ease';
    });
}


toggleBtn.addEventListener('click', (e) => {
    e.stopPropagation(); 
    toggleSidebar();
});


sidebar.addEventListener('click', (e) => {

    if (e.target.closest('.menu-item')) {
        return;
    }
    
    if (e.target.closest('#toggleSidebar')) {
        return;
    }
    
    if (e.target.closest('#logo') || e.target.closest('#logo-text')) {
        return;
    }
    toggleSidebar();
});