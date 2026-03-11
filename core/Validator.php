<?php
/**
 * Enterprise Input Validation Framework
 * 
 * Declarative, chainable validation for controller input.
 * Eliminates scattered validation logic and provides consistent error handling.
 * 
 * Usage:
 *   $v = new Validator($_POST, [
 *       'name'     => 'required|string|min:2|max:100',
 *       'email'    => 'required|email',
 *       'quantity' => 'required|integer|min:1|max:9999',
 *       'price'    => 'required|float|min:0',
 *       'date'     => 'required|date',
 *       'status'   => 'required|in:active,inactive',
 *       'phone'    => 'regex:/^[0-9+\-\s()]+$/',
 *   ]);
 *   if ($v->fails()) {
 *       return $v->errors(); // ['name' => 'Name is required.', ...]
 *   }
 *   $clean = $v->validated(); // sanitized values
 */
class Validator {
    private $data;
    private $rules;
    private $errors = [];
    private $validated = [];

    public function __construct(array $data, array $rules) {
        $this->data = $data;
        $this->rules = $rules;
        $this->validate();
    }

    /**
     * Run all validation rules
     */
    private function validate() {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;
            $label = ucfirst(str_replace('_', ' ', $field));

            foreach ($rules as $rule) {
                $param = null;
                if (strpos($rule, ':') !== false) {
                    [$rule, $param] = explode(':', $rule, 2);
                }

                $error = $this->applyRule($rule, $field, $value, $param, $label);
                if ($error) {
                    $this->errors[$field] = $error;
                    break; // Stop on first error per field
                }
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = is_string($value) ? trim($value) : $value;
            }
        }
    }

    /**
     * Apply a single validation rule
     */
    private function applyRule($rule, $field, $value, $param, $label) {
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || $value === []) {
                    return "{$label} is required.";
                }
                break;

            case 'string':
                if ($value !== null && !is_string($value)) {
                    return "{$label} must be a string.";
                }
                break;

            case 'integer':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_INT) && $value !== '0' && $value !== 0) {
                    return "{$label} must be a whole number.";
                }
                break;

            case 'float':
            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    return "{$label} must be a number.";
                }
                break;

            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "{$label} must be a valid email address.";
                }
                break;

            case 'date':
                if ($value !== null && $value !== '') {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) || !strtotime($value)) {
                        return "{$label} must be a valid date (YYYY-MM-DD).";
                    }
                }
                break;

            case 'min':
                if ($value !== null && $value !== '') {
                    if (is_numeric($value) && (float)$value < (float)$param) {
                        return "{$label} must be at least {$param}.";
                    }
                    if (is_string($value) && !is_numeric($value) && strlen($value) < (int)$param) {
                        return "{$label} must be at least {$param} characters.";
                    }
                }
                break;

            case 'max':
                if ($value !== null && $value !== '') {
                    if (is_numeric($value) && (float)$value > (float)$param) {
                        return "{$label} must not exceed {$param}.";
                    }
                    if (is_string($value) && !is_numeric($value) && strlen($value) > (int)$param) {
                        return "{$label} must not exceed {$param} characters.";
                    }
                }
                break;

            case 'in':
                $allowed = explode(',', $param);
                if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
                    return "{$label} must be one of: " . implode(', ', $allowed) . ".";
                }
                break;

            case 'regex':
                if ($value !== null && $value !== '' && !preg_match($param, $value)) {
                    return "{$label} format is invalid.";
                }
                break;

            case 'boolean':
                if ($value !== null && !in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true)) {
                    return "{$label} must be true or false.";
                }
                break;

            case 'array':
                if ($value !== null && !is_array($value)) {
                    return "{$label} must be an array.";
                }
                break;

            case 'nullable':
                // Allows null/empty — skip subsequent rules if empty
                if ($value === null || $value === '') {
                    $this->validated[$field] = null;
                    return null;
                }
                break;
        }
        return null;
    }

    public function fails(): bool {
        return !empty($this->errors);
    }

    public function errors(): array {
        return $this->errors;
    }

    public function firstError(): string {
        return reset($this->errors) ?: '';
    }

    public function validated(): array {
        return $this->validated;
    }

    /**
     * Static shorthand for controller use
     */
    public static function make(array $data, array $rules): self {
        return new self($data, $rules);
    }
}
