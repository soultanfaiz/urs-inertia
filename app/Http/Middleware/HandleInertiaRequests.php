<?php

namespace App\Http\Middleware;

use App\Models\Notification;
use App\Enums\RequestStatus;
use App\Enums\VerificationStatus;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'csrf_token' => csrf_token(),
            'auth' => function () use ($request) {
                $user = $request->user()?->load('roles:name');

                if (!$user) {
                    return ['user' => null, 'notifications' => [], 'notifications_count' => 0];
                }

                // 1. Buat query awal berdasarkan peran pengguna.
                $query = Notification::with('requestHistory.appRequest')->where('user_id', $user->id)->latest('created_at')->limit(50);
                $allRelevantNotifications = $query->get();

                // 2. Proses koleksi di PHP untuk mendapatkan `count` dan `list`.
                $notifications_count = $allRelevantNotifications
                    ->where('user_id', $user->id)
                    ->where('is_read', false)
                    ->count();

                $notifications_list = $allRelevantNotifications
                    ->take(10) // Ambil 10 teratas untuk ditampilkan
                    ->map(function ($notification) {
                        $appRequest = $notification->requestHistory->appRequest ?? null;
                        if (!$appRequest)
                            return null;

                        return [
                            'id' => $notification->id,
                            'title' => "Update pada: " . \Illuminate\Support\Str::limit($appRequest->title, 25),
                            'message' => $notification->message,
                            'link' => route('app-requests.show', $appRequest->id),
                            'created_at' => $notification->created_at,
                            'is_read' => (bool) $notification->is_read,
                        ];
                    })->filter();

                return [
                    'user' => $user,
                    'notifications_count' => $notifications_count,
                    'notifications' => $notifications_list,
                ];
            },
            'enums' => [
                'verificationStatus' => collect(VerificationStatus::cases())->mapWithKeys(function ($case) {
                    return [$case->name => $case->value];
                })->all(),
                'requestStatus' => collect(RequestStatus::cases())->mapWithKeys(function ($case) {
                    return [$case->name => $case->value];
                })->all(),
                'requestStatusLabels' => collect(RequestStatus::cases())->mapWithKeys(function ($case) {
                    return [$case->value => $case->label()];
                })->all(),
            ],
        ]);
    }
}
