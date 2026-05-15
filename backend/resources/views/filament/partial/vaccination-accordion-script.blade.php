<script>
    (function () {
        const closeOtherSections = (openedSection) => {
            const form = openedSection?.closest('.vaccination-accordion-form');
            if (!form) {
                return;
            }

            form.querySelectorAll('.vaccination-accordion-section').forEach((section) => {
                if (section === openedSection) {
                    return;
                }

                if (section.getAttribute('aria-expanded') !== 'true') {
                    return;
                }

                const toggle = section.querySelector('.fi-section-header-button, .fi-section-header [type="button"]');
                toggle?.click();
            });
        };

        const bindAccordion = (root = document) => {
            root.querySelectorAll('.vaccination-accordion-form .vaccination-accordion-section .fi-section-header').forEach((header) => {
                if (header.dataset.accordionBound === '1') {
                    return;
                }

                header.dataset.accordionBound = '1';

                header.addEventListener('click', () => {
                    const section = header.closest('.vaccination-accordion-section');
                    if (!section) {
                        return;
                    }

                    window.setTimeout(() => {
                        if (section.getAttribute('aria-expanded') === 'true') {
                            closeOtherSections(section);
                        }
                    }, 80);
                });
            });
        };

        document.addEventListener('DOMContentLoaded', () => bindAccordion());
        document.addEventListener('livewire:navigated', () => bindAccordion());
        document.addEventListener('livewire:initialized', () => {
            bindAccordion();
            Livewire.hook('morph.updated', ({ el }) => bindAccordion(el));
        });
    })();
</script>
