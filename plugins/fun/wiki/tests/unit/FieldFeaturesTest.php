<?php declare(strict_types=1);

namespace Fun\Wiki\Tests\Unit;

use Fun\Wiki\Models\Template;
use Fun\Wiki\Models\Field;
use Fun\Wiki\Services\TemplateInheritanceService;
use Schema;

class FieldFeaturesTest extends \PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/../../updates/create_templates_table.php';
        require_once __DIR__ . '/../../updates/create_fields_table.php';
        require_once __DIR__ . '/../../updates/create_articles_table.php';

        Schema::dropIfExists('fun_wiki_articles');
        Schema::dropIfExists('fun_wiki_fields');
        Schema::dropIfExists('fun_wiki_templates');

        (new \Fun\Wiki\Updates\CreateTemplatesTable)->up();
        (new \Fun\Wiki\Updates\CreateFieldsTable)->up();
        (new \Fun\Wiki\Updates\CreateArticlesTable)->up();
    }

    public function testGlobalFieldsAreInheritedByDescendants(): void
    {
        // 1. Создаем родительский шаблон (Персона) с глобальным полем
        $personTemplate = Template::create([
            'name' => 'Персона',
            'code' => 'person',
            'type' => 'article',
        ]);

        $globalField = Field::create([
            'template_id'  => $personTemplate->id,
            'label'        => 'Оригинальное имя',
            'name'         => 'original_name',
            'type'         => 'text',
            'is_global'    => true,
            'is_mandatory' => false,
        ]);

        // 2. Создаем дочерний шаблон (Писатель)
        $writerTemplate = Template::create([
            'name'      => 'Писатель',
            'code'      => 'writer',
            'type'      => 'article',
            'parent_id' => $personTemplate->id,
        ]);

        $ownField = Field::create([
            'template_id'  => $writerTemplate->id,
            'label'        => 'Библиография',
            'name'         => 'bibliography',
            'type'         => 'markdown',
            'is_global'    => false,
            'is_mandatory' => false,
        ]);

        // Перестраиваем структуру дерева NestedTree
        (new Template)->resetTreeNesting();

        $service = new TemplateInheritanceService();
        $effectiveFields = $service->getEffectiveFields($writerTemplate);

        // Дочерний шаблон должен содержать и свое поле, и глобальное поле родителя
        $this->assertTrue($effectiveFields->contains('name', 'bibliography'));
        $this->assertTrue($effectiveFields->contains('name', 'original_name'));

        $inheritedOriginalName = $effectiveFields->firstWhere('name', 'original_name');
        $this->assertTrue($inheritedOriginalName->is_inherited);
    }

    public function testFieldTypeConfigurations(): void
    {
        $template = Template::create([
            'name' => 'Тест',
            'code' => 'test',
        ]);

        // Image with gallery and cover
        $imageField = Field::create([
            'template_id' => $template->id,
            'label'       => 'Галерея фото',
            'name'        => 'gallery',
            'type'        => 'image',
            'config'      => [
                'as_gallery' => true,
                'as_cover'   => true,
            ],
        ]);

        $registry = \Fun\Wiki\Classes\FieldTypeRegistry::instance();
        $imageConfig = $registry->get('image')->getFormFieldConfig($imageField);

        $this->assertEquals('mediafinder', $imageConfig['type']);
        $this->assertEquals(20, $imageConfig['maxItems']);

        // Tags with group and single selection
        $tagField = Field::create([
            'template_id' => $template->id,
            'label'       => 'Страна',
            'name'        => 'country',
            'type'        => 'tags',
            'config'      => [
                'field_group_name' => 'Страны',
                'is_multiple'      => false,
                'is_auto_create'   => true,
            ],
        ]);

        $tagConfig = $registry->get('tags')->getFormFieldConfig($tagField);
        $this->assertEquals('taglist', $tagConfig['type']);
        $this->assertEquals(1, $tagConfig['maxItems']);
        $this->assertTrue($tagConfig['customTags']);

        // Relation with multiple
        $relField = Field::create([
            'template_id' => $template->id,
            'label'       => 'Авторы',
            'name'        => 'authors',
            'type'        => 'relation',
            'config'      => [
                'target_template' => $template->id,
                'is_multiple'     => true,
            ],
        ]);

        $relConfig = $registry->get('relation')->getFormFieldConfig($relField);
        $this->assertEquals('taglist', $relConfig['type']);
        $this->assertEquals('array', $relConfig['mode']);
    }
}
