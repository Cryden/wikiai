<?php namespace Fun\Wiki\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddIsGlobalColumnToFieldsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('fun_wiki_fields') && !Schema::hasColumn('fun_wiki_fields', 'is_global')) {
            Schema::table('fun_wiki_fields', function ($table) {
                $table->boolean('is_global')->default(false)->after('is_mandatory');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('fun_wiki_fields') && Schema::hasColumn('fun_wiki_fields', 'is_global')) {
            Schema::table('fun_wiki_fields', function ($table) {
                $table->dropColumn('is_global');
            });
        }
    }
}

