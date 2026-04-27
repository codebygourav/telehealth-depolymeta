<?php

namespace App\Enums;

enum DepartmentRole: string
{
    case Head = 'Head';
    case Surgeon = 'Surgeon';
    case Assistant = 'Assistant';
    case Resident = 'Resident';
    case SeniorConsultant = 'Senior Consultant';
    case Physician = 'Physician';
    case Consultant = 'Consultant';
    case SeniorSurgeon = 'Senior Surgeon';
    case SeniorNeurologist = 'Senior Neurologist';
    case ProfessorAndActingHOD = 'Professor & Acting HOD';
    case Professor = 'Professor';
    case AssistantProfessor = 'Assistant Professor';
    case ProfessorAndHOD = 'Professor & HOD';
    case SeniorResident = 'Senior Resident';
    case AssociateProfessor = 'Associate Professor';
    case AssistantProfessorAndHead = 'Assistant Professor & Head';
    case ProfessorAndHead = 'Professor & Head';

    public static function labels(): array
    {
        return [
            self::Head->value => 'Head',
            self::Surgeon->value => 'Surgeon',
            self::Assistant->value => 'Assistant',
            self::Resident->value => 'Resident',
            self::SeniorConsultant->value => 'Senior Consultant',
            self::Physician->value => 'Physician',
            self::Consultant->value => 'Consultant',
            self::SeniorSurgeon->value => 'Senior Surgeon',
            self::SeniorNeurologist->value => 'Senior Neurologist',
            self::ProfessorAndActingHOD->value => 'Professor & Acting HOD',
            self::Professor->value => 'Professor',
            self::AssistantProfessor->value => 'Assistant Professor',
            self::ProfessorAndHOD->value => 'Professor & HOD',
            self::SeniorResident->value => 'Senior Resident',
            self::AssociateProfessor->value => 'Associate Professor',
            self::AssistantProfessorAndHead->value => 'Assistant Professor & Head',
            self::ProfessorAndHead->value => 'Professor & Head',
        ];
    }
    public static function Keylabels(): array
    {
        $roles = [];

        foreach (self::cases() as $case) {
            $roles[$case->name] = $case->value;
        }

        return $roles;
    }
}