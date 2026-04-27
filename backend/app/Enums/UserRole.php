<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Patient = 'patient';
    case Doctor = 'doctor';
    case DoctorManager = 'doctor_manager';
    case DepartmentManager = 'department_manager';
    case MedicalManager = 'medicine_manager';
    case Admin = 'admin';


    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
