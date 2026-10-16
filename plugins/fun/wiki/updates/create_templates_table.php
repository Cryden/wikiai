<?php namespace Fun\Wiki\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('fun_wiki_templates', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            
            // 3 типа: 'node' (Узел), 'article' (Статья), 'variant' (Вариант)
            $table->string('type')->default('article');
            
            // NestedTree: родитель и служебные поля дерева
            $table->integer('parent_id')->unsigned()->nullable()->index();
            $table->integer('nest_left')->nullable()->index();
            $table->integer('nest_right')->nullable()->index();
            $table->integer('nest_depth')->nullable()->index();
            
            // Фиксированный слаг для узлов (например, 'books', 'characters')
            $table->string('slug')->nullable();
            
            // Правила подчиненности
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_auto_create')->default(false);
            $table->boolean('is_nested')->default(false);
            
            // Список кодов отображаемых полей (JSON)
            $table->text('display_fields')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fun_wiki_templates');
    }
}
