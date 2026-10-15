<?php
declare(strict_types=1);

namespace App\Services;

class UploadService
{
    public function __construct(private string $storageDir, private array $allowedMimes, private int $maxBytes) {}

    public static function proofs(array $config): self
    {
        return new self(
            $config['storage']['payment_proofs'],
            $config['payment']['allowed_proof_mimes'],
            $config['payment']['max_proof_mb'] * 1024 * 1024
        );
    }

    /** Validate + move uploaded proof. Returns stored filename. */
    public function store(array $file, int $orderId): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('File bukti wajib diunggah.');
        }
        if ($file['size'] > $this->maxBytes) {
            throw new \RuntimeException('Ukuran file maksimal ' . (int)($this->maxBytes / 1024 / 1024) . 'MB.');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $this->allowedMimes, true)) {
            throw new \RuntimeException('Format file tidak didukung (jpg/png/webp/pdf).');
        }
        // Extra image sanity check
        if (str_starts_with($mime, 'image/') && @getimagesize($file['tmp_name']) === false) {
            throw new \RuntimeException('File gambar tidak valid.');
        }
        // Pemindaian awal: tolak file yang diawali signature executable/script.
        $head = @file_get_contents($file['tmp_name'], false, null, 0, 4096);
        if ($head !== false) {
            $lower = strtolower($head);
            if (
                str_contains($lower, '<?php') || str_contains($lower, '<?=')
                || str_contains($lower, '<script') || str_contains($lower, '<%')
                || str_contains($lower, '#!/')
            ) {
                throw new \RuntimeException('File ditolak: terdeteksi konten script.');
            }
        }
        if (!is_dir($this->storageDir) && !mkdir($this->storageDir, 0755, true)) {
            throw new \RuntimeException('Storage tidak dapat ditulis.');
        }
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
        $name = 'order' . $orderId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $this->storageDir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest) && !rename($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Gagal menyimpan file.');
        }
        chmod($dest, 0644);
        return $name;
    }

    public function path(string $filename): string
    {
        $safe = basename($filename);
        return $this->storageDir . '/' . $safe;
    }
}
