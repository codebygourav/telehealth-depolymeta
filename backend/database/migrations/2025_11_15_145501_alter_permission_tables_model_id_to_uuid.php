<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teams = config('permission.teams');

        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';

        // Fix model_has_permissions
        $this->fix(
            $tableNames['model_has_permissions'],
            $modelMorphKey,
            $pivotPermission,
            $teams ? ($columnNames['team_foreign_key'] ?? null) : null
        );

        // Fix model_has_roles
        $this->fix(
            $tableNames['model_has_roles'],
            $modelMorphKey,
            $pivotRole,
            $teams ? ($columnNames['team_foreign_key'] ?? null) : null
        );
    }


    private function fix(string $table, string $modelMorphKey, string $pivotCol, $teamKey): void
    {
        if (!Schema::hasTable($table)) return;

        // 1️⃣ Drop ALL foreign keys referencing this table
        $this->dropForeignKeys($table);

        // 2️⃣ Drop PRIMARY KEY
        $this->dropPrimaryKey($table);

        // 3️⃣ Change model_id type
        DB::statement("
            ALTER TABLE `{$table}`
            MODIFY `{$modelMorphKey}` CHAR(36) NOT NULL
        ");

        // 4️⃣ Add composite primary key
        if ($teamKey) {
            DB::statement("
                ALTER TABLE `{$table}`
                ADD PRIMARY KEY (`{$teamKey}`, `{$pivotCol}`, `{$modelMorphKey}`, `model_type`)
            ");
        } else {
            DB::statement("
                ALTER TABLE `{$table}`
                ADD PRIMARY KEY (`{$pivotCol}`, `{$modelMorphKey}`, `model_type`)
            ");
        }
    }


    private function dropForeignKeys(string $table): void
    {
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '{$table}'
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");

        foreach ($foreignKeys as $fk) {
            $name = $fk->CONSTRAINT_NAME;
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$name}`");
        }
    }


    private function dropPrimaryKey(string $table): void
    {
        $pk = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$table}'
              AND CONSTRAINT_TYPE = 'PRIMARY KEY'
        ");

        if ($pk) {
            DB::statement("ALTER TABLE `{$table}` DROP PRIMARY KEY");
        }
    }
};
