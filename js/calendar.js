// Check if user is admin
const currentUser = JSON.parse(localStorage.getItem('currentUser'));
if (currentUser && currentUser.isAdmin) {
    document.body.classList.add('is-admin');
}

// Create Task Modal
const createTaskBtn = document.getElementById('createTaskBtn');
const createTaskModal = document.getElementById('createTaskModal');
const closeCreateTaskModal = document.getElementById('closeCreateTaskModal');
const createTaskForm = document.getElementById('createTaskForm');

if (createTaskBtn) {
    createTaskBtn.addEventListener('click', () => {
        createTaskModal.classList.add('active');
    });
}

if (closeCreateTaskModal) {
    closeCreateTaskModal.addEventListener('click', () => {
        createTaskModal.classList.remove('active');
    });
}

if (createTaskModal) {
    createTaskModal.addEventListener('click', (e) => {
        if (e.target === createTaskModal) {
            createTaskModal.classList.remove('active');
        }
    });
}

if (createTaskForm) {
    createTaskForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(createTaskForm);
        const taskData = Object.fromEntries(formData.entries());
        console.log('Nieuwe taak:', taskData);
        alert('Taak aangemaakt! (Later: opslaan in database)');
        createTaskModal.classList.remove('active');
        createTaskForm.reset();
    });
}

// Calendar Rendering Functions

function renderDayView() {
    const container = document.getElementById('dayEventsContainer');
    container.innerHTML = '';
    
    // Get tasks for current date
    const tasks = getTasksForDate(currentDate);
    
    tasks.forEach(task => {
        const taskElement = createTaskElement(task);
        container.appendChild(taskElement);
    });
}

function renderWeekView() {
    const weekDaysHeader = document.getElementById('weekDaysHeader');
    const weekColumns = document.getElementById('weekColumns');
    
    weekDaysHeader.innerHTML = '';
    weekColumns.innerHTML = '';
    
    const weekStart = getWeekStart(currentDate);
    const dayNames = ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'];
    
    // Create headers and columns
    for (let i = 0; i < 7; i++) {
        const date = new Date(weekStart);
        date.setDate(date.getDate() + i);
        
        const headerDiv = document.createElement('div');
        headerDiv.className = 'week-day-header';
        headerDiv.innerHTML = `
            <div class="week-day-name">${dayNames[i]}</div>
            <div class="week-day-number">${date.getDate()}</div>
        `;
        weekDaysHeader.appendChild(headerDiv);
        
        // Create column for tasks
        const columnDiv = document.createElement('div');
        columnDiv.className = 'week-column';
        
        const tasksForDay = getTasksForDate(date);
        tasksForDay.forEach(task => {
            const taskElement = createWeekTaskElement(task);
            columnDiv.appendChild(taskElement);
        });
        
        weekColumns.appendChild(columnDiv);
    }
}

function createWeekTaskElement(task) {
    const taskDiv = document.createElement('div');
    taskDiv.className = `task-card status-${task.status}`;
    
    // Calculate position based on time
    const startHour = parseInt(task.startTime.split(':')[0]);
    const startMinute = parseInt(task.startTime.split(':')[1]);
    const endHour = parseInt(task.endTime.split(':')[0]);
    const endMinute = parseInt(task.endTime.split(':')[1]);
    
    const startPosition = (startHour * 60 + startMinute) / 60 * 60; // 60px per hour
    const duration = ((endHour * 60 + endMinute) - (startHour * 60 + startMinute)) / 60 * 60;
    
    taskDiv.style.position = 'absolute';
    taskDiv.style.top = `${startPosition}px`;
    taskDiv.style.height = `${duration}px`;
    taskDiv.style.left = '4px';
    taskDiv.style.right = '4px';
    
    taskDiv.innerHTML = `
        <div class="task-title">${task.title}</div>
    `;
    
    taskDiv.addEventListener('click', () => openTaskDetailModal(task));
    
    return taskDiv;
}

