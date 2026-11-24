<div x-data="paymentPrompt(@js($settings))" {{ $attributes->class('w-full px-2 text-center') }}>
    {{--  One screen from the PIN prompt to the confirmed payment: the dots turn into a tick in the same place, so it reads as one continuous flow  --}}
    <div x-show="['pending', 'paid', 'confirmed'].includes(prompt.status)" x-cloak role="status" aria-live="polite">
        <h3 class="mb-4 text-xl text-primary" x-text="prompt.title"></h3>

        <div class="relative mx-auto mb-8 flex h-28 w-28 items-center justify-center text-primary">
            {{--  Eight dots that turn in steps, with a soft pulse behind them like a notification arriving  --}}
            <div x-show="prompt.status !== 'confirmed'" x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-75 opacity-0" class="absolute inset-0 flex items-center justify-center">
                <span class="absolute inset-2 rounded-full bg-current opacity-10 motion-safe:animate-ping" aria-hidden="true"></span>
                <span class="absolute inset-4 rounded-full bg-current opacity-5" aria-hidden="true"></span>
                <svg viewBox="0 0 100 100" class="relative h-24 w-24 motion-safe:animate-[spin_1s_steps(8,end)_infinite]" fill="currentColor" aria-hidden="true">
                    @foreach (range(0, 7) as $dot)
                        <circle cx="{{ round(50 + 38 * sin($dot * M_PI / 4), 2) }}" cy="{{ round(50 - 38 * cos($dot * M_PI / 4), 2) }}" r="{{ 4 + $dot * 0.75 }}" />
                    @endforeach
                </svg>
            </div>

            {{--  The tick that takes the dots' place once the payment is confirmed  --}}
            <div x-show="prompt.status === 'confirmed'" x-cloak x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="scale-50 opacity-0" x-transition:enter-end="scale-100 opacity-100" class="absolute inset-0 flex items-center justify-center">
                <span class="absolute inset-2 rounded-full bg-current opacity-10" aria-hidden="true"></span>
                <svg xmlns="http://www.w3.org/2000/svg" class="relative h-24 w-24" fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
        </div>

        <p class="mb-3 text-gray-700" x-show="prompt.status === 'pending'">{!! $pendingMessage !!}</p>
        <p class="mb-3 text-gray-700" x-show="prompt.status !== 'pending'" x-text="prompt.message"></p>

        {{--  How long until the manual instructions show, as a bar and in words  --}}
        <div x-show="prompt.status === 'pending'" class="mx-auto max-w-xs">
            <div class="mb-2 h-1 overflow-hidden rounded-full bg-gray-200" aria-hidden="true">
                <div class="h-full rounded-full bg-primary transition-[width] duration-1000 ease-linear" x-bind:style="`width: ${prompt.progress}%`"></div>
            </div>
        </div>
        <p class="text-primary" x-show="prompt.status === 'pending'" x-text="prompt.countdown"></p>

        <p class="mt-4 text-xs text-gray-400" x-show="prompt.status !== 'confirmed'">{{ __('Please keep this page open until the payment is done.') }}</p>
    </div>

    {{--  A failed push, or a payment nobody was prompted for: why, and the way to pay by hand  --}}
    <div x-show="['failed', 'timed-out', 'manual'].includes(prompt.status)" x-cloak>
        {{--  Nothing found yet, after the payer asked  --}}
        <p class="mb-4 rounded-panel border border-primary/30 bg-primary/10 p-2 text-sm text-primary" x-show="prompt.notReceived" x-cloak role="status">{{ __('We have not received the payment yet. Please wait a moment and check again.') }}</p>

        <h3 class="mb-4 text-xl text-primary" x-show="prompt.title" x-text="prompt.title"></h3>

        {{--  Why it did not go through  --}}
        <p class="mb-4 text-gray-700" x-text="prompt.message" x-bind:role="prompt.status === 'manual' ? 'status' : 'alert'"></p>

        <template x-if="prompt.showsManual">
            <div class="mb-4 text-left">
                <p class="mb-2 text-center text-sm text-gray-600">{{ __('Open M-PESA, choose Pay Bill, then enter:') }}</p>

                {{--  The three things to type in, tap one to copy it  --}}
                <dl class="divide-y divide-gray-100 overflow-hidden rounded-panel border border-gray-200 bg-white">
                    @foreach ([
                        ['name' => 'paybill', 'label' => __('Business number'), 'value' => 'prompt.manual.paybill', 'display' => 'prompt.manual.paybill'],
                        ['name' => 'account', 'label' => __('Account number'), 'value' => 'prompt.manual.account', 'display' => 'prompt.manual.account'],
                        ['name' => 'amount', 'label' => __('Amount'), 'value' => 'prompt.manual.amount', 'display' => 'prompt.amount'],
                    ] as $detail)
                        <div class="flex items-center justify-between gap-3 px-3 py-2.5">
                            <dt class="text-sm text-gray-500">{{ $detail['label'] }}</dt>
                            <dd>
                                <button type="button" class="inline-flex items-center gap-2 font-mono text-base font-semibold text-gray-900 hover:text-primary" x-on:click="prompt.copy('{{ $detail['name'] }}', {{ $detail['value'] }})" title="{{ $settings['texts']['copy'] }}">
                                    <span x-text="{{ $detail['display'] }}"></span>
                                    <svg x-show="prompt.copied !== '{{ $detail['name'] }}'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                                    </svg>
                                    <svg x-show="prompt.copied === '{{ $detail['name'] }}'" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </button>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </template>

        @if ($checkUrl)
            <button type="button" class="btn btn-lg btn-primary w-full" x-show="prompt.showsManual" x-bind:disabled="prompt.checking" x-on:click="prompt.check()" x-text="prompt.checking ? @js($settings['texts']['checking']) : @js(__('I have paid, check'))"></button>

            <p class="mt-3 text-xs text-gray-500" x-show="prompt.showsManual">{{ __('Already paid?') }} <strong class="text-gray-700">{{ __('Do not pay again') }}</strong>, {{ __('tap the button above.') }}</p>
        @endif

        {{--  Still listening, so a payment made by hand moves this screen on to the confirmation by itself  --}}
        <p class="mt-3 flex items-center justify-center gap-2 text-xs text-gray-500" x-show="prompt.showsManual">
            <span class="relative flex h-2 w-2" aria-hidden="true">
                <span class="absolute inline-flex h-full w-full rounded-full bg-primary opacity-60 motion-safe:animate-ping"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-primary"></span>
            </span>
            {{ __('We will confirm your payment automatically once we receive it.') }}
        </p>
    </div>
</div>
