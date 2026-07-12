const initializeRoleFields = () => {
    const roleSelect = document.getElementById('role-select');
    const studentField = document.getElementById('student-link-field');
    const professorField = document.getElementById('professor-classrooms-field');

    if (!roleSelect || !studentField || !professorField) {
        return;
    }

    const syncRoleFields = () => {
        studentField.classList.toggle('hidden', roleSelect.value !== 'Student');
        professorField.classList.toggle('hidden', roleSelect.value !== 'Professor');
    };

    if (roleSelect.dataset.roleFieldsInitialized !== 'true') {
        roleSelect.dataset.roleFieldsInitialized = 'true';
        roleSelect.addEventListener('change', syncRoleFields);
    }

    syncRoleFields();
};

document.addEventListener('DOMContentLoaded', initializeRoleFields);
document.addEventListener('livewire:navigated', initializeRoleFields);
