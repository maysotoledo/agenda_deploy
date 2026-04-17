<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    use HasPageShield;

    protected static ?string $model = AuditLog::class;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Logs';
    }

    public static function getNavigationLabel(): string
    {
        return 'Auditoria (Logs)';
    }

    public static function getModelLabel(): string
    {
        return 'Log de Auditoria';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Logs de Auditoria';
    }

    public static function getNavigationSort(): ?int
    {
        return 98;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('action')
                    ->label('Ação')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'request' => 'Acesso',
                        'created' => 'Criou',
                        'updated' => 'Editou',
                        'deleted' => 'Excluiu',
                        'login_success' => 'Login OK',
                        'login_failed' => 'Login Falhou',
                        'logout' => 'Logout',
                        default => $state ?? '—',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'login_failed' => 'danger',
                        'deleted' => 'danger',
                        'updated' => 'warning',
                        'created' => 'success',
                        'login_success' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                // ✅ NOVA COLUNA: ORIGEM (resource/page ou route)
                TextColumn::make('origem')
                    ->label('Origem')
                    ->state(function (AuditLog $record): string {
                        $panel = data_get($record->meta, 'filament.panel');
                        $res = data_get($record->meta, 'filament.resource_slug');
                        $page = data_get($record->meta, 'filament.page');

                        $parts = array_values(array_filter([
                            $panel ? "panel:{$panel}" : null,
                            $res ? "resource:{$res}" : null,
                            $page ? "page:{$page}" : null,
                        ]));

                        if (! empty($parts)) {
                            return implode(' | ', $parts);
                        }

                        return $record->route ?? '-';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('route', 'like', "%{$search}%")
                            ->orWhere('url', 'like', "%{$search}%");
                    })
                    ->wrap(),

                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->state(fn (AuditLog $record) => $record->user?->name ?? '—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('user', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
                    })
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->wrap(),

                TextColumn::make('ip')
                    ->label('IP')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('model_type')
                    ->label('Model')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('model_id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('route')
                    ->label('Rota')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                TextColumn::make('method')
                    ->label('Método')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('url')
                    ->label('URL')
                    ->limit(60)
                    ->tooltip(fn (AuditLog $record) => $record->url)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Ação')
                    ->options([
                        'request' => 'Acesso (painel)',
                        'created' => 'Criou',
                        'updated' => 'Editou',
                        'deleted' => 'Excluiu',
                        'login_success' => 'Login OK',
                        'login_failed' => 'Login Falhou',
                        'logout' => 'Logout',
                    ]),

                SelectFilter::make('user_id')
                    ->label('Usuário')
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),

                Filter::make('email')
                    ->label('Email')
                    ->form([TextInput::make('email')])
                    ->query(fn (Builder $q, array $data) => ($v = trim((string) ($data['email'] ?? ''))) === '' ? $q : $q->where('email', 'like', "%{$v}%")),

                Filter::make('ip')
                    ->label('IP')
                    ->form([TextInput::make('ip')])
                    ->query(fn (Builder $q, array $data) => ($v = trim((string) ($data['ip'] ?? ''))) === '' ? $q : $q->where('ip', 'like', "%{$v}%")),

                Filter::make('periodo')
                    ->label('Período')
                    ->form([
                        DatePicker::make('from')->label('De'),
                        DatePicker::make('until')->label('Até'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        return $q
                            ->when($data['from'] ?? null, fn (Builder $qq, $from) => $qq->whereDate('occurred_at', '>=', $from))
                            ->when($data['until'] ?? null, fn (Builder $qq, $until) => $qq->whereDate('occurred_at', '<=', $until));
                    }),
            ])
            ->emptyStateHeading('Nenhum log encontrado');
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
}
