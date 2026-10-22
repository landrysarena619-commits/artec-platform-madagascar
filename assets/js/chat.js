// ============================================================
// ARTEC Madagascar — Messagerie instantanée (polling AJAX)
// ============================================================

class ArtectChat {
    constructor(opts) {
        this.apiSend      = opts.apiSend;
        this.apiGet       = opts.apiGet;
        this.container    = document.getElementById(opts.container);
        this.form         = document.getElementById(opts.form);
        this.input        = document.getElementById(opts.input);
        this.params       = opts.params;          // objet de query params (publication_id, operateur_id, visitor_token si opérateur)
        this.csrfToken    = opts.csrfToken;
        this.pollInterval = opts.pollInterval || 3000;
        this.lastId       = 0;
        this.pollTimer    = null;
        this.bind();
        this.poll();
        this.pollTimer = setInterval(() => this.poll(), this.pollInterval);
    }

    bind() {
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.send();
        });
    }

    async send() {
        const text = this.input.value.trim();
        if (!text) return;

        const fd = new FormData();
        fd.append('csrf_token', this.csrfToken);
        fd.append('contenu', text);
        for (const [k, v] of Object.entries(this.params)) {
            fd.append(k, v);
        }

        this.input.value = '';
        this.input.disabled = true;

        try {
            const res = await fetch(this.apiSend, { method: 'POST', body: fd });
            const data = await res.json();
            if (!data.success) {
                this.appendSystem(data.message || 'Erreur.');
            }
            await this.poll();
        } catch (err) {
            this.appendSystem('Erreur réseau. Réessayez.');
        } finally {
            this.input.disabled = false;
            this.input.focus();
        }
    }

    async poll() {
        const q = new URLSearchParams({ since_id: this.lastId, ...this.params });
        try {
            const res = await fetch(this.apiGet + '?' + q.toString());
            const data = await res.json();
            if (!data.success) return;

            if (data.messages.length > 0) {
                for (const m of data.messages) {
                    this.append(m);
                    this.lastId = Math.max(this.lastId, m.id);
                }
                this.scrollToBottom();
            }
        } catch (err) {
            // silencieux
        }
    }

    append(m) {
        const div = document.createElement('div');
        div.className = 'chat-message ' + (m.mine ? 'msg-me' : 'msg-other');
        div.innerHTML = this.escape(m.contenu) + '<span class="msg-time">' + this.escape(m.time) + '</span>';
        this.container.appendChild(div);
    }

    appendSystem(text) {
        const div = document.createElement('div');
        div.className = 'text-center text-muted small my-2';
        div.textContent = text;
        this.container.appendChild(div);
        this.scrollToBottom();
    }

    scrollToBottom() {
        this.container.scrollTop = this.container.scrollHeight;
    }

    escape(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML.replace(/\n/g, '<br>');
    }

    destroy() {
        if (this.pollTimer) clearInterval(this.pollTimer);
    }
}