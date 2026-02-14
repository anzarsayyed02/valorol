<?php
/**
 * Valorol Admin API
 * ─────────────────
 * Handles all CRUD operations for COA entries.
 * Actions: list, add, edit, delete, change_password
 */

session_start();

// ── Auth check (all actions require login) ──
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db.php';

// ── Paths ──
$jsonFile = __DIR__ . '/../coa_data.json';
$uploadDir = __DIR__ . '/../assets/coa/';

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ── Helpers ──

function loadCoaData($file) {
    if (!file_exists($file)) {
        return [];
    }
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function saveCoaData($file, $data) {
    // Re-index array to ensure clean JSON array
    $data = array_values($data);
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getNextId($data) {
    if (empty($data)) return 1;
    $maxId = max(array_column($data, 'id'));
    return $maxId + 1;
}

function sanitizeInput($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function sendJson($payload, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

// ── Route by action ──
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ─────────────────────────────────────
    // LIST all COA entries
    // ─────────────────────────────────────
    case 'list':
        $data = loadCoaData($jsonFile);
        sendJson(['success' => true, 'data' => $data]);
        break;

    // ─────────────────────────────────────
    // ADD a new COA entry
    // ─────────────────────────────────────
    case 'add':
        $product = sanitizeInput($_POST['product'] ?? '');
        $batch   = sanitizeInput($_POST['batch'] ?? '');
        $code    = sanitizeInput($_POST['code'] ?? '');

        if (empty($product) || empty($batch) || empty($code)) {
            sendJson(['success' => false, 'message' => 'All fields are required.'], 400);
        }

        // Validate PDF upload
        if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
            sendJson(['success' => false, 'message' => 'Please upload a valid PDF file.'], 400);
        }

        $file = $_FILES['pdf'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'pdf') {
            sendJson(['success' => false, 'message' => 'Only PDF files are allowed.'], 400);
        }

        if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
            sendJson(['success' => false, 'message' => 'File size must be under 10MB.'], 400);
        }

        // Generate safe filename
        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $batch) . '_' . time() . '.pdf';
        $destination = $uploadDir . $safeFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            sendJson(['success' => false, 'message' => 'Failed to upload file.'], 500);
        }

        // Add entry to JSON
        $data = loadCoaData($jsonFile);
        $newEntry = [
            'id'      => getNextId($data),
            'product' => $product,
            'batch'   => $batch,
            'code'    => $code,
            'file'    => $safeFilename,
        ];
        $data[] = $newEntry;
        saveCoaData($jsonFile, $data);

        sendJson(['success' => true, 'message' => 'COA added successfully.', 'entry' => $newEntry]);
        break;

    // ─────────────────────────────────────
    // EDIT an existing COA entry
    // ─────────────────────────────────────
    case 'edit':
        $id      = intval($_POST['id'] ?? 0);
        $product = sanitizeInput($_POST['product'] ?? '');
        $batch   = sanitizeInput($_POST['batch'] ?? '');
        $code    = sanitizeInput($_POST['code'] ?? '');

        if ($id <= 0 || empty($product) || empty($batch) || empty($code)) {
            sendJson(['success' => false, 'message' => 'All fields are required.'], 400);
        }

        $data = loadCoaData($jsonFile);
        $index = null;

        foreach ($data as $i => $entry) {
            if ($entry['id'] === $id) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            sendJson(['success' => false, 'message' => 'COA entry not found.'], 404);
        }

        // Update text fields
        $data[$index]['product'] = $product;
        $data[$index]['batch']   = $batch;
        $data[$index]['code']    = $code;

        // If a new PDF was uploaded, replace the old one
        if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['pdf'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($ext !== 'pdf') {
                sendJson(['success' => false, 'message' => 'Only PDF files are allowed.'], 400);
            }

            if ($file['size'] > 10 * 1024 * 1024) {
                sendJson(['success' => false, 'message' => 'File size must be under 10MB.'], 400);
            }

            // Delete old file
            $oldFile = $uploadDir . $data[$index]['file'];
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }

            // Upload new file
            $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $batch) . '_' . time() . '.pdf';
            $destination = $uploadDir . $safeFilename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                sendJson(['success' => false, 'message' => 'Failed to upload new file.'], 500);
            }

            $data[$index]['file'] = $safeFilename;
        }

        saveCoaData($jsonFile, $data);
        sendJson(['success' => true, 'message' => 'COA updated successfully.', 'entry' => $data[$index]]);
        break;

    // ─────────────────────────────────────
    // DELETE a COA entry
    // ─────────────────────────────────────
    case 'delete':
        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            sendJson(['success' => false, 'message' => 'Invalid entry ID.'], 400);
        }

        $data = loadCoaData($jsonFile);
        $found = false;

        foreach ($data as $i => $entry) {
            if ($entry['id'] === $id) {
                // Delete the PDF file
                $pdfPath = $uploadDir . $entry['file'];
                if (file_exists($pdfPath)) {
                    unlink($pdfPath);
                }
                unset($data[$i]);
                $found = true;
                break;
            }
        }

        if (!$found) {
            sendJson(['success' => false, 'message' => 'COA entry not found.'], 404);
        }

        saveCoaData($jsonFile, $data);
        sendJson(['success' => true, 'message' => 'COA deleted successfully.']);
        break;

    // ─────────────────────────────────────
    // CHANGE admin password
    // ─────────────────────────────────────
    case 'change_password':
        $currentPass = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
            sendJson(['success' => false, 'message' => 'All password fields are required.'], 400);
        }

        if ($newPass !== $confirmPass) {
            sendJson(['success' => false, 'message' => 'New passwords do not match.'], 400);
        }

        if (strlen($newPass) < 6) {
            sendJson(['success' => false, 'message' => 'New password must be at least 6 characters.'], 400);
        }

        $adminId = $_SESSION['admin_id'];
        $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE id = ?");
        $stmt->execute([$adminId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPass, $user['password'])) {
            sendJson(['success' => false, 'message' => 'Current password is incorrect.'], 403);
        }

        $hashedNew = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedNew, $adminId]);

        sendJson(['success' => true, 'message' => 'Password changed successfully.']);
        break;

    // ─────────────────────────────────────
    // UNKNOWN action
    // ─────────────────────────────────────
    default:
        sendJson(['success' => false, 'message' => 'Invalid action.'], 400);
        break;
}
