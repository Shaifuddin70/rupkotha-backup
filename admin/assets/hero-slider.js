document.addEventListener('DOMContentLoaded', function () {
    const tableContainer = document.getElementById('hero-slider-table-container');
    const editModalEl = document.getElementById('editItemModal');
    const editModal = new bootstrap.Modal(editModalEl);
    const editForm = document.getElementById('editItemForm');

    // Function to load table content
    async function loadTable(page = 1) {
        try {
            const response = await fetch(`ajax/hero_slider_partial.php?page=${page}`);
            if (!response.ok) throw new Error('Network response was not ok.');
            tableContainer.innerHTML = await response.text();
        } catch (error) {
            tableContainer.innerHTML = `<div class="alert alert-danger">Failed to load content: ${error.message}</div>`;
        }
    }

    // Event Delegation for all actions
    tableContainer.addEventListener('click', async (e) => {
        // Pagination links
        if (e.target.matches('.page-link')) {
            e.preventDefault();
            const page = new URL(e.target.href).searchParams.get('page');
            loadTable(page);
        }

        // Delete button
        if (e.target.matches('.delete-btn')) {
            e.preventDefault();
            const itemId = e.target.dataset.id;
            if (confirm('Are you sure you want to delete this item?')) {
                const formData = new FormData();
                formData.append('id', itemId);
                formData.append('delete_item', '1');

                try {
                    const response = await fetch('ajax/hero_slider_actions.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.status === 'success') {
                        loadTable(); // Reload table
                        alert(result.message);
                    } else {
                        alert(`Error: ${result.message}`);
                    }
                } catch (error) {
                    alert(`An error occurred: ${error.message}`);
                }
            }
        }

        // Edit button
        if (e.target.matches('.edit-btn')) {
            e.preventDefault();
            const itemId = e.target.dataset.id;
            try {
                const response = await fetch(`ajax/get_hero_item.php?id=${itemId}`);
                const result = await response.json();
                if (result.status === 'success') {
                    editModalEl.querySelector('.modal-body').innerHTML = result.html;
                    editModal.show();
                } else {
                    alert(`Error: ${result.message}`);
                }
            } catch (error) {
                alert(`An error occurred: ${error.message}`);
            }
        }
    });
    tableContainer.addEventListener('change', async (e) => {
        const toggle = e.target.closest('.status-toggle');
        if (toggle) {
            const itemId = toggle.dataset.id;
            const newStatus = toggle.checked ? 1 : 0;

            const formData = new FormData();
            formData.append('id', itemId);
            formData.append('status', newStatus);

            try {
                const response = await fetch('ajax/toggle_hero_status.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    // Update the badge text and color visually
                    const badge = toggle.nextElementSibling;
                    badge.textContent = newStatus ? 'Active' : 'Inactive';
                    badge.classList.toggle('bg-success', newStatus === 1);
                    badge.classList.toggle('bg-secondary', newStatus === 0);
                } else {
                    alert(`Error: ${result.message}`);
                    toggle.checked = !toggle.checked; // Revert the toggle on failure
                }
            } catch (error) {
                alert(`An error occurred: ${error.message}`);
            }
        }
    });
    // Handle the Edit Form submission
    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(editForm);
        formData.append('update_item', '1');

        try {
            const response = await fetch('ajax/hero_slider_actions.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.status === 'success') {
                editModal.hide();
                loadTable(); // Reload table on success
                alert(result.message);
            } else {
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            alert(`An error occurred: ${error.message}`);
        }
    });

    // Initial load
    loadTable();
});