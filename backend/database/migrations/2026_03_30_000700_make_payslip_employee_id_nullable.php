<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            // Change the foreign key constraint from cascadeOnDelete to nullOnDelete
            // and make employee_id nullable so payslips persist when an employee is deleted
            $table->foreignId('employee_id')->nullable()->change();
            
            // Drop the old foreign key constraint
            $table->dropForeign(['employee_id']);
            
            // Add the new foreign key with nullOnDelete
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            // Revert to the original cascadeOnDelete constraint
            $table->dropForeign(['employee_id']);
            
            $table->foreignId('employee_id')->change();
            
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->cascadeOnDelete();
        });
    }
};