function renderMonthView() {
    const monthGrid = document.getElementById('monthGrid');
    monthGrid.innerHTML = '';
    
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    // Get first day of month
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    
    // Get day of week (0 = Sunday, need to convert to Monday = 0)
    let startDay = firstDay.getDay() - 1;
    if (startDay === -1) startDay = 6;
    
    // Add days from previous month
    const prevMonthLastDay = new Date(year, month, 0).getDate();
    for (let i = startDay - 1; i >= 0; i--) {
        const cell = createMonthCell(new Date(year, month - 1, prevMonthLastDay - i), true);
        monthGrid.appendChild(cell);
    }
    
    // Add days of current month
    for (let day = 1; day <= lastDay.getDate(); day++) {
        const cell = createMonthCell(new Date(year, month, day), false);
        monthGrid.appendChild(cell);
    }
    
    // Add days from next month to fill grid
    const totalCells = monthGrid.children.length;
    const remainingCells = 35 - totalCells; // Ensure at least 5 weeks
    for (let day = 1; day <= remainingCells; day++) {
        const cell = createMonthCell(new Date(year, month + 1, day), true);
        monthGrid.appendChild(cell);
    }
}

function createMonthCell(date, isOtherMonth) {
    const cell = document.createElement('div');
    cell.className = 'month-cell';
    if (isOtherMonth) {
        cell.classList.add('other-month');
    }
    
    const dateDiv = document.createElement('div');
    dateDiv.className = 'month-cell-date';
    dateDiv.textContent = date.getDate();
    cell.appendChild(dateDiv);
    
    const tasksDiv = document.createElement('div');
    tasksDiv.className = 'month-tasks';
    
    const tasks = getTasksForDate(date);
    const displayTasks = tasks.slice(0, 3); // Show max 3 tasks
    
    displayTasks.forEach(task => {
        const taskDiv = document.createElement('div');
        taskDiv.className = `month-task status-${task.status}`;
        taskDiv.textContent = task.title;
        taskDiv.addEventListener('click', () => openTaskDetailModal(task));
        tasksDiv.appendChild(taskDiv);
    });
    
    if (tasks.length > 3) {
        const countDiv = document.createElement('div');
        countDiv.className = 'month-task-count';
        countDiv.textContent = `+${tasks.length - 3} meer`;
        tasksDiv.appendChild(countDiv);
    }
    
    cell.appendChild(tasksDiv);
    return cell;
}

function createTaskElement(task) {
    const taskDiv = document.createElement('div');
    taskDiv.className = `task-card status-${task.status}`;
    
    // Calculate position based on time
    const startHour = parseInt(task.startTime.split(':')[0]);
    const startMinute = parseInt(task.startTime.split(':')[1]);
    const endHour = parseInt(task.endTime.split(':')[0]);
    const endMinute = parseInt(task.endTime.split(':')[1]);
    
    const startPosition = (startHour * 60 + startMinute) / 60 * 60; // 60px per hour
    const duration = ((endHour * 60 + endMinute) - (startHour * 60 + startMinute)) / 60 * 60;
    
    taskDiv.style.top = `${startPosition}px`;
    taskDiv.style.height = `${duration}px`;
    
    taskDiv.innerHTML = `
        <div class="task-title">${task.title}</div>
        <div class="task-volunteers">
            ${task.volunteers.map(v => `
                <div class="task-volunteer-item">
                    <span class="material-icons">person</span>
                    <span>${v.name}</span>
                </div>
            `).join('')}
            ${task.volunteers.length === 0 ? '<div class="task-volunteer-item"><span class="material-icons">person</span><span>empty</span></div>' : ''}
        </div>
        <button class="task-info-btn">
            <span class="material-icons">info</span>
        </button>
    `;
    
    taskDiv.addEventListener('click', (e) => {
        if (!e.target.closest('.task-info-btn')) {
            openTaskDetailModal(task);
        }
    });
    
    const infoBtn = taskDiv.querySelector('.task-info-btn');
    infoBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        openTaskDetailModal(task);
    });
    
    return taskDiv;
}

