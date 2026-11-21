<?php
declare(strict_types=1);

namespace ApexGuild\Domain;

/**
 * Dynamic JSON Schema Form Compiler & Validator
 * Compiles dynamic workflow schema definitions into responsive enterprise HTML form controls,
 * validates incoming payloads, and renders formatted readonly inspection cards.
 */
class FormCompiler
{
    /**
     * Render HTML input controls from schema fields
     */
    public function renderFormHtml(array $fields, array $values = [], array $errors = []): string
    {
        $html = '<div class="row g-3">';

        foreach ($fields as $field) {
            $name        = htmlspecialchars($field['name'] ?? '', ENT_QUOTES, 'UTF-8');
            $label       = htmlspecialchars($field['label'] ?? ucfirst($name), ENT_QUOTES, 'UTF-8');
            $type        = $field['type'] ?? 'text';
            $required    = !empty($field['required']);
            $placeholder = htmlspecialchars($field['placeholder'] ?? '', ENT_QUOTES, 'UTF-8');
            $hint        = htmlspecialchars($field['hint'] ?? '', ENT_QUOTES, 'UTF-8');
            $col         = (int)($field['col'] ?? 12);
            $val         = $values[$name] ?? '';
            $valSafe     = is_string($val) ? htmlspecialchars($val, ENT_QUOTES, 'UTF-8') : '';
            $hasError    = isset($errors[$name]);
            $errorMsg    = $hasError ? htmlspecialchars($errors[$name], ENT_QUOTES, 'UTF-8') : '';
            $errClass    = $hasError ? 'is-invalid' : '';

            $reqBadge = $required ? '<span class="text-danger ms-1">*</span>' : '<span class="text-muted ms-1 small fw-normal">(Optional)</span>';

            $html .= "<div class=\"col-12 col-md-{$col}\">";
            $html .= "<div class=\"form-group mb-2\">";
            $html .= "<label for=\"field_{$name}\" class=\"form-label fw-semibold text-secondary small text-uppercase tracking-wider\">{$label} {$reqBadge}</label>";

            switch ($type) {
                case 'textarea':
                    $rows = (int)($field['rows'] ?? 3);
                    $html .= "<textarea id=\"field_{$name}\" name=\"data[{$name}]\" class=\"form-control enterprise-input {$errClass}\" rows=\"{$rows}\" placeholder=\"{$placeholder}\"" . ($required ? ' required' : '') . ">{$valSafe}</textarea>";
                    break;

                case 'select':
                    $options = $field['options'] ?? [];
                    $html .= "<select id=\"field_{$name}\" name=\"data[{$name}]\" class=\"form-select enterprise-input {$errClass}\"" . ($required ? ' required' : '') . ">";
                    $html .= "<option value=\"\">-- Select {$label} --</option>";
                    foreach ($options as $opt) {
                        $optVal = is_array($opt) ? ($opt['value'] ?? '') : $opt;
                        $optLabel = is_array($opt) ? ($opt['label'] ?? $optVal) : $opt;
                        $selected = ((string)$val === (string)$optVal) ? ' selected' : '';
                        $html .= "<option value=\"" . htmlspecialchars((string)$optVal, ENT_QUOTES, 'UTF-8') . "\"{$selected}>" . htmlspecialchars((string)$optLabel, ENT_QUOTES, 'UTF-8') . "</option>";
                    }
                    $html .= "</select>";
                    break;

                case 'amount':
                case 'currency':
                    $min = $field['min'] ?? '0.01';
                    $step = $field['step'] ?? '0.01';
                    $html .= "<div class=\"input-group\">";
                    $html .= "<span class=\"input-group-text bg-light text-muted fw-bold\">\$</span>";
                    $html .= "<input type=\"number\" id=\"field_{$name}\" name=\"data[{$name}]\" value=\"{$valSafe}\" step=\"{$step}\" min=\"{$min}\" class=\"form-control enterprise-input {$errClass}\" placeholder=\"0.00\"" . ($required ? ' required' : '') . ">";
                    $html .= "<span class=\"input-group-text bg-light text-muted small\">USD</span>";
                    $html .= "</div>";
                    break;

                case 'number':
                    $min = isset($field['min']) ? " min=\"{$field['min']}\"" : '';
                    $max = isset($field['max']) ? " max=\"{$field['max']}\"" : '';
                    $step = isset($field['step']) ? " step=\"{$field['step']}\"" : ' step="1"';
                    $html .= "<input type=\"number\" id=\"field_{$name}\" name=\"data[{$name}]\" value=\"{$valSafe}\" class=\"form-control enterprise-input {$errClass}\" placeholder=\"{$placeholder}\"{$min}{$max}{$step}" . ($required ? ' required' : '') . ">";
                    break;

                case 'date':
                    $html .= "<input type=\"date\" id=\"field_{$name}\" name=\"data[{$name}]\" value=\"{$valSafe}\" class=\"form-control enterprise-input {$errClass}\"" . ($required ? ' required' : '') . ">";
                    break;

                case 'file':
                    $accept = htmlspecialchars($field['accept'] ?? '*/*', ENT_QUOTES, 'UTF-8');
                    $html .= "<input type=\"file\" id=\"field_{$name}\" name=\"file_{$name}\" class=\"form-control enterprise-input {$errClass}\" accept=\"{$accept}\"" . ($required && empty($valSafe) ? ' required' : '') . ">";
                    if (!empty($valSafe)) {
                        $html .= "<div class=\"form-text text-success small mt-1\"><i class=\"bi bi-paperclip\"></i> Attached file: <strong>{$valSafe}</strong></div>";
                    }
                    break;

                default: // text, email, etc.
                    $inputType = ($type === 'email') ? 'email' : 'text';
                    $html .= "<input type=\"{$inputType}\" id=\"field_{$name}\" name=\"data[{$name}]\" value=\"{$valSafe}\" class=\"form-control enterprise-input {$errClass}\" placeholder=\"{$placeholder}\"" . ($required ? ' required' : '') . ">";
                    break;
            }

            if ($hint !== '') {
                $html .= "<div class=\"form-text text-muted small mt-1\"><i class=\"bi bi-info-circle me-1\"></i>{$hint}</div>";
            }

            if ($hasError) {
                $html .= "<div class=\"invalid-feedback d-block\">{$errorMsg}</div>";
            }

            $html .= "</div>"; // .form-group
            $html .= "</div>"; // .col
        }

        $html .= '</div>'; // .row
        return $html;
    }

