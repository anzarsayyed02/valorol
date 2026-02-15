<?php
session_start();

// Path to user store
$usersFile = __DIR__ . '/users.json';

// Initialize users.json with default user if missing or empty
if (!file_exists($usersFile) || filesize($usersFile) === 0) {
	$users = [];
} else {
	$users = json_decode(file_get_contents($usersFile), true) ?: [];
}

$hasDefault = false;
foreach ($users as $u) {
	if (isset($u['username']) && strcasecmp($u['username'], 'Rupesh Mudliar') === 0) { $hasDefault = true; break; }
}

if (!$hasDefault) {
	$defaultPass = 'CCPL@2026';
	$nextId = 1;
	foreach ($users as $u) { if (isset($u['id']) && intval($u['id']) >= $nextId) $nextId = intval($u['id']) + 1; }
	$users[] = [
		'id' => $nextId,
		'username' => 'Rupesh Mudliar',
		'password' => password_hash($defaultPass, PASSWORD_DEFAULT),
		'role' => 'admin'
	];
	file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = trim($_POST['username'] ?? '');
	$password = $_POST['password'] ?? '';

	if ($username === '' || $password === '') {
		$error = 'Please enter both username and password.';
	} else {
		$users = json_decode(file_get_contents($usersFile), true) ?: [];
		$found = null;
		foreach ($users as $u) {
			if (strcasecmp($u['username'], $username) === 0) {
				$found = $u;
				break;
			}
		}

		if ($found && password_verify($password, $found['password'])) {
			session_regenerate_id(true);
			$_SESSION['admin_logged_in'] = true;
			$_SESSION['admin_id'] = $found['id'];
			$_SESSION['admin_username'] = $found['username'];
			$_SESSION['admin_role'] = $found['role'] ?? 'viewer';
			header('Location: dashboard.php');
			exit;
		} else {
			$error = 'Invalid username or password.';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin Login | Valorol</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
	<style>
		body{font-family:Poppins, sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f0f4f8}
		.card{width:420px;border-radius:12px;padding:24px;background:#fff;box-shadow:0 6px 30px rgba(2,6,23,.08)}
		.brand{display:flex;align-items:center;gap:10px;margin-bottom:12px}
		.brand i{font-size:1.25rem;color:#004990}
		label{font-weight:600;font-size:.8rem}
		.btn-login{width:100%;padding:10px;border-radius:8px;background:#004990;color:#fff;border:0}
		.alert{margin-bottom:12px}
	</style>
</head>
<body>
	<div class="card">
		<div class="brand">
			<i class="bi bi-shield-lock-fill"></i>
			<div>
				<div style="font-weight:700">VALOROL</div>
				<div style="font-size:.85rem;color:#6b7280">Admin Login</div>
			</div>
		</div>

		<?php if ($error !== ''): ?>
			<div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
		<?php endif; ?>

		<form method="POST" action="index.php" autocomplete="off">
			<div class="mb-3">
				<label for="username">Username</label>
				<input id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
			</div>
			<div class="mb-3">
				<label for="password">Password</label>
				<input id="password" name="password" type="password" class="form-control" required>
			</div>
			<div>
				<button class="btn-login" type="submit"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</button>
			</div>
		</form>

		<div style="margin-top:12px;text-align:center">
			<a href="../index.html">Back to website</a>
		</div>
	</div>
</body>
</html>

