/**
 * Valorol Admin Panel - JavaScript
 * ─────────────────────────────────
 * Handles all CRUD operations, modals, and UI interactions.
 */

// ── State ──
let coaEntries = [];
let editingId = null;
let deletingId = null;

// ── DOM References ──
const coaTableBody = document.getElementById("coaTableBody");
const totalCount = document.getElementById("totalCount");
const searchInput = document.getElementById("searchInput");
const coaForm = document.getElementById("coaForm");
const passwordForm = document.getElementById("passwordForm");

// ── Init ──
document.addEventListener("DOMContentLoaded", () => {
  loadEntries();
  setupFileUpload();
  setupSearch();
});

// ═══════════════════════════════════════
// DATA LOADING
// ═══════════════════════════════════════

async function loadEntries() {
  try {
    const res = await fetch("api.php?action=list");
    const json = await res.json();

    if (json.success) {
      coaEntries = json.data || [];
      renderTable(coaEntries);
      totalCount.textContent = coaEntries.length;
    } else {
      showToast(json.message || "Failed to load entries", "error");
    }
  } catch (err) {
    showToast("Could not connect to server. Check your setup.", "error");
    coaTableBody.innerHTML = `
      <tr>
        <td colspan="6" class="empty-state">
          <i class="bi bi-wifi-off"></i>
          <p>Connection error. Make sure db.php is configured.</p>
        </td>
      </tr>
    `;
  }
}

// ═══════════════════════════════════════
// TABLE RENDERING
// ═══════════════════════════════════════

function renderTable(data) {
  if (data.length === 0) {
    coaTableBody.innerHTML = `
      <tr>
        <td colspan="6" class="empty-state">
          <i class="bi bi-inbox"></i>
          <p>No COA entries found. Click "Add New COA" to get started.</p>
        </td>
      </tr>
    `;
    return;
  }

  coaTableBody.innerHTML = data
    .map(
      (item, index) => `
    <tr>
      <td>${index + 1}</td>
      <td class="td-product">${escapeHtml(item.product)}</td>
      <td>${escapeHtml(item.batch)}</td>
      <td>${escapeHtml(item.code)}</td>
      <td>
        <div class="td-file">
          <i class="bi bi-file-earmark-pdf-fill"></i>
          <a href="../assets/coa/${encodeURIComponent(item.file)}" target="_blank" title="View PDF">
            ${escapeHtml(item.file.length > 25 ? item.file.substring(0, 25) + "..." : item.file)}
          </a>
        </div>
      </td>
      <td>
        <div class="action-group">
          <button class="btn-action btn-action-edit" onclick="openEditModal(${item.id})" title="Edit">
            <i class="bi bi-pencil-fill"></i>
          </button>
          <button class="btn-action btn-action-delete" onclick="openDeleteConfirm(${item.id})" title="Delete">
            <i class="bi bi-trash3-fill"></i>
          </button>
        </div>
      </td>
    </tr>
  `
    )
    .join("");
}

// ═══════════════════════════════════════
// SEARCH
// ═══════════════════════════════════════

function setupSearch() {
  searchInput.addEventListener("input", () => {
    const query = searchInput.value.trim().toLowerCase();
    if (query === "") {
      renderTable(coaEntries);
      return;
    }
    const filtered = coaEntries.filter(
      (item) =>
        item.product.toLowerCase().includes(query) ||
        item.batch.toLowerCase().includes(query) ||
        item.code.toLowerCase().includes(query)
    );
    renderTable(filtered);
  });
}

// ═══════════════════════════════════════
// ADD / EDIT MODAL
// ═══════════════════════════════════════

function openAddModal() {
  editingId = null;
  document.getElementById("modalTitle").textContent = "Add New COA";
  document.getElementById("saveBtnText").textContent = "Save Entry";
  document.getElementById("entryId").value = "";
  document.getElementById("productName").value = "";
  document.getElementById("batchNo").value = "";
  document.getElementById("codeNo").value = "";
  document.getElementById("pdfFile").value = "";
  document.getElementById("pdfRequired").style.display = "inline";
  document.getElementById("currentFileInfo").style.display = "none";
  resetFileUploadUI();
  openModal("coaModal");
}

function openEditModal(id) {
  const entry = coaEntries.find((e) => e.id === id);
  if (!entry) return;

  editingId = id;
  document.getElementById("modalTitle").textContent = "Edit COA Entry";
  document.getElementById("saveBtnText").textContent = "Update Entry";
  document.getElementById("entryId").value = id;
  document.getElementById("productName").value = entry.product;
  document.getElementById("batchNo").value = entry.batch;
  document.getElementById("codeNo").value = entry.code;
  document.getElementById("pdfFile").value = "";
  document.getElementById("pdfRequired").style.display = "none";
  document.getElementById("currentFileInfo").style.display = "flex";
  document.getElementById("currentFileName").textContent = entry.file;
  resetFileUploadUI();
  openModal("coaModal");
}

function closeModal() {
  closeOverlay("coaModal");
}

// ── Form Submit ──
coaForm.addEventListener("submit", async (e) => {
  e.preventDefault();

  const formData = new FormData(coaForm);
  formData.append("action", editingId ? "edit" : "add");

  const saveBtn = document.getElementById("saveBtn");
  saveBtn.disabled = true;
  document.getElementById("saveBtnText").textContent = "Saving...";

  try {
    const res = await fetch("api.php", {
      method: "POST",
      body: formData,
    });
    const json = await res.json();

    if (json.success) {
      showToast(json.message, "success");
      closeModal();
      await loadEntries();
    } else {
      showToast(json.message || "Operation failed", "error");
    }
  } catch (err) {
    showToast("Network error. Please try again.", "error");
  } finally {
    saveBtn.disabled = false;
    document.getElementById("saveBtnText").textContent = editingId
      ? "Update Entry"
      : "Save Entry";
  }
});

