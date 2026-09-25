<?php
declare(strict_types=1);

namespace Archium\Validation;

/** Validatore minimale: required, email, min:N, max:N, confirmed. */
final class Validator
{
    /** @var array<string, string[]> */
    private array $errors = [];

    private function __construct(private array $data)
    {
    }

    /** @param array<string, string> $rules es. ['email' => 'required|email|max:190'] */
    public static function make(array $data, array $rules): self
    {
        $v = new self($data);
        foreach ($rules as $field => $ruleString) {
            foreach (explode('|', $ruleString) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $v->apply($field, $name, $param, $data[$field] ?? null);
            }
        }
        return $v;
    }

    private function apply(string $field, string $rule, ?string $param, mixed $value): void
    {
        $str = is_string($value) ? trim($value) : '';
        switch ($rule) {
            case 'required':
                if ($str === '') {
                    $this->errors[$field][] = "Il campo {$field} e' obbligatorio.";
                }
                break;
            case 'email':
                if ($str !== '' && !filter_var($str, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = "Il campo {$field} non e' un'email valida.";
                }
                break;
            case 'min':
                if ($str !== '' && mb_strlen($str) < (int) $param) {
                    $this->errors[$field][] = "Il campo {$field} deve avere almeno {$param} caratteri.";
                }
                break;
            case 'max':
                if ($str !== '' && mb_strlen($str) > (int) $param) {
                    $this->errors[$field][] = "Il campo {$field} non puo' superare {$param} caratteri.";
                }
                break;
            case 'confirmed':
                $other = $this->data[$field . '_confirmation'] ?? '';
                if ($str !== (is_string($other) ? $other : '')) {
                    $this->errors[$field][] = "La conferma di {$field} non corrisponde.";
                }
                break;
        }
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string[]> */
    public function errors(): array
    {
        return $this->errors;
    }
}