<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('attendance_records');

        if (Schema::hasColumn('employees', 'attendance_pin')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('attendance_pin');
            });
        }
    }

    public function down(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();
            $table->unsignedInteger('minutes_late')->default(0);
            $table->unsignedInteger('minutes_undertime')->default(0);
            $table->unsignedInteger('minutes_overtime')->default(0);
            $table->enum('status', ['present', 'absent', 'leave', 'holiday'])->default('present');
            $table->enum('source', ['manual', 'biometric', 'self_service'])->default('self_service');
            $table->timestamps();

            $table->unique(['employee_id', 'work_date']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('attendance_pin')->nullable()->after('basic_salary');
        });
    }
};
