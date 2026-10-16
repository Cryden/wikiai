<?php namespace Fun\Wiki\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateFieldsTable extends Migration
{
    public function up()
    {
        Schema::create('fun_wiki_fields', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('template_id')->unsigned()->index();
            $table->string('label');
            $table->string('name');
            $table->string('type')->default('text'); // text, markdown, number, date, file, relation, tags, geopoint
            
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_global')->default(false);
            $table->boolean('is_parent_field')->default(false);
            $table->boolean('is_inherited')->default(false);
            
            // Условное отображение (Depends On)
            $table->string('depends_on_field')->nullable();
            $table->string('depends_on_value')->nullable();
            
            $table->integer('sort_order')->default(0);
            $table->text('config')->nullable(); // JSON параметры поля
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fun_wiki_fields');
    }
}
