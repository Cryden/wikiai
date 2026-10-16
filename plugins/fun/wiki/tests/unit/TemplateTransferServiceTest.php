<?php declare(strict_types=1);

namespace Fun\Wiki\Tests\Unit;

use Fun\Wiki\Services\TemplateTransferService;
use Fun\Wiki\Models\Template;
use Fun\Wiki\Models\Field;
use Schema;

class TemplateTransferServiceTest extends \PluginTestCase
{
    protected TemplateTransferService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new TemplateTransferService();

        require_once __DIR__ . '/../../updates/create_templates_table.php';
        require_once __DIR__ . '/../../updates/create_fields_table.php';

        Schema::dropIfExists('fun_wiki_fields');
        Schema::dropIfExists('fun_wiki_templates');

        (new \Fun\Wiki\Updates\CreateTemplatesTable)->up();
        (new \Fun\Wiki\Updates\CreateFieldsTable)->up();
    }

    public function testThrowsExceptionOnInvalidJson(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->import('invalid-json-content');
    }

    public function testThrowsExceptionOnEmptyData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->import([]);
    }

    public function testImportUserTemplatesJsonSuccessfully(): void
    {
        $jsonPath = 'C:/Users/PC/.gemini/antigravity/brain/01c49221-f0c4-4d8a-a94f-968c3267f07b/scratch/user_templates.json';
        $this->assertFileExists($jsonPath);

        $json = file_get_contents($jsonPath);
        $result = $this->service->import($json, true);

        $this->assertGreaterThan(0, $result['templates']);
        $this->assertGreaterThan(0, $result['fields']);

        $person = Template::where('code', 'person')->first();
        $this->assertNotNull($person);
        $this->assertEquals('Персона', $person->name);
        $this->assertNotNull($person->parent);
        $this->assertEquals('persons', $person->parent->code);

        $universe = Template::where('code', 'universe')->first();
        $this->assertNotNull($universe);

        $book = Template::where('code', 'book')->first();
        $this->assertNotNull($book);

        // Verify ancestry parent fields are present on book
        $parentField = $book->fields()->where('is_parent_field', true)->first();
        $this->assertNotNull($parentField);

        // Test export produces correct structure
        $export = $this->service->export();
        $this->assertIsArray($export);
        $this->assertCount($result['templates'], $export);
    }

    public function testRoundTripExportAndReimport(): void
    {
        $jsonPath = 'C:/Users/PC/.gemini/antigravity/brain/01c49221-f0c4-4d8a-a94f-968c3267f07b/scratch/user_templates.json';
        $json = file_get_contents($jsonPath);
        $this->service->import($json, true);

        $exportData = $this->service->export();
        $this->assertNotEmpty($exportData);

        // Re-import the exported data
        $reimportResult = $this->service->import($exportData, true);
        $this->assertEquals(count($exportData), $reimportResult['templates']);

        $reExportData = $this->service->export();
        $this->assertEquals(count($exportData), count($reExportData));
    }
}

