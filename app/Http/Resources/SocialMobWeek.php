<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Carbon;

class SocialMobWeek extends ResourceCollection
{
    public $collects = GrowthSession::class;
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $requestedDate = $request->input('date', now()->toDateString());
        return $this->emptyWeek($requestedDate)->merge(collect(parent::toArray($request))->groupBy('date'))->toArray();
    }

    private function emptyWeek($requestedDate)
    {
        /** @var Carbon $monday */
        $monday = ($this->collection->first()->date ?? Carbon::parse($requestedDate))->startOfWeek();

        return collect(CarbonPeriod::between($monday, $monday->clone()->endOfWeek(Carbon::FRIDAY)))
            ->mapWithKeys(fn($date) => [$date->toDateString() => collect()]);
    }
}
