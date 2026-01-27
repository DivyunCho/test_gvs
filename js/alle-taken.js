function confirmDelete(taskId, taskTitle) {
    if (confirm('Weet je zeker dat je de taak "' + taskTitle + '" wilt verwijderen?')) {
        window.location.href = 'taak-verwijderen.php?id=' + taskId;
    }
}

const taskDetailModal = document.getElementById('taskModal');
const alertModal = document.getElementById('alertModal');
const modalCloseBtns = document.querySelectorAll('.modal-close');

const categoryFilter = document.getElementById('categoryFilter');
const statusFilter = document.getElementById('statusFilter');

const taskInfoBtns = document.querySelectorAll('.btn-task-info');
const taskEditBtns = document.querySelectorAll('.btn-task-edit');
const taskDeleteBtns = document.querySelectorAll('.btn-task-delete');
const subscribeBtn = document.querySelector('.btn-subscribe');
const unsubscribeBtn = document.querySelector('.btn-unsubscribe');

const userAvatar = document.querySelector('.btn-user-avatar');

const navItems = document.querySelectorAll('.nav-item');

function initializeFilters() {
    categoryFilter.addEventListener('change', filterTasks);
    statusFilter.addEventListener('change', filterTasks);
}

function filterTasks() {
    const selectedCategory = categoryFilter.value;
    const selectedStatus = statusFilter.value;
    const taskItems = document.querySelectorAll('.task-item');
    
    taskItems.forEach(task => {
        const taskCategory = task.getAttribute('data-category');
        const taskStatus = task.classList.contains('status-available') ? 'available' :
                          task.classList.contains('status-full') ? 'full' :
                          task.classList.contains('status-not-available') ? 'not-available' : '';
        
        let showTask = true;
        
        // Filter by category
        if (selectedCategory !== 'all' && taskCategory !== selectedCategory) {
            showTask = false;
        }
        
        // Filter by status
        if (selectedStatus !== 'all' && taskStatus !== selectedStatus) {
            showTask = false;
        }
        
        // Show or hide task
        if (showTask) {
            task.style.display = 'flex';
        } else {
            task.style.display = 'none';
        }
    });
}

// Open task detail modal
function openTaskDetail(taskElement) {
    const taskName = taskElement.querySelector('.task-name').textContent;
    
    // Get full date text (e.g., "Zaterdag 15 November 2025")
    const dateElement = taskElement.querySelector('.task-date');
    const dateText = Array.from(dateElement.childNodes)
        .filter(node => node.nodeType === Node.TEXT_NODE)
        .map(node => node.textContent.trim())
        .join('');
    
    // Get time text (e.g., "09:00 - 12:00")
    const timeElement = taskElement.querySelector('.task-time');
    const timeText = Array.from(timeElement.childNodes)
        .filter(node => node.nodeType === Node.TEXT_NODE)
        .map(node => node.textContent.trim())
        .join('');
    
    // Get location text
    const locationElement = taskElement.querySelector('.task-location');
    const locationText = Array.from(locationElement.childNodes)
        .filter(node => node.nodeType === Node.TEXT_NODE)
        .map(node => node.textContent.trim())
        .join('');
    
    // Get volunteers text (e.g., "2 / 5 vrijwilligers")
    const volunteersElement = taskElement.querySelector('.task-volunteers');
    const volunteersText = Array.from(volunteersElement.childNodes)
        .filter(node => node.nodeType === Node.TEXT_NODE)
        .map(node => node.textContent.trim())
        .join('');
    
    // Get category from task icon
    const categoryIcon = taskElement.querySelector('.task-icon .material-icons').textContent;
    const categoryMap = {
        'cleaning_services': 'Schoonmaken',
        'format_paint': 'Kalken',
        'local_cafe': 'Bardienst',
        'sports_soccer': 'Training',
        'emoji_events': 'Wedstrijd',
        'handyman': 'Onderhoud'
    };
    const category = categoryMap[categoryIcon] || 'Onbekend';
    
    // Update modal content
    document.querySelector('.task-detail-header h2').textContent = taskName;
    document.getElementById('taskTime').textContent = `${dateText} ${timeText}`;
    document.getElementById('taskLocation').textContent = locationText;
    document.getElementById('taskVolunteers').textContent = volunteersText;
    document.getElementById('taskCategory').textContent = category;
    
    // Show modal
    taskDetailModal.classList.add('active');
}

// Close modal
function closeModal(modal) {
    modal.classList.remove('active');
}

// Initialize task info buttons
function initializeTaskInfoButtons() {
    taskInfoBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const taskItem = e.target.closest('.task-item');
            openTaskDetail(taskItem);
        });
    });
}

// Initialize modal close buttons
function initializeModalCloseButtons() {
    modalCloseBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal');
            closeModal(modal);
        });
    });
    
    // Close modal when clicking outside
    [taskDetailModal, alertModal].forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });
}

// Initialize subscribe button
function initializeSubscribeButton() {
    if (subscribeBtn) {
        subscribeBtn.addEventListener('click', () => {
            // Check if user is logged in
            const currentUser = localStorage.getItem('currentUser');
            if (!currentUser) {
                // Show alert modal
                alertModal.classList.add('active');
                closeModal(taskDetailModal);
                return;
            }
            
            // Here you would add the subscription logic
            alert('Je bent ingeschreven voor deze taak!');
            closeModal(taskDetailModal);
        });
    }
}

// Initialize unsubscribe button
function initializeUnsubscribeButton() {
    if (unsubscribeBtn) {
        unsubscribeBtn.addEventListener('click', () => {
            // Here you would add the unsubscription logic
            alert('Je bent uitgeschreven voor deze taak!');
            closeModal(taskDetailModal);
        });
    }
}

// Initialize navigation
function initializeNavigation() {
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            const href = item.getAttribute('href');
            if (href && href !== '#') {
                e.preventDefault();
                window.location.href = href;
            }
        });
    });
}

// Initialize user avatar logout
function initializeUserAvatar() {
    if (userAvatar) {
        userAvatar.addEventListener('click', () => {
            if (confirm('Wilt u uitloggen?')) {
                localStorage.removeItem('currentUser');
                window.location.href = 'index.php';
            }
        });
    }
}

// Initialize edit buttons (admin only)
function initializeEditButtons() {
    taskEditBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const taskId = btn.getAttribute('data-task-id');
            window.location.href = `taak-wijzigen.html?id=${taskId}`;
        });
    });
}

// Initialize delete buttons (admin only)
function initializeDeleteButtons() {
    taskDeleteBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const taskId = btn.getAttribute('data-task-id');
            const taskItem = btn.closest('.task-item');
            const taskName = taskItem.querySelector('.task-name').textContent;
            
            if (confirm(`Weet je zeker dat je "${taskName}" wilt verwijderen?`)) {
                console.log('Deleting task:', taskId);
                // TODO: Delete from backend
                taskItem.remove();
                alert('Taak verwijderd! (Later: verwijderen uit database)');
            }
        });
    });
}

// Initialize all event listeners
function initialize() {
    initializeFilters();
    initializeTaskInfoButtons();
    initializeEditButtons();
    initializeDeleteButtons();
    initializeModalCloseButtons();
    initializeSubscribeButton();
    initializeUnsubscribeButton();
    initializeNavigation();
    initializeUserAvatar();
}

// Run initialization when DOM is ready
document.addEventListener('DOMContentLoaded', initialize);
