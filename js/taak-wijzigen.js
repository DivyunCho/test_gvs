document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('taskCategory');
    const newCategoryInput = document.getElementById('newCategoryInput');
    const newCategoryName = document.getElementById('newCategoryName');

    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            if (this.value === 'new') {
                newCategoryInput.style.display = 'block';
                newCategoryName.required = true;
                newCategoryName.focus();
            } else {
                newCategoryInput.style.display = 'none';
                newCategoryName.required = false;
                newCategoryName.value = '';
            }
        });

        if (categorySelect.value === 'new') {
            newCategoryInput.style.display = 'block';
            newCategoryName.required = true;
        }
    }
    
    const updateTypeRadios = document.querySelectorAll('input[name="updateType"]');
    const repeatTypeGroup = document.getElementById('repeatTypeGroup');
    
    if (updateTypeRadios.length > 0 && repeatTypeGroup) {
        updateTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'all') {
                    repeatTypeGroup.style.display = 'block';
                } else {
                    repeatTypeGroup.style.display = 'none';
                }
            });
        });
    }
});
