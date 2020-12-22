<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateAuthTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auth_rule', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pid')->default(0)->comment('父级ID');
            $table->string('path',100)->default('')->comment('路由地址（前端）');
            $table->string('auth',100)->default('')->comment('接口地址（后端）');
            $table->string('title',50)->default('')->comment('标题');
            $table->string('icon',50)->default('')->comment('图标');
            $table->string('remark',255)->default('')->comment('备注');
            $table->unsignedtinyInteger('ismenu')->default(0)->comment('是否菜单');
            $table->integer('weigh')->default(0)->comment('权重');
            $table->TinyInteger('status')->default(1)->comment('状态');
        });

        Schema::create('auth_group',function (Blueprint $table){
            $table->increments('id');
            $table->unsignedInteger('pid')->default(0)->comment('父级ID');
            $table->string('name',50)->default('')->comment('权限组名');
            $table->text('rules')->comment('规则ID');
            $table->TinyInteger('status')->default(1)->comment('状态');
        });

        Schema::create('auth_group_access',function (Blueprint $table){
            $table->unsignedInteger('uid')->comment('用户ID');
            $table->unsignedInteger('group_id')->comment('权限组ID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_rule');
        Schema::dropIfExists('auth_group');
        Schema::dropIfExists('auth_group_access');
    }
}
