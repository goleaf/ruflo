const flatpickrScriptUrl = 'https://cdn.jsdelivr.net/npm/flatpickr';
const flatpickrStylesheetUrl = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';

let flatpickrLoader;
let modalDatePickerRegistered = false;

function appendFlatpickrStylesheet() {
    if (document.querySelector(`link[href="${flatpickrStylesheetUrl}"]`)) {
        return;
    }

    const stylesheet = document.createElement('link');
    stylesheet.rel = 'stylesheet';
    stylesheet.href = flatpickrStylesheetUrl;

    document.head.append(stylesheet);
}

function appendFlatpickrTheme() {
    if (document.getElementById('ruflo-flatpickr-theme')) {
        return;
    }

    const theme = document.createElement('style');
    theme.id = 'ruflo-flatpickr-theme';
    theme.textContent = `
        .flatpickr-calendar.inline {
            border: 0;
            box-shadow: none;
            width: 100%;
        }

        .flatpickr-calendar .flatpickr-days,
        .flatpickr-calendar .dayContainer {
            width: 100%;
            min-width: 100%;
            max-width: 100%;
        }

        .flatpickr-calendar .flatpickr-day.selected,
        .flatpickr-calendar .flatpickr-day.startRange,
        .flatpickr-calendar .flatpickr-day.endRange {
            background: #2563eb;
            border-color: #2563eb;
        }

        .dark .flatpickr-calendar {
            background: #09090b;
            color: #f4f4f5;
        }

        .dark .flatpickr-calendar .flatpickr-months,
        .dark .flatpickr-calendar .flatpickr-weekdays {
            background: #09090b;
            color: #f4f4f5;
        }

        .dark .flatpickr-calendar .flatpickr-monthDropdown-months,
        .dark .flatpickr-calendar .numInput {
            color: #f4f4f5;
        }

        .dark .flatpickr-calendar .flatpickr-day {
            color: #f4f4f5;
        }

        .dark .flatpickr-calendar .flatpickr-day.prevMonthDay,
        .dark .flatpickr-calendar .flatpickr-day.nextMonthDay {
            color: #71717a;
        }

        .dark .flatpickr-calendar .flatpickr-day:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.1);
        }
    `;

    document.head.append(theme);
}

function loadFlatpickr() {
    if (window.flatpickr) {
        appendFlatpickrStylesheet();
        appendFlatpickrTheme();

        return Promise.resolve(window.flatpickr);
    }

    if (flatpickrLoader) {
        return flatpickrLoader;
    }

    appendFlatpickrStylesheet();

    flatpickrLoader = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = flatpickrScriptUrl;
        script.async = true;

        script.addEventListener('load', () => {
            appendFlatpickrTheme();
            resolve(window.flatpickr);
        }, { once: true });

        script.addEventListener('error', () => {
            flatpickrLoader = undefined;
            reject(new Error('Unable to load flatpickr.'));
        }, { once: true });

        document.head.append(script);
    });

    return flatpickrLoader;
}

function registerModalDatePicker() {
    if (modalDatePickerRegistered || !window.Alpine) {
        return;
    }

    modalDatePickerRegistered = true;

    window.Alpine.data('modalDatePicker', ({ modalName, value }) => ({
        value,
        modalName,
        picker: null,
        isLoading: true,
        loadError: false,

        async init() {
            try {
                const flatpickr = await loadFlatpickr();

                this.picker = flatpickr(this.$refs.calendar, {
                    inline: true,
                    dateFormat: 'Y-m-d',
                    defaultDate: this.value || null,
                    disableMobile: true,
                    onChange: (_selectedDates, selectedDate) => {
                        this.value = selectedDate;
                    },
                });

                this.$watch('value', (date) => {
                    if (!this.picker) {
                        return;
                    }

                    const nextDate = date || '';

                    if (this.picker.input.value === nextDate) {
                        return;
                    }

                    this.picker.setDate(nextDate || null, false);
                });
            } catch {
                this.loadError = true;
            } finally {
                this.isLoading = false;
            }
        },

        clear() {
            this.value = '';

            if (this.picker) {
                this.picker.clear();
            }
        },
    }));
}

document.addEventListener('alpine:init', registerModalDatePicker);
registerModalDatePicker();
