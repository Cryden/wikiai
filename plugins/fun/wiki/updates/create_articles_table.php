<?php namespace Fun\Wiki\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateArticlesTable extends Migration
{
    public function up()
    {
        Schema::create('fun_wiki_articles', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('title');
            $table->string('slug')->index();
            
            $table->integer('template_id')->unsigned()->index();
            
            // NestedTree: родитель и индексы дерева
            $table->integer('parent_id')->unsigned()->nullable()->index();
            $table->integer('nest_left')->nullable()->index();
            $table->integer('nest_right')->nullable()->index();
            $table->integer('nest_depth')->nullable()->index();
            
            // Для Варианта: ссылка на базовую статью
            $table->integer('base_article_id')->unsigned()->nullable()->index();
            
            // 3 типа: 'node', 'article', 'variant'
            $table->string('type')->default('article');
            
            // Данные полей в формате JSON (Key-Value)
            $table->longText('data')->nullable();
            
            // Основной контент (Markdown)
            $table->longText('content')->nullable();
            
            $table->string('status')->default('published');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fun_wiki_articles');
    }
}
