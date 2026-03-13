<?php
require_once __DIR__ . '/../config/database.php';

class ImageUploader
{
    private string $supabaseUrl;
    private string $serviceKey;
    private string $bucket;

    public function __construct()
    {
        $this->supabaseUrl = SUPABASE_URL;
        $this->serviceKey = SUPABASE_SERVICE_KEY;
        $this->bucket = SUPABASE_BUCKET;
    }

    /**
     * Upload an image file to Supabase Storage
     * Returns the public URL or throws on error
     */
    public function upload(array $file, string $carId): string
    {
        $this->validateFile($file);

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $carId . '/' . uniqid('img_', true) . '.' . $ext;

        $fileContent = file_get_contents($file['tmp_name']);
        $contentType = $file['type'];

        $url = "{$this->supabaseUrl}/storage/v1/object/{$this->bucket}/{$filename}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $fileContent,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->serviceKey}",
                "Content-Type: {$contentType}",
                "x-upsert: true",
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException("Image upload failed: HTTP {$httpCode} - {$response}");
        }

        // Return public URL
        return "{$this->supabaseUrl}/storage/v1/object/public/{$this->bucket}/{$filename}";
    }

    private function validateFile(array $file): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('File upload error: ' . $file['error']);
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('File size must be under 5MB.');
        }
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file['type'], $allowed)) {
            throw new RuntimeException('Only JPEG, PNG, WebP images are allowed.');
        }
    }

    /**
     * Process multiple uploads from $_FILES array
     */
    public function uploadMultiple(array $files, string $carId): array
    {
        $urls = [];
        // Handle multi-file input (files['images'][0], files['images'][1], ...)
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $single = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
                $urls[] = $this->upload($single, $carId);
            }
        }
        return $urls;
    }
}
