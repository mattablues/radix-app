<?php

declare(strict_types=1);

use Radix\Database\Migration\Blueprint;
use Radix\Database\Migration\Schema;

return new class {
    public function up(Schema $schema): void
    {
        $schema->create('system_updates', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20);
            $table->string('title', 100);
            $table->text('description');
            $table->dateTime('released_at');
            $table->boolean('is_major', ['default' => false]);
            $table->timestamps();
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('system_updates');
    }
};
