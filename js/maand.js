let currentDate = new Date(typeof selectedDateStr !== 'undefined' ? selectedDateStr : new Date());
let currentTaskId = null;

function openTaskModal(taskId) {
    const task = allTaskDetails[taskId];
    if (!task) return;
    
    document.getElementById('modalTaskTitle').textContent = task.title;
    document.getElementById('modalTaskTime').textContent = 
        task.start_datetime.split(' ')[0] + ' ' + 
        task.start_datetime.split(' ')[1].substring(0,5) + ' - ' + 
        task.end_datetime.split(' ')[1].substring(0,5);
    document.getElementById('modalTaskCapacity').textContent = 
        task.signup_count + '/' + task.capacity + ' vrijwilligers';
    document.getElementById('modalTaskDescription').textContent = task.description || 'Geen beschrijving';
    
    // Alleen voor reguliere gebruikers
    const modalTaskId = document.getElementById('modalTaskId');
    if (modalTaskId) modalTaskId.value = taskId;
    
    const modalTaskId2 = document.getElementById('modalTaskId2');
    if (modalTaskId2) modalTaskId2.value = taskId;
    
    const deleteField = document.getElementById('modalTaskIdDelete');
    if (deleteField) deleteField.value = taskId;
    
    const addUserField = document.getElementById('modalTaskIdAddUser');
    if (addUserField) addUserField.value = taskId;
    
    const volunteersList = document.getElementById('modalVolunteersList');
    volunteersList.innerHTML = '';
    if (task.volunteers && task.volunteers.length > 0) {
        task.volunteers.forEach(v => {
            volunteersList.innerHTML += `
                <div class="volunteer-item">
                    <span class="material-icons">person</span>
                    <span class="volunteer-name">${v.username}</span>
                </div>
            `;
        });
    } else {
        volunteersList.innerHTML = '<p style="color: #666;">Nog geen inschrijvingen</p>';
    }
    
    // Admin: Toon lijst met verwijder opties
    if (typeof isAdmin !== 'undefined' && isAdmin) {
        const adminVolunteersList = document.getElementById('adminVolunteersList');
        if (adminVolunteersList) {
            adminVolunteersList.innerHTML = '';
            if (task.volunteers && task.volunteers.length > 0) {
                task.volunteers.forEach(v => {
                    adminVolunteersList.innerHTML += `
                        <form method="POST" action="maand.php" style="display: flex; align-items: center; justify-content: space-between; padding: 8px; background: white; border-radius: 4px; margin-bottom: 8px; border: 1px solid #ddd;">
                            <input type="hidden" name="task_id" value="${taskId}">
                            <input type="hidden" name="user_id" value="${v.user_id}">
                            <input type="hidden" name="action" value="admin_remove_user">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="material-icons" style="color: #666;">person</span>
                                <span style="font-weight: 500;">${v.username}</span>
                            </div>
                            <button type="submit" class="btn-danger" style="padding: 4px 12px; background: #e74c3c; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;" 
                                    onclick="return confirm('Weet je zeker dat je ${v.username} wilt verwijderen van deze taak?')">
                                <span class="material-icons" style="font-size: 16px; vertical-align: middle;">person_remove</span>
                                Verwijderen
                            </button>
                        </form>
                    `;
                });
            } else {
                adminVolunteersList.innerHTML = '<p style="color: #999; font-style: italic; padding: 10px;">Geen vrijwilligers ingeschreven</p>';
            }
        }
    }
    
    const subscribeBtn = document.getElementById('modalSubscribeBtn');
    const unsubscribeBtn = document.getElementById('modalUnsubscribeBtn');
    
    // Admin mag niet inschrijven/uitschrijven
    if (typeof isAdmin !== 'undefined' && !isAdmin && subscribeBtn && unsubscribeBtn) {
        if (task.is_subscribed) {
            subscribeBtn.style.display = 'none';
            unsubscribeBtn.style.display = 'inline-block';
        } else if (task.is_full) {
            subscribeBtn.style.display = 'none';
            unsubscribeBtn.style.display = 'none';
        } else {
            subscribeBtn.style.display = 'inline-block';
            unsubscribeBtn.style.display = 'none';
        }
    }
    
    document.getElementById('taskModal').classList.add('active');
}

function closeTaskModal() {
    document.getElementById('taskModal').classList.remove('active');
}

// Initialize calendar
document.addEventListener('DOMContentLoaded', function() {
    loadMonthView();
    
    // Navigation
    document.getElementById('prevPeriod').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        const dateStr = currentDate.toISOString().split('T')[0];
        window.location.href = 'maand.php?date=' + dateStr;
    });
    
    document.getElementById('nextPeriod').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        const dateStr = currentDate.toISOString().split('T')[0];
        window.location.href = 'maand.php?date=' + dateStr;
    });
    
    document.getElementById('todayBtn').addEventListener('click', () => {
        window.location.href = 'maand.php';
    });
    
    // View toggles
    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const view = btn.getAttribute('data-view');
            if (view === 'day') window.location.href = 'dag.php';
            if (view === 'week') window.location.href = 'week.php';
        });
    });
    
    // Modal handlers - closeTaskModal is nu in maand.php gedefinieerd
    document.getElementById('taskModal').addEventListener('click', (e) => {
        if (e.target.id === 'taskModal') closeTaskModal();
    });
    
    // Subscribe/unsubscribe buttons werken nu via forms (niet via JavaScript)
    
    document.getElementById('closeAlertModal').addEventListener('click', closeAlertModal);
    document.getElementById('closeAlertBtn').addEventListener('click', closeAlertModal);
});

