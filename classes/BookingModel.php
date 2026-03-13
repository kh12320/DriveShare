<?php
require_once __DIR__ . '/../config/database.php';

class BookingModel
{

    public function createBooking(array $data): array
    {
        $start = new DateTime($data['start_date']);
        $end = new DateTime($data['end_date']);
        $days = max(1, $start->diff($end)->days);

        // Get car
        $cars = Database::select('cars', ['id' => $data['car_id']], 'price_24h,owner_id');
        if (empty($cars))
            return ['success' => false, 'message' => 'Car not found.'];
        $car = $cars[0];

        $totalPrice = $days * (float) $car['price_24h'];

        try {
            $row = Database::insert('bookings', [
                'id' => Database::uuid(),
                'customer_id' => $data['customer_id'],
                'car_id' => $data['car_id'],
                'owner_id' => $car['owner_id'],
                'customer_name' => trim($data['customer_name']),
                'customer_contact' => trim($data['customer_contact']),
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'total_days' => $days,
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

            return [
                'success' => true,
                'booking_id' => $row['id'],
                'total_days' => $days,
                'total_price' => $totalPrice,
                'message' => 'Booking submitted! Waiting for owner approval.',
            ];
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => 'Booking failed: ' . $e->getMessage()];
        }
    }

    public function getCustomerBookings(string $customerId): array
    {
        $bookings = Database::select('bookings', ['customer_id' => $customerId], '*', 'created_at.desc');

        foreach ($bookings as &$b) {
            // Car info
            $cars = Database::select('cars', ['id' => $b['car_id']], 'name,type');
            $b['car_name'] = $cars[0]['name'] ?? 'Unknown';
            $b['car_type'] = $cars[0]['type'] ?? '';

            // Primary image
            $imgs = Database::select('car_images', ['car_id' => $b['car_id'], 'is_primary' => 'true'], 'image_url');
            $b['car_image'] = $imgs[0]['image_url'] ?? null;

            // Owner info — only reveal phone if approved+
            $owners = Database::select('users', ['id' => $b['owner_id']], 'name,phone');
            $b['owner_name'] = $owners[0]['name'] ?? '';
            // Phone revealed only when status is approved or beyond
            $b['owner_phone'] = in_array($b['status'], ['owner_approved', 'customer_confirmed', 'completed'])
                ? ($owners[0]['phone'] ?? null)
                : null;
        }

        return $bookings;
    }

    public function getOwnerBookings(string $ownerId): array
    {
        $bookings = Database::select('bookings', ['owner_id' => $ownerId], '*', 'created_at.desc');

        foreach ($bookings as &$b) {
            $cars = Database::select('cars', ['id' => $b['car_id']], 'name,type');
            $b['car_name'] = $cars[0]['name'] ?? 'Unknown';
            $b['car_type'] = $cars[0]['type'] ?? '';

            $imgs = Database::select('car_images', ['car_id' => $b['car_id'], 'is_primary' => 'true'], 'image_url');
            $b['car_image'] = $imgs[0]['image_url'] ?? null;

            $customers = Database::select('users', ['id' => $b['customer_id']], 'name,email');
            $b['customer_name'] = $customers[0]['name'] ?? '';
            $b['customer_email'] = $customers[0]['email'] ?? '';
        }

        return $bookings;
    }

    public function approveBooking(string $bookingId, string $ownerId): bool
    {
        // Verify ownership and pending status
        $bks = Database::select('bookings', ['id' => $bookingId, 'owner_id' => $ownerId], 'id,status');
        if (empty($bks))
            return false;

        if ($bks[0]['status'] === 'owner_approved')
            return true;

        if ($bks[0]['status'] !== 'pending')
            return false;

        $count = Database::update('bookings', ['status' => 'owner_approved'], ['id' => $bookingId]);
        return $count > 0;
    }

    public function declineBooking(string $bookingId, string $ownerId): bool
    {
        $bks = Database::select('bookings', ['id' => $bookingId, 'owner_id' => $ownerId], 'id,status');
        if (empty($bks))
            return false;

        if ($bks[0]['status'] === 'cancelled')
            return true;

        if ($bks[0]['status'] !== 'pending')
            return false;

        $count = Database::update('bookings', ['status' => 'cancelled'], ['id' => $bookingId]);
        return $count > 0;
    }

    public function confirmBooking(string $bookingId, string $customerId): ?array
    {
        // Accept pending, owner_approved, or customer_confirmed (idempotent)
        $bks = Database::select('bookings', [
            'id' => $bookingId,
            'customer_id' => $customerId,
        ]);
        if (empty($bks))
            return null;

        $b = $bks[0];

        // If already confirmed, just return enriched booking
        if (!in_array($b['status'], ['owner_approved', 'customer_confirmed']))
            return null;

        if ($b['status'] === 'owner_approved') {
            Database::update('bookings', ['status' => 'customer_confirmed'], ['id' => $bookingId]);
            $b['status'] = 'customer_confirmed';
        }

        // Enrich with car + owner
        $cars = Database::select('cars', ['id' => $b['car_id']], 'name,type');
        $owners = Database::select('users', ['id' => $b['owner_id']], 'name,phone');

        $b['car_name'] = $cars[0]['name'] ?? '';
        $b['car_type'] = $cars[0]['type'] ?? '';
        $b['owner_name'] = $owners[0]['name'] ?? '';
        $b['owner_phone'] = $owners[0]['phone'] ?? '';

        return $b;
    }

    public function cancelBooking(string $bookingId, string $customerId): bool
    {
        $bks = Database::select('bookings', ['id' => $bookingId, 'customer_id' => $customerId], 'id,status');
        if (empty($bks))
            return false;

        if ($bks[0]['status'] === 'cancelled')
            return true;

        if (!in_array($bks[0]['status'], ['pending', 'owner_approved']))
            return false;

        $count = Database::update('bookings', ['status' => 'cancelled'], ['id' => $bookingId]);
        return $count > 0;
    }

    public function getOwnerStats(string $ownerId): array
    {
        $cars = Database::select('cars', ['owner_id' => $ownerId], 'id,name');
        $stats = [];

        foreach ($cars as $car) {
            $bookings = Database::select('bookings', ['car_id' => $car['id']], 'status,total_days,total_price');

            $totalDays = 0;
            $totalRevenue = 0;

            foreach ($bookings as $b) {
                if (in_array($b['status'], ['customer_confirmed', 'completed'])) {
                    $totalDays += (int) $b['total_days'];
                    $totalRevenue += (float) $b['total_price'];
                }
            }

            $stats[] = [
                'car_name' => $car['name'],
                'total_bookings' => count($bookings),
                'total_days_rented' => $totalDays,
                'total_revenue' => $totalRevenue,
            ];
        }

        usort($stats, fn($a, $b) => $b['total_bookings'] - $a['total_bookings']);
        return $stats;
    }

    public function getBooking(string $bookingId): ?array
    {
        $bks = Database::select('bookings', ['id' => $bookingId]);
        if (empty($bks))
            return null;

        $b = $bks[0];
        $cars = Database::select('cars', ['id' => $b['car_id']], 'name,type');
        $customers = Database::select('users', ['id' => $b['customer_id']], 'name');
        $owners = Database::select('users', ['id' => $b['owner_id']], 'name,phone');

        $b['car_name'] = $cars[0]['name'] ?? '';
        $b['car_type'] = $cars[0]['type'] ?? '';
        $b['customer_name_full'] = $customers[0]['name'] ?? '';
        $b['owner_name'] = $owners[0]['name'] ?? '';
        $b['owner_phone'] = $owners[0]['phone'] ?? '';

        return $b;
    }
}
