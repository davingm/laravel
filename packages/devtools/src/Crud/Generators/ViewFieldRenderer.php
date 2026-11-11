<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Data\ColumnDefinition;
use Davingm\LaravelDevtools\Crud\Data\RelationshipDefinition;
use Illuminate\Support\Str;

final class ViewFieldRenderer
{
    public function renderField(
        ColumnDefinition $column,
        ?RelationshipDefinition $relationship,
        string $modelVar,
        bool $isEdit,
    ): string {
        $name = $column->name;
        $escapedName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $label = htmlspecialchars(Str::headline($name), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $modelValue = '$'.$modelVar.$this->propertyAccess($name);
        $oldValue = $isEdit ? "old('{$escapedName}', {$modelValue} ?? '')" : "old('{$escapedName}')";

        if ($relationship !== null && $relationship->isNamed() && $relationship->supportsEloquentRelation()) {
            return $this->renderRelationshipSelect($column, $relationship, $modelVar, $escapedName, $label, $isEdit);
        }

        $type = $this->normalizedType($column->type);
        if (in_array($type, ['bool', 'boolean'], true)) {
            $checkedValue = $isEdit ? "old('{$escapedName}', {$modelValue} ?? false)" : "old('{$escapedName}', false)";

            return implode(PHP_EOL, [
                '<div class="form-check">',
                "    <input type=\"checkbox\" name=\"{$escapedName}\" id=\"{$escapedName}\" value=\"1\" class=\"form-check-input\" {{ {$checkedValue} ? 'checked' : '' }}>",
                "    <label for=\"{$escapedName}\" class=\"form-check-label\">{$label}</label>",
                '</div>',
            ]);
        }

        if ($type === 'enum') {
            $enumValues = $this->enumValues($column);
            if ($enumValues !== null) {
                return $this->renderEnumSelect($column, $escapedName, $label, $oldValue, $enumValues);
            }

            return "<!-- TODO: replace with select when enum metadata available -->".PHP_EOL
                .$this->renderInput('text', $escapedName, $label, $oldValue);
        }

        if (in_array($type, ['text', 'mediumtext', 'longtext'], true)) {
            return implode(PHP_EOL, [
                '<div class="form-group">',
                "    <label for=\"{$escapedName}\">{$label}</label>",
                "    <textarea name=\"{$escapedName}\" id=\"{$escapedName}\" class=\"form-control\">{{ {$oldValue} }}</textarea>",
                '</div>',
            ]);
        }

        $inputType = match ($type) {
            'integer', 'bigint', 'smallint', 'tinyint' => 'number',
            'decimal', 'float', 'double', 'numeric' => 'number',
            'date' => 'date',
            'datetime', 'timestamp' => 'datetime-local',
            'time' => 'time',
            default => 'text',
        };
        $step = in_array($type, ['decimal', 'float', 'double', 'numeric'], true) ? '0.01' : null;

        return $this->renderInput($inputType, $escapedName, $label, $oldValue, $step);
    }

    private function renderInput(string $type, string $name, string $label, string $value, ?string $step = null): string
    {
        $stepAttribute = $step === null ? '' : ' step="'.$step.'"';

        return implode(PHP_EOL, [
            '<div class="form-group">',
            "    <label for=\"{$name}\">{$label}</label>",
            "    <input type=\"{$type}\" name=\"{$name}\" id=\"{$name}\" class=\"form-control\"{$stepAttribute} value=\"{{ {$value} }}\">",
            '</div>',
        ]);
    }

    private function renderRelationshipSelect(
        ColumnDefinition $column,
        RelationshipDefinition $relationship,
        string $modelVar,
        string $name,
        string $label,
        bool $isEdit,
    ): string {
        if (count($relationship->columns) !== 1 || count($relationship->foreignColumns) !== 1) {
            return $this->renderInput('text', $name, $label, "old('{$name}')");
        }

        $relatedTableVar = Str::camel($relationship->foreignTable);
        $relatedColumn = $this->propertyAccess($relationship->foreignColumns[0]);
        $localModelValue = '$'.$modelVar.$this->propertyAccess($column->name);
        $selectedValue = $isEdit
            ? "old('{$name}', {$localModelValue} ?? '')"
            : "old('{$name}', '')";
        $optionLabel = '$option->name ?? $option->title ?? $option->label ?? $option'.$relatedColumn;

        return implode(PHP_EOL, [
            '<div class="form-group">',
            "    <label for=\"{$name}\">{$label}</label>",
            "    <select name=\"{$name}\" id=\"{$name}\" class=\"form-control\">",
            '        <option value="">-- Select --</option>',
            "        @foreach(\${$relatedTableVar} as \$option)",
            "            <option value=\"{{ \$option{$relatedColumn} }}\" {{ {$selectedValue} == \$option{$relatedColumn} ? 'selected' : '' }}>",
            "                {{ {$optionLabel} }}",
            '            </option>',
            '        @endforeach',
            '    </select>',
            '</div>',
        ]);
    }

    /** @param list<string> $values */
    private function renderEnumSelect(ColumnDefinition $column, string $name, string $label, string $oldValue, array $values): string
    {
        $lines = [
            '<div class="form-group">',
            "    <label for=\"{$name}\">{$label}</label>",
            "    <select name=\"{$name}\" id=\"{$name}\" class=\"form-control\">",
        ];
        foreach ($values as $value) {
            $escapedValue = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $lines[] = "        <option value=\"{$escapedValue}\" {{ {$oldValue} == ".var_export($value, true)." ? 'selected' : '' }}>{$escapedValue}</option>";
        }
        $lines[] = '    </select>';
        $lines[] = '</div>';

        return implode(PHP_EOL, $lines);
    }

    /** @return list<string>|null */
    private function enumValues(ColumnDefinition $column): ?array
    {
        foreach (['allowed', 'values', 'enum_values'] as $key) {
            if (! array_key_exists($key, $column->metadata)) {
                continue;
            }

            $values = $column->metadata[$key];
            if (! is_array($values)) {
                return null;
            }

            foreach ($values as $value) {
                if (! is_string($value)) {
                    return null;
                }
            }

            return array_values($values);
        }

        return null;
    }

    private function normalizedType(string $type): string
    {
        $type = strtolower(trim($type));
        $type = preg_replace('/\s*\(.*$/', '', $type) ?? $type;

        return explode(' ', $type, 2)[0];
    }

    private function propertyAccess(string $name): string
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $name) === 1) {
            return '->'.$name;
        }

        return '->{'.var_export($name, true).'}';
    }
}
