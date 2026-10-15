<?php
declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $errors = [];

    public function __construct(private array $data) {}

    public function required(string $field, string $label): self
    {
        if (trim((string)($this->data[$field] ?? '')) === '') {
            $this->errors[$field] = "{$label} wajib diisi.";
        }
        return $this;
    }

    public function email(string $field, string $label = 'Email'): self
    {
        $v = (string)($this->data[$field] ?? '');
        if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label} tidak valid.";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label): self
    {
        if (mb_strlen((string)($this->data[$field] ?? '')) < $min) {
            $this->errors[$field] = "{$label} minimal {$min} karakter.";
        }
        return $this;
    }

    public function numeric(string $field, string $label): self
    {
        $v = $this->data[$field] ?? null;
        if ($v !== null && $v !== '' && !is_numeric($v)) {
            $this->errors[$field] = "{$label} harus angka.";
        }
        return $this;
    }

    public function min(string $field, float $min, string $label): self
    {
        $v = $this->data[$field] ?? null;
        if (is_numeric($v) && (float)$v < $min) {
            $this->errors[$field] = "{$label} minimal {$min}.";
        }
        return $this;
    }

    public function in(string $field, array $allowed, string $label): self
    {
        if (!in_array($this->data[$field] ?? null, $allowed, true)) {
            $this->errors[$field] = "{$label} tidak valid.";
        }
        return $this;
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public static function slug(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim((string)$text, '-');
    }
}
