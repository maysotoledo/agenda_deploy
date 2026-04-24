@php
    $user = filament()->auth()->user();
    $profileUrl = filament()->getProfileUrl();
    $googleCalendar = app(\App\Services\GoogleCalendarService::class);
@endphp

<x-filament-widgets::widget class="fi-account-widget">
    <x-filament::section>
        <x-filament-panels::avatar.user
            size="lg"
            :user="$user"
            loading="lazy"
        />

        <div class="fi-account-widget-main">
            <h2 class="fi-account-widget-heading">
                {{ __('filament-panels::widgets/account-widget.welcome', ['app' => config('app.name')]) }}
            </h2>

            <p class="fi-account-widget-user-name">
                {{ filament()->getUserName($user) }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($user?->hasRole('epc') || $user?->hasRole('cartorio_central'))
                @if($googleCalendar->canSync($user))
                    <form method="post" action="{{ route('google-calendar.disconnect') }}">
                        @csrf

                        <x-filament::button
                            color="success"
                            icon="heroicon-o-calendar-days"
                            labeled-from="sm"
                            tag="button"
                            type="submit"
                        >
                            Google Agenda conectado
                        </x-filament::button>
                    </form>
                @else
                    <x-filament::button
                        color="info"
                        icon="heroicon-o-calendar-days"
                        labeled-from="sm"
                        tag="a"
                        :href="route('google-calendar.connect')"
                        :disabled="! $googleCalendar->isConfigured()"
                        :tooltip="$googleCalendar->isConfigured() ? null : 'Configure as credenciais do Google Calendar no .env'"
                    >
                        Conectar Google Agenda
                    </x-filament::button>
                @endif
            @endif

            @if($profileUrl)
                <x-filament::button
                    color="primary"
                    icon="heroicon-o-key"
                    labeled-from="sm"
                    tag="a"
                    :href="$profileUrl"
                >
                    Alterar senha
                </x-filament::button>
            @endif

            <form
                action="{{ filament()->getLogoutUrl() }}"
                method="post"
                class="fi-account-widget-logout-form"
            >
                @csrf

                <x-filament::button
                    color="gray"
                    icon="heroicon-o-arrow-left-end-on-rectangle"
                    labeled-from="sm"
                    tag="button"
                    type="submit"
                >
                    {{ __('filament-panels::widgets/account-widget.actions.logout.label') }}
                </x-filament::button>
            </form>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
