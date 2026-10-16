<?php declare(strict_types=1);

namespace Fun\Wiki\Classes\Fields;

class MarkdownFieldType extends BaseFieldType
{
    public function getCode(): string
    {
        return 'markdown';
    }

    public function getLabel(): string
    {
        return 'Большой текст (Markdown)';
    }

    public function getDescription(): string
    {
        return 'Многострочный текст с поддержкой Markdown разметки';
    }

    public function getIcon(): string
    {
        return 'icon-file-text-o';
    }

    public function getFieldConfig(): array
    {
        return [
            'type' => 'markdown',
            'size' => 'large',
            'span' => 'full'
        ];
    }
}
