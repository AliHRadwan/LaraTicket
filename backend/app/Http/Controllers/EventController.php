<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessEventImage;
use App\Models\Event;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 50);

        $sortField = match ($request->query('sort')) {
            'price' => 'price',
            'title' => 'title',
            'start_date' => 'start_datetime',
            default => 'created_at',
        };

        $cacheKey = 'events:list:' . md5(json_encode([
            $request->query('search'),
            $request->query('upcoming'),
            $request->query('location'),
            $request->query('sort'),
            $request->query('page', 1),
            $perPage,
        ]));

        $events = Cache::remember($cacheKey, 60, fn () =>
            Event::query()
                ->when($request->search, fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
                ->when($request->boolean('upcoming'), fn ($q) => $q->where('start_datetime', '>', now()))
                ->when($request->location, fn ($q, $loc) => $q->where('location', 'like', "%{$loc}%"))
                ->orderByDesc($sortField)
                ->paginate($perPage)
        );

        return response()->json($events);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('event-images', 'public');
        }

        $event = Event::create([
            ...collect($validated)->except('image')->all(),
            'user_id' => $request->user()->id,
            'slug' => Str::slug($validated['title']) . '-' . Str::random(6),
            'available_tickets' => $validated['total_tickets'],
            'image_url' => $imagePath ?? ($validated['image_url'] ?? null),
        ]);

        if ($imagePath) {
            ProcessEventImage::dispatch($event->id, $imagePath);
        }

        Cache::flush();

        Log::info('Event created', [
            'event_id' => $event->id,
            'admin_id' => $request->user()->id,
            'title' => $event->title,
        ]);

        return response()->json([
            'message' => 'Event created successfully.',
            'event' => $event,
        ], 201);
    }

    public function show(Event $event): JsonResponse
    {
        $cached = Cache::remember("events:{$event->id}", 300, fn () => $event);

        return response()->json([
            'event' => $cached,
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            if ($event->image_url && ! str_starts_with($event->image_url, 'http')) {
                Storage::disk('public')->delete($event->image_url);
            }

            $imagePath = $request->file('image')->store('event-images', 'public');
            $validated['image_url'] = $imagePath;

            ProcessEventImage::dispatch($event->id, $imagePath);
        }

        $event->update(collect($validated)->except('image')->all());

        Cache::forget("events:{$event->id}");
        Cache::flush();

        Log::info('Event updated', [
            'event_id' => $event->id,
            'admin_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Event updated successfully.',
            'event' => $event->fresh(),
        ]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);

        $eventId = $event->id;
        $event->delete();

        Cache::forget("events:{$eventId}");
        Cache::flush();

        Log::info('Event deleted', [
            'event_id' => $eventId,
            'admin_id' => request()->user()->id,
        ]);

        return response()->json([
            'message' => 'Event deleted successfully.',
        ]);
    }
}