function openTaskDetailModal(task) {
    const modal = document.getElementById('taskModal');
    
    // Populate modal with task details
    document.getElementById('taskTitle').textContent = task.title;
    document.getElementById('taskTime').textContent = `${formatDate(task.date)} ${task.startTime} - ${task.endTime}`;
    document.getElementById('taskLocation').textContent = task.location || 'Niet opgegeven';
    document.getElementById('taskCategory').textContent = task.category || task.title;
    
    // Populate volunteers
    const volunteersList = document.getElementById('volunteersList');
    volunteersList.innerHTML = '';
    
    if (task.volunteers.length === 0) {
        volunteersList.innerHTML = '<p style="color: var(--md-sys-color-on-surface-variant);">Nog geen vrijwilligers ingeschreven</p>';
    } else {
        task.volunteers.forEach(volunteer => {
            const volunteerDiv = document.createElement('div');
            volunteerDiv.className = 'volunteer-item';
            volunteerDiv.innerHTML = `
                <span class="material-icons">person</span>
                <span class="volunteer-name">${volunteer.name}</span>
            `;
            volunteersList.appendChild(volunteerDiv);
        });
    }
    
    // Setup action buttons
    const subscribeBtn = document.getElementById('subscribeBtn');
    const unsubscribeBtn = document.getElementById('unsubscribeBtn');
    
    const isSubscribed = task.volunteers.some(v => v.username === currentUser.username);
    const isFull = task.volunteers.length >= task.maxVolunteers;
    
    if (isSubscribed) {
        subscribeBtn.style.display = 'none';
        unsubscribeBtn.style.display = 'block';
        unsubscribeBtn.onclick = () => handleUnsubscribe(task);
    } else {
        unsubscribeBtn.style.display = 'none';
        if (isFull) {
            subscribeBtn.style.display = 'none';
        } else {
            subscribeBtn.style.display = 'block';
            subscribeBtn.onclick = () => handleSubscribe(task);
        }
    }
    
    modal.classList.add('active');
}

function handleSubscribe(task) {
    // Check for time conflicts
    const allTasks = getAllTasks();
    const userTasks = allTasks.filter(t => 
        t.volunteers.some(v => v.username === currentUser.username) &&
        t.date === task.date
    );
    
    for (let userTask of userTasks) {
        if (tasksOverlap(task, userTask)) {
            showAlert('Je kunt niet inschrijven voor overlappende taken!');
            return;
        }
    }
    
    // Check if task is full
    if (task.volunteers.length >= task.maxVolunteers) {
        showAlert('Deze taak is al vol!');
        return;
    }
    
    // Add user to task
    task.volunteers.push({
        username: currentUser.username,
        name: currentUser.username
    });
    
    // Update task status
    updateTaskStatus(task);
    
    // Save tasks
    saveTasks();
    
    // Close modal and refresh view
    closeTaskModal();
    updateCalendarDisplay();
    
    alert('Je bent succesvol ingeschreven!');
}

function handleUnsubscribe(task) {
    // Check if task is within 24 hours
    const taskDateTime = new Date(task.date + ' ' + task.startTime);
    const now = new Date();
    const hoursDiff = (taskDateTime - now) / (1000 * 60 * 60);
    
    if (hoursDiff < 24) {
        showAlert('Uitschrijven kan alleen 24 uur van te voren');
        return;
    }
    
    // Remove user from task
    task.volunteers = task.volunteers.filter(v => v.username !== currentUser.username);
    
    // Update task status
    updateTaskStatus(task);
    
    // Save tasks
    saveTasks();
    
    // Close modal and refresh view
    closeTaskModal();
    updateCalendarDisplay();
    
    alert('Je bent uitgeschreven van deze taak.');
}

function tasksOverlap(task1, task2) {
    const start1 = timeToMinutes(task1.startTime);
    const end1 = timeToMinutes(task1.endTime);
    const start2 = timeToMinutes(task2.startTime);
    const end2 = timeToMinutes(task2.endTime);
    
    return (start1 < end2 && end1 > start2);
}

function timeToMinutes(time) {
    const [hours, minutes] = time.split(':').map(Number);
    return hours * 60 + minutes;
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    const days = ['Zo', 'Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za'];
    const months = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];
    
    return `${days[date.getDay()]} ${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear()}`;
}
