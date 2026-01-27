// Check if user is admin
const currentUser = JSON.parse(localStorage.getItem('currentUser'));
if (currentUser && currentUser.isAdmin) {
    document.body.classList.add('is-admin');
}

// Modal Elements
const taskDetailModal = document.getElementById('taskModal');
const alertModal = document.getElementById('alertModal');
const modalCloseBtns = document.querySelectorAll('.modal-close');

// Button Elements
const logoutBtn = document.querySelector('.btn-logout');
const unsubscribeBtn = document.getElementById('unsubscribeBtn');
const taskDetailBtns = document.querySelectorAll('.btn-task-detail');
const closeAlertBtn = document.getElementById('closeAlertBtn');

// Navigation Elements
const navItems = document.querySelectorAll('.nav-item');

// Sample task data for subscribed tasks (later from backend)
const subscribedTasks = [
    {
        id: 1,
        name: 'Schoon maken',
        time: '18:00-21:00',
        date: 'Zaterdag 15 November 2025',
        location: 'Kleedkamer A & B',
        volunteers: '3 / 5 vrijwilligers',
        category: 'Schoonmaken',
        subscribedVolunteers: ['Jan Peeters', 'Marie De Vries', 'Luc Janssens']
    },
    {
        id: 2,
        name: 'Schoon maken',
        time: '18:00-21:00',
        date: 'Zondag 16 November 2025',
        location: 'Clubhuis',
        volunteers: '2 / 5 vrijwilligers',
        category: 'Schoonmaken',
        subscribedVolunteers: ['Sara Claes', 'Peter Maes']
    },
    {
        id: 3,
        name: 'Schoon maken',
        time: '18:00-21:00',
        date: 'Maandag 17 November 2025',
        location: 'Kantine',
        volunteers: '4 / 5 vrijwilligers',
        category: 'Schoonmaken',
        subscribedVolunteers: ['Lisa Van Damme', 'Koen Hermans', 'Tom Vermeulen', 'Ann Peeters']
    }
];

// Open task detail modal
function openTaskDetail(taskId) {
    const task = subscribedTasks.find(t => t.id === taskId);
    
    if (!task) return;
    
    // Update modal content
    document.getElementById('taskTitle').textContent = task.name;
    document.getElementById('taskTime').textContent = `${task.date} ${task.time}`;
    document.getElementById('taskLocation').textContent = task.location;
    document.getElementById('taskVolunteers').textContent = task.volunteers;
    document.getElementById('taskCategory').textContent = task.category;
    
    // Update volunteers list
    const volunteersList = document.getElementById('volunteersList');
    volunteersList.innerHTML = '';
    
    task.subscribedVolunteers.forEach(volunteer => {
        const volunteerItem = document.createElement('div');
        volunteerItem.className = 'volunteer-item';
        volunteerItem.innerHTML = `
            <span class="material-icons">person</span>
            <span class="volunteer-name">${volunteer}</span>
        `;
        volunteersList.appendChild(volunteerItem);
    });
    
    // Show modal
    taskDetailModal.classList.add('active');
}

// Close modal
function closeModal(modal) {
    modal.classList.remove('active');
}

// Initialize task detail buttons
function initializeTaskDetailButtons() {
    taskDetailBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const taskCard = e.target.closest('.subscribed-task-card');
            const taskId = parseInt(taskCard.getAttribute('data-task-id'));
            openTaskDetail(taskId);
        });
    });
    
    // Also allow clicking on the card itself
    const taskCards = document.querySelectorAll('.subscribed-task-card');
    taskCards.forEach(card => {
        card.addEventListener('click', (e) => {
            if (!e.target.closest('.btn-task-detail')) {
                const taskId = parseInt(card.getAttribute('data-task-id'));
                openTaskDetail(taskId);
            }
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

// Initialize unsubscribe button
function initializeUnsubscribeButton() {
    if (unsubscribeBtn) {
        unsubscribeBtn.addEventListener('click', () => {
            // Check if within 24 hours
            const currentDate = new Date();
            const taskDate = new Date('2025-11-15'); // Example date
            const timeDiff = taskDate - currentDate;
            const hoursDiff = timeDiff / (1000 * 60 * 60);
            
            if (hoursDiff < 24 && hoursDiff > 0) {
                // Show alert modal
                document.getElementById('alertMessage').textContent = 'Uitschrijven kan alleen 24 uur van te voren';
                alertModal.classList.add('active');
                closeModal(taskDetailModal);
            } else {
                // Allow unsubscribe
                alert('Je bent uitgeschreven voor deze taak!');
                closeModal(taskDetailModal);
                // Refresh page or update UI
                location.reload();
            }
        });
    }
}

// Initialize close alert button
function initializeCloseAlertButton() {
    if (closeAlertBtn) {
        closeAlertBtn.addEventListener('click', () => {
            closeModal(alertModal);
        });
    }
}

// Initialize logout button
function initializeLogoutButton() {
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            if (confirm('Wilt u uitloggen?')) {
                localStorage.removeItem('currentUser');
                window.location.href = 'index.php';
            }
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

// Initialize all event listeners
function initialize() {
    initializeTaskDetailButtons();
    initializeModalCloseButtons();
    initializeUnsubscribeButton();
    initializeCloseAlertButton();
    initializeLogoutButton();
    initializeNavigation();
}

// Run initialization when DOM is ready
document.addEventListener('DOMContentLoaded', initialize);
