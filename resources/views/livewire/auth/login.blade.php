<x-layouts::auth :title="__('auth.login.title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('auth.login.heading')" :description="__('auth.login.description')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('auth.login.email')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                :placeholder="__('auth.login.email_placeholder')"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('auth.login.password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('auth.login.password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('auth.login.forgot_password') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('auth.login.remember')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('auth.login.submit') }}
                </flux:button>
            </div>
        </form>

        @isset($demoUsers)
            @if ($demoUsers !== [])
                <flux:card class="space-y-4" data-test="demo-login-panel" aria-labelledby="demo-login-heading">
                    <div class="space-y-1">
                        <flux:heading id="demo-login-heading" size="sm">{{ __('auth.demo.heading') }}</flux:heading>
                        <flux:text variant="subtle" class="text-sm">{{ __('auth.demo.description') }}</flux:text>
                    </div>

                    <div class="space-y-3">
                        @foreach ($demoUsers as $demoUser)
                            <div class="space-y-3 rounded-lg border border-zinc-200 p-3 dark:border-white/10">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $demoUser->name }}</p>
                                        <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">{{ $demoUser->description }}</p>
                                    </div>

                                    <flux:badge size="sm" color="zinc">{{ $demoUser->role }}</flux:badge>
                                </div>

                                <dl class="grid gap-2 text-xs text-zinc-600 dark:text-zinc-400 sm:grid-cols-2">
                                    <div>
                                        <dt class="font-medium text-zinc-900 dark:text-white">{{ __('auth.demo.email') }}</dt>
                                        <dd class="break-all">{{ $demoUser->email }}</dd>
                                    </div>

                                    <div>
                                        <dt class="font-medium text-zinc-900 dark:text-white">{{ __('auth.demo.password') }}</dt>
                                        <dd>{{ $demoUser->password }}</dd>
                                    </div>
                                </dl>

                                <form method="POST" action="{{ route('login.store') }}">
                                    @csrf
                                    <input type="hidden" name="email" value="{{ $demoUser->email }}">
                                    <input type="hidden" name="password" value="{{ $demoUser->password }}">
                                    <input type="hidden" name="remember" value="1">

                                    <flux:button type="submit" variant="subtle" class="w-full" data-test="demo-login-button">
                                        {{ __('auth.demo.quick_login', ['name' => $demoUser->name]) }}
                                    </flux:button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif
        @endisset

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('auth.login.signup_prompt') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('auth.login.signup') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
