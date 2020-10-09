<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Carbon;

class SocialMobWeek extends ResourceCollection
{
    public $collects = SocialMob::class;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->emptyWeek()->merge(collect(parent::toArray($request))->groupBy('date'));
    }

    private function emptyWeek()
    {

        /** @var Carbon $firstSessionDate */
        $firstSessionDate = $this->collection->first()->date;

        $monday = $firstSessionDate->clone()->subDays(Carbon::MONDAY - $firstSessionDate->dayOfWeek);

        return collect(CarbonPeriod::between($monday, $monday->clone()->addDays(4)))
            ->mapWithKeys(fn ($date) => [$date->toDateString() => collect()]);

    }
}
