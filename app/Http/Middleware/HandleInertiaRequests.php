<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    // public function share(Request $request): array
    // {
    //     [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

    //     return [
    //         ...parent::share($request),
    //         'name' => config('app.name'),
    //         'quote' => ['message' => trim($message), 'author' => trim($author)],
    //         'auth' => [
    //             'user' => $request->user(),
    //         ],
    //     ];
    // }
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => function () use ($request) {
                if (!$request->user()) {
                    return [
                        'user' => null,
                        'permissions' => null,
                        'roles' => null,
                    ];
                }

                $permissions = $request->user()->getAllPermissions()->pluck('name')->toArray();
                $roles = $request->user()->getRoleNames()->toArray();

                return [
                    'user' => [
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                        'created_at' => $request->user()->created_at->format('Y-m-d H:i:s'),
                    ],
                    'permissions' => base64_encode(json_encode($permissions)),
                    'roles' => base64_encode(json_encode($roles)),
                ];
            },
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
            ],          
        ]);
    }
    
}