function loadMonthView() {
    const maanden = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 
                     'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
    
    const monthName = maanden[currentDate.getMonth()];
    const year = currentDate.getFullYear();
    
    document.getElementById('currentPeriodText').textContent = monthName.charAt(0).toUpperCase() + monthName.slice(1);
    document.getElementById('monthYearText').textContent = year;
    
    // Get month start and end
    const monthStart = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const monthEnd = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    
    // Get first day of month (0 = Sunday, we want Monday = 0)
    let firstDay = monthStart.getDay() - 1;
    if (firstDay === -1) firstDay = 6; // Sunday becomes 6
    
    // Calculate calendar start (can be in previous month)
    const calendarStart = new Date(monthStart);
    calendarStart.setDate(calendarStart.getDate() - firstDay);
    
    // Calculate calendar end (42 days for 6 weeks)
    const calendarEnd = new Date(calendarStart);
    calendarEnd.setDate(calendarEnd.getDate() + 41);
    
    loadMonthTasks(calendarStart, calendarEnd, monthStart, monthEnd);
}

function loadMonthTasks(calendarStart, calendarEnd, monthStart, monthEnd) {
    const startDateStr = formatDate(calendarStart);
    const endDateStr = formatDate(calendarEnd);
    
    const tasksInRange = Object.values(allTaskDetails).filter(task => {
        return task.date >= startDateStr && task.date <= endDateStr;
    });
    
    console.log('Month tasks loaded:', tasksInRange.length, 'tasks from', startDateStr, 'to', endDateStr);
    console.log('All task details:', Object.keys(allTaskDetails).length, 'total tasks');
    renderMonthGrid(calendarStart, monthStart, monthEnd, tasksInRange);
}

function renderMonthGrid(calendarStart, monthStart, monthEnd, tasks) {
    const monthGrid = document.getElementById('monthGrid');
    monthGrid.innerHTML = '';
    
    // Group tasks by date
    const tasksByDate = {};
    tasks.forEach(task => {
        const dateKey = task.date;
        if (!tasksByDate[dateKey]) {
            tasksByDate[dateKey] = [];
        }
        tasksByDate[dateKey].push(task);
    });
    
    // Create 42 cells (6 weeks)
    for (let i = 0; i < 42; i++) {
        const cellDate = new Date(calendarStart);
        cellDate.setDate(cellDate.getDate() + i);
        
        const isCurrentMonth = cellDate >= monthStart && cellDate <= monthEnd;
        const isToday = cellDate.toDateString() === new Date().toDateString();
        
        const cell = document.createElement('div');
        cell.className = 'month-cell';
        if (!isCurrentMonth) cell.classList.add('other-month');
        if (isToday) cell.classList.add('today');
        
        const dateKey = formatDate(cellDate);
        const dayTasks = tasksByDate[dateKey] || [];
        
        cell.innerHTML = `<div class="date-number">${cellDate.getDate()}</div>`;
        
        // Add tasks (max 3 visible)
        dayTasks.slice(0, 3).forEach(task => {
            const taskEvent = document.createElement('div');
            taskEvent.className = `task-event ${getStatusClass(task)}`;
            taskEvent.innerHTML = `
                <span class="task-title">${task.title}</span>
                <button class="task-info-btn" onclick="openTaskModal(${task.task_id})">
                    <span class="material-icons">info</span>
                </button>
            `;
            cell.appendChild(taskEvent);
        });
        
        // Show "+X more" if more than 3 tasks
        if (dayTasks.length > 3) {
            const moreIndicator = document.createElement('div');
            moreIndicator.className = 'more-tasks';
            moreIndicator.textContent = `+${dayTasks.length - 3} meer`;
            cell.appendChild(moreIndicator);
        }
        
        monthGrid.appendChild(cell);
    }
}

function getStatusClass(task) {
    if (task.is_subscribed) return 'status-available';
    if (task.signup_count >= task.capacity) return 'status-full';
    return 'status-not-available';
}

// openTaskModal functie is nu in maand.php gedefinieerd (gebruikt pre-loaded data)

// closeTaskModal functie is nu in maand.php gedefinieerd

function showAlert(message) {
    document.getElementById('alertMessage').textContent = message;
    document.getElementById('alertModal').classList.add('active');
}

function closeAlertModal() {
    document.getElementById('alertModal').classList.remove('active');
}

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}
