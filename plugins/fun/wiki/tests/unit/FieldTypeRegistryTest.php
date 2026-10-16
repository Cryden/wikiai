<?php declare(strict_types=1);

namespace Fun\Wiki\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Fun\Wiki\Classes\FieldTypeRegistry;
use Fun\Wiki\Contracts\FieldTypeInterface;

class FieldTypeRegistryTest extends TestCase
{
    protected FieldTypeRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new FieldTypeRegistry();
    }

    public function testDefaultFieldTypesAreRegistered(): void
    {
        $expectedTypes = [
            'text',
            'markdown',
            'number',
            'date',
            'checkbox',
            'image',
            'file',
            'relation',
            'parent',
            'tags'
        ];

        foreach ($expectedTypes as $typeCode) {
            $type = $this->registry->get($typeCode);
            $this->assertNotNull($type, "Field type '{$typeCode}' should be registered");
            $this->assertInstanceOf(FieldTypeInterface::class, $type);
            $this->assertSame($typeCode, $type->getCode());
            $this->assertNotEmpty($type->getLabel());
        }
    }

    public function testGetOptionsReturnsAllLabels(): void
    {
        $options = $this->registry->getOptions();

        $this->assertArrayHasKey('text', $options);
        $this->assertArrayHasKey('markdown', $options);
        $this->assertArrayHasKey('relation', $options);
        $this->assertArrayHasKey('parent', $options);
        $this->assertSame('Текст (Строка)', $options['text']);
        $this->assertSame('Связь со статьей (Сущность)', $options['relation']);
        $this->assertSame('Родительское поле (Цепочка предков)', $options['parent']);
    }

    public function testGetUnknownFieldReturnsNull(): void
    {
        $this->assertNull($this->registry->get('non_existent_type'));
    }
}
