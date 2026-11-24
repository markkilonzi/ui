@extends('docs::layout')

@section('page', 'Payment prompt')
@section('lead', 'One screen for following an M-Pesa payment your app started: live status, a countdown, and how to pay by hand whenever the push does not succeed.')

@section('content')
    <x-docs::section id="basic" title="Use the component">
        <p>Never build your own payment screen. Your app starts the push in whatever way suits the page: a plain request, Livewire or ajax. When the response is ok, add <code>&lt;x-nyumbani::payment-prompt&gt;</code> to the page with the contract it returned. The prompt waits for the status the server broadcasts and shows how to pay by hand whenever the push does not succeed. On the server it pairs with <code>StkPushManager</code> from <code>nyumbani/daraja</code>, which you inject (there is no facade).</p>
        <x-docs::code lang="blade">
            @verbatim
            <x-nyumbani::payment-prompt
                :attempt="$attempt"
                :check-url="route('invoices.pay.check', ['invoice' => $invoice])"
                :redirect="route('invoices.show', ['invoice' => $invoice])"
            />
            @endverbatim
        </x-docs::code>
        <x-docs::table :columns="['Prop', 'What it does']" :rows="[
            ['attempt', 'Required. The contract $stkPush->contract() returns: the id, the channel, the status, the number prompted, how long to wait and how to pay by hand.'],
            ['check-url', 'Receives the id in a JSON POST and answers with the contract again. It backs the I have paid button and catches up on a status that changed before the page subscribed. Without it there is no button.'],
            ['pushed', 'True by default. Pass false for a payment nobody was prompted for, made with $stkPush->track(): the prompt then shows how to pay and waits for the payment.'],
            ['redirect', 'Where to go about a second after a confirmed payment. The component also dispatches payment-confirmed.'],
            ['timeout', 'Seconds before an unanswered push is treated as timed out. It defaults to the timeout in the contract, which nyumbani/daraja sets from DARAJA_STK_TIMEOUT (120). Pass it to override one page.'],
            ['confirm-timeout', 'Seconds a paid push may wait for its confirmation before the prompt says the payment arrived and must not be repeated (30).'],
        ]" />
        <x-docs::callout type="note" title="It never starts a push, and never retries">
            The prompt has no start button, no initiate URL and no Try again button. After a failure the payer pays by hand, or your page starts a new push and renders a new prompt. Keeping that in the app means a modal stays open, and the amount and the payer are always decided on the server.
        </x-docs::callout>
    </x-docs::section>

    <x-docs::section id="states" title="What the payer sees">
        <p>The waiting, paid and confirmed states share one screen, so a confirmed payment feels like the next beat of the one before: the eight dots turn into a tick in the same place and the heading changes. Everything uses the theme's primary colour.</p>
        <x-docs::table :columns="['Status', 'Shown']" :rows="[
            ['pending', 'The heading Watch out for an MPESA PIN prompt on your phone!, the eight turning dots, the number prompted and the amount that will be deducted in bold, a bar and a countdown in seconds to when the manual instructions show, and a note to keep the page open.'],
            ['paid', 'Payment received, then Payment received. We are confirming it... After confirm-timeout seconds it says the payment arrived and must not be repeated. The manual instructions are never shown.'],
            ['confirmed', 'Payment confirmed and Thank you! Your payment has been confirmed., with a tick in place of the dots, then the redirect.'],
            ['timed-out', 'The reason it got no response, and how to pay by hand, with no heading.'],
            ['failed', 'Payment failed, with the reason (cancelled, not enough balance, wrong PIN, timed out, could not reach the phone), and how to pay by hand.'],
            ['manual', 'Pay with M-Pesa, with how to pay by hand, for a payment nobody was prompted for.'],
        ]" />
    </x-docs::section>

    <x-docs::section id="manual" title="Pay by hand">
        <p>After a failure, or for a payment nobody was prompted for, the prompt shows the short way to pay: open M-PESA and choose Pay Bill, then the business number, the account number and the amount in one small card. Tap a value to copy it. A line says <strong>Already paid? Do not pay again</strong>, and the I have paid, check button asks your check URL. A small pulsing dot says the prompt is still listening, so a payment made by hand moves the screen on to the confirmation by itself. If the check finds nothing, a notice at the top says so.</p>
        <x-docs::callout type="tip" title="Copying works without https">
            The browser only lets a secure page use its clipboard API, so on plain http the prompt copies through a hidden field inside itself, which also works inside a modal. Either way a toast says Copied to clipboard, or that it could not copy.
        </x-docs::callout>
    </x-docs::section>

    <x-docs::section id="modal" title="Inside a modal">
        <p>The prompt sits in <code>&lt;x-nyumbani::modal&gt;</code> like any content. Pad the body with <code>p-4</code>, and open the modal by event as the modal page describes.</p>
        <x-docs::code lang="blade">
            @verbatim
            <x-nyumbani::modal name="pay" title="Pay with M-Pesa" size="sm">
                <div class="p-4">
                    <x-nyumbani::payment-prompt :attempt="$attempt" :check-url="route('invoices.pay.check', ['invoice' => $invoice])" />
                </div>
            </x-nyumbani::modal>
            @endverbatim
        </x-docs::code>
    </x-docs::section>

    <x-docs::section id="events" title="Events and the redirect">
        <x-docs::table :columns="['What', 'When']" :rows="[
            ['payment-confirmed', 'A browser event dispatched from the prompt, with the attempt id in detail.id, when the payment is confirmed. It is for the screen only: fulfil orders on the server, from the C2B payment you recorded.'],
            ['redirect', 'The prop moves the browser on about a second later, so the payer sees the confirmation first.'],
        ]" />
    </x-docs::section>

    <x-docs::section id="reverb" title="Reverb">
        <p>The prompt has no fallback to polling, so it needs a websocket server and throws a <code>LogicException</code> without one. It reads <code>config('ui.broadcasting')</code>, which defaults to <code>REVERB_APP_KEY</code>, <code>REVERB_HOST</code>, <code>REVERB_PORT</code> and <code>REVERB_SCHEME</code>. Set <code>UI_ECHO_KEY</code>, <code>UI_ECHO_HOST</code>, <code>UI_ECHO_PORT</code> and <code>UI_ECHO_SCHEME</code> only when the browser reaches Reverb differently from the server, such as behind a proxy. Run <code>php artisan reverb:start</code> (add <code>--debug</code> to see each connection and event) and a queue worker, since the broadcast is queued. Echo and Pusher are bundled with the UI package and load only when a prompt is on the page.</p>
        <x-docs::callout type="warning" title="The channel is public">
            The server makes the UUID the channel is named after, and the broadcast carries only a status, never a phone or a receipt. Fulfil orders on the server, from the C2B payment you recorded, never on the browser's <code>payment-confirmed</code>.
        </x-docs::callout>
    </x-docs::section>

    <x-docs::section id="stuck" title="When the screen is stuck">
        <x-docs::table :columns="['You see', 'Because', 'Do']" :rows="[
            ['We lost the connection', 'The browser could not subscribe to Reverb within 8 seconds.', 'Start Reverb and check the REVERB_* or UI_ECHO_* values. curl http://localhost:8080/up answers 200 when it runs.'],
            ['Waiting never moves on', 'The STK result never reached the app: Safaricom cannot reach a local machine.', 'Use a tunnel, or DARAJA_STK_SIMULATE with the stk-simulator buttons.'],
            ['Confirmed at once, with no payment', 'A resolver confirmed the attempt from an old payment.', 'Make the resolver check the amount and that the payment is newer than the attempt.'],
            ['No countdown or wrong length', 'The contract carries the timeout it was built with.', 'Set DARAJA_STK_TIMEOUT and start a new push, or pass :timeout.'],
            ['Copy does nothing', 'The browser refused the copy.', 'The value can still be typed in. The toast says so.'],
        ]" />
    </x-docs::section>
@endsection
