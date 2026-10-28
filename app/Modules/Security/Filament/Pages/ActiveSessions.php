<?php

namespace App\Modules\Security\Filament\Pages;

use App\Modules\Logs\Support\AgentClassifier;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * "Where am I logged in?" for the current admin, with "log out other devices".
 * Requires SESSION_DRIVER=database (the default in .env.example).
 */
class ActiveSessions extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?int $navigationSort = 95;

    protected string $view = 'admin.active-sessions';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.account');
    }

    public static function getNavigationLabel(): string
    {
        return __('larawebkit-security.sessions.title');
    }

    public function getTitle(): string
    {
        return __('larawebkit-security.sessions.title');
    }

    public static function usesDatabaseSessions(): bool
    {
        return config('session.driver') === 'database';
    }

    /** @return list<array{id: string, ip: ?string, agent: string, last_active: Carbon, current: bool}> */
    public function getSessions(): array
    {
        if (! self::usesDatabaseSessions()) {
            return [];
        }

        return DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', Auth::id())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'ip' => $s->ip_address,
                'agent' => self::describeAgent((string) $s->user_agent),
                'last_active' => Carbon::createFromTimestamp($s->last_activity),
                'current' => $s->id === session()->getId(),
            ])->all();
    }

    public static function describeAgent(string $ua): string
    {
        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            AgentClassifier::classify($ua)['class'] !== AgentClassifier::HUMAN => 'Bot',
            default => __('larawebkit-security.sessions.unknown'),
        };
        $os = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Linux') => 'Linux',
            default => '',
        };

        return trim($browser.($os ? ' · '.$os : ''));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('logoutOthers')
                ->label(__('larawebkit-security.sessions.logout_others'))
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription(__('larawebkit-security.sessions.logout_others_help'))
                ->schema([
                    TextInput::make('password')->label(__('larawebkit-security.sessions.password'))->password()->required()->revealable(),
                ])
                ->action(fn (array $data) => $this->logoutOtherDevices($data['password'])),
        ];
    }

    public function logoutOtherDevices(string $password): void
    {
        $user = Auth::user();
        if (! Hash::check($password, $user->getAuthPassword())) {
            throw ValidationException::withMessages(['password' => __('larawebkit-security.sessions.wrong_password')]);
        }

        Auth::logoutOtherDevices($password);

        if (self::usesDatabaseSessions()) {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->getAuthIdentifier())
                ->where('id', '!=', session()->getId())
                ->delete();
        }

        Notification::make()->success()->title(__('larawebkit-security.sessions.logged_out'))->send();
    }

    public function revoke(string $sessionId): void
    {
        if ($sessionId === session()->getId() || ! self::usesDatabaseSessions()) {
            return;
        }

        DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', Auth::id())->where('id', $sessionId)->delete();

        Notification::make()->success()->title(__('larawebkit-security.sessions.revoked'))->send();
    }
}
