<?php

namespace App\Services;

use App\Models\Evento;
use Illuminate\Support\Facades\DB;

class EventoService
{
    public function __construct(
        private readonly GoogleCalendarService $googleCalendar,
    ) {}

    public function criar(array $data): Evento
    {
        $evento = DB::transaction(function () use ($data) {
            $userId = auth()->id();

            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;

            return Evento::create($data);
        });

        $this->googleCalendar->syncEvento($evento->refresh());

        return $evento;
    }

    public function editar(Evento $evento, array $data): Evento
    {
        $evento = DB::transaction(function () use ($evento, $data) {
            $userId = auth()->id();

            $data['updated_by'] = $userId;

            $evento->fill($data);
            $evento->save();

            return $evento->refresh();
        });

        $this->googleCalendar->syncEvento($evento);

        return $evento;
    }

    /**
     * Cancelamento (soft delete) com auditoria.
     */
    public function cancelar(Evento $evento): void
    {
        DB::transaction(function () use ($evento) {
            $userId = auth()->id();

            $evento->forceFill([
                'updated_by' => $userId,
                'deleted_by' => $userId,
            ])->save();

            $evento->delete(); // SoftDeletes => deleted_at
        });

        $this->googleCalendar->deleteEvento($evento);
    }

    /**
     * Restaurar evento cancelado (opcional, mas útil).
     */
    public function restaurar(int $eventoId): Evento
    {
        $evento = DB::transaction(function () use ($eventoId) {
            $userId = auth()->id();

            $evento = Evento::withTrashed()->findOrFail($eventoId);
            $evento->restore();

            $evento->forceFill([
                'deleted_by' => null,
                'updated_by' => $userId,
            ])->save();

            return $evento->refresh();
        });

        $this->googleCalendar->syncEvento($evento);

        return $evento;
    }
}
