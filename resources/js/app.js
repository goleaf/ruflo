window.RuFlo = window.RuFlo || {};

window.RuFlo.localReminderNotifications = function ({ reminders, labels }) {
    return {
        reminders,
        labels,
        enabled: false,
        permission: 'default',
        statusMessage: '',
        timer: null,

        init() {
            this.permission = this.currentPermission();
            this.enabled = this.readEnabled() && this.permission === 'granted';
            this.syncStatus();
            this.tick();
            this.timer = window.setInterval(() => this.tick(), 30000);
        },

        destroy() {
            if (this.timer) {
                window.clearInterval(this.timer);
            }
        },

        supported() {
            return 'Notification' in window && window.isSecureContext;
        },

        secureRequired() {
            return 'Notification' in window && ! window.isSecureContext;
        },

        currentPermission() {
            if (! ('Notification' in window)) {
                return 'unsupported';
            }

            return Notification.permission;
        },

        canEnable() {
            return this.supported() && this.permission !== 'denied';
        },

        canTest() {
            return this.supported() && this.permission === 'granted';
        },

        pendingCount() {
            return this.reminders.length;
        },

        statusLabel() {
            if (this.enabled) {
                return this.labels.enabled;
            }

            if (this.permission === 'denied') {
                return this.labels.permission_denied;
            }

            return this.labels.disabled;
        },

        permissionLabel() {
            if (this.secureRequired()) {
                return this.labels.secure_required;
            }

            if (this.permission === 'granted') {
                return this.labels.permission_granted;
            }

            if (this.permission === 'denied') {
                return this.labels.permission_denied;
            }

            if (this.permission === 'unsupported') {
                return this.labels.unsupported;
            }

            return this.labels.permission_default;
        },

        pendingLabel() {
            return this.labels.pending_count.replace(':count', this.pendingCount());
        },

        async enable() {
            if (! this.supported()) {
                this.syncStatus();

                return;
            }

            this.permission = await this.requestPermission();
            this.enabled = this.permission === 'granted';
            this.writeEnabled(this.enabled);
            this.syncStatus();
            this.tick();
        },

        disable() {
            this.enabled = false;
            this.writeEnabled(false);
            this.syncStatus();
        },

        requestPermission() {
            return new Promise((resolve) => {
                let completed = false;
                const finish = (permission) => {
                    if (completed) {
                        return;
                    }

                    completed = true;
                    resolve(permission || Notification.permission);
                };
                const request = Notification.requestPermission(finish);

                if (request && typeof request.then === 'function') {
                    request.then(finish).catch(() => finish(Notification.permission));
                }
            });
        },

        tick() {
            this.permission = this.currentPermission();

            if (! this.enabled || this.permission !== 'granted') {
                return;
            }

            const sent = this.readSent();
            const now = Date.now();

            this.reminders.forEach((reminder) => {
                const reminderTime = Date.parse(reminder.remindAt);

                if (Number.isNaN(reminderTime) || reminderTime > now) {
                    return;
                }

                const key = `${reminder.id}:${reminder.remindAt}`;

                if (sent[key]) {
                    return;
                }

                if (this.notify(reminder)) {
                    sent[key] = new Date().toISOString();
                }
            });

            this.writeSent(sent);
        },

        notify(reminder) {
            try {
                const notification = new Notification(reminder.title, {
                    body: reminder.body,
                    icon: '/favicon.svg',
                    tag: reminder.tag,
                    timestamp: Date.parse(reminder.remindAt),
                });

                notification.onclick = () => {
                    window.focus();

                    if (reminder.url) {
                        if (window.Livewire && typeof window.Livewire.navigate === 'function') {
                            window.Livewire.navigate(reminder.url);
                        } else {
                            window.location.href = reminder.url;
                        }
                    }

                    notification.close();
                };

                return true;
            } catch {
                this.statusMessage = this.labels.failed;

                return false;
            }
        },

        sendTest() {
            if (! this.canTest()) {
                return;
            }

            this.notify({
                id: 'test',
                title: this.labels.test_title,
                body: this.labels.test_body,
                url: window.location.href,
                remindAt: new Date().toISOString(),
                tag: 'ruflo-reminder-test',
            });
        },

        syncStatus() {
            if (! ('Notification' in window)) {
                this.statusMessage = this.labels.unsupported;

                return;
            }

            if (this.secureRequired()) {
                this.statusMessage = this.labels.secure_required;

                return;
            }

            if (this.permission === 'denied') {
                this.statusMessage = this.labels.denied_help;

                return;
            }

            this.statusMessage = this.enabled ? this.labels.ready : this.labels.offline;
        },

        readEnabled() {
            try {
                return window.localStorage.getItem('ruflo:local-reminders:enabled') === 'true';
            } catch {
                return false;
            }
        },

        writeEnabled(enabled) {
            try {
                window.localStorage.setItem('ruflo:local-reminders:enabled', enabled ? 'true' : 'false');
            } catch {
                this.statusMessage = this.labels.storage_unavailable;
            }
        },

        readSent() {
            try {
                return JSON.parse(window.localStorage.getItem('ruflo:local-reminders:sent') || '{}');
            } catch {
                return {};
            }
        },

        writeSent(sent) {
            try {
                window.localStorage.setItem('ruflo:local-reminders:sent', JSON.stringify(sent));
            } catch {
                this.statusMessage = this.labels.storage_unavailable;
            }
        },
    };
};
