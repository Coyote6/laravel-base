<?php

namespace Coyote6\LaravelBase\Tests;

use Coyote6\LaravelBase\Providers\BaseServiceProvider;
use Coyote6\LaravelStr\Providers\StrServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            StrServiceProvider::class,
            BaseServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('client_id')->nullable();
            $table->string('client_reference')->nullable();
            $table->timestamps();
        });

        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->string('user_id')->nullable();
            $table->string('author_id')->nullable();
            $table->string('original_author_id')->nullable();
            $table->string('client_id')->nullable();
            $table->string('tenant_id')->nullable();
            $table->string('owner')->nullable();
            $table->string('machine_name')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });

        Schema::create('test_machine_name_as_id_models', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('test_option_models', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('test_option_abbr_models', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('abbr')->nullable();
            $table->timestamps();
        });
    }
}
