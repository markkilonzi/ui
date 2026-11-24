/**
 * Register the `paymentPrompt` Alpine component used by `<x-nyumbani::payment-prompt>`: it follows an M-Pesa payment
 * the app already started, waits for the status the server broadcasts on a public channel, and shows how to pay by
 * hand whenever the push does not succeed. Laravel Echo is loaded only when a prompt is on the page.
 */

/**
 * The Echo connection, made once for the page
 */
let connection = null;

/**
 * Copy text to the clipboard, also on a page served without https, where the clipboard API is not allowed
 */
const copyText = async (text, container) => {
    //  Use the clipboard API where the page is secure
    if (window.isSecureContext && navigator.clipboard) {
        await navigator.clipboard.writeText(text);

        return;
    }

    //  Otherwise select the text in a hidden field and copy it, inside the prompt so a modal's focus trap allows it
    const field = document.createElement('textarea');
    field.value = text;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.opacity = '0';
    container.appendChild(field);
    field.select();

    //  Copy, and clean up whatever the result
    const copied = document.execCommand('copy');
    container.removeChild(field);

    //  Tell the caller when the browser refused
    if (!copied) throw new Error('copy');
};

/**
 * Get the Echo connection, reusing the app's own window.Echo when it made one
 */
const connect = (settings) => {
    //  Reuse the connection the app or an earlier prompt made
    if (window.Echo) return Promise.resolve(window.Echo);

    //  Load Echo and Pusher only now, so a page without a payment never downloads them
    connection ??= Promise
        .all([import('laravel-echo'), import('pusher-js')])
        .then(([echo, pusher]) => {
            //  Echo reads Pusher from the window
            window.Pusher = pusher.default;

            //  Connect to the Reverb server
            window.Echo = new echo.default({
                //  Talk to Reverb, which speaks the Pusher protocol
                broadcaster: 'reverb',
                //  The public key of the Reverb app
                key: settings.key,
                //  The host and port the browser reaches Reverb on
                wsHost: settings.host,
                wsPort: settings.port,
                wssPort: settings.port,
                //  Use a secure socket only when the page is served over https
                forceTLS: settings.scheme === 'https',
                //  Allow plain and secure websockets, and no fallback transport
                enabledTransports: ['ws', 'wss'],
            });

            //  Return the connection
            return window.Echo;
        });

    //  Hand back the same connection to every prompt
    return connection;
};

/**
 * Add an alpine:init event listener to the document
 */
