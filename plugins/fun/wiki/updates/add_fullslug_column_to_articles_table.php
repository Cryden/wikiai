<?php namespace Fun\Wiki\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddFullslugColumnToArticlesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('fun_wiki_articles') && !Schema::hasColumn('fun_wiki_articles', 'fullslug')) {
            Schema::table('fun_wiki_articles', function ($table) {
                $table->string('fullslug')->nullable()->index()->after('slug');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('fun_wiki_articles') && Schema::hasColumn('fun_wiki_articles', 'fullslug')) {
            Schema::table('fun_wiki_articles', function ($table) {
                $table->dropColumn('fullslug');
            });
        }
    }
}

