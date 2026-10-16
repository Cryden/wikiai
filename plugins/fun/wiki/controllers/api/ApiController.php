<?php namespace Fun\Wiki\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Fun\Wiki\Models\Template;
use Fun\Wiki\Models\Article;
use Fun\Wiki\Services\AncestryService;

class ApiController extends Controller
{
    protected AncestryService $ancestryService;

    public function __construct()
    {
        $this->ancestryService = new AncestryService();
    }

    /**
     * Список всех шаблонов вместе с полями
     */
    public function getTemplates(): JsonResponse
    {
        $templates = Template::with(['parent_template', 'fields'])->get()->map(function ($template) {
            return [
                'id'                 => $template->id,
                'name'               => $template->name,
                'code'               => $template->code,
                'description'        => $template->description,
                'type'               => $template->type,
                'parent_template_id' => $template->parent_template_id,
                'parent_code'        => $template->parent_template?->code,
                'slug'               => $template->slug,
                'is_mandatory'       => (bool) $template->is_mandatory,
                'is_auto_create'     => (bool) $template->is_auto_create,
                'is_nested'          => (bool) $template->is_nested,
                'display_fields'     => $template->display_fields ?? [],
                'effective_fields'   => $template->effective_fields
            ];
        });

        return response()->json(['data' => $templates]);
    }

    /**
     * Создание или обновление шаблона из Локального приложения
     */
    public function saveTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'                 => 'nullable|integer',
            'name'               => 'required|string',
            'code'               => 'required|string',
            'description'        => 'nullable|string',
            'type'               => 'required|in:node,article,variant',
            'parent_template_id' => 'nullable|integer',
            'slug'               => 'nullable|string',
            'is_mandatory'       => 'boolean',
            'is_auto_create'     => 'boolean',
            'is_nested'          => 'boolean',
            'display_fields'     => 'nullable|array',
            'fields'             => 'nullable|array'
        ]);

        $template = !empty($data['id'])
            ? Template::findOrFail($data['id'])
            : new Template();

        $template->fill($data);
        $template->save();

        // Сохранение переданных полей
        if (isset($data['fields']) && is_array($data['fields'])) {
            foreach ($data['fields'] as $index => $fieldData) {
                // Не перезаписываем родительские поля, если они сгенерированы
                if (!empty($fieldData['is_parent_field']) && !empty($fieldData['id'])) {
                    continue;
                }

                $template->fields()->updateOrCreate(
                    ['id' => $fieldData['id'] ?? null],
                    [
                        'label'            => $fieldData['label'],
                        'name'             => $fieldData['name'],
                        'type'             => $fieldData['type'] ?? 'text',
                        'is_mandatory'     => !empty($fieldData['is_mandatory']),
                        'depends_on_field' => $fieldData['depends_on_field'] ?? null,
                        'depends_on_value' => $fieldData['depends_on_value'] ?? null,
                        'sort_order'       => $index,
                        'config'           => $fieldData['config'] ?? null,
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $template->load('fields')
        ]);
    }

    /**
     * Список статей с фильтрацией по шаблону или родителю
     */
    public function getArticles(Request $request): JsonResponse
    {
        $query = Article::with(['template']);

        if ($request->has('template_id')) {
            $query->where('template_id', $request->input('template_id'));
        }

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        return response()->json(['data' => $query->paginate(50)]);
    }

    /**
     * Создание статьи с автопостроением цепочки предков
     */
    public function createArticle(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'title'           => 'required|string',
            'slug'            => 'nullable|string',
            'template_id'     => 'required|integer|exists:fun_wiki_templates,id',
            'parent_id'       => 'nullable|integer|exists:fun_wiki_articles,id',
            'base_article_id' => 'nullable|integer|exists:fun_wiki_articles,id',
            'data'            => 'nullable|array',
            'content'         => 'nullable|string',
            'status'          => 'nullable|string'
        ]);

        $article = $this->ancestryService->createArticle($payload);

        return response()->json([
            'success' => true,
            'data'    => $article->load(['template', 'parent'])
        ], 201);
    }

    /**
     * Детальная информация о статье (включая инфобокс и хлебные крошки)
     */
    public function getArticle(int $id): JsonResponse
    {
        $article = Article::with(['template.fields', 'parent', 'variants'])->findOrFail($id);

        return response()->json([
            'data' => [
                'id'             => $article->id,
                'title'          => $article->title,
                'slug'           => $article->slug,
                'type'           => $article->type,
                'content'        => $article->content,
                'raw_data'       => $article->data,
                'display_data'   => $article->display_fields_data,
                'breadcrumbs'    => $article->breadcrumbs,
                'variants'       => $article->variants,
                'template'       => $article->template
            ]
        ]);
    }

    /**
     * Полное дерево иерархии базы знаний для визуализации и навигации
     */
    public function getTree(): JsonResponse
    {
        $rootArticles = Article::whereNull('parent_id')
            ->with(['children' => function ($query) {
                $this->buildTreeRecursively($query);
            }])
            ->get();

        return response()->json(['data' => $rootArticles]);
    }

    protected function buildTreeRecursively($query)
    {
        $query->with(['children' => function ($q) {
            $this->buildTreeRecursively($q);
        }]);
    }
}