    /**
     * Validate incoming form submission against schema
     *
     * @return array{valid: bool, sanitized: array, errors: array}
     */
    public function validate(array $fields, array $data, array $files = []): array
    {
        $sanitized = [];
        $errors = [];

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($fields as $field) {
            $name     = $field['name'] ?? '';
            $label    = $field['label'] ?? ucfirst($name);
            $type     = $field['type'] ?? 'text';
            $required = !empty($field['required']);

            if ($type === 'file') {
                $fileKey = "file_{$name}";
                if (isset($files[$fileKey]) && $files[$fileKey]['error'] === UPLOAD_ERR_OK) {
                    $file = $files[$fileKey];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowedExts = ['pdf', 'png', 'jpg', 'jpeg', 'docx', 'xlsx', 'csv', 'txt'];
                    
                    if (!in_array($ext, $allowedExts, true)) {
                        $errors[$name] = "Uploaded file type (.{$ext}) is not permitted. Allowed: " . implode(', ', $allowedExts);
                    } elseif ($file['size'] > 15 * 1024 * 1024) {
                        $errors[$name] = "File exceeds the maximum upload threshold (15MB).";
                    } else {
                        $safeFilename = 'doc_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        $dest = $uploadDir . $safeFilename;
                        if (move_uploaded_file($file['tmp_name'], $dest)) {
                            $sanitized[$name] = $safeFilename;
                        } else {
                            $errors[$name] = "Internal error writing uploaded file to storage.";
                        }
                    }
                } elseif ($required && empty($data[$name])) {
                    $errors[$name] = "{$label} attachment is required.";
                } else {
                    // Keep existing file if editing/re-submitting
                    $sanitized[$name] = $data[$name] ?? '';
                }
                continue;
            }

            $rawVal = $data[$name] ?? null;

            if ($required && ($rawVal === null || trim((string)$rawVal) === '')) {
                $errors[$name] = "{$label} is required.";
                continue;
            }

            if ($rawVal === null || trim((string)$rawVal) === '') {
                $sanitized[$name] = '';
                continue;
            }

            $rawVal = trim((string)$rawVal);

            switch ($type) {
                case 'amount':
                case 'currency':
                case 'number':
                    if (!is_numeric($rawVal)) {
                        $errors[$name] = "{$label} must be a valid numeric amount.";
                    } else {
                        $num = (float)$rawVal;
                        if (isset($field['min']) && $num < (float)$field['min']) {
                            $errors[$name] = "{$label} must be at least " . $field['min'];
                        } elseif (isset($field['max']) && $num > (float)$field['max']) {
                            $errors[$name] = "{$label} cannot exceed " . $field['max'];
                        } else {
                            $sanitized[$name] = ($type === 'number') ? (string)$num : number_format($num, 2, '.', '');
                        }
                    }
                    break;

                case 'email':
                    if (!filter_var($rawVal, FILTER_VALIDATE_EMAIL)) {
                        $errors[$name] = "{$label} must be a valid corporate email address.";
                    } else {
                        $sanitized[$name] = $rawVal;
                    }
                    break;

                case 'date':
                    $d = \DateTime::createFromFormat('Y-m-d', $rawVal);
                    if (!$d || $d->format('Y-m-d') !== $rawVal) {
                        $errors[$name] = "{$label} must be in valid YYYY-MM-DD date format.";
                    } else {
                        $sanitized[$name] = $rawVal;
                    }
                    break;

                case 'select':
                    $validOptions = $field['options'] ?? [];
                    // Extract values if associative
                    $allowedVals = [];
                    foreach ($validOptions as $opt) {
                        $allowedVals[] = is_array($opt) ? (string)($opt['value'] ?? '') : (string)$opt;
                    }
                    if (!empty($allowedVals) && !in_array($rawVal, $allowedVals, true)) {
                        $errors[$name] = "Selected value is not among approved options.";
                    } else {
                        $sanitized[$name] = $rawVal;
                    }
                    break;

                default:
                    $sanitized[$name] = htmlspecialchars($rawVal, ENT_QUOTES, 'UTF-8');
                    break;
            }
        }

        return [
            'valid'     => empty($errors),
            'sanitized' => $sanitized,
            'errors'    => $errors
        ];
    }

    /**
     * Render a clean presentation array for details views
     */
    public function formatReadonlyFields(array $fields, array $data): array
    {
        $formatted = [];
        foreach ($fields as $field) {
            $name  = $field['name'] ?? '';
            $label = $field['label'] ?? ucfirst($name);
            $type  = $field['type'] ?? 'text';
            $val   = $data[$name] ?? '';

            if ($val === '' || $val === null) {
                $display = '<span class="text-muted fst-italic">Not Specified</span>';
            } elseif ($type === 'amount' || $type === 'currency') {
                $display = '<strong class="text-success fs-5">$' . number_format((float)$val, 2) . ' USD</strong>';
            } elseif ($type === 'file') {
                $display = '<a href="/uploads/' . htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-arrow-down me-1"></i> ' . htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') . '</a>';
            } elseif ($type === 'date') {
                $display = date('M d, Y', strtotime((string)$val));
            } elseif ($type === 'textarea') {
                $display = nl2br(htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8'));
            } else {
                $display = htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
            }

            $formatted[] = [
                'name'    => $name,
                'label'   => $label,
                'type'    => $type,
                'raw'     => $val,
                'display' => $display,
                'col'     => $field['col'] ?? 6
            ];
        }

        return $formatted;
    }
}
