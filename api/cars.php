<?php
// Suppress PHP warnings/deprecations from corrupting JSON response body
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
ob_start();

require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../classes/CarModel.php';
require_once __DIR__ . '/../classes/ImageUploader.php';

header('Content-Type: application/json');

$user = getCurrentUser();
if (!$user) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$action = $_GET['action'] ?? '';
$carModel = new CarModel();

// Helper: send JSON and exit
function jsonOut(array $data): void
{
    ob_end_clean();
    echo json_encode($data);
    exit;
}

switch ($action) {

    // =========================================================
    case 'add':
        if ($user['role'] !== 'owner') {
            jsonOut(['success' => false, 'message' => 'Access denied.']);
        }

        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $price = (float) ($_POST['price_24h'] ?? 0);
        $cap = (int) ($_POST['capacity'] ?? 4);
        $desc = trim($_POST['description'] ?? '');

        if (!$name || !$type || $price <= 0) {
            jsonOut(['success' => false, 'message' => 'Name, type, and price are required.']);
        }

        $result = $carModel->addCar($user['id'], [
            'name' => $name,
            'type' => $type,
            'price_24h' => $price,
            'capacity' => $cap,
            'description' => $desc,
        ]);

        if (!$result['success']) {
            jsonOut($result);
        }

        $carId = $result['car_id'];

        // Upload images (optional - car is already saved)
        if (!empty($_FILES['images']['name'][0]) && $_FILES['images']['name'][0] !== '') {
            $uploader = new ImageUploader();
            $uploaded = 0;
            $count = count($_FILES['images']['name']);

            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK)
                    continue;
                $single = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i],
                ];
                try {
                    $url = $uploader->upload($single, $carId);
                    $carModel->addImage($carId, $url, $uploaded === 0);
                    $uploaded++;
                } catch (RuntimeException $e) {
                    error_log('Image upload error: ' . $e->getMessage());
                }
            }
        }

        jsonOut(['success' => true, 'car_id' => $carId, 'message' => 'Car listed successfully!']);

    // =========================================================
    case 'get':
        $carId = $_GET['car_id'] ?? '';
        $car = $carId ? $carModel->getCar($carId) : null;
        if (!$car) {
            jsonOut(['success' => false, 'message' => 'Car not found.']);
        }
        if ($user['role'] === 'owner' && $car['owner_id'] !== $user['id']) {
            jsonOut(['success' => false, 'message' => 'Access denied.']);
        }
        jsonOut(['success' => true, 'car' => $car]);

    // =========================================================
    case 'update':
        if ($user['role'] !== 'owner') {
            jsonOut(['success' => false, 'message' => 'Access denied.']);
        }
        $carId = trim($_POST['car_id'] ?? '');
        if (!$carId) {
            jsonOut(['success' => false, 'message' => 'Car ID required.']);
        }

        $ok = $carModel->updateCar($carId, $user['id'], [
            'name' => trim($_POST['name'] ?? ''),
            'type' => trim($_POST['type'] ?? ''),
            'price_24h' => (float) ($_POST['price_24h'] ?? 0),
            'capacity' => (int) ($_POST['capacity'] ?? 4),
            'description' => trim($_POST['description'] ?? ''),
            'status' => trim($_POST['status'] ?? 'available'),
        ]);

        // Detect if request expects JSON or redirect
        $wantsJson = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
        if ($wantsJson) {
            jsonOut(['success' => $ok, 'message' => $ok ? 'Car updated!' : 'Update failed.']);
        }
        ob_end_clean();
        $msg = $ok ? 'Car updated successfully!' : 'Failed to update car.';
        $type = $ok ? 'success' : 'danger';
        header("Location: /dashboard/owner.php?tab=my-cars&msg=" . urlencode($msg) . "&type={$type}");
        exit;

    // =========================================================
    case 'delete':
        if ($user['role'] !== 'owner') {
            jsonOut(['success' => false, 'message' => 'Access denied.']);
        }
        $carId = trim($_POST['car_id'] ?? '');
        $ok = $carId ? $carModel->deleteCar($carId, $user['id']) : false;
        ob_end_clean();
        $msg = $ok ? urlencode('Car deleted.') : urlencode('Delete failed.');
        $type = $ok ? 'success' : 'danger';
        header("Location: /dashboard/owner.php?tab=my-cars&msg={$msg}&type={$type}");
        exit;

    // =========================================================
    case 'add_image':
        if ($user['role'] !== 'owner') {
            jsonOut(['success' => false, 'message' => 'Access denied.']);
        }
        $carId = trim($_POST['car_id'] ?? '');
        if (!$carId || empty($_FILES['image'])) {
            jsonOut(['success' => false, 'message' => 'Car ID and image required.']);
        }
        $uploader = new ImageUploader();
        try {
            $url = $uploader->upload($_FILES['image'], $carId);
            $carModel->addImage($carId, $url, (bool) ($_POST['is_primary'] ?? false));
            jsonOut(['success' => true, 'url' => $url]);
        } catch (RuntimeException $e) {
            jsonOut(['success' => false, 'message' => $e->getMessage()]);
        }

    // =========================================================
    case 'delete_image':
        if ($user['role'] !== 'owner') {
            jsonOut(['success' => false, 'message' => 'Access denied.']);
        }
        $imageId = trim($_POST['image_id'] ?? '');
        $ok = $imageId ? $carModel->deleteImage($imageId, $user['id']) : false;
        jsonOut(['success' => $ok, 'message' => $ok ? 'Image removed.' : 'Failed.']);

    // =========================================================
    case 'list':
        $filters = [];
        $type = $_GET['type'] ?? '';
        $capacity = (int) ($_GET['capacity'] ?? 0);
        if ($type)
            $filters['type'] = $type;
        if ($capacity)
            $filters['capacity'] = $capacity;
        jsonOut(['success' => true, 'cars' => $carModel->getAllCars($filters)]);

    default:
        http_response_code(400);
        jsonOut(['success' => false, 'message' => 'Invalid action.']);
}
