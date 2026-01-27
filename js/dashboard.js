// Dashboard Main Script
let currentUser = null;
let currentView = 'day';
let currentDate = new Date();

document.addEventListener('DOMContentLoaded', function() {
    // Check authentication
    currentUser = checkAuth();
    if (!currentUser) return;
    
    // Initialize dashboard
    initializeDashboard();
    
    // Event Listeners
    setupEventListeners();
    
    // Initial render
    renderDayView();
    updateCalendarDisplay();
});

function initializeDashboard() {
    // Set user initial in avatar
    const userAvatar = document.querySelector('.user-initial');
    if (userAvatar && currentUser) {
        userAvatar.textContent = currentUser.username.charAt(0).toUpperCase();
    }
    
    // Load tasks from storage
    loadTasks();
}

function setupEventListeners() {
    // View toggle buttons
    const viewButtons = document.querySelectorAll('.toggle-btn');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Check if this button is wrapped in an anchor tag (for navigation)
            const anchor = this.closest('a');
            if (anchor && anchor.getAttribute('href')) {
                // Let the anchor handle the navigation - don't prevent default
                return;
            }
            
            // Otherwise, handle view switching
            const view = this.getAttribute('data-view');
            switchView(view);
        });
    });
    
    // Navigation buttons
    document.getElementById('prevPeriod').addEventListener('click', navigatePrevious);
    document.getElementById('nextPeriod').addEventListener('click', navigateNext);
    document.getElementById('todayBtn').addEventListener('click', navigateToday);
    
    // Create task button
    document.getElementById('createTaskBtn').addEventListener('click', openCreateTaskModal);
    
    // User avatar - logout
    document.querySelector('.btn-user-avatar').addEventListener('click', function() {
        if (confirm('Wilt u uitloggen?')) {
            logout();
        }
    });
    
    // Navigation items
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
            
            const page = this.getAttribute('data-page');
            handleNavigation(page);
        });
    });
    
    // Modal close buttons
    document.getElementById('closeTaskModal').addEventListener('click', closeTaskModal);
    document.getElementById('closeAlertModal').addEventListener('click', closeAlertModal);
    document.getElementById('closeAlertBtn').addEventListener('click', closeAlertModal);
}

function switchView(view) {
    currentView = view;
    
    // Update toggle buttons
    const toggleButtons = document.querySelectorAll('.toggle-btn');
    toggleButtons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-view') === view) {
            btn.classList.add('active');
        }
    });
    
    // Update calendar views
    const views = document.querySelectorAll('.calendar-view');
    views.forEach(v => v.classList.remove('active'));
    
    if (view === 'day') {
        document.getElementById('dayView').classList.add('active');
    } else if (view === 'week') {
        document.getElementById('weekView').classList.add('active');
    } else if (view === 'month') {
        document.getElementById('monthView').classList.add('active');
    }
    
    updateCalendarDisplay();
}

function navigatePrevious() {
    if (currentView === 'day') {
        currentDate.setDate(currentDate.getDate() - 1);
    } else if (currentView === 'week') {
        currentDate.setDate(currentDate.getDate() - 7);
    } else if (currentView === 'month') {
        currentDate.setMonth(currentDate.getMonth() - 1);
    }
    updateCalendarDisplay();
}

function navigateNext() {
    if (currentView === 'day') {
        currentDate.setDate(currentDate.getDate() + 1);
    } else if (currentView === 'week') {
        currentDate.setDate(currentDate.getDate() + 7);
    } else if (currentView === 'month') {
        currentDate.setMonth(currentDate.getMonth() + 1);
    }
    updateCalendarDisplay();
}

function navigateToday() {
    currentDate = new Date();
    updateCalendarDisplay();
}

function updateCalendarDisplay() {
    const monthYearText = document.getElementById('monthYearText');
    const currentPeriodText = document.getElementById('currentPeriodText');
    
    const months = ['Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni', 
                   'Juli', 'Augustus', 'September', 'Oktober', 'November', 'December'];
    const days = ['Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag'];
    
    monthYearText.textContent = `${months[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
    
    if (currentView === 'day') {
        currentPeriodText.textContent = `${currentDate.getDate()} ${months[currentDate.getMonth()]}`;
        renderDayView();
    } else if (currentView === 'week') {
        const weekStart = getWeekStart(currentDate);
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekEnd.getDate() + 6);
        currentPeriodText.textContent = `${weekStart.getDate()} - ${weekEnd.getDate()}`;
        renderWeekView();
    } else if (currentView === 'month') {
        currentPeriodText.textContent = months[currentDate.getMonth()];
        renderMonthView();
    }
}

function getWeekStart(date) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(d.setDate(diff));
}

function handleNavigation(page) {
    // Handle different navigation pages
    if (page === 'calendar') {
        // Already on calendar
    } else if (page === 'tasks') {
        // Show all tasks view
        alert('Alle taken weergave komt binnenkort!');
    } else if (page === 'profile') {
        // Show profile
        alert('Profiel pagina komt binnenkort!');
    }
}

function openCreateTaskModal() {
    alert('Taak aanmaken functionaliteit komt binnenkort!');
}

function closeTaskModal() {
    document.getElementById('taskModal').classList.remove('active');
}

function closeAlertModal() {
    document.getElementById('alertModal').classList.remove('active');
}

function showAlert(message) {
    document.getElementById('alertMessage').textContent = message;
    document.getElementById('alertModal').classList.add('active');
}

// Load tasks from localStorage (temporary)
function loadTasks() {
    const tasks = localStorage.getItem('tasks');
    if (!tasks) {
        // Create sample tasks
        createSampleTasks();
    }
}

function createSampleTasks() {
    // Sample tasks will be created in tasks.js
}
