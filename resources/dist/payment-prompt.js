import { a as e } from "./rolldown-runtime-DUcC5eAW.js";
// resources/js/payment-prompt.js
var t = null, n = async (e, t) => {
	if (window.isSecureContext && navigator.clipboard) {
		await navigator.clipboard.writeText(e);
		return;
	}
	let n = document.createElement("textarea");
	n.value = e, n.setAttribute("readonly", ""), n.style.position = "fixed", n.style.opacity = "0", t.appendChild(n), n.select();
	let r = document.execCommand("copy");
	if (t.removeChild(n), !r) throw Error("copy");
}, r = (n) => window.Echo ? Promise.resolve(window.Echo) : (t ??= Promise.all([import("./echo-BUCAHA_f.js"), import("./pusher-DclBfsad.js").then((t) => e(t.default, 1))]).then(([e, t]) => (window.Pusher = t.default, window.Echo = new e.default({
	broadcaster: "reverb",
	key: n.key,
	wsHost: n.host,
	wsPort: n.port,
	wssPort: n.port,
	forceTLS: n.scheme === "https",
	enabledTransports: ["ws", "wss"]
}), window.Echo)), t);
document.addEventListener("alpine:init", () => {
	Alpine.data("paymentPrompt", (e) => {
		let t, i = null, a = {}, o = !1;
		return {
			prompt: {
				status: e.attempt.status === "pending" && !e.pushed ? "manual" : e.attempt.status,
				reason: e.attempt.reason,
				id: e.attempt.id,
				manual: e.attempt.manual,
				checking: !1,
				notReceived: !1,
				slow: !1,
				remaining: e.timeout,
				copied: null,
				get showsManual() {
					return [
						"failed",
						"timed-out",
						"manual"
					].includes(this.status) && !!this.manual;
				},
				get message() {
					return this.status === "failed" || this.status === "timed-out" ? e.texts.reasons[this.reason] ?? e.texts.reasons.other : this.slow ? e.texts.slow : e.texts.statuses[this.status] ?? "";
				},
				get countdown() {
					let t = `${this.remaining} ${e.texts.seconds[this.remaining === 1 ? 0 : 1]}`;
					return e.texts.waiting.replace(":time", t);
				},
				get title() {
					return e.texts.titles[this.status] ?? "";
				},
				get progress() {
					return Math.max(0, Math.min(100, this.remaining / e.timeout * 100));
				},
				get amount() {
					return this.manual && window.money ? window.money(this.manual.amount) : this.manual?.amount;
				},
				setup() {
					this.apply(e.attempt, !0), this.status !== "confirmed" && (e.mode === "poll" ? this.poll() : this.listen().catch(() => this.fail("connection")));
				},
				async poll() {
					let t = Date.now() + e.duration * 1e3;
					for (; !o && this.status !== "confirmed" && Date.now() < t;) await new Promise((t) => setTimeout(t, e.interval * 1e3)), document.hidden || await this.catchUp();
				},
				async listen() {
					i = (await r(e.echo)).channel(e.attempt.channel).listen(".status.changed", (e) => this.apply(e)), await new Promise((e, t) => {
						let n = setTimeout(() => t(Error("subscription")), 8e3);
						i.subscribed(() => {
							clearTimeout(n), e();
						});
					}), await this.catchUp();
				},
				async catchUp() {
					if (e.checkUrl) try {
						let t = await this.send(e.checkUrl, { id: this.id }), n = t.ok ? await t.json() : null, r = [
							"manual",
							"timed-out",
							"failed"
						].includes(this.status);
						n && n.status !== this.status && (!r || n.status === "confirmed") && this.apply(n);
					} catch {}
				},
				apply(n, r = !1) {
					n.manual && typeof n.manual == "object" && (this.manual = n.manual), this.status = r && this.status === "manual" ? "manual" : n.status, this.reason = n.reason ?? null, this.slow = !1, this.notReceived = !1, this.clock(), this.status === "confirmed" && (t.dispatchEvent(new CustomEvent("payment-confirmed", {
						bubbles: !0,
						detail: { id: this.id }
					})), e.redirect && setTimeout(() => window.location.href = e.redirect, 1500));
				},
				clock() {
					Object.values(a).forEach(clearTimeout), this.status === "pending" && (this.remaining = e.timeout, a.pending = setInterval(() => {
						this.remaining--, this.remaining <= 0 && this.apply({
							status: "timed-out",
							reason: "no-response"
						});
					}, 1e3)), this.status === "paid" && (a.paid = setTimeout(() => this.slow = !0, e.confirmTimeout * 1e3));
				},
				fail(e) {
					this.status !== "manual" && (this.status = "failed", this.reason = e);
				},
				async check() {
					if (!this.checking && e.checkUrl) {
						this.checking = !0, this.notReceived = !1;
						try {
							let t = await this.send(e.checkUrl, { id: this.id }), n = t.ok ? await t.json() : null;
							n && n.status === "confirmed" ? this.apply(n) : this.notReceived = !0;
						} catch {
							this.notReceived = !0;
						} finally {
							this.checking = !1;
						}
					}
				},
				async copy(r, i) {
					try {
						await n(String(i), t), this.copied = r, setTimeout(() => this.copied = null, 1500), window.toastr?.success(e.texts.copiedToast);
					} catch {
						window.toastr?.error(e.texts.copyFailed);
					}
				},
				send(e, t) {
					return fetch(e, {
						method: "POST",
						headers: {
							"Content-Type": "application/json",
							Accept: "application/json",
							"X-Requested-With": "XMLHttpRequest",
							"X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]")?.content ?? ""
						},
						body: JSON.stringify(t)
					});
				}
			},
			destroy() {
				o = !0;
			},
			init() {
				t = this.$el, this.prompt.setup();
			}
		};
	});
});
