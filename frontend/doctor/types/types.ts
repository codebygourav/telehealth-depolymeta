/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

export interface Baby {
    id: string;
    name: string;
    age: string;
    weight: string;
    height: string;
    bloodGroup: string;
    gender: 'Male' | 'Female';
    avatarUrl: string;
    lastVisit: string;
    patientId: string;
}

export interface VaccinationTemplate {
    id: string;
    name: string;
    ageGroup: string;
    totalVaccines: number;
    lastUpdated: string;
    vaccines: Vaccine[];
}

export interface Vaccine {
    id: string;
    name: string;
    type: string;
    preventDisease: string;
    company?: string;
    price?: string;
    recommendedAge: string;
    dosage: string;
    scheduledDate?: string;
    status: 'Pending' | 'Completed' | 'Overdue' | 'Rescheduled';
    completedDate?: string;
    doctorNotes?: string;
}

export interface DietTemplate {
    id: string;
    name: string;
    calories: string;
    mealCount: number;
    duration: string;
    meals: DietMeal[];
}

export interface DietMeal {
    id: string;
    time: string;
    type: 'Breakfast' | 'Morning Snack' | 'Lunch' | 'Afternoon Snack' | 'Evening Snack' | 'Dinner' | 'Night';
    items: string;
    calories: number;
    notes?: string;
    status?: 'Pending' | 'Followed' | 'Missed';
    completedAt?: string;
}
