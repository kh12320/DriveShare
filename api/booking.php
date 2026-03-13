<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
ob_start();

require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../classes/BookingModel.php';

$user = getCurrentUser();
if (!$user) {
    header('Location: /index.php?msg=Please+login');
    exit;
}

$action = $_GET['action'] ?? '';
$bookModel = new BookingModel();

switch ($action) {

    // =========================================================
    case 'create':
        if ($user['role'] !== 'customer') {
            echo json_encode(['success' => false, 'message' => 'Only customers can book cars.']);
            exit;
        }

        header('Content-Type: application/json');

        $carId = trim($_POST['car_id'] ?? '');
        $startD = trim($_POST['start_date'] ?? '');
        $endD = trim($_POST['end_date'] ?? '');
        $name = trim($_POST['customer_name'] ?? '');
        $contact = trim($_POST['customer_contact'] ?? '');

        if (!$carId || !$startD || !$endD || !$name || !$contact) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }

        if (strtotime($endD) <= strtotime($startD)) {
            echo json_encode(['success' => false, 'message' => 'End date must be after start date.']);
            exit;
        }

        if (strtotime($startD) < strtotime('today')) {
            echo json_encode(['success' => false, 'message' => 'Start date cannot be in the past.']);
            exit;
        }

        $result = $bookModel->createBooking([
            'customer_id' => $user['id'],
            'car_id' => $carId,
            'customer_name' => $name,
            'customer_contact' => $contact,
            'start_date' => $startD,
            'end_date' => $endD,
        ]);

        echo json_encode($result);
        break;

    // =========================================================
    case 'approve':
        if ($user['role'] !== 'owner') {
            redirectWithMsg('/dashboard/owner.php', 'Access denied.', 'danger');
            exit;
        }

        $bookingId = trim($_POST['booking_id'] ?? '');
        $ok = $bookingId ? $bookModel->approveBooking($bookingId, $user['id']) : false;
        $msg = $ok ? 'Booking approved! Customer notified.' : 'Failed to approve booking.';
        $type = $ok ? 'success' : 'danger';
        redirectWithMsg('/dashboard/owner.php', $msg, $type);
        break;

    // =========================================================
    case 'decline':
        if ($user['role'] !== 'owner') {
            redirectWithMsg('/dashboard/owner.php', 'Access denied.', 'danger');
            exit;
        }

        $bookingId = trim($_POST['booking_id'] ?? '');
        $ok = $bookingId ? $bookModel->declineBooking($bookingId, $user['id']) : false;
        $msg = $ok ? 'Booking declined.' : 'Failed to decline booking.';
        $type = $ok ? 'info' : 'danger';
        redirectWithMsg('/dashboard/owner.php', $msg, $type);
        break;

    // =========================================================
    case 'confirm':
        if ($user['role'] !== 'customer') {
            header('Location: /dashboard/customer.php');
            exit;
        }

        $bookingId = trim($_POST['booking_id'] ?? '');
        $booking = $bookingId ? $bookModel->confirmBooking($bookingId, $user['id']) : null;

        if ($booking) {
            redirectWithMsg('/dashboard/customer.php', 'Booking confirmed! Have a great ride! 🚗', 'success');
        } else {
            redirectWithMsg('/dashboard/customer.php', 'Failed to confirm booking.', 'danger');
        }
        break;

    // =========================================================
    case 'cancel':
        if ($user['role'] !== 'customer') {
            header('Location: /dashboard/customer.php');
            exit;
        }

        $bookingId = trim($_POST['booking_id'] ?? '');
        $ok = $bookingId ? $bookModel->cancelBooking($bookingId, $user['id']) : false;
        $msg = $ok ? 'Booking cancelled.' : 'Failed to cancel booking.';
        $type = $ok ? 'info' : 'danger';
        redirectWithMsg('/dashboard/customer.php', $msg, $type);
        break;

    // =========================================================
    case 'get_whatsapp':
        header('Content-Type: application/json');
        if ($user['role'] !== 'customer') {
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        $bookingId = $_GET['booking_id'] ?? '';
        $booking = $bookingId ? $bookModel->getBooking($bookingId) : null;

        if (!$booking || $booking['customer_id'] !== $user['id']) {
            echo json_encode(['success' => false, 'message' => 'Booking not found.']);
            exit;
        }

        if (!in_array($booking['status'], ['owner_approved', 'customer_confirmed', 'completed'])) {
            echo json_encode(['success' => false, 'message' => 'Owner has not approved yet.']);
            exit;
        }

        $phone = preg_replace('/[^0-9]/', '', $booking['owner_phone'] ?? '');
        if (strlen($phone) === 10)
            $phone = '91' . $phone;

        $msg = urlencode(
            "Hello! I'm *{$booking['customer_name_full']}* and I'd like to confirm my rental booking.\n\n" .
            "🚗 *Car:* {$booking['car_name']} ({$booking['car_type']})\n" .
            "📅 *Start:* {$booking['start_date']}\n" .
            "📅 *End:* {$booking['end_date']}\n" .
            "⏱️ *Duration:* {$booking['total_days']} day(s)\n" .
            "💰 *Total Amount:* ₹{$booking['total_price']}\n" .
            "📞 *My Contact:* {$booking['customer_contact']}\n\n" .
            "Please confirm. Thank you! 🙏"
        );

        echo json_encode([
            'success' => true,
            'wa_link' => "https://wa.me/{$phone}?text={$msg}",
            'owner_name' => $booking['owner_name'],
            'owner_phone' => $booking['owner_phone'],
        ]);
        break;

    default:
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
