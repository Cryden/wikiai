<?php declare(strict_types=1);

namespace Fun\Wiki\Tests\Unit;

use Fun\Wiki\Models\Article;
use Fun\Wiki\Models\Template;
use Fun\Wiki\Models\Field;
use Fun\Wiki\Services\TemplateTransferService;
use Schema;

class ArticleDynamicFieldsTest extends \PluginTestCase
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

    public function testGetTemplateFormFieldsReturnsDynamicFields(): void
    {
        $jsonPath = 'C:/Users/PC/.gemini/antigravity/brain/01c49221-f0c4-4d8a-a94f-968c3267f07b/scratch/user_templates.json';
        $json = file_get_contents($jsonPath);
        (new TemplateTransferService())->import($json, true);

        $bookTemplate = Template::where('code', 'book')->first();
        $this->assertNotNull($bookTemplate);

        $article = new Article();
        $article->template_id = $bookTemplate->id;

        $formFields = $article->getTemplateFormFields();
        $this->assertNotEmpty($formFields);

        // Should contain parent field in 'Цепочка предков' tab
        $this->assertArrayHasKey('data[universe]', $formFields);
        $this->assertEquals('Цепочка предков', $formFields['data[universe]']['tab']);

        // Should contain data field in 'Поля данных' tab
        $this->assertArrayHasKey('data[author]', $formFields);
        $this->assertEquals('Поля данных', $formFields['data[author]']['tab']);
    }

    public function testSaveArticleWithParentResolution(): void
    {
        $jsonPath = 'C:/Users/PC/.gemini/antigravity/brain/01c49221-f0c4-4d8a-a94f-968c3267f07b/scratch/user_templates.json';
        $json = file_get_contents($jsonPath);
        (new TemplateTransferService())->import($json, true);

        $universeTemplate = Template::where('code', 'universe')->first();
        $bookTemplate = Template::where('code', 'book')->first();

        // 1. Create a Universe article
        $universeArticle = new Article();
        $universeArticle->title = 'Властелин Колец';
        $universeArticle->slug = 'lotr';
        $universeArticle->template_id = $universeTemplate->id;
        $universeArticle->save();

        $this->assertNotNull($universeArticle->id);

        // 2. Create a Book article with data[universe] pointing to universeArticle->id
        $bookArticle = new Article();
        $bookArticle->title = 'Братство Кольца';
        $bookArticle->slug = 'fotr';
        $bookArticle->template_id = $bookTemplate->id;
        $bookArticle->data = [
            'universe' => $universeArticle->id,
            'pages_count' => 450,
        ];
        $bookArticle->save();

        $this->assertNotNull($bookArticle->id);
        // Parent ID should be resolved from data['universe']
        $this->assertEquals($universeArticle->id, $bookArticle->parent_id);
    }
}
