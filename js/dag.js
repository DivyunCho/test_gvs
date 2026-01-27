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
                    const selectedDate = typeof selectedDateStr !== 'undefined' ? selectedDateStr : '';
                    const action = selectedDate ? `dag.php?date=${selectedDate}` : 'dag.php';
                    adminVolunteersList.innerHTML += `
                        <form method="POST" action="${action}" style="display: flex; align-items: center; justify-content: space-between; padding: 8px; background: white; border-radius: 4px; margin-bottom: 8px; border: 1px solid #ddd;">
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

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('taskModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeTaskModal();
        });
    }
});