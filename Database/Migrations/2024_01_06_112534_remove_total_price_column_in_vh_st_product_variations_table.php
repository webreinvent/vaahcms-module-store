<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveTotalPriceColumnInVhStProductVariationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vh_st_product_variations', function($table) {
            $table->dropColumn('total_price');

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
            $table->integer('total_price');
        });
    }
}
