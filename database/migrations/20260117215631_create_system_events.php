<?php

declare(strict_types=1);

use Radix\Database\Migration\Blueprint;
use Radix\Database\Migration\Schema;

return new class {
    public function up(Schema $schema): void
    {
        $schema->create('system_events', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['info', 'system', 'warning', 'error'], ['default' => 'info']);
            $table->string('message', 255);
            $table->integer('user_id', true, ['nullable' => true]);
            $table->timestamps();

            $table->foreign('user_id', 'users', 'id', 'set null', 'cascade');
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('system_events');
    }
};
