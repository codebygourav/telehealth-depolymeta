<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="{
        state: $wire.entangle('{{ $getStatePath() }}'),
        hoverRating: 0
    }"
    class="flex flex-col space-y-2">
        <div class="flex items-center gap-1" @mouseleave="hoverRating = 0">
            @for ($i = 1; $i <= 5; $i++)
                <button type="button" 
                    @click="state = {{ $i }}" 
                    @mouseenter="hoverRating = {{ $i }}"
                    class="transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded p-1"
                    :class="{
                        'scale-110': hoverRating >= {{ $i }} || (hoverRating === 0 && state >= {{ $i }}),
                        'scale-100': hoverRating < {{ $i }} && (hoverRating > 0 || state < {{ $i }})
                    }">
                    <!-- Filled Star -->
                    <x-heroicon-s-star
                        x-show="hoverRating >= {{ $i }} || (hoverRating === 0 && state >= {{ $i }})"
                        class="w-8 h-8 cursor-pointer text-yellow-400 transition-colors duration-200" 
                    />
                    <!-- Empty Star -->
                    <x-heroicon-o-star
                        x-show="hoverRating < {{ $i }} && (hoverRating > 0 || state < {{ $i }})"
                        class="w-8 h-8 cursor-pointer text-gray-300 transition-colors duration-200" 
                    />
                </button>
            @endfor

            <span x-show="state > 0" x-text="state + ' out of 5'"
                class="ml-3 text-sm text-gray-600 dark:text-gray-400"></span>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">
            Hover over the stars to preview, click to select
        </p>
    </div>
</x-dynamic-component>
