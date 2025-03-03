<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsInVhStProductVariation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {



        Schema::table('vh_st_product_variations', function($table) {

            $table->after('meta',function ($table){
                $table->string('meta_title')->nullable()->index();
                $table->text('meta_description')->nullable();
                $table->text('meta_keywords')->nullable()->index();

            });

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vh_st_product_variations', function($table) {
            $table->dropColumn(['meta_title','meta_description','meta_keywords']);
        });
    }
}
