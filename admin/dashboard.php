<?php
session_start();

// Auth guard
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$username = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Valorol COA Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Top Navigation -->
    <nav class="admin-nav">
        <div class="nav-inner">
            <div class="nav-brand">
                <div class="brand-icon">
                    <i class="bi bi-file-earmark-medical-fill"></i>
                </div>
                <div>
                    <span class="brand-name">Valorol</span>
                    <span class="brand-sub">COA Manager</span>
                </div>
            </div>
            <div class="nav-actions">
                <button class="nav-btn" onclick="openPasswordModal()" title="Change Password">
                    <i class="bi bi-key-fill"></i>
                    <span class="nav-btn-label">Password</span>
                </button>
                <div class="nav-divider"></div>
                <div class="nav-user">
                    <i class="bi bi-person-circle"></i>
                    <span><?php echo $username; ?></span>
                </div>
                <a href="logout.php" class="nav-btn nav-btn-logout" title="Sign Out">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="nav-btn-label">Sign Out</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Header Row -->
        <div class="page-header">
            <div>
                <h1 class="page-title">COA Entries</h1>
                <p class="page-subtitle">Manage your Certificate of Analysis documents</p>
            </div>
            <button class="btn-primary-custom" onclick="openAddModal()">
                <i class="bi bi-plus-lg"></i>
                Add New COA
            </button>
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar" id="statsBar">
            <div class="stat-item">
                <i class="bi bi-files"></i>
                <span><strong id="totalCount">0</strong> Total Entries</span>
            </div>
        </div>

        <!-- Search -->
        <div class="search-bar">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInput" placeholder="Search by product name, batch no. or code...">
        </div>

        <!-- Table -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="th-id">#</th>
                            <th>Product Name</th>
                            <th>Batch No.</th>
                            <th>Code</th>
                            <th>PDF File</th>
                            <th class="th-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="coaTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-5" style="color: #9ca3af;">
                                <i class="bi bi-arrow-clockwise spin-icon fs-3 d-block mb-2"></i>
                                Loading entries...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- ═══════════════════════════════════════ -->
    <!-- ADD / EDIT MODAL                       -->
    <!-- ═══════════════════════════════════════ -->
    <div class="modal-overlay" id="coaModal">
        <div class="modal-box">
            <div class="modal-header-custom">
                <h3 id="modalTitle">Add New COA</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form id="coaForm" enctype="multipart/form-data">
                <input type="hidden" id="entryId" name="id" value="">
                <div class="modal-body-custom">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="productName">Product Name <span class="required">*</span></label>
                            <input type="text" id="productName" name="product" placeholder="e.g. Acetone BP" required>
                        </div>
                    </div>
                    <div class="form-row form-row-2">
                        <div class="form-col">
                            <label for="batchNo">Batch No. <span class="required">*</span></label>
                            <input type="text" id="batchNo" name="batch" placeholder="e.g. BF2507003" required>
                        </div>
                        <div class="form-col">
                            <label for="codeNo">Code <span class="required">*</span></label>
                            <input type="text" id="codeNo" name="code" placeholder="e.g. C40002" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label for="pdfFile">
                                PDF File <span class="required" id="pdfRequired">*</span>
                            </label>
                            <div class="file-upload" id="fileUploadZone">
                                <input type="file" id="pdfFile" name="pdf" accept=".pdf" class="file-input">
                                <div class="file-upload-content" id="fileUploadContent">
                                    <i class="bi bi-cloud-arrow-up-fill"></i>
                                    <span class="file-upload-text">Click to select or drag a PDF file</span>
                                    <span class="file-upload-hint">Max file size: 10MB</span>
                                </div>
                            </div>
                            <div class="current-file" id="currentFileInfo" style="display:none;">
                                <i class="bi bi-file-earmark-pdf-fill"></i>
                                <span id="currentFileName"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer-custom">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save" id="saveBtn">
                        <i class="bi bi-check-lg"></i>
                        <span id="saveBtnText">Save Entry</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- DELETE CONFIRMATION MODAL              -->
    <!-- ═══════════════════════════════════════ -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box modal-box-sm">
            <div class="modal-body-custom text-center" style="padding: 32px;">
                <div class="delete-icon-wrapper">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <h3 style="color: #1f2937; margin-bottom: 8px;">Delete COA Entry?</h3>
                <p style="color: #6b7280; font-size: 0.9rem;">
                    This will permanently remove <strong id="deleteProductName"></strong> and its PDF file. This action cannot be undone.
                </p>
                <div class="modal-footer-custom" style="justify-content: center; border: none; padding-top: 16px;">
                    <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button class="btn-delete" id="confirmDeleteBtn">
                        <i class="bi bi-trash3-fill"></i>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- CHANGE PASSWORD MODAL                  -->
    <!-- ═══════════════════════════════════════ -->
    <div class="modal-overlay" id="passwordModal">
        <div class="modal-box modal-box-sm">
            <div class="modal-header-custom">
                <h3>Change Password</h3>
                <button class="modal-close" onclick="closePasswordModal()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form id="passwordForm">
                <div class="modal-body-custom">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="currentPassword">Current Password <span class="required">*</span></label>
                            <input type="password" id="currentPassword" name="current_password" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label for="newPassword">New Password <span class="required">*</span></label>
                            <input type="password" id="newPassword" name="new_password" required minlength="6">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label for="confirmPassword">Confirm New Password <span class="required">*</span></label>
                            <input type="password" id="confirmPassword" name="confirm_password" required minlength="6">
                        </div>
                    </div>
                </div>
                <div class="modal-footer-custom">
                    <button type="button" class="btn-cancel" onclick="closePasswordModal()">Cancel</button>
                    <button type="submit" class="btn-save">
                        <i class="bi bi-check-lg"></i>
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
