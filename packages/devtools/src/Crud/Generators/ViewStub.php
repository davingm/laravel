<?php

declare(strict_types=1);

namespace Davingm\LaravelDevtools\Crud\Generators;

final class ViewStub
{
    /**
     * @param list<array{name: string, label: string, blade_expr: string}> $columns
     */
    public function renderIndex(
        string $table,
        string $modelVar,
        string $modelPluralVar,
        string $title,
        string $createButtonLabel,
        array $columns,
        string $editRoute,
        string $destroyRoute,
        bool $hasPagination,
    ): string {
        $lines = [
            "@extends('layouts.app')",
            '',
            "@section('content')",
            '<div class="container">',
            '    <div class="d-flex justify-content-between align-items-center mb-3">',
            '        <h1>'.htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</h1>',
            '        <a href="{{ route('.var_export($table.'.create', true).') }}" class="btn btn-primary">'.htmlspecialchars($createButtonLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</a>',
            '    </div>',
            '    <table class="table table-bordered">',
            '        <thead>',
            '            <tr>',
        ];

        foreach ($columns as $column) {
            $lines[] = '                <th>'.htmlspecialchars($column['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</th>';
        }
        $lines[] = '                <th>Actions</th>';
        $lines[] = '            </tr>';
        $lines[] = '        </thead>';
        $lines[] = '        <tbody>';
        $lines[] = "            @foreach(\${$modelPluralVar} as \${$modelVar})";
        $lines[] = '                <tr>';
        foreach ($columns as $column) {
            $lines[] = '                    <td>{{ '.$column['blade_expr'].' }}</td>';
        }
        $lines[] = '                    <td>';
        $lines[] = '                        <a href="{{ route('.var_export($editRoute, true).', $'.$modelVar.') }}" class="btn btn-primary">Edit</a>';
        $lines[] = '                        <form method="POST" action="{{ route('.var_export($destroyRoute, true).', $'.$modelVar.') }}" onsubmit="return confirm(\'Are you sure?\')" class="d-inline">';
        $lines[] = '                            @csrf';
        $lines[] = "                            @method('DELETE')";
        $lines[] = '                            <button type="submit" class="btn btn-danger">Delete</button>';
        $lines[] = '                        </form>';
        $lines[] = '                    </td>';
        $lines[] = '                </tr>';
        $lines[] = '            @endforeach';
        $lines[] = '        </tbody>';
        $lines[] = '    </table>';
        if ($hasPagination) {
            $lines[] = "    {{ \${$modelPluralVar}->links() }}";
        }
        $lines[] = '</div>';
        $lines[] = '@endsection';

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    /** @param list<string> $formFields */
    public function renderCreate(
        string $table,
        string $title,
        array $formFields,
        string $storeRoute,
        string $backRoute,
    ): string {
        $lines = [
            "@extends('layouts.app')",
            '',
            "@section('content')",
            '<div class="container">',
            '    <h1>'.htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</h1>',
            $this->errorBlock(),
            '    <form method="POST" action="{{ route('.var_export($storeRoute, true).') }}">',
            '        @csrf',
        ];
        foreach ($formFields as $field) {
            $lines[] = $this->indent($field, 2);
        }
        $lines[] = '        <button type="submit" class="btn btn-primary">Save</button>';
        $lines[] = '        <a href="{{ route('.var_export($backRoute, true).') }}" class="btn btn-secondary">Cancel</a>';
        $lines[] = '    </form>';
        $lines[] = '</div>';
        $lines[] = '@endsection';

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    /** @param list<string> $formFields */
    public function renderEdit(
        string $table,
        string $modelVar,
        string $title,
        array $formFields,
        string $updateRoute,
        string $backRoute,
    ): string {
        $lines = [
            "@extends('layouts.app')",
            '',
            "@section('content')",
            '<div class="container">',
            '    <h1>'.htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</h1>',
            $this->errorBlock(),
            '    <form method="POST" action="{{ route('.var_export($updateRoute, true).', $'.$modelVar.') }}">',
            '        @csrf',
            "        @method('PUT')",
        ];
        foreach ($formFields as $field) {
            $lines[] = $this->indent($field, 2);
        }
        $lines[] = '        <button type="submit" class="btn btn-primary">Update</button>';
        $lines[] = '        <a href="{{ route('.var_export($backRoute, true).') }}" class="btn btn-secondary">Cancel</a>';
        $lines[] = '    </form>';
        $lines[] = '</div>';
        $lines[] = '@endsection';

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    private function errorBlock(): string
    {
        return implode(PHP_EOL, [
            '    @if($errors->any())',
            '        <div class="alert alert-danger">',
            '            <ul>',
            '                @foreach($errors->all() as $error)',
            '                    <li>{{ $error }}</li>',
            '                @endforeach',
            '            </ul>',
            '        </div>',
            '    @endif',
        ]);
    }

    private function indent(string $content, int $levels): string
    {
        $indent = str_repeat('    ', $levels);

        return $indent.str_replace(PHP_EOL, PHP_EOL.$indent, $content);
    }
}
