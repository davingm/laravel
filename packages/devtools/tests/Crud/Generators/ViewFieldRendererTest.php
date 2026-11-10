<?php

declare(strict_types=1);

namespace Tests\Crud\Generators;

use Davingm\LaravelDevtools\Crud\Data\ColumnDefinition;
use Davingm\LaravelDevtools\Crud\Data\RelationshipDefinition;
use Davingm\LaravelDevtools\Crud\Generators\ViewFieldRenderer;
use PHPUnit\Framework\TestCase;

final class ViewFieldRendererTest extends TestCase
{
    public function test_text_field_renders_input(): void
    {
        $html = (new ViewFieldRenderer())->renderField($this->column('name', 'varchar'), null, 'product', false);

        self::assertStringContainsString('<input type="text" name="name"', $html);
    }

    public function test_textarea_for_text_type(): void
    {
        $html = (new ViewFieldRenderer())->renderField($this->column('description', 'text'), null, 'product', false);

        self::assertStringContainsString('<textarea name="description"', $html);
    }

    public function test_number_with_step_for_decimal(): void
    {
        $html = (new ViewFieldRenderer())->renderField($this->column('price', 'decimal'), null, 'product', false);

        self::assertStringContainsString('type="number"', $html);
        self::assertStringContainsString('step="0.01"', $html);
    }

    public function test_checkbox_for_boolean(): void
    {
        $html = (new ViewFieldRenderer())->renderField($this->column('is_active', 'boolean'), null, 'product', true);

        self::assertStringContainsString('class="form-check"', $html);
        self::assertStringContainsString('type="checkbox"', $html);
        self::assertStringContainsString("old('is_active', \$product->is_active ?? false) ? 'checked' : ''", $html);
    }

    public function test_select_for_foreign_key(): void
    {
        $relationship = new RelationshipDefinition(null, ['category_id'], 'categories', ['id']);
        $relationship->assignNames('category', 'Category');
        $html = (new ViewFieldRenderer())->renderField($this->column('category_id', 'bigint'), $relationship, 'product', true);

        self::assertStringContainsString('<select name="category_id"', $html);
        self::assertStringContainsString('@foreach($categories as $option)', $html);
        self::assertStringContainsString('$option->name ?? $option->title ?? $option->label ?? $option->id', $html);
        self::assertStringContainsString('$option->id', $html);
        self::assertStringNotContainsString('TODO: use related display column', $html);
    }

    public function test_enum_fallback_to_text_with_todo(): void
    {
        $html = (new ViewFieldRenderer())->renderField($this->column('status', 'enum'), null, 'product', false);

        self::assertStringContainsString('<!-- TODO:', $html);
        self::assertStringContainsString('<input type="text" name="status"', $html);
    }

    public function test_edit_uses_old_with_model_default(): void
    {
        $html = (new ViewFieldRenderer())->renderField($this->column('name', 'varchar'), null, 'product', true);

        self::assertStringContainsString("old('name', \$product->name ?? '')", $html);
    }

    private function column(string $name, string $type): ColumnDefinition
    {
        return new ColumnDefinition($name, $type, false, null, false, false);
    }
}
