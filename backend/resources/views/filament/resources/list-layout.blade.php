{{--
    Shared resource list layout helper.
    Keeps resource tabs after the above-content filters on every list page,
    including after Livewire re-renders and client-side navigation.
--}}
<div
    x-data="{
        isBusy: false,
        observer: null,

        pageRoot() {
            return document.querySelector('[class*=\'fi-resource-\'][class*=\'-list-\'][class*=\'-page\']') ?? document;
        },

        moveTabs() {
            const root = this.pageRoot();
            const tabs = root.querySelector('nav.fi-tabs');
            const filters = root.querySelector('.fi-ta-filters-above-content-ctn');
            const table = root.querySelector('.fi-ta');

            if (!tabs) return;

            if (filters) {
                if (filters.nextElementSibling === tabs) return;

                this.isBusy = true;
                filters.after(tabs);
                setTimeout(() => { this.isBusy = false; }, 100);

                return;
            }

            if (table && table.firstElementChild !== tabs) {
                this.isBusy = true;
                table.prepend(tabs);
                setTimeout(() => { this.isBusy = false; }, 100);
            }
        },

        moveToolbar() {
            const root = this.pageRoot();
            const filters = root.querySelector('.fi-ta-filters-above-content-ctn');
            const toolbar = root.querySelector('.fi-ta-header-toolbar');

            if (!filters || !toolbar) return;

            const filterRow = filters.querySelector('.fi-ta-filters form > .fi-sc')
                           ?? filters.querySelector('.fi-ta-filters form .fi-sc')
                           ?? filters.querySelector('.fi-ta-filters form .grid')
                           ?? filters.querySelector('.fi-ta-filters form > div');

            if (!filterRow) return;
            if (toolbar.parentElement === filterRow) return;

            this.isBusy = true;
            filterRow.append(toolbar);
            setTimeout(() => { this.isBusy = false; }, 100);
        },

        startObserver() {
            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }

            const self = this;
            this.observer = new MutationObserver(function() {
                if (self.isBusy) return;
                self.moveTabs();
                self.moveToolbar();
            });

            this.observer.observe(document.body, {
                childList: true,
                subtree: true,
            });
        },
    }"
    x-init="
        moveTabs();
        moveToolbar();
        startObserver();
        document.addEventListener('livewire:navigated', () => {
            moveTabs();
            moveToolbar();
            startObserver();
        });
    "
    class="hidden"
></div>
