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
    if (isAdmin) {
        const adminVolunteersList = document.getElementById('adminVolunteersList');
        if (adminVolunteersList) {
            adminVolunteersList.innerHTML = '';
            if (task.volunteers && task.volunteers.length > 0) {
                task.volunteers.forEach(v => {
                    adminVolunteersList.innerHTML += `
                        <form method="POST" action="week.php" style="display: flex; align-items: center; justify-content: space-between; padding: 8px; background: white; border-radius: 4px; margin-bottom: 8px; border: 1px solid #ddd;">
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
    if (!isAdmin && subscribeBtn && unsubscribeBtn) {
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

document.addEventListener('DOMContentLoaded', function() {
    loadWeekView();
    
    document.getElementById('prevPeriod').addEventListener('click', () => {
        currentDate.setDate(currentDate.getDate() - 7);
        const dateStr = currentDate.toISOString().split('T')[0];
        window.location.href = 'week.php?date=' + dateStr;
    });
    
    document.getElementById('nextPeriod').addEventListener('click', () => {
        currentDate.setDate(currentDate.getDate() + 7);
        const dateStr = currentDate.toISOString().split('T')[0];
        window.location.href = 'week.php?date=' + dateStr;
    });
    
    document.getElementById('todayBtn').addEventListener('click', () => {
        window.location.href = 'week.php';
    });
    
    // View toggles
    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const view = btn.getAttribute('data-view');
            if (view === 'day') window.location.href = 'dag.php';
            if (view === 'month') window.location.href = 'maand.php';
        });
    });
    
    // Modal handlers - closeTaskModal is nu in week.php gedefinieerd
    document.getElementById('taskModal').addEventListener('click', (e) => {
        if (e.target.id === 'taskModal') closeTaskModal();
    });
    
    // Subscribe/unsubscribe buttons werken nu via forms (niet via JavaScript)
    
    document.getElementById('closeAlertModal').addEventListener('click', closeAlertModal);
    document.getElementById('closeAlertBtn').addEventListener('click', closeAlertModal);
});

function getWeekStart(date) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day + (day == 0 ? -6 : 1); // Maandag als start
    return new Date(d.setDate(diff));
}

function loadWeekView() {
    const weekStart = getWeekStart(currentDate);
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);
    
    // Update header
    const maanden = ['jan', 'feb', 'ma', 'ap', 'mei', 'juni', 
                     'juli', 'aug', 'sep', 'okt', 'nov', 'dec'];
    
    const startDay = weekStart.getDate();
    const endDay = weekEnd.getDate();
    const startMonth = maanden[weekStart.getMonth()];
    const endMonth = maanden[weekEnd.getMonth()];
    const year = weekStart.getFullYear();
    
    const periodText = startMonth === endMonth 
        ? `${startDay} - ${endDay} ${startMonth}`
        : `${startDay} ${startMonth} - ${endDay} ${endMonth}`;
    
    document.getElementById('currentPeriodText').textContent = periodText;
    document.getElementById('monthYearText').textContent = `${startMonth.charAt(0).toUpperCase() + startMonth.slice(1)} ${year}`;
    
    // Update week header
    updateWeekHeader(weekStart);
    
    // Load tasks
    loadWeekTasks(weekStart, weekEnd);
}

function updateWeekHeader(weekStart) {
    const dagen = ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'];
    const header = document.getElementById('weekDaysHeader');
    header.innerHTML = '';
    
    for (let i = 0; i < 7; i++) {
        const date = new Date(weekStart);
        date.setDate(date.getDate() + i);
        
        const dayHeader = document.createElement('div');
        dayHeader.className = 'week-day-header';
        dayHeader.innerHTML = `
            <div class="week-day-name">${dagen[i]}</div>
            <div class="week-day-date">${date.getDate()}</div>
        `;
        header.appendChild(dayHeader);
    }
}

function loadWeekTasks(startDate, endDate) {
    const startDateStr = formatDate(startDate);
    const endDateStr = formatDate(endDate);
    
    const tasksInRange = Object.values(allTaskDetails).filter(task => {
        return task.date >= startDateStr && task.date <= endDateStr;
    });
    
    console.log('Week tasks loaded:', tasksInRange.length, 'tasks from', startDateStr, 'to', endDateStr);
    renderWeekTasks(tasksInRange, startDate);
}

function renderWeekTasks(tasks, weekStart) {
    // Clear existing tasks
    document.querySelectorAll('.week-column .week-events-container').forEach(container => {
        container.innerHTML = '';
    });
    
    tasks.forEach(task => {
        const taskDate = new Date(task.date + 'T00:00:00');
        const weekStartDate = new Date(weekStart);
        weekStartDate.setHours(0, 0, 0, 0);
        taskDate.setHours(0, 0, 0, 0);
        
        const dayIndex = Math.floor((taskDate - weekStartDate) / (1000 * 60 * 60 * 24));
        
        if (dayIndex >= 0 && dayIndex < 7) {
            const column = document.querySelectorAll('.week-column')[dayIndex];
            if (column) {
                const container = column.querySelector('.week-events-container');
                const taskCard = createWeekTaskCard(task);
                container.appendChild(taskCard);
            }
        }
    });
}

function createWeekTaskCard(task) {
    const [hours, minutes] = task.start_time.split(':');
    const topPosition = (parseInt(hours) * 60) + parseInt(minutes); // minutes from midnight
    const duration = task.duration || 120; // default 2 hours
    
    const card = document.createElement('div');
    card.className = `task-card ${getStatusClass(task)}`;
    card.style.top = topPosition + 'px';
    card.style.height = duration + 'px';
    card.dataset.taskId = task.task_id;
    
    const endHour = Math.floor((parseInt(hours) * 60 + parseInt(minutes) + duration) / 60);
    const endMinute = (parseInt(hours) * 60 + parseInt(minutes) + duration) % 60;
    const endTime = `${String(endHour).padStart(2, '0')}:${String(endMinute).padStart(2, '0')}`;
    
    card.innerHTML = `
        <div class="task-title">${task.title}</div>
        <div class="task-time">${task.start_time} - ${endTime}</div>
        <button class="task-info-btn" onclick="openTaskModal(${task.task_id})">
            <span class="material-icons">info</span>
        </button>
        ${isAdmin ? `
            <button class="task-delete-btn admin-only" onclick="deleteTask(${task.task_id}, event)">
                <span class="material-icons">delete</span>
            </button>
        ` : ''}
    `;
    
    return card;
}

function getStatusClass(task) {
    if (task.is_subscribed) return 'status-available';
    if (task.signup_count >= task.capacity) return 'status-full';
    return 'status-not-available';
}

function deleteTask(taskId, event) {
    event.stopPropagation();
    
    if (!confirm('Weet je zeker dat je deze taak wilt verwijderen?')) {
        return;
    }
    
    window.location.href = `taak-verwijderen.php?id=${taskId}`;
}

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
