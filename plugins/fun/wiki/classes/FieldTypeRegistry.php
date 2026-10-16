<?php declare(strict_types=1);

namespace Fun\Wiki\Classes;

use App;
use Fun\Wiki\Contracts\FieldTypeInterface;
use Fun\Wiki\Classes\Fields\TextFieldType;
use Fun\Wiki\Classes\Fields\MarkdownFieldType;
use Fun\Wiki\Classes\Fields\NumberFieldType;
use Fun\Wiki\Classes\Fields\DateFieldType;
use Fun\Wiki\Classes\Fields\CheckboxFieldType;
use Fun\Wiki\Classes\Fields\ImageFieldType;
use Fun\Wiki\Classes\Fields\FileFieldType;
use Fun\Wiki\Classes\Fields\RelationFieldType;
use Fun\Wiki\Classes\Fields\ParentFieldType;
use Fun\Wiki\Classes\Fields\TagFieldType;
use System\Classes\PluginManager;

class FieldTypeRegistry
{
    /** @var array<string, FieldTypeInterface> */
    protected array $fieldTypes = [];

    protected ?PluginManager $pluginManager = null;

    public function __construct(?PluginManager $pluginManager = null)
    {
        $this->pluginManager = $pluginManager;
        $this->registerDefaultTypes();

        if ($this->pluginManager !== null) {
            $this->loadPluginExtensions();
        } else {
            try {
                if (\Illuminate\Support\Facades\App::bound('system.plugins')) {
                    $this->pluginManager = PluginManager::instance();
                    $this->loadPluginExtensions();
                }
            } catch (\Throwable $e) {
                // Ignore if container not fully booted (e.g. in standalone unit tests)
            }
        }
    }

    public static function instance(): static
    {
        if (!App::bound('fun.wiki.field_registry')) {
            App::singleton('fun.wiki.field_registry', fn() => new static());
        }

        return App::make('fun.wiki.field_registry');
    }

    protected function registerDefaultTypes(): void
    {
        $this->register(new TextFieldType());
        $this->register(new MarkdownFieldType());
        $this->register(new NumberFieldType());
        $this->register(new DateFieldType());
        $this->register(new CheckboxFieldType());
        $this->register(new ImageFieldType());
        $this->register(new FileFieldType());
        $this->register(new RelationFieldType());
        $this->register(new ParentFieldType());
        $this->register(new TagFieldType());
    }

    public function register(FieldTypeInterface $fieldType): void
    {
        $this->fieldTypes[$fieldType->getCode()] = $fieldType;
    }

    public function get(string $code): ?FieldTypeInterface
    {
        $code = match ($code) {
            'tag' => 'tags',
            'relation_list' => 'relation',
            default => $code,
        };

        return $this->fieldTypes[$code] ?? null;
    }

    /**
     * @return array<string, FieldTypeInterface> Code => FieldTypeInterface
     */
    public function getAll(): array
    {
        return $this->fieldTypes;
    }

    /**
     * @return array<string, string> Code => Label
     */
    public function getOptions(): array
    {
        $options = [];
        foreach ($this->fieldTypes as $code => $type) {
            $options[$code] = $type->getLabel();
        }

        return $options;
    }

    protected function loadPluginExtensions(): void
    {
        foreach ($this->pluginManager->getPlugins() as $plugin) {
            if (method_exists($plugin, 'registerTemplateFields')) {
                $customTypes = $plugin->registerTemplateFields();
                if (is_array($customTypes)) {
                    foreach ($customTypes as $class) {
                        if (is_subclass_of($class, FieldTypeInterface::class)) {
                            $this->register(new $class());
                        }
                    }
                }
            }
        }
    }
}
