<?php
declare(strict_types=1);

final class Validator
{
    private array $errors = [];

    private function textLength(string $value): int
    {
        $length=preg_match_all('/./us',$value);
        return $length===false?strlen($value):$length;
    }

    public function required(array $data, string $field, string $label): self
    {
        if (!isset($data[$field]) || !is_scalar($data[$field]) || trim((string) $data[$field]) === '') $this->errors[$field] = "$label is required.";
        return $this;
    }

    public function email(array $data, string $field, string $label = 'Email'): self
    {
        if (isset($data[$field]) && $data[$field] !== '' && (!is_scalar($data[$field]) || !filter_var((string)$data[$field], FILTER_VALIDATE_EMAIL))) $this->errors[$field] = "$label must be a valid email address.";
        return $this;
    }

    public function length(array $data, string $field, string $label, int $min, int $max): self
    {
        $value = isset($data[$field]) && is_scalar($data[$field]) ? trim((string)$data[$field]) : '';
        if ($value !== '' && ($this->textLength($value) < $min || $this->textLength($value) > $max)) $this->errors[$field] = "$label must be between $min and $max characters.";
        return $this;
    }

    public function numericRange(array $data, string $field, string $label, float $min, float $max): self
    {
        $value = $data[$field] ?? null;
        if ($value !== null && !is_scalar($value)) $value = false;
        if ($value === null || $value === '') $this->errors[$field] = "$label is required.";
        elseif (!is_numeric($value) || (float) $value < $min || (float) $value > $max) $this->errors[$field] = "$label must be between $min and $max.";
        return $this;
    }

    public function add(string $field, string $message): self { $this->errors[$field] = $message; return $this; }
    public function errors(): array { return $this->errors; }
    public function passes(): bool { return $this->errors === []; }
}
