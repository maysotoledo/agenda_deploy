<?php

namespace App\Services;

use App\Models\Evento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GoogleCalendarService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const CALENDAR_API_URL = 'https://www.googleapis.com/calendar/v3';
    private const SCOPE = 'https://www.googleapis.com/auth/calendar.events';
    private const AGENDA_TIMEZONE = 'America/Sao_Paulo';

    public function isConfigured(): bool
    {
        return filled($this->clientId())
            && filled($this->clientSecret())
            && filled($this->redirectUri());
    }

    public function getAuthorizationUrl(User $user): string
    {
        $this->ensureConfigured();

        $state = Str::random(48);

        session([
            'google_calendar_oauth_state' => $state,
            'google_calendar_oauth_user_id' => $user->getKey(),
        ]);

        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    public function connect(User $user, string $code): void
    {
        $this->ensureConfigured();

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri(),
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Falha ao conectar Google Calendar: ' . $response->body());
        }

        $payload = $response->json();

        $user->forceFill([
            'google_calendar_token' => $payload['access_token'] ?? null,
            'google_calendar_refresh_token' => $payload['refresh_token'] ?? $user->google_calendar_refresh_token,
            'google_calendar_token_expires_at' => now()->addSeconds((int) ($payload['expires_in'] ?? 3600) - 60),
            'google_calendar_id' => $user->google_calendar_id ?: $this->defaultCalendarId(),
        ])->save();
    }

    public function disconnect(User $user): void
    {
        $user->forceFill([
            'google_calendar_token' => null,
            'google_calendar_refresh_token' => null,
            'google_calendar_token_expires_at' => null,
            'google_calendar_id' => $this->defaultCalendarId(),
        ])->save();
    }

    public function syncEvento(Evento $evento): void
    {
        try {
            $evento->loadMissing('user');
            $user = $evento->user;

            if (! $user || ! $this->canSync($user)) {
                return;
            }

            if ($evento->trashed()) {
                $this->deleteEvento($evento);
                return;
            }

            $accessToken = $this->validAccessToken($user);
            $calendarId = rawurlencode($user->google_calendar_id ?: $this->defaultCalendarId());
            $payload = $this->makeEventPayload($evento);

            if ($evento->google_calendar_event_id) {
                $response = Http::withToken($accessToken)
                    ->patch(self::CALENDAR_API_URL . "/calendars/{$calendarId}/events/{$evento->google_calendar_event_id}", $payload);

                if ($response->status() === 404) {
                    $this->createEvento($evento, $accessToken, $calendarId, $payload);
                    return;
                }

                $this->throwIfFailed($response, 'atualizar evento no Google Calendar');
                return;
            }

            $this->createEvento($evento, $accessToken, $calendarId, $payload);
        } catch (Throwable $exception) {
            Log::warning('Falha ao sincronizar agendamento com Google Calendar.', [
                'evento_id' => $evento->getKey(),
                'user_id' => $evento->user_id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function deleteEvento(Evento $evento): void
    {
        if (! $evento->google_calendar_event_id) {
            return;
        }

        try {
            $evento->loadMissing('user');
            $user = $evento->user;

            if (! $user || ! $this->canSync($user)) {
                return;
            }

            $calendarId = rawurlencode($user->google_calendar_id ?: $this->defaultCalendarId());
            $response = Http::withToken($this->validAccessToken($user))
                ->delete(self::CALENDAR_API_URL . "/calendars/{$calendarId}/events/{$evento->google_calendar_event_id}");

            if (! in_array($response->status(), [200, 204, 404, 410], true)) {
                $this->throwIfFailed($response, 'remover evento do Google Calendar');
            }

            $evento->forceFill(['google_calendar_event_id' => null])->saveQuietly();
        } catch (Throwable $exception) {
            Log::warning('Falha ao remover agendamento do Google Calendar.', [
                'evento_id' => $evento->getKey(),
                'user_id' => $evento->user_id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function canSync(User $user): bool
    {
        return $this->isConfigured()
            && filled($user->google_calendar_refresh_token);
    }

    private function createEvento(Evento $evento, string $accessToken, string $calendarId, array $payload): void
    {
        $response = Http::withToken($accessToken)
            ->post(self::CALENDAR_API_URL . "/calendars/{$calendarId}/events", $payload);

        $this->throwIfFailed($response, 'criar evento no Google Calendar');

        $googleEventId = $response->json('id');

        if ($googleEventId) {
            $evento->forceFill(['google_calendar_event_id' => $googleEventId])->saveQuietly();
        }
    }

    private function makeEventPayload(Evento $evento): array
    {
        $timezone = self::AGENDA_TIMEZONE;
        $startsAt = $this->parseAgendaWallClock($evento, 'starts_at');
        $endsAt = $this->parseAgendaWallClock($evento, 'ends_at')
            ?: $startsAt->copy()->addHour();

        $tipo = $evento->oitiva_online ? 'Online' : 'Presencial';
        $intimado = $evento->intimado ?: 'Agendamento';

        $description = array_filter([
            $evento->numero_procedimento ? "Procedimento: {$evento->numero_procedimento}" : null,
            $evento->whatsapp ? "WhatsApp: {$evento->whatsapp}" : null,
            "Modalidade: {$tipo}",
            "Agendamento criado pelo sistema " . config('app.name') . ".",
        ]);

        return [
            'summary' => "Oitiva - {$intimado}",
            'description' => implode("\n", $description),
            'start' => [
                'dateTime' => $startsAt->toRfc3339String(),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => $endsAt->toRfc3339String(),
                'timeZone' => $timezone,
            ],
        ];
    }

    private function parseAgendaWallClock(Evento $evento, string $attribute): ?Carbon
    {
        $timezone = self::AGENDA_TIMEZONE;
        $raw = $evento->getRawOriginal($attribute);

        if (is_string($raw) && trim($raw) !== '') {
            return Carbon::createFromFormat('Y-m-d H:i:s', $raw, $timezone);
        }

        $value = $evento->getAttribute($attribute);

        if ($value instanceof Carbon) {
            return Carbon::createFromFormat('Y-m-d H:i:s', $value->format('Y-m-d H:i:s'), $timezone);
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value, $timezone);
        }

        return null;
    }

    private function validAccessToken(User $user): string
    {
        if (
            filled($user->google_calendar_token)
            && $user->google_calendar_token_expires_at
            && $user->google_calendar_token_expires_at->isFuture()
        ) {
            return $user->google_calendar_token;
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'refresh_token' => $user->google_calendar_refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Falha ao renovar token do Google Calendar: ' . $response->body());
        }

        $payload = $response->json();

        $user->forceFill([
            'google_calendar_token' => $payload['access_token'] ?? null,
            'google_calendar_token_expires_at' => now()->addSeconds((int) ($payload['expires_in'] ?? 3600) - 60),
        ])->save();

        return (string) $user->google_calendar_token;
    }

    private function throwIfFailed(Response $response, string $action): void
    {
        if ($response->failed()) {
            throw new RuntimeException("Falha ao {$action}: " . $response->body());
        }
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Configure GOOGLE_CALENDAR_CLIENT_ID e GOOGLE_CALENDAR_CLIENT_SECRET.');
        }
    }

    private function clientId(): ?string
    {
        return config('services.google_calendar.client_id');
    }

    private function clientSecret(): ?string
    {
        return config('services.google_calendar.client_secret');
    }

    private function redirectUri(): ?string
    {
        return config('services.google_calendar.redirect_uri') ?: route('google-calendar.callback');
    }

    private function defaultCalendarId(): string
    {
        return config('services.google_calendar.calendar_id', 'primary') ?: 'primary';
    }
}
