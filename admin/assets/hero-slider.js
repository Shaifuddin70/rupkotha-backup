document.addEventListener("DOMContentLoaded", function () {
  const tableContainer = document.getElementById("hero-slider-table-container");
  const editModalEl = document.getElementById("editItemModal");
  const editModal = new bootstrap.Modal(editModalEl);
  const editForm = document.getElementById("editItemForm");

  // Function to load table content
  async function loadTable(page = 1) {
    try {
      const response = await fetch(`ajax/hero_slider_partial.php?page=${page}`);
      if (!response.ok) throw new Error("Network response was not ok.");
      tableContainer.innerHTML = await response.text();
    } catch (error) {
      tableContainer.innerHTML = `<div class="alert alert-danger">Failed to load content: ${error.message}</div>`;
    }
  }

  // Event Delegation for all actions
  tableContainer.addEventListener("click", async (e) => {
    // Pagination links (support modern link class)
    const pageLink = e.target.closest(".page-link, .page-link-modern");
    if (pageLink) {
      e.preventDefault();
      const page = new URL(pageLink.href).searchParams.get("page");
      loadTable(page);
      return;
    }

    // Delete button (support clicks on icon inside button)
    const deleteBtn = e.target.closest(".delete-btn");
    if (deleteBtn) {
      e.preventDefault();
      const itemId = deleteBtn.dataset.id;
      if (confirm("Are you sure you want to delete this item?")) {
        const formData = new FormData();
        formData.append("id", itemId);
        formData.append("delete_item", "1");

        try {
          const response = await fetch("ajax/hero_slider_actions.php", {
            method: "POST",
            body: formData,
          });
          const result = await response.json();
          if (result.status === "success") {
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

    // Edit button (support clicks on icon inside button)
    const editBtn = e.target.closest(".edit-btn");
    if (editBtn) {
      e.preventDefault();
      const itemId = editBtn.dataset.id;
      try {
        const response = await fetch(`ajax/get_hero_item.php?id=${itemId}`);
        const result = await response.json();
        if (result.status === "success") {
          editModalEl.querySelector(".modal-body").innerHTML = result.html;
          editModal.show();
        } else {
          alert(`Error: ${result.message}`);
        }
      } catch (error) {
        alert(`An error occurred: ${error.message}`);
      }
    }
  });
  tableContainer.addEventListener("change", async (e) => {
    const toggle = e.target.closest(".status-toggle");
    if (toggle) {
      const itemId = toggle.dataset.id;
      const newStatus = toggle.checked ? 1 : 0;

      const formData = new FormData();
      formData.append("id", itemId);
      formData.append("status", newStatus);

      try {
        const response = await fetch("ajax/toggle_hero_status.php", {
          method: "POST",
          body: formData,
        });
        const result = await response.json();
        if (result.status === "success") {
          // Update the badge text and classes visually
          const cell = toggle.closest("td");
          const badge = cell
            ? cell.querySelector(".badge, .badge-modern")
            : null;
          if (badge) {
            badge.textContent = newStatus ? "Active" : "Inactive";
            // Switch between modern badge styles
            badge.classList.toggle("badge-status", newStatus === 1);
            badge.classList.toggle("badge-deleted", newStatus === 0);
            // Clean older bootstrap bg classes if present
            badge.classList.remove("bg-success", "bg-secondary");
          }
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
  editForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(editForm);
    formData.append("update_item", "1");

    try {
      const response = await fetch("ajax/hero_slider_actions.php", {
        method: "POST",
        body: formData,
      });
      const result = await response.json();

      if (result.status === "success") {
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
