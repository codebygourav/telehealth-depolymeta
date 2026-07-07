@props([
    'doctor' => null,
    'alpine' => false,
    'doctorAccessor' => 'currentDoctor()',
    'displayCopyAccessor' => 'displayCopy',
])

@php
    $doctor = is_array($doctor) ? $doctor : [];
    $doctorName = $doctor['name'] ?? 'Dr. Doctor';
    $doctorDepartment = $doctor['department'] ?? 'General Practice';
    $doctorSubTitle = $doctor['sub_title'] ?? '';
    $doctorExperience = $doctor['experience'] ?? '';
    $doctorInitials = $doctor['initials'] ?? 'DR';
    $doctorAvatar = $doctor['avatar'] ?? '';
    $doctorEducation = $doctor['education_list'] ?? [];
    $doctorBio = $doctor['bio'] ?? '';
    $doctorBreakNote = $doctor['is_on_break'] ?? false;
    $queueSummary = $doctor['queue_summary'] ?? [];
    $currentSlotTime = $doctor['current_slot_time'] ?? ($queueSummary['current_time_slot'] ?? null);
    $nextSlotTime = $doctor['next_slot_time'] ?? ($queueSummary['next_time_slot'] ?? null);
@endphp

<div class="doctor-card-main">
    @if($alpine)
        <template x-if="{{ $doctorAccessor }}?.avatar && !{{ $doctorAccessor }}?.avatar.includes('user-avatar.png')">
            <img :src="{{ $doctorAccessor }}.avatar" class="doctor-avatar" :alt="{{ $doctorAccessor }}?.name">
        </template>
        <template x-if="!{{ $doctorAccessor }}?.avatar || {{ $doctorAccessor }}?.avatar.includes('user-avatar.png')">
            <div class="doctor-avatar-fallback" x-text="{{ $doctorAccessor }}?.initials || 'DR'"></div>
        </template>
    @else
        @if(!empty($doctorAvatar) && !str_contains($doctorAvatar, 'user-avatar.png'))
            <img src="{{ $doctorAvatar }}" class="doctor-avatar" alt="{{ $doctorName }}">
        @else
            <div class="doctor-avatar-fallback">{{ $doctorInitials }}</div>
        @endif
    @endif

    <div class="doctor-info">
        @if($alpine)
            <h2 x-text="{{ $doctorAccessor }}?.name || {{ $displayCopyAccessor }}.empty_state_title"></h2>
            <div class="speciality-row">
                <span class="speciality" x-text="{{ $doctorAccessor }}?.department || 'General Practice'"></span>
                <span class="exp-badge" x-show="{{ $doctorAccessor }}?.experience">
                    <span class="star-icon">★</span>
                    <span x-text="{{ $doctorAccessor }}?.experience" style="font-weight: 600; color: #1e293b;"></span>
                </span>
            </div>
            <div class="doctor-sub-title" x-show="{{ $doctorAccessor }}?.sub_title" x-text="{{ $doctorAccessor }}?.sub_title"></div>
           
            <div class="doctor-slot-summary" x-show="{{ $doctorAccessor }}?.queue_summary?.current_time_slot || {{ $doctorAccessor }}?.queue_summary?.next_time_slot">
                <div class="slot-chip current" x-show="{{ $doctorAccessor }}?.queue_summary?.current_time_slot">
                    <span class="slot-chip-label">Current Slot</span>
                    <span x-text="{{ $doctorAccessor }}?.queue_summary?.current_time_slot"></span>
                </div>
                <div class="slot-chip next" x-show="{{ $doctorAccessor }}?.queue_summary?.next_time_slot">
                    <span class="slot-chip-label">Next Slot</span>
                    <span x-text="{{ $doctorAccessor }}?.queue_summary?.next_time_slot"></span>
                </div>
            </div>
           
            <div class="doctor-break-note" x-show="{{ $doctorAccessor }}?.is_on_break" x-cloak>Doctor is on break</div>
        @else
            <h2>{{ $doctorName }}</h2>
            <div class="speciality-row">
                <span class="speciality">{{ $doctorDepartment }}</span>
                @if(!empty($doctorExperience))
                    <span class="exp-badge">
                        <span class="star-icon">★</span>
                        <span style="font-weight: 800; color: #1e293b;">{{ $doctorExperience }}</span>
                    </span>
                @endif
            </div>
               @if(!empty($doctorSubTitle))
                <div class="doctor-sub-title">{{ $doctorSubTitle }}</div>
            @endif
            @if(!empty($currentSlotTime) || !empty($nextSlotTime))
                <div class="doctor-slot-summary">
                    @if(!empty($currentSlotTime))
                        <div class="slot-chip current">
                            <span class="slot-chip-label">Current Slot</span>
                            <span>{{ $currentSlotTime }}</span>
                        </div>
                    @endif
                    @if(!empty($nextSlotTime))
                        <div class="slot-chip next">
                            <span class="slot-chip-label">Next Slot</span>
                            <span>{{ $nextSlotTime }}</span>
                        </div>
                    @endif
                </div>
            @endif
            
        
           
            @if($doctorBreakNote)
                <div class="doctor-break-note">Doctor is on break</div>
            @endif
        @endif
    </div>
</div>
