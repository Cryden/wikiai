<?php declare(strict_types=1);

namespace Fun\Wiki\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Fun\Wiki\Models\Article;
use Fun\Wiki\Models\Template;

class HasDisplayFieldsTest extends TestCase
{
    public function testFiltersDataBasedOnTemplateDisplayFields(): void
    {
        $template = new Template([
            'display_fields' => ['population', 'leader']
        ]);

        $article = new Article([
            'data' => [
                'population' => 5000000,
                'leader'     => 'Aragorn',
                'secret_key' => 'hidden_value',
                'notes'      => 'internal author notes'
            ]
        ]);
        $article->setRelation('template', $template);

        $displayData = $article->getDisplayData();

        $this->assertArrayHasKey('population', $displayData);
        $this->assertArrayHasKey('leader', $displayData);
        $this->assertArrayNotHasKey('secret_key', $displayData);
        $this->assertArrayNotHasKey('notes', $displayData);
        $this->assertEquals(5000000, $displayData['population']);
        $this->assertEquals('Aragorn', $displayData['leader']);
    }

    public function testReturnsEmptyArrayWhenTemplateNotSet(): void
    {
        $article = new Article(['data' => ['foo' => 'bar']]);
        $this->assertEmpty($article->getDisplayData());
    }
}
