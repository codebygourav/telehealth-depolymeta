<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorReplacement;
use App\Models\DoctorAvailability;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DoctorReplacementService
{
    /**
     * Create a doctor replacement and handle all related transfers
     */
    public function createReplacement(array $data): DoctorReplacement
    {
        return DB::transaction(function () use ($data) {
            // Create the replacement record
            $replacement = DoctorReplacement::create([
                'original_doctor_id' => $data['original_doctor_id'],
                'replacement_doctor_id' => $data['replacement_doctor_id'],
                'replacement_type' => $data['replacement_type'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'reason' => $data['reason'] ?? 'unavailable',
                'notes' => $data['notes'] ?? null,
                'is_active' => true,
                'replaced_by' => $data['replaced_by'] ?? \Illuminate\Support\Facades\Auth::id(),
            ]);

            $replacementRoom = $data['replacement_room'] ?? null;
            $transferAvailability = $data['transfer_availability'] ?? true;
            $selectedAvailabilityIds = $data['selected_availability_ids'] ?? null;

            // Handle availability transfers if requested
            if ($transferAvailability) {
                $this->transferAvailability($replacement, $replacementRoom, $selectedAvailabilityIds);
            }

            // Handle appointment transfers based on replacement type
            $this->transferAppointments($replacement, $replacementRoom);

            return $replacement;
        });
    }

    /**
     * Transfer availability from original doctor to replacement doctor
     * When a doctor is replaced in a date range, their availability in that range
     * is replaced by the replacement doctor's availability
     *
     * @param array|null $selectedAvailabilityIds If provided, only replace these specific availability slots
     */
    public function transferAvailability(DoctorReplacement $replacement, ?string $room = null, ?array $selectedAvailabilityIds = null): void
    {
        $query = DoctorAvailability::where('doctor_id', $replacement->original_doctor_id)
            ->where('is_available', true);

        // If specific availability IDs are selected, only replace those
        if (!empty($selectedAvailabilityIds)) {
            $query->whereIn('id', $selectedAvailabilityIds);
        } else {
            // Apply date filters based on replacement type
            switch ($replacement->replacement_type) {
                case 'permanent':
                    $startDate = $replacement->start_date ?? Carbon::today();
                    $query->where(function ($q) use ($startDate) {
                        $q->where(function ($sq) use ($startDate) {
                            $sq->where('is_recurring', false)
                                ->where('date', '>=', $startDate->format('Y-m-d'));
                        })
                            ->orWhere(function ($sq) use ($startDate) {
                                $sq->where('is_recurring', true)
                                    ->where(function ($q) use ($startDate) {
                                        $q->whereNull('recurring_end_date')
                                            ->orWhere('recurring_end_date', '>=', $startDate);
                                    });
                            });
                    });
                    break;

                case 'all':
                    if ($replacement->start_date && $replacement->end_date) {
                        $query->where(function ($q) use ($replacement) {
                            $q->where(function ($sq) use ($replacement) {
                                $sq->where('is_recurring', false)
                                    ->whereBetween('date', [
                                        $replacement->start_date->format('Y-m-d'),
                                        $replacement->end_date->format('Y-m-d')
                                    ]);
                            })
                                ->orWhere(function ($sq) use ($replacement) {
                                    $sq->where('is_recurring', true)
                                        ->where('recurring_start_date', '<=', $replacement->end_date)
                                        ->where(function ($q) use ($replacement) {
                                            $q->whereNull('recurring_end_date')
                                                ->orWhere('recurring_end_date', '>=', $replacement->start_date);
                                        });
                                });
                        });
                    }
                    break;

                case 'selected':
                    // For selected replacements, availability transfer is handled per appointment
                    return;

                case 'single':
                    // For single replacements, availability transfer is handled per appointment
                    return;
            }
        }

        $availabilities = $query->get();

        foreach ($availabilities as $availability) {
            // Replace availability: update doctor_id to replacement doctor
            $this->replaceAvailabilityForReplacement($availability, $replacement, $room);
        }
    }

    /**
     * Replace an availability slot: update doctor_id to replacement doctor
     * This way, queries by doctor_id work automatically
     * Original availability is updated (not deleted) to preserve history
     */
    protected function replaceAvailabilityForReplacement(
        DoctorAvailability $originalAvailability,
        DoctorReplacement $replacement,
        ?string $room = null
    ): DoctorAvailability {
        // Update the availability's doctor_id to replacement doctor
        // This makes queries by doctor_id work automatically
        $originalAvailability->update([
            'doctor_id' => $replacement->replacement_doctor_id,
            'is_available' => true,
            'doctor_room' => $room ?? $originalAvailability->doctor_room,
        ]);

        return $originalAvailability;
    }

    /**
     * Transfer appointments to replacement doctor
     */
    public function transferAppointments(DoctorReplacement $replacement, ?string $room = null): void
    {
        $query = Appointment::where('doctor_id', $replacement->original_doctor_id)
            ->whereNotIn('status', ['cancelled', 'completed']);

        // Apply date filters based on replacement type
        switch ($replacement->replacement_type) {
            case 'permanent':
                $startDate = $replacement->start_date ?? Carbon::today();
                $query->where('appointment_date', '>=', $startDate->format('Y-m-d'));
                break;

            case 'all':
                if ($replacement->start_date && $replacement->end_date) {
                    $query->whereBetween('appointment_date', [
                        $replacement->start_date->format('Y-m-d'),
                        $replacement->end_date->format('Y-m-d')
                    ]);
                }
                break;

            case 'selected':
                // For selected, appointments are transferred individually
                return;

            case 'single':
                // For single, appointment is transferred individually
                return;
        }

        $appointments = $query->get();

        foreach ($appointments as $appointment) {
            $this->transferAppointment($appointment, $replacement, $room);
        }
    }

    /**
     * Transfer a single appointment to replacement doctor
     * Updates doctor_id to replacement doctor AND sets replaced_by_id
     * This way, queries by doctor_id work automatically without changes
     */
    public function transferAppointment(Appointment $appointment, DoctorReplacement $replacement, ?string $room = null): void
    {
        // Find or create availability for replacement doctor
        $replacementAvailability = $this->findOrCreateAvailabilityForAppointment(
            $replacement->replacement_doctor_id,
            $appointment->appointment_date,
            $appointment->appointment_time,
            $appointment->consultation_type,
            $room
        );

        // Update appointment - set doctor_id to replacement (for queries) AND replaced_by_id (for tracking)
        $appointment->update([
            'doctor_id' => $replacement->replacement_doctor_id,
            'replaced_by_id' => $replacement->replacement_doctor_id,
            'availability_id' => $replacementAvailability->id,
        ]);
    }

    /**
     * Find or create availability for a specific appointment
     */
    protected function findOrCreateAvailabilityForAppointment(
        string $doctorId,
        string $date,
        string $time,
        string $consultationType,
        ?string $room = null
    ): DoctorAvailability {
        // Try to find existing availability
        $availability = DoctorAvailability::where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->whereTime('start_time', '<=', $time)
            ->whereTime('end_time', '>=', $time)
            ->where('consultation_type', $consultationType)
            ->where('is_available', true)
            ->first();

        if ($availability) {
            // Update room if provided and different
            if ($room && $availability->doctor_room !== $room) {
                $availability->update(['doctor_room' => $room]);
            }
            return $availability;
        }

        // Create new availability slot
        $startTime = Carbon::parse($time);
        $endTime = $startTime->copy()->addHour(); // Default 1 hour slot

        return DoctorAvailability::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'doctor_id' => $doctorId,
            'date' => $date,
            'day_of_week' => strtolower(Carbon::parse($date)->format('l')),
            'start_time' => $startTime->format('H:i:00'),
            'end_time' => $endTime->format('H:i:00'),
            'consultation_type' => $consultationType,
            'capacity' => 1,
            'doctor_room' => $room,
            'is_available' => true,
            'is_recurring' => false,
            'created_by' => \Illuminate\Support\Facades\Auth::id(),
        ]);
    }

    /**
     * Revert a doctor replacement and restore original state
     * Restores doctor_id to original doctor and clears replaced_by_id
     */
    public function revertReplacement(DoctorReplacement $replacement): void
    {
        DB::transaction(function () use ($replacement) {
            // Get all appointments that were replaced (where replaced_by_id matches replacement doctor)
            $query = Appointment::where('replaced_by_id', $replacement->replacement_doctor_id)
                ->where('doctor_id', $replacement->replacement_doctor_id) // Currently set to replacement
                ->whereNotIn('status', ['cancelled', 'completed']);

            if ($replacement->start_date) {
                $query->whereDate('appointment_date', '>=', $replacement->start_date);
            }
            if ($replacement->end_date) {
                $query->whereDate('appointment_date', '<=', $replacement->end_date);
            }

            $appointments = $query->get();

            // Revert appointments: restore doctor_id to original and clear replaced_by_id
            foreach ($appointments as $appointment) {
                $shouldRevert = true;
                if ($replacement->start_date && $appointment->appointment_date < $replacement->start_date) {
                    $shouldRevert = false;
                }
                if ($replacement->end_date && $appointment->appointment_date > $replacement->end_date) {
                    $shouldRevert = false;
                }

                if ($shouldRevert) {
                    // Find or create availability for original doctor
                    $originalAvailability = $this->findOrCreateAvailabilityForAppointment(
                        $replacement->original_doctor_id,
                        $appointment->appointment_date,
                        $appointment->appointment_time,
                        $appointment->consultation_type,
                        null
                    );

                    // Restore original doctor_id and clear replaced_by_id
                    $appointment->update([
                        'doctor_id' => $replacement->original_doctor_id,
                        'replaced_by_id' => null,
                        'availability_id' => $originalAvailability->id,
                    ]);
                }
            }

            // Revert availabilities: restore doctor_id to original doctor
            $this->revertAvailability($replacement);

            // Deactivate the replacement
            $replacement->update(['is_active' => false]);
        });
    }

    /**
     * Revert availability slots back to original doctor
     */
    protected function revertAvailability(DoctorReplacement $replacement): void
    {
        $query = DoctorAvailability::where('doctor_id', $replacement->replacement_doctor_id)
            ->where('is_available', true);

        // Apply date filters
        if ($replacement->start_date && $replacement->end_date) {
            $query->where(function ($q) use ($replacement) {
                $q->where(function ($sq) use ($replacement) {
                    $sq->where('is_recurring', false)
                        ->whereBetween('date', [
                            $replacement->start_date->format('Y-m-d'),
                            $replacement->end_date->format('Y-m-d')
                        ]);
                })
                    ->orWhere(function ($sq) use ($replacement) {
                        $sq->where('is_recurring', true)
                            ->where('recurring_start_date', '<=', $replacement->end_date)
                            ->where(function ($q) use ($replacement) {
                                $q->whereNull('recurring_end_date')
                                    ->orWhere('recurring_end_date', '>=', $replacement->start_date);
                            });
                    });
            });
        } elseif ($replacement->start_date) {
            $query->where(function ($q) use ($replacement) {
                $q->where(function ($sq) use ($replacement) {
                    $sq->where('is_recurring', false)
                        ->where('date', '>=', $replacement->start_date->format('Y-m-d'));
                })
                    ->orWhere(function ($sq) use ($replacement) {
                        $sq->where('is_recurring', true)
                            ->where(function ($q) use ($replacement) {
                                $q->whereNull('recurring_end_date')
                                    ->orWhere('recurring_end_date', '>=', $replacement->start_date);
                            });
                    });
            });
        }

        $availabilities = $query->get();

        // Restore doctor_id to original doctor
        foreach ($availabilities as $availability) {
            $availability->update([
                'doctor_id' => $replacement->original_doctor_id,
            ]);
        }
    }

    /**
     * Get effective availability for a doctor (considering replacements)
     */
    public function getEffectiveAvailabilityForDoctor(Doctor $doctor, ?string $date = null): array
    {
        $availabilities = [];

        // Get original doctor's availability
        $query = $doctor->availabilities()->where('is_available', true);

        if ($date) {
            $query->where(function ($q) use ($date) {
                $q->where('date', $date)
                    ->orWhere(function ($sq) use ($date) {
                        $sq->where('is_recurring', true)
                            ->where('recurring_start_date', '<=', $date)
                            ->where(function ($q) use ($date) {
                                $q->whereNull('recurring_end_date')
                                    ->orWhere('recurring_end_date', '>=', $date);
                            });
                    });
            });
        }

        $originalAvailabilities = $query->get();

        // Check for active replacements
        $activeReplacements = DoctorReplacement::where('original_doctor_id', $doctor->id)
            ->where('is_active', true)
            ->when($date, function ($query) use ($date) {
                $query->where(function ($q) use ($date) {
                    $q->whereNull('start_date')
                        ->orWhere('start_date', '<=', $date);
                })->where(function ($q) use ($date) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $date);
                });
            })
            ->get();

        // If there are active replacements, include replacement doctor's availability
        foreach ($activeReplacements as $replacement) {
            $replacementAvailabilities = $replacement->replacementDoctor
                ->availabilities()
                ->where('is_available', true)
                ->when($date, function ($query) use ($date) {
                    $query->where('date', $date);
                })
                ->get();

            $availabilities = array_merge($availabilities, $replacementAvailabilities->toArray());
        }

        // Add original availabilities
        $availabilities = array_merge($availabilities, $originalAvailabilities->toArray());

        return $availabilities;
    }

    /**
     * Check if a doctor has active replacements
     */
    public function hasActiveReplacements(string $doctorId, ?string $date = null): bool
    {
        $query = DoctorReplacement::where('original_doctor_id', $doctorId)
            ->where('is_active', true);

        if ($date) {
            $query->where(function ($q) use ($date) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $date);
            })->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            });
        }

        return $query->exists();
    }

    /**
     * Get active replacements for a doctor
     */
    public function getActiveReplacements(string $doctorId, ?string $date = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = DoctorReplacement::where('original_doctor_id', $doctorId)
            ->where('is_active', true);

        if ($date) {
            $query->where(function ($q) use ($date) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $date);
            })->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            });
        }

        return $query->get();
    }
}
