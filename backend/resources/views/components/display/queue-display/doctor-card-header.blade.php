@props([
    'doctor' => null,
    'alpine' => false,
])

@php
    $doctor = is_array($doctor) ? $doctor : [];
    $doctorName = $doctor['name'] ?? 'Dr. Doctor';
    $doctorDepartment = $doctor['department'] ?? 'General Practice';
    $doctorExperience = $doctor['experience'] ?? '';
    $doctorInitials = $doctor['initials'] ?? 'DR';
    $doctorAvatar = $doctor['avatar'] ?? '';
    $doctorEducation = $doctor['education_list'] ?? [];
    $doctorBreakNote = $doctor['is_on_break'] ?? false;
@endphp

<div class="doctor-card-main">
    @if($alpine)
        <template x-if="currentDoctor()?.avatar && !currentDoctor()?.avatar.includes('user-avatar.png')">
            <img :src="currentDoctor().avatar" class="doctor-avatar" :alt="currentDoctor()?.name">
        </template>
        <template x-if="!currentDoctor()?.avatar || currentDoctor()?.avatar.includes('user-avatar.png')">
            <div class="doctor-avatar-fallback" x-text="currentDoctor()?.initials || 'DR'"></div>
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
            <h2 x-text="currentDoctor()?.name || displayCopy.empty_state_title"></h2>
            <div class="speciality-row">
                <span class="speciality" x-text="currentDoctor()?.department || 'General Practice'"></span>
                <span class="exp-badge" x-show="currentDoctor()?.experience">
                    <span class="star-icon">★</span>
                    <span x-text="currentDoctor()?.experience" style="font-weight: 800; color: #1e293b;"></span>
                </span>
            </div>
            <div class="qualifications-list">
                <template x-for="qual in currentDoctor()?.education_list || []" :key="qual">
                    <div class="qual-item" x-html="formatQual(qual)"></div>
                </template>
            </div>
            <div class="doctor-break-note" x-show="currentDoctor()?.is_on_break" x-cloak>Doctor is on break</div>
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
            @if(!empty($doctorEducation))
                <div class="qualifications-list">
                    @foreach($doctorEducation as $qual)
                        <div class="qual-item">{{ $qual }}</div>
                    @endforeach
                </div>
            @endif
            @if($doctorBreakNote)
                <div class="doctor-break-note">Doctor is on break</div>
            @endif
        @endif
    </div>
</div>
