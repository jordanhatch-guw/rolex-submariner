<?php

use Livewire\Component;

new class extends Component {
	// Folder under public/ holding dial.png, hour.png, minute.png, second.png
	public string $folder = 'images/watch';

	// Which city's time the watch shows (changed by the dropdown below)
	public string $timezone = 'America/Phoenix';

	// 'sweep' = smooth second hand, 'tick' = jumps once per second
	public string $mode = 'sweep';

	// Largest the watch will display (any CSS size: '400px', '28rem', ...). It shrinks on small screens.
	public string $size = '28rem';

	// Hand size: 1 = as generated, 0.8 = 80%, etc.
	public float $handScale = 1.0;

	// Optional per-hand sizes; any left empty use $handScale
	public ?float $hourScale = null;
	public ?float $minuteScale = null;
	public ?float $secondScale = null;

	public function zones(): array
	{
		return [
			'America/Phoenix' => 'Phoenix',
			'America/Los_Angeles' => 'Los Angeles',
			'America/Denver' => 'Denver',
			'America/Chicago' => 'Chicago',
			'America/New_York' => 'New York',
			'Europe/London' => 'London',
			'Europe/Zurich' => 'Geneva',
			'Asia/Tokyo' => 'Tokyo',
		];
	}

	// Runs on the server whenever the dropdown changes $timezone
	public function updatedTimezone(string $value): void
	{
		if (! array_key_exists($value, $this->zones())) {
			$this->timezone = 'America/Phoenix';
		}
	}

	public function scaleFor(string $hand): float
	{
		return $this->{$hand.'Scale'} ?? $this->handScale;
	}
}; ?>

<div style="display: flex; flex-direction: column; align-items: center; gap: 1rem; width: 100%; max-width: {{ $size }}; margin-inline: auto;">
    {{-- The face: Alpine moves the hands in the browser; wire:ignore stops Livewire re-rendering it.
         Layout uses inline styles so the watch holds together even if Tailwind hasn't rebuilt. --}}
    <div
            wire:ignore
            style="position: relative; width: 100%; aspect-ratio: 1 / 1;"
            x-data="{
            tz: $wire.entangle('timezone'),
            sweep: @js($mode !== 'tick'),
            hour: 0, minute: 0, second: 0,
            init() {
                const tick = () => {
                    const now = new Date();
                    const t = Object.fromEntries(
                        new Intl.DateTimeFormat('en-US', {
                            timeZone: this.tz, hour12: false,
                            hour: 'numeric', minute: 'numeric', second: 'numeric',
                        }).formatToParts(now).map(p => [p.type, Number(p.value)])
                    );
                    const s = t.second + (this.sweep ? now.getMilliseconds() / 1000 : 0);
                    const m = t.minute + s / 60;
                    const h = (t.hour % 12) + m / 60;
                    this.second = s * 6;
                    this.minute = m * 6;
                    this.hour = h * 30;
                    requestAnimationFrame(tick);
                };
                tick();
            },
        }"
    >
        <img src="{{ asset($folder.'/dial.png') }}" alt="Watch dial" style="position: absolute; inset: 0; width: 100%; height: 100%;">

        @foreach (['hour', 'minute', 'second'] as $hand)
            <img
                    src="{{ asset("{$folder}/{$hand}.png") }}"
                    alt=""
                    style="position: absolute; inset: 0; width: 100%; height: 100%;"
                    :style="{ transform: `rotate(${ {{ $hand }} }deg) scale({{ $this->scaleFor($hand) }})` }"
            >
        @endforeach
    </div>

    {{-- The server side: changing this updates $timezone in PHP, and the watch follows --}}
    <select wire:model.live="timezone" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
        @foreach ($this->zones() as $zone => $city)
            <option value="{{ $zone }}">{{ $city }}</option>
        @endforeach
    </select>

    <p class="text-sm text-zinc-500">Showing the time in {{ $this->zones()[$timezone] }}</p>
</div>
