document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('taskCategory');
    const newCategoryInput = document.getElementById('newCategoryInput');
    const newCategoryName = document.getElementById('newCategoryName');

    if (!categorySelect) return;

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
});
