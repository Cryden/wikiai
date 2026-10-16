<?php declare(strict_types=1);

namespace Fun\Wiki\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\NestedTree;
use Fun\Wiki\Contracts\DisplayableEntityInterface;
use Fun\Wiki\Contracts\SyncableEntityInterface;
use Fun\Wiki\Traits\HasTemplateSchema;
use Fun\Wiki\Traits\HasAncestryChain;
use Fun\Wiki\Traits\HasDisplayFields;

/**
 * Class Article
 * Модель статьи базы знаний
 */
class Article extends Model implements DisplayableEntityInterface, SyncableEntityInterface
{
    use Validation;
    use NestedTree;
    use HasTemplateSchema;
    use HasAncestryChain;
    use HasDisplayFields;

    public $table = 'fun_wiki_articles';

    protected $fillable = [
        'title',
        'slug',
        'template_id',
        'parent_id',
        'base_article_id',
        'type',
        'data',
        'content',
        'status'
    ];

    protected $jsonable = ['data'];

    public $rules = [
        'title'       => 'required',
        'template_id' => 'required'
    ];

    public $belongsTo = [
        'template'     => [Template::class, 'key' => 'template_id'],
        'base_article' => [Article::class, 'key' => 'base_article_id'],
    ];

    public $hasMany = [
        'variants' => [Article::class, 'key' => 'base_article_id'],
    ];

    /**
     * Вычисление контрольного хэша содержимого (для детекции конфликтов при Sync)
     */
    public function getContentHash(): string
    {
        return md5(json_encode([
            'title'   => $this->title,
            'slug'    => $this->slug,
            'data'    => $this->data,
            'content' => $this->content,
            'type'    => $this->type,
        ]));
    }

    /**
     * Экспорт в формат сериализации для API и Local Studio
     */
    public function toSyncArray(): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'slug'            => $this->slug,
            'type'            => $this->type,
            'template_code'   => $this->template?->code,
            'parent_slug'     => $this->parent?->slug,
            'base_slug'       => $this->base_article?->slug,
            'data'            => $this->data ?? [],
            'display_data'    => $this->getDisplayData(),
            'breadcrumbs'     => $this->getBreadcrumbs(),
            'full_path'       => $this->getFullSlugPath(),
            'content'         => $this->content,
            'content_hash'    => $this->getContentHash(),
            'status'          => $this->status,
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
