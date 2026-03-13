<?php
require_once __DIR__ . '/../config/database.php';

class CarModel
{

    public function addCar(string $ownerId, array $data): array
    {
        try {
            $row = Database::insert('cars', [
                'id' => Database::uuid(),
                'owner_id' => $ownerId,
                'type' => trim($data['type']),
                'name' => trim($data['name']),
                'price_24h' => (float) $data['price_24h'],
                'capacity' => (int) $data['capacity'],
                'description' => trim($data['description'] ?? ''),
                'status' => 'available',
            ]);
            return ['success' => true, 'car_id' => $row['id']];
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function addImage(string $carId, string $imageUrl, bool $isPrimary = false): void
    {
        if ($isPrimary) {
            // Unset all primary flags for this car
            $images = Database::select('car_images', ['car_id' => $carId, 'is_primary' => 'true'], 'id');
            foreach ($images as $img) {
                Database::update('car_images', ['is_primary' => false], ['id' => $img['id']]);
            }
        }
        Database::insert('car_images', [
            'id' => Database::uuid(),
            'car_id' => $carId,
            'image_url' => $imageUrl,
            'is_primary' => $isPrimary,
        ]);
    }

    public function deleteImage(string $imageId, string $ownerId): bool
    {
        // Verify ownership via join (manual lookup)
        $imgs = Database::select('car_images', ['id' => $imageId], 'id,car_id');
        if (empty($imgs))
            return false;

        $cars = Database::select('cars', ['id' => $imgs[0]['car_id'], 'owner_id' => $ownerId], 'id');
        if (empty($cars))
            return false;

        return Database::delete('car_images', ['id' => $imageId]);
    }

    public function getCarImages(string $carId): array
    {
        return Database::select('car_images', ['car_id' => $carId], '*', 'is_primary.desc,created_at.asc');
    }

    public function getCar(string $carId): ?array
    {
        $cars = Database::select('cars', ['id' => $carId]);
        if (empty($cars))
            return null;

        $car = $cars[0];

        // Get owner info
        $owners = Database::select('users', ['id' => $car['owner_id']], 'id,name,phone');
        if (!empty($owners)) {
            $car['owner_name'] = $owners[0]['name'];
            $car['owner_phone'] = $owners[0]['phone'];
        }

        $car['images'] = $this->getCarImages($carId);
        return $car;
    }

    public function getOwnerCars(string $ownerId): array
    {
        $cars = Database::select('cars', ['owner_id' => $ownerId], '*', 'created_at.desc');

        foreach ($cars as &$car) {
            $imgs = Database::select(
                'car_images',
                ['car_id' => $car['id'], 'is_primary' => 'true'],
                'image_url'
            );
            $car['primary_image'] = $imgs[0]['image_url'] ?? null;

            $allImgs = Database::select('car_images', ['car_id' => $car['id']], 'id');
            $car['image_count'] = count($allImgs);
        }

        return $cars;
    }

    public function getAllCars(array $filters = []): array
    {
        // Build filter URL manually for the REST client
        $url = SUPABASE_URL . '/rest/v1/cars?status=eq.available&select=*';

        if (!empty($filters['type'])) {
            $url .= '&type=eq.' . rawurlencode($filters['type']);
        }
        if (!empty($filters['capacity'])) {
            $url .= '&capacity=gte.' . (int) $filters['capacity'];
        }
        $url .= '&order=created_at.desc';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . SUPABASE_SERVICE_KEY,
                'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
            ],
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        $cars = json_decode($body, true) ?? [];

        // Attach primary image + owner name
        foreach ($cars as &$car) {
            $imgs = Database::select('car_images', ['car_id' => $car['id'], 'is_primary' => 'true'], 'image_url');
            $car['primary_image'] = $imgs[0]['image_url'] ?? null;

            $owners = Database::select('users', ['id' => $car['owner_id']], 'name');
            $car['owner_name'] = $owners[0]['name'] ?? 'Unknown';
        }

        return $cars;
    }

    public function getCarTypes(): array
    {
        $cars = Database::select('cars', ['status' => 'available'], 'type', 'type.asc');
        $types = array_unique(array_column($cars, 'type'));
        return array_values($types);
    }

    public function updateCar(string $carId, string $ownerId, array $data): bool
    {
        // Verify ownership first
        $cars = Database::select('cars', ['id' => $carId, 'owner_id' => $ownerId], 'id');
        if (empty($cars))
            return false;

        $count = Database::update('cars', [
            'type' => trim($data['type']),
            'name' => trim($data['name']),
            'price_24h' => (float) $data['price_24h'],
            'capacity' => (int) $data['capacity'],
            'description' => trim($data['description'] ?? ''),
            'status' => $data['status'] ?? 'available',
        ], ['id' => $carId, 'owner_id' => $ownerId]);

        return $count > 0;
    }

    public function deleteCar(string $carId, string $ownerId): bool
    {
        // Cascade: delete images first
        $imgs = Database::select('car_images', ['car_id' => $carId], 'id');
        foreach ($imgs as $img) {
            Database::delete('car_images', ['id' => $img['id']]);
        }
        return Database::delete('cars', ['id' => $carId, 'owner_id' => $ownerId]);
    }
}