document.addEventListener('alpine:init', () => {
    /**
     * Get the state of a payment prompt from the attempt the app started
     *
     * The status is the one the server broadcasts (pending, paid, failed, timed-out or confirmed), plus manual for a
     * payment nobody was prompted for. Everything lives under one key, prompt, so the page's own x-data never clashes.
     */
    Alpine.data('paymentPrompt', (config) => {
        //  The element the prompt sits on, which announces the payment
        let root;

        //  The channel being listened to, kept out of the reactive state so Alpine never wraps it
        let channel = null;

        //  The timers of the waiting and confirming steps
        const timers = {};

        //  Return the reactive state
        return {
            prompt: {
                //  Start as manual when nobody was prompted, else with the status the app gave
                status: config.attempt.status === 'pending' && !config.pushed
                    ? 'manual'
                    : config.attempt.status,
                //  Why the attempt failed, when it did
                reason: config.attempt.reason,
                //  The id of the attempt, which the check url is asked about
                id: config.attempt.id,
                //  How to pay by hand: paybill, account and amount
                manual: config.attempt.manual,
                //  True while the check button is asking the app
                checking: false,
                //  True when the check found no payment yet
                notReceived: false,
                //  True when a paid push is slow to be confirmed
                slow: false,
                //  The seconds left before an unanswered push is shown as timed out
                remaining: config.timeout,
                //  The detail that was copied last, which its button says so for a moment
                copied: null,

                /**
                 * Check whether the prompt shows how to pay by hand
                 */
                get showsManual() {
                    //  Only after a failure or for a payment made by hand, and only when there is something to show
                    return ['failed', 'timed-out', 'manual'].includes(this.status) && !!this.manual;
                },

                /**
                 * Get the sentence that explains where the payment stands
                 */
                get message() {
                    //  A failure is explained by its reason
                    if (this.status === 'failed' || this.status === 'timed-out') {
                        //  Use the sentence of the reason, or the general one when it is not known
                        return config.texts.reasons[this.reason] ?? config.texts.reasons.other;
                    }

                    //  Anything else by its status, and a slow confirmation by its own sentence
                    return this.slow ? config.texts.slow : config.texts.statuses[this.status] ?? '';
                },

                /**
                 * Get the sentence that tells the payer how long to wait before the manual instructions show
                 */
                get countdown() {
                    //  Say the seconds left in words, with the singular for the last one
                    const seconds = `${this.remaining} ${config.texts.seconds[this.remaining === 1 ? 0 : 1]}`;

                    //  Put them in the sentence
                    return config.texts.waiting.replace(':time', seconds);
                },

                /**
                 * Get the heading that names where the payment stands
                 */
                get title() {
                    return config.texts.titles[this.status] ?? '';
                },

                /**
                 * Get how much of the waiting time is left, as a percentage for the progress bar
                 */
                get progress() {
                    return Math.max(0, Math.min(100, (this.remaining / config.timeout) * 100));
                },

                /**
                 * Get the amount in the format of the app, which the formatting helpers define on the window
                 */
                get amount() {
                    //  Format it with the money helper when the page has one, else show it as it is
                    return this.manual && window.money ? window.money(this.manual.amount) : this.manual?.amount;
                },

                /**
                 * Start following the attempt, which Alpine calls once when the prompt is created
                 */
                setup() {
                    //  Show the status the app started with, which also starts its timers
                    this.apply(config.attempt, true);

                    //  A confirmed payment has nothing left to hear
                    if (this.status === 'confirmed') return;

                    //  Listen for the result, showing the way to pay by hand when the server cannot be reached
                    this.listen().catch(() => this.fail('connection'));
                },

                /**
                 * Subscribe to the channel of the attempt and wait until the subscription holds, then catch up on what was missed
                 */
                async listen() {
                    //  Connect, which can fail when the server cannot be reached
                    const echo = await connect(config.echo);

                    //  Subscribe to the public channel and take every status change
                    channel = echo
                        .channel(config.attempt.channel)
                        .listen('.status.changed', (event) => this.apply(event));

                    //  Wait for the subscription, but not forever
                    await new Promise((resolve, reject) => {
                        //  Give up after 8 seconds
                        const giveUp = setTimeout(() => reject(new Error('subscription')), 8000);

                        //  Listen for the subscription to hold
                        channel.subscribed(() => {
                            //  Stop the countdown
                            clearTimeout(giveUp);

                            //  Carry on
                            resolve();
                        });
                    });

                    //  Catch up on a status that changed before the subscription held
                    await this.catchUp();
                },

                /**
                 * Ask the app where the attempt stands now, without telling the payer when nothing changed
                 */
                async catchUp() {
                    //  Without a check url there is nothing to ask
                    if (!config.checkUrl) return;

                    try {
                        //  Ask the app about this attempt
                        const response = await this.send(config.checkUrl, { id: this.id });

                        //  Read the answer, which is the attempt again
                        const attempt = response.ok ? await response.json() : null;

                        //  Take a status that moved on
                        if (attempt && attempt.status !== this.status && this.status !== 'manual') this.apply(attempt);
                    } catch {
                        //  The broadcast is still coming, so a failed catch-up is no failure
                    }
                },

                /**
                 * Take a status, from the server's broadcast or from the app, and show it
                 */
                apply(data, initial = false) {
                    //  Keep how to pay by hand when the data carries it: the broadcast's own manual is only a flag
                    if (data.manual && typeof data.manual === 'object') this.manual = data.manual;

                    //  Take the status, but keep manual on the first look for a payment nobody was prompted for
                    this.status = initial && this.status === 'manual' ? 'manual' : data.status;

                    //  Take the reason for a failure, or none
                    this.reason = data.reason ?? null;

                    //  A new status clears the note that confirming is slow
                    this.slow = false;

                    //  A new status clears the note that nothing was found
                    this.notReceived = false;

                    //  Start or stop the timers the new status needs
                    this.clock();

                    //  Tell the page a payment arrived, and move on when the app asked for it
                    if (this.status === 'confirmed') {
                        //  Announce it from the prompt's element, so the page can listen for it
                        root.dispatchEvent(new CustomEvent('payment-confirmed', { bubbles: true, detail: { id: this.id } }));

                        //  Leave for the redirect url after a moment, so the payer sees the success message
                        if (config.redirect) setTimeout(() => (window.location.href = config.redirect), 1500);
                    }
                },

                /**
                 * Run the timer of the current status: a push nobody answers times out here, and a payment that is slow to confirm says so
                 */
                clock() {
                    //  Stop the timers of the status that has passed, which clearTimeout does for a countdown too
                    Object.values(timers).forEach(clearTimeout);

                    //  A push that stays pending is treated as unanswered, which the server settles first in the normal case
                    if (this.status === 'pending') {
                        //  Start the countdown from the full timeout
                        this.remaining = config.timeout;

                        //  Count down every second
                        timers.pending = setInterval(() => {
                            //  One second less to wait
                            this.remaining--;

                            //  Out of time: show it as timed out, which also stops this countdown
                            if (this.remaining <= 0) this.apply({ status: 'timed-out', reason: 'no-response' });
                        }, 1000);
                    }

                    //  A paid push waits on the payment, which is no reason to pay again
                    if (this.status === 'paid') {
                        //  After the confirm timeout, say the payment arrived and is still being confirmed
                        timers.paid = setTimeout(() => (this.slow = true), config.confirmTimeout * 1000);
                    }
                },

                /**
                 * Show the way to pay by hand because the payment cannot be followed
                 */
                fail(reason) {
                    //  Show the failure, unless the attempt is only waiting on a payment made by hand
                    if (this.status !== 'manual') {
                        //  Show it as failed
                        this.status = 'failed';

                        //  With the reason it failed for
                        this.reason = reason;
                    }
                },

                /**
                 * Ask the app whether the payment arrived, for the payer who paid by hand
                 */
                async check() {
                    //  Do not let the button be pressed while it is asking
                    if (this.checking || !config.checkUrl) return;

                    //  Ask
                    this.checking = true;
                    this.notReceived = false;

                    try {
                        //  Ask the app about this attempt
                        const response = await this.send(config.checkUrl, { id: this.id });

                        //  Read the answer, which is the attempt again
                        const attempt = response.ok ? await response.json() : null;

                        //  A payment shows as confirmed, anything else as not received yet
                        if (attempt && attempt.status === 'confirmed') this.apply(attempt);
                        else this.notReceived = true;
                    } catch {
                        //  An answer that did not come means nothing was found
                        this.notReceived = true;
                    } finally {
                        //  Let the button be pressed again
                        this.checking = false;
                    }
                },

                /**
                 * Copy a detail of how to pay by hand to the clipboard, and say so for a moment
                 */
                async copy(name, value) {
                    try {
                        //  Put it on the clipboard
                        await copyText(String(value), root);

                        //  Say it was copied, then stop saying so
                        this.copied = name;
                        setTimeout(() => (this.copied = null), 1500);
                        window.toastr?.success(config.texts.copiedToast);
                    } catch {
                        //  Without clipboard access the payer can still read and type the detail
                        window.toastr?.error(config.texts.copyFailed);
                    }
                },

                /**
                 * Post JSON to the app with the CSRF token of the page
                 */
                send(url, body) {
                    return fetch(url, {
                        //  Always a post
                        method: 'POST',
                        headers: {
                            //  Send JSON and ask for JSON back
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            //  Mark it as an ajax request
                            'X-Requested-With': 'XMLHttpRequest',
                            //  Prove the request comes from the page, with the token in its meta tag
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        //  The data to send
                        body: JSON.stringify(body),
                    });
                },
            },

            /**
             * Keep the element the prompt sits on, then start following the attempt
             */
            init() {
                //  Keep the element it sits on
                root = this.$el;

                //  Follow the attempt
                this.prompt.setup();
            },
        };
    });
});
