import { VaccinationTemplate, DietTemplate, Baby } from '../../types/types';

export const MOCK_PATIENT: Baby = {
    id: '1',
    patientId: '#BC-2024-0892',
    name: 'Aryan Kumar',
    age: '8 Months',
    weight: '8.2',
    height: '68',
    bloodGroup: 'O+ Negative',
    gender: 'Male',
    avatarUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuB0MoVRvL6Urxip7UY1hj0jnaWeOapBJ7bf5SDuLrvDonOjVvUNUGJVOL7_DZMw5uZzYo3aSDUfWTnWjkQq40PY9lOjOB8cbz6-R45ezMB4YaYlP_iM_XuSjZOBw5uLHLMiRlZQJRMjbIZ04oi4J-ZaROcYqxJGe8yfKOFmBGUz050t3upoxZ1kDbIMeOeFOdtp03vg83DVQC1VBEXCec1CJYAB_DxLkbY9lW0h20hviafaYi9r3ATMOdVh3MmEVXkW9UrW3h5oqcn4',
    lastVisit: '12 Oct 2023'
};

export const VACCINATION_TEMPLATES: VaccinationTemplate[] = [
    {
        id: 'vt-1',
        name: 'Infant Primary Schedule',
        ageGroup: '0-12 Months',
        totalVaccines: 8,
        lastUpdated: '15 Jan 2024',
        vaccines: [
            { id: 'v1', name: 'BCG', type: 'Live Attenuated', preventDisease: 'Tuberculosis', recommendedAge: 'At Birth', dosage: '0.05ml', status: 'Pending' },
            { id: 'v2', name: 'HepB-1', type: 'Recombinant', preventDisease: 'Hepatitis B', recommendedAge: 'At Birth', dosage: '0.5ml', status: 'Pending' },
            { id: 'v3', name: 'OPV-0', type: 'Oral Live', preventDisease: 'Polio', recommendedAge: 'At Birth', dosage: '2 drops', status: 'Pending' },
            { id: 'v4', name: 'Pentavalent-1', type: 'Combination', preventDisease: 'DPT, HepB, Hib', recommendedAge: '6 Weeks', dosage: '0.5ml', status: 'Pending' }
        ]
    },
    {
        id: 'vt-2',
        name: 'Toddler Booster Pack',
        ageGroup: '1-3 Years',
        totalVaccines: 5,
        lastUpdated: '20 Feb 2024',
        vaccines: [
            { id: 'v5', name: 'MMR-1', type: 'Live Attenuated', preventDisease: 'Measles, Mumps, Rubella', recommendedAge: '9 Months', dosage: '0.5ml', status: 'Pending' },
            { id: 'v6', name: 'JE-1', type: 'Inactivated', preventDisease: 'Japanese Encephalitis', recommendedAge: '9 Months', dosage: '0.5ml', status: 'Pending' }
        ]
    }
];

export const DIET_TEMPLATES: DietTemplate[] = [
    {
        id: 'dt-1',
        name: 'Child Nutrition Plan',
        calories: '1200 kcal',
        mealCount: 5,
        duration: '30 Days',
        meals: [
            { id: 'm1', time: '08:00 AM', type: 'Breakfast', items: 'Oatmeal with Mashed Banana', calories: 250 },
            { id: 'm2', time: '11:00 AM', type: 'Morning Snack', items: 'Soft Apple Slices', calories: 100 },
            { id: 'm3', time: '01:30 PM', type: 'Lunch', items: 'Khichdi with Steamed Carrots', calories: 350 },
            { id: 'm4', time: '04:30 PM', type: 'Evening Snack', items: 'Yogurt with Pear Puree', calories: 150 },
            { id: 'm5', time: '08:00 PM', type: 'Dinner', items: 'Finger Foods - Sweet Potato', calories: 350 }
        ]
    },
    {
        id: 'dt-2',
        name: 'Weight Gain Diet',
        calories: '1500 kcal',
        mealCount: 6,
        duration: '60 Days',
        meals: [
            { id: 'm1-wg', time: '07:30 AM', type: 'Breakfast', items: 'Avocado and Egg Mash', calories: 400 }
        ]
    }
];
