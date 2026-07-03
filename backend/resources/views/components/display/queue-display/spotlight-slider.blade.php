<div class="queue-spotlight-modal" x-show="activeAdPopup?.kind === 'ad'" x-cloak>
    <div class="queue-spotlight-card fade-enter">
        <div class="queue-spotlight-head" x-show="displayCopy.show_slide_heading" x-text="displayCopy.ad_popup_title || 'Doctor Spotlight'"></div>

        <div class="queue-spotlight-wrap">
            <button type="button" class="queue-spotlight-arrow queue-spotlight-arrow-left" @click="spotlightPrevSlide()" aria-label="Previous ad">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            <div class="queue-spotlight-main">
                <div class="queue-spotlight-media">
                    <template x-if="spotlightCurrentSlide()?.embed_url && spotlightCurrentSlide()?.embed_type === 'iframe'">
                        <iframe
                            :src="spotlightCurrentSlide().embed_url"
                            title="Doctor spotlight"
                            allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                            referrerpolicy="strict-origin-when-cross-origin"
                            @load="bindMediaFrame($el, spotlightCurrentSlide())"
                        ></iframe>
                    </template>

                    <template x-if="spotlightCurrentSlide()?.video_url && !(spotlightCurrentSlide()?.embed_url && spotlightCurrentSlide()?.embed_type === 'iframe')">
                        <video
                            :src="spotlightCurrentSlide().video_url"
                            :autoplay="spotlightCurrentSlide()?.autoplay ?? true"
                            :muted="spotlightCurrentSlide()?.muted ?? true"
                            :loop="false"
                            playsinline
                            controls
                            @ended="spotlightNextSlide()"
                        ></video>
                    </template>

                    <template x-if="spotlightCurrentSlide()?.image && !(spotlightCurrentSlide()?.embed_url && spotlightCurrentSlide()?.embed_type === 'iframe') && !spotlightCurrentSlide()?.video_url">
                        <img :src="spotlightCurrentSlide().image" :alt="spotlightCurrentSlide()?.title || 'Doctor ad'">
                    </template>

                    <template x-if="!spotlightCurrentSlide()?.image && !spotlightCurrentSlide()?.video_url && !(spotlightCurrentSlide()?.embed_url && spotlightCurrentSlide()?.embed_type === 'iframe')">
                        <div class="queue-spotlight-empty">
                            <div class="queue-spotlight-empty-title" x-text="spotlightText(spotlightCurrentSlide()?.title || 'Doctor Spotlight')"></div>
                            <div class="queue-spotlight-empty-desc" x-text="spotlightText(spotlightCurrentSlide()?.description || displayCopy.empty_slide_text)"></div>
                        </div>
                    </template>
                </div>

                @if((bool) ($board['display']['show_slide_heading'] ?? false) || (bool) ($board['display']['show_slide_description'] ?? false))
                    <div class="queue-spotlight-content" x-show="shouldShowSpotlightContent()">
                        <div class="queue-spotlight-kicker">
                            <div class="popup-meta-chip" x-text="activeAdPopup?.doctor_name || 'Doctor'"></div>
                            <div class="popup-meta-chip" x-text="spotlightCurrentSlide()?.category_label || displayCopy.now_showing_label || 'Now showing'"></div>
                        </div>

                        <div>
                            <div class="queue-spotlight-title" x-show="displayCopy.show_slide_heading" x-text="spotlightText(spotlightCurrentSlide()?.title || displayCopy.ad_popup_title || 'Doctor Spotlight')"></div>
                            <div class="queue-spotlight-desc" x-show="displayCopy.show_slide_description" x-text="spotlightText(spotlightCurrentSlide()?.description || displayCopy.empty_slide_text || '')"></div>
                        </div>
                    </div>
                @endif

                <div class="queue-spotlight-footer">
                    <span x-text="activeAdPopup?.doctor_name || 'Doctor'"></span>
                    <span x-text="spotlightCurrentSlide()?.category_label || displayCopy.now_showing_label || 'Now showing'"></span>
                </div>
            </div>

            <button type="button" class="queue-spotlight-arrow queue-spotlight-arrow-right" @click="spotlightNextSlide()" aria-label="Next ad">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>

        <div class="queue-spotlight-pagination" x-show="spotlightSlides().length > 1">
            <span x-text="(spotlightSlideIndex() + 1) + ' / ' + spotlightSlides().length"></span>
        </div>

        <div class="queue-spotlight-dots" x-show="spotlightSlides().length > 1">
            <template x-for="(slide, idx) in spotlightSlides()" :key="slide.spotlight_uid || (slide.id + '-spotlight-' + idx)">
                <button
                    type="button"
                    :class="{ 'active': idx === spotlightSlideIndex() }"
                    @click="spotlightIndex = idx; syncSpotlightPopup()"
                    :aria-label="'Go to slide ' + (idx + 1)"
                ></button>
            </template>
        </div>
    </div>
</div>
