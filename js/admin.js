// Navigation Elements
const navItems = document.querySelectorAll('.nav-item');
const contentSections = document.querySelectorAll('.content-section');
const pageTitle = document.getElementById('pageTitle');

// Button Elements
const logoutBtn = document.querySelector('.btn-logout');
const btnAddTask = document.getElementById('btnAddTask');
const btnAddUser = document.getElementById('btnAddUser');
const btnAddCategory = document.getElementById('btnAddCategory');

// Modal Elements
const addTaskModal = document.getElementById('addTaskModal');
const modalCloseBtns = document.querySelectorAll('.modal-close, .modal-close-btn');

// Section titles
const sectionTitles = {
    'overview': 'Dashboard Overview',
    'tasks': 'Taken beheren',
    'users': 'Gebruikers beheren',
    'categories': 'Categorieën beheren',
    'settings': 'Instellingen'
};

// Initialize navigation
function initializeNavigation() {
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const section = item.getAttribute('data-section');
            
            if (section) {
                switchSection(section);
            }
        });
    });
}

// Switch between sections
function switchSection(sectionName) {
    // Update active nav item
    navItems.forEach(item => {
        if (item.getAttribute('data-section') === sectionName) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
    
    // Update active content section
    contentSections.forEach(section => {
        const sectionId = section.id.replace('Section', '');
        if (sectionId === sectionName) {
            section.classList.add('active');
        } else {
            section.classList.remove('active');
        }
    });
    
    // Update page title
    pageTitle.textContent = sectionTitles[sectionName] || 'Admin Panel';
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

// Initialize modals
function initializeModals() {
    // Open add task modal
    if (btnAddTask) {
        btnAddTask.addEventListener('click', () => {
            addTaskModal.classList.add('active');
        });
    }
    
    // Close modals
    modalCloseBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            closeAllModals();
        });
    });
    
    // Close modal when clicking outside
    if (addTaskModal) {
        addTaskModal.addEventListener('click', (e) => {
            if (e.target === addTaskModal) {
                closeAllModals();
            }
        });
    }
}

// Close all modals
function closeAllModals() {
    if (addTaskModal) {
        addTaskModal.classList.remove('active');
    }
}

// Handle add task form submission
function initializeTaskForm() {
    const taskForm = document.querySelector('.modal-form');
    
    if (taskForm) {
        taskForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Get form values
            const taskData = {
                name: document.getElementById('taskName').value,
                category: document.getElementById('taskCategory').value,
                date: document.getElementById('taskDate').value,
                timeStart: document.getElementById('taskTimeStart').value,
                timeEnd: document.getElementById('taskTimeEnd').value,
                location: document.getElementById('taskLocation').value,
                maxVolunteers: document.getElementById('taskMaxVolunteers').value,
                description: document.getElementById('taskDescription').value
            };
            
            console.log('New task:', taskData);
            
            // Here you would save to database
            alert('Taak aangemaakt! (Deze functionaliteit wordt geïmplementeerd met de backend)');
            
            // Reset form and close modal
            taskForm.reset();
            closeAllModals();
        });
    }
}

// Initialize delete buttons
function initializeDeleteButtons() {
    const deleteButtons = document.querySelectorAll('.btn-icon[title="Verwijderen"]');
    
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (confirm('Weet je zeker dat je dit item wilt verwijderen?')) {
                // Here you would delete from database
                const row = e.target.closest('tr');
                if (row) {
                    row.remove();
                }
                alert('Item verwijderd!');
            }
        });
    });
}

// Initialize edit buttons
function initializeEditButtons() {
    const editButtons = document.querySelectorAll('.btn-icon[title="Bewerken"]');
    
    editButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            alert('Bewerk functionaliteit wordt geïmplementeerd met de backend');
        });
    });
}

// Initialize add user button
function initializeAddUserButton() {
    if (btnAddUser) {
        btnAddUser.addEventListener('click', () => {
            alert('Nieuwe gebruiker toevoegen - deze functionaliteit wordt geïmplementeerd met de backend');
        });
    }
}

// Initialize add category button
function initializeAddCategoryButton() {
    if (btnAddCategory) {
        btnAddCategory.addEventListener('click', () => {
            alert('Nieuwe categorie toevoegen - deze functionaliteit wordt geïmplementeerd met de backend');
        });
    }
}

// Check if user is admin
function checkAdminAccess() {
    const currentUser = localStorage.getItem('currentUser');
    
    if (!currentUser) {
        // Redirect to login if not logged in
        window.location.href = 'index.php';
        return false;
    }
    
    // Here you would check if user has admin role
    // For now, we allow all logged in users
    return true;
}

// Initialize all event listeners
function initialize() {
    if (!checkAdminAccess()) {
        return;
    }
    
    initializeNavigation();
    initializeLogoutButton();
    initializeModals();
    initializeTaskForm();
    initializeDeleteButtons();
    initializeEditButtons();
    initializeAddUserButton();
    initializeAddCategoryButton();
}

// Run initialization when DOM is ready
document.addEventListener('DOMContentLoaded', initialize);
