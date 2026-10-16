<?php declare(strict_types=1);

namespace Fun\Wiki\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Fun\Wiki\Services\AncestryService;
use Fun\Wiki\Models\Template;
use Fun\Wiki\Models\Article;

class AncestryServiceTest extends TestCase
{
    protected AncestryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AncestryService();
    }

    public function testReturnsParentIdWhenTemplateHasNoParent(): void
    {
        $template = new Template(['type' => 'article']);
        // parent is null

        $result = $this->service->resolveIntermediateNode($template, 42);

        $this->assertEquals(42, $result);
    }

    public function testReturnsParentIdWhenParentTemplateIsNotNode(): void
    {
        $child = new Template(['type' => 'article']);
        $parent = new Template(['type' => 'article', 'is_mandatory' => true]);
        $child->setRelation('parent', $parent);

        $result = $this->service->resolveIntermediateNode($child, 100);

        $this->assertEquals(100, $result);
    }
}
