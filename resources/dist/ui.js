import "./toastr-Bct8UAj8.js";
import "./fancybox-DByp6PhQ.js";
import "./shell.js";
import "./submit.js";
import "./number-input.js";
import "./file-input.js";
import "./modal-DQKQ_V_m.js";
import "./period-picker-C4L1VkJQ.js";
import "./payment-prompt.js";
// resources/js/stk-simulator.js
window.__ = (e, t = {}) => {
	let n = window.__lang && window.__lang[e] || e;
	for (let [e, r] of Object.entries(t)) n = n.replace(`:${e}`, r);
	return n;
}, document.addEventListener("alpine:init", () => {
	window.Alpine.data("stkSimulator", (e) => ({
		attemptId: e,
		busy: !1,
		result: null,
		error: null,
		async simulate(e, t) {
			this.busy = !0, this.error = null;
			let n = document.querySelector("meta[name=\"csrf-token\"]")?.content ?? "", r = await fetch(e, {
				method: "POST",
				headers: {
					"Content-Type": "application/json",
					Accept: "application/json",
					"X-CSRF-TOKEN": n
				},
				body: JSON.stringify({
					id: this.attemptId,
					outcome: t
				})
			}), i = await r.json().catch(() => ({}));
			this.result = r.ok ? i : null, this.error = r.ok ? null : i.message ?? window.__("The simulation failed."), this.busy = !1;
		}
	}));
});
