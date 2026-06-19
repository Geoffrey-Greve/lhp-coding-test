<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventVisualResource;
use App\Services\LocationResolver;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventVisualController extends Controller
{
    public function __construct(
        private LocationResolver $locationResolver,
    ) {}

    public function visualOne(Request $request): Response
    {
        return Inertia::render('Events/VisualOne', $this->pageProps($request));
    }

    public function visualTwo(Request $request): Response
    {
        return Inertia::render('Events/VisualTwo', $this->pageProps($request));
    }

    public function data(Request $request): JsonResponse
    {
        $events = $this->query($request);

        return response()->json([
            'data' => EventVisualResource::collection($events->items())->resolve(),
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'total' => $events->total(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pageProps(Request $request): array
    {
        return [
            'filters' => [
                'from' => $request->input('from', now()->subMonth()->toDateString()),
                'to' => $request->input('to', now()->addMonths(3)->toDateString()),
                'city' => $request->input('city', ''),
            ],
            'cities' => $this->locationResolver->cityOptions(),
        ];
    }

    private function query(Request $request): LengthAwarePaginator
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $city = $request->input('city');
        $box = $this->locationResolver->boundingBoxForSlug($city);

        return \App\Models\Event::query()
            ->with('images')
            ->withCount('attendees')
            ->where('status', 'published')
            ->when($from, fn ($q, $date) => $q->where('created_time', '>=', Carbon::parse($date, 'UTC')->startOfDay()->timestamp))
            ->when($to, fn ($q, $date) => $q->where('created_time', '<=', Carbon::parse($date, 'UTC')->endOfDay()->timestamp))
            ->when($box, function ($q) use ($box) {
                $q->whereBetween('latitude', [$box['min_lat'], $box['max_lat']])
                    ->whereBetween('longitude', [$box['min_lng'], $box['max_lng']]);
            })
            ->orderBy('created_time')
            ->paginate(24)
            ->withQueryString();
    }
}
