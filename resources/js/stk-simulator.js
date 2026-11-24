/**
 * Register the `stkSimulator` Alpine component, whose buttons ask the app to post a simulated Safaricom
 * callback to its own hooks and show what was sent and what the hook answered.
 */

//  Wait for Alpine to start, so the component can be registered on it
document.addEventListener('alpine:init', () => {
    /**
     * Define the component, which the panel starts with the id of the attempt it answers for
     */
    window.Alpine.data('stkSimulator', (attemptId) => ({
        attemptId,
        busy: false,
        result: null,
        error: null,

        /**
         * Post an outcome to a simulation route and keep the report
         */
        async simulate(url, outcome) {
            //  Start from a clean slate: lock the buttons
            this.busy = true;

            //  Forget any error
            this.error = null;

            //  Get the csrf token
            const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            //  Ask the app to post the simulated callback to its own hook
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({ id: this.attemptId, outcome }),
            });

            //  Read the answer, which is an empty object when it was not JSON
            const body = await response.json().catch(() => ({}));

            //  Keep the report when it worked, and drop the old one when it did not
            this.result = response.ok ? body : null;

            //  Keep the reason it was refused, or a plain message when the answer gave none
            this.error = response.ok ? null : (body.message ?? window.__('The simulation failed.'));

            //  Unlock the buttons
            this.busy = false;
        },
    }));
});
