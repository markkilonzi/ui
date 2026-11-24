{{--  Buttons that answer for Safaricom while pushes are simulated: each makes the server post to the app's own hooks  --}}
<div x-data="stkSimulator(@js($attempt['id']))" {{ $attributes->class('space-y-3 text-sm') }}>
    <p class="text-xs text-secondary">{{ __('Each button makes the server post to the hooks of this app, as Safaricom would.') }}</p>

    <div>
        <p class="mb-1 text-xs font-medium">{{ __('STK result') }}</p>
        <div class="flex flex-wrap gap-1.5">
            @foreach (['paid' => __('Paid'), 'cancelled' => __('Cancelled'), 'insufficient-funds' => __('No funds'), 'wrong-pin' => __('Wrong PIN'), 'timeout' => __('Timeout')] as $outcome => $label)
                <button type="button" class="btn btn-sm btn-primary" :disabled="busy" x-on:click="simulate(@js($stkUrl), @js($outcome))">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <div>
        <p class="mb-1 text-xs font-medium">{{ __('C2B confirmation, the real proof of payment') }}</p>
        <div class="flex flex-wrap gap-1.5">
            @foreach (['stk' => __('For the STK payment'), 'manual' => __('Paid by hand'), 'short' => __('Paid by hand, KES 1 short')] as $outcome => $label)
                <button type="button" class="btn btn-sm btn-primary" :disabled="busy" x-on:click="simulate(@js($confirmationUrl), @js($outcome))">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <p x-show="error" x-cloak x-text="error" class="text-sm text-danger"></p>

    <template x-if="result">
        <div class="space-y-2 text-xs">
            <p>{{ __('Posted to') }} <code x-text="result.url"></code>, {{ __('answered') }} <b x-text="result.status"></b></p>
            <p class="font-medium">{{ __('Attempt now') }}</p>
            <pre class="overflow-x-auto rounded bg-gray-100 p-2" x-text="JSON.stringify(result.attempt, null, 2)"></pre>
            <p class="font-medium">{{ __('Payload sent') }}</p>
            <pre class="max-h-64 overflow-auto rounded bg-gray-100 p-2" x-text="JSON.stringify(result.sent, null, 2)"></pre>
        </div>
    </template>
</div>
