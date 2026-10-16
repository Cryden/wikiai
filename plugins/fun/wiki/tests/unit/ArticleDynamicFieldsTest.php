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
        // Parent of Book article is automatically resolved to the intermediate structural node 'books'
        $this->assertEquals('books', $bookArticle->parent?->slug);
        $this->assertEquals($universeArticle->id, $bookArticle->parent?->parent_id);

        // Verify fullslug generation with intermediate nodes included
        $this->assertEquals('universes/lotr', $universeArticle->fullslug);
        $this->assertEquals('universes/lotr/books/fotr', $bookArticle->fullslug);
    }

    public function testFullSlugCalculationAndSearch(): void
    {
        $template = Template::create([
            'name' => 'General',
            'code' => 'general'
        ]);

        // 1. Root article
        $root = Article::create([
            'title'       => 'Universes',
            'slug'        => 'universes',
            'template_id' => $template->id,
        ]);
        $this->assertEquals('universes', $root->fullslug);

        // 2. Child article
        $child = Article::create([
            'title'       => 'Dune',
            'slug'        => 'dune',
            'parent_id'   => $root->id,
            'template_id' => $template->id,
        ]);
        $this->assertEquals('universes/dune', $child->fullslug);

        // 3. Grandchild article
        $grandchild = Article::create([
            'title'       => 'Paul Atreides',
            'slug'        => 'paul',
            'parent_id'   => $child->id,
            'template_id' => $template->id,
        ]);
        $this->assertEquals('universes/dune/paul', $grandchild->fullslug);

        // 4. Fast search via scopeByFullSlug
        $found = Article::byFullSlug('universes/dune/paul')->first();
        $this->assertNotNull($found);
        $this->assertEquals($grandchild->id, $found->id);

        // 5. Updating child slug cascades to grandchild fullslug
        $child->slug = 'arrakis';
        $child->save();

        $this->assertEquals('universes/arrakis', $child->fullslug);
        $grandchild->refresh();
        $this->assertEquals('universes/arrakis/paul', $grandchild->fullslug);
    }
}
