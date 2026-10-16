<?php

declare(strict_types=1);

namespace Fun\Wiki;

use System\Classes\PluginBase;
use Fun\Wiki\Contracts\AncestryResolverInterface;
use Fun\Wiki\Services\AncestryService;
use Fun\Wiki\Classes\FieldTypeRegistry;

class Plugin extends PluginBase
{
    public function pluginDetails(): array
    {
        return [
            'name'        => 'fun.wiki::lang.plugin.name',
            'description' => 'fun.wiki::lang.plugin.description',
            'author'      => 'Fun',
            'icon'        => 'icon-sitemap'
        ];
    }

    public function register(): void
    {
        $this->app->singleton(AncestryResolverInterface::class, AncestryService::class);
        $this->app->singleton('fun.wiki.ancestry', AncestryService::class);
        $this->app->singleton('fun.wiki.field_registry', FieldTypeRegistry::class);
    }

    public function registerPermissions(): array
    {
        return [
            'fun.wiki.manage_templates' => [
                'tab'   => 'fun.wiki::lang.permissions.tab',
                'label' => 'fun.wiki::lang.permissions.manage_templates',
            ],
            'fun.wiki.manage_articles' => [
                'tab'   => 'fun.wiki::lang.permissions.tab',
                'label' => 'fun.wiki::lang.permissions.manage_articles',
            ],
        ];
    }

    public function registerNavigation(): array
    {
        return [
            'wiki' => [
                'label'       => 'fun.wiki::lang.navigation.wiki',
                'url'         => \Backend::url('fun/wiki/articles'),
                'icon'        => 'icon-book',
                'permissions' => ['fun.wiki.*'],
                'order'       => 200,
                'sideMenu'    => [
                    'articles' => [
                        'label'       => 'fun.wiki::lang.navigation.articles',
                        'icon'        => 'icon-file-text-o',
                        'url'         => \Backend::url('fun/wiki/articles'),
                        'permissions' => ['fun.wiki.manage_articles'],
                    ],
                    'templates' => [
                        'label'       => 'fun.wiki::lang.navigation.templates',
                        'icon'        => 'icon-sitemap',
                        'url'         => \Backend::url('fun/wiki/templates'),
                        'permissions' => ['fun.wiki.manage_templates'],
                    ],
                    'templatevue' => [
                        'label'       => 'fun.wiki::lang.navigation.templates_vue',
                        'icon'        => 'icon-code',
                        'url'         => \Backend::url('fun/wiki/templatevue'),
                        'permissions' => ['fun.wiki.manage_templates'],
                    ],
                ]
            ]
        ];
    }

    public function registerFormWidgets(): array
    {
        return [
            \Fun\Wiki\FormWidgets\MetaFields::class => 'wikix_meta_fields',
        ];
    }

    public function boot(): void
    {
        // Инициализация сервисов при загрузке системы
    }
}