// ═══════════════════════════════════════
// DELETE MODAL
// ═══════════════════════════════════════

function openDeleteConfirm(id) {
  const entry = coaEntries.find((e) => e.id === id);
  if (!entry) return;

  deletingId = id;
  document.getElementById("deleteProductName").textContent = entry.product;
  openModal("deleteModal");

  // Re-bind confirm button
  const confirmBtn = document.getElementById("confirmDeleteBtn");
  const newBtn = confirmBtn.cloneNode(true);
  confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);
  newBtn.addEventListener("click", confirmDelete);
}

function closeDeleteModal() {
  closeOverlay("deleteModal");
  deletingId = null;
}

async function confirmDelete() {
  if (!deletingId) return;

  const btn = document.getElementById("confirmDeleteBtn");
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Deleting...';

  try {
    const formData = new FormData();
    formData.append("action", "delete");
    formData.append("id", deletingId);

    const res = await fetch("api.php", {
      method: "POST",
      body: formData,
    });
    const json = await res.json();

    if (json.success) {
      showToast(json.message, "success");
      closeDeleteModal();
      await loadEntries();
    } else {
      showToast(json.message || "Delete failed", "error");
    }
  } catch (err) {
    showToast("Network error. Please try again.", "error");
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-trash3-fill"></i> Delete';
  }
}

// ═══════════════════════════════════════
// PASSWORD MODAL
// ═══════════════════════════════════════

function openPasswordModal() {
  document.getElementById("currentPassword").value = "";
  document.getElementById("newPassword").value = "";
  document.getElementById("confirmPassword").value = "";
  openModal("passwordModal");
}

function closePasswordModal() {
  closeOverlay("passwordModal");
}

passwordForm.addEventListener("submit", async (e) => {
  e.preventDefault();

  const formData = new FormData(passwordForm);
  formData.append("action", "change_password");

  try {
    const res = await fetch("api.php", {
      method: "POST",
      body: formData,
    });
    const json = await res.json();

    if (json.success) {
      showToast(json.message, "success");
      closePasswordModal();
    } else {
      showToast(json.message || "Password change failed", "error");
    }
  } catch (err) {
    showToast("Network error. Please try again.", "error");
  }
});

// ═══════════════════════════════════════
// FILE UPLOAD UI
// ═══════════════════════════════════════

function setupFileUpload() {
  const zone = document.getElementById("fileUploadZone");
  const input = document.getElementById("pdfFile");
  const content = document.getElementById("fileUploadContent");

  input.addEventListener("change", () => {
    if (input.files.length > 0) {
      const file = input.files[0];
      zone.classList.add("has-file");
      content.querySelector(".file-upload-text").textContent = file.name;
      content.querySelector(".file-upload-hint").textContent = formatFileSize(
        file.size
      );
    } else {
      resetFileUploadUI();
    }
  });

  // Drag and drop
  zone.addEventListener("dragover", (e) => {
    e.preventDefault();
    zone.classList.add("drag-over");
  });

  zone.addEventListener("dragleave", () => {
    zone.classList.remove("drag-over");
  });

  zone.addEventListener("drop", (e) => {
    e.preventDefault();
    zone.classList.remove("drag-over");
    if (e.dataTransfer.files.length > 0) {
      input.files = e.dataTransfer.files;
      input.dispatchEvent(new Event("change"));
    }
  });
}

function resetFileUploadUI() {
  const zone = document.getElementById("fileUploadZone");
  const content = document.getElementById("fileUploadContent");
  zone.classList.remove("has-file", "drag-over");
  content.querySelector(
    ".file-upload-text"
  ).textContent = "Click to select or drag a PDF file";
  content.querySelector(".file-upload-hint").textContent = "Max file size: 10MB";
}

// ═══════════════════════════════════════
// MODAL HELPERS
// ═══════════════════════════════════════

function openModal(id) {
  document.getElementById(id).classList.add("active");
  document.body.style.overflow = "hidden";
}

function closeOverlay(id) {
  document.getElementById(id).classList.remove("active");
  document.body.style.overflow = "";
}

// Close modal on backdrop click
document.querySelectorAll(".modal-overlay").forEach((overlay) => {
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) {
      overlay.classList.remove("active");
      document.body.style.overflow = "";
    }
  });
});

// Close modal on Escape key
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    document.querySelectorAll(".modal-overlay.active").forEach((overlay) => {
      overlay.classList.remove("active");
    });
    document.body.style.overflow = "";
  }
});

// ═══════════════════════════════════════
// TOAST NOTIFICATIONS
// ═══════════════════════════════════════

function showToast(message, type = "success") {
  const container = document.getElementById("toastContainer");
  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <i class="bi ${
      type === "success" ? "bi-check-circle-fill" : "bi-x-circle-fill"
    }"></i>
    <span>${escapeHtml(message)}</span>
  `;
  container.appendChild(toast);

  setTimeout(() => {
    toast.classList.add("fade-out");
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

// ═══════════════════════════════════════
// UTILITIES
// ═══════════════════════════════════════

function escapeHtml(str) {
  const div = document.createElement("div");
  div.appendChild(document.createTextNode(str));
  return div.innerHTML;
}

function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + " B";
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + " KB";
  return (bytes / 1048576).toFixed(1) + " MB";
}
