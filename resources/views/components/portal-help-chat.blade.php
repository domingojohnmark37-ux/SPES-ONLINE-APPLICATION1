<style>
    .portal-help {
        position:fixed;
        right:20px;
        bottom:18px;
        z-index:300;
        font-family:inherit;
    }
    .portal-help-toggle {
        display:flex;
        align-items:center;
        gap:10px;
        padding:10px 16px 10px 10px;
        border:0;
        border-radius:999px;
        background:var(--primary);
        color:#fff;
        box-shadow:0 8px 24px rgba(102,0,0,.28);
        cursor:pointer;
        font:inherit;
        font-size:.85rem;
        font-weight:700;
    }
    .portal-help-toggle-icon {
        display:grid;
        place-items:center;
        width:38px;
        height:38px;
        border-radius:50%;
        background:#fff;
        color:var(--primary);
        font-size:1rem;
    }
    .portal-help-panel {
        position:absolute;
        right:0;
        bottom:62px;
        display:none;
        flex-direction:column;
        width:min(360px, calc(100vw - 28px));
        max-height:min(600px, calc(100dvh - 110px));
        overflow:hidden;
        border:1px solid rgba(139,0,0,.12);
        border-radius:14px;
        background:#fff;
        box-shadow:0 18px 50px rgba(17,24,39,.2);
    }
    .portal-help-panel[aria-hidden="false"] { display:flex; }
    .portal-help-header {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:15px 16px;
        background:var(--primary-dark);
        color:#fff;
    }
    .portal-help-title { font-size:.95rem; font-weight:800; }
    .portal-help-subtitle { margin-top:3px; color:rgba(255,255,255,.78); font-size:.75rem; }
    .portal-help-close {
        display:grid;
        place-items:center;
        flex:0 0 34px;
        width:34px;
        height:34px;
        border:1px solid rgba(255,255,255,.35);
        border-radius:8px;
        background:transparent;
        color:#fff;
        cursor:pointer;
    }
    .portal-help-thread {
        display:flex;
        flex:1;
        flex-direction:column;
        gap:10px;
        min-height:110px;
        max-height:190px;
        overflow-y:auto;
        padding:14px;
        background:#f7f8fa;
    }
    .portal-help-message {
        max-width:90%;
        padding:10px 12px;
        border-radius:12px;
        font-size:.82rem;
        line-height:1.45;
        overflow-wrap:anywhere;
    }
    .portal-help-message-bot {
        align-self:flex-start;
        border:1px solid #e5e7eb;
        border-bottom-left-radius:4px;
        background:#fff;
        color:#263238;
    }
    .portal-help-message-user {
        align-self:flex-end;
        border-bottom-right-radius:4px;
        background:#f8e8e8;
        color:var(--primary-dark);
    }
    .portal-help-questions {
        overflow-y:auto;
        padding:12px 14px 14px;
    }
    .portal-help-topic + .portal-help-topic { margin-top:12px; }
    .portal-help-topic-title {
        margin-bottom:7px;
        color:var(--text-muted);
        font-size:.68rem;
        font-weight:800;
        text-transform:uppercase;
    }
    .portal-help-question-list { display:flex; flex-wrap:wrap; gap:7px; }
    .portal-help-question {
        padding:7px 10px;
        border:1px solid #e3c5c5;
        border-radius:999px;
        background:#fff;
        color:var(--primary-dark);
        cursor:pointer;
        font:inherit;
        font-size:.74rem;
        line-height:1.25;
        text-align:left;
    }
    .portal-help-question:hover, .portal-help-question:focus-visible {
        border-color:var(--primary);
        background:#fff6f6;
        outline:none;
    }
    @media (max-width:767.98px) {
        .portal-help { right:12px; bottom:12px; }
        .portal-help-panel { bottom:58px; max-height:calc(100dvh - 88px); }
        .portal-help-thread { max-height:24dvh; }
    }
</style>

<aside class="portal-help" data-portal-help>
    <section class="portal-help-panel" id="portalHelpPanel" aria-label="SPES help topics" aria-hidden="true">
        <header class="portal-help-header">
            <div>
                <div class="portal-help-title">SPES Applicant Help</div>
                <div class="portal-help-subtitle">Choose a topic to see an answer</div>
            </div>
            <button class="portal-help-close" type="button" aria-label="Close help" data-help-close>
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </header>
        <div class="portal-help-thread" id="portalHelpThread" aria-live="polite" aria-relevant="additions">
            <div class="portal-help-message portal-help-message-bot">Hi! Pick a question below and I’ll show you the answer.</div>
        </div>
        <div class="portal-help-questions">
            <section class="portal-help-topic" aria-labelledby="helpTopicApplication">
                <h3 class="portal-help-topic-title" id="helpTopicApplication">Application</h3>
                <div class="portal-help-question-list">
                    <button class="portal-help-question" type="button" data-question="How do I apply?" data-answer="Choose Apply Now in the portal, complete the application form, attach the required documents, then submit it for review.">How do I apply?</button>
                    <button class="portal-help-question" type="button" data-question="How can I check my application status?" data-answer="Open My Application from the portal menu to review your current status and any feedback from the PESO officer.">Check application status</button>
                    <button class="portal-help-question" type="button" data-question="Can I edit my application?" data-answer="You can update and re-submit an application when it has been denied. Open My Application and choose the edit or reapply option shown there.">Edit or reapply</button>
                </div>
            </section>
            <section class="portal-help-topic" aria-labelledby="helpTopicDocuments">
                <h3 class="portal-help-topic-title" id="helpTopicDocuments">Documents</h3>
                <div class="portal-help-question-list">
                    <button class="portal-help-question" type="button" data-question="What documents should I prepare?" data-answer="Prepare your resume, application letter, and certificate of indigency. The application form shows which uploads are required for your submission.">Required documents</button>
                    <button class="portal-help-question" type="button" data-question="What file types and sizes are accepted?" data-answer="Upload PDF documents. Each file must be 5 MB or smaller.">File type and size</button>
                    <button class="portal-help-question" type="button" data-question="Where can I update my personal information?" data-answer="Open Edit Profile in the portal menu, update your details, and select Save Profile & Continue.">Update profile details</button>
                </div>
            </section>
            <section class="portal-help-topic" aria-labelledby="helpTopicAfterApproval">
                <h3 class="portal-help-topic-title" id="helpTopicAfterApproval">After approval</h3>
                <div class="portal-help-question-list">
                    <button class="portal-help-question" type="button" data-question="What happens after my application is approved?" data-answer="You’ll be notified when your application is approved. Open My Application to see the next post-approval forms and any instructions from PESO.">Next steps after approval</button>
                    <button class="portal-help-question" type="button" data-question="Where can I find announcements?" data-answer="Approved applicants can open Updates from the portal navigation to read published SPES announcements.">View announcements</button>
                </div>
            </section>
        </div>
    </section>
    <button class="portal-help-toggle" type="button" aria-expanded="false" aria-controls="portalHelpPanel" data-help-toggle>
        <span class="portal-help-toggle-icon"><i class="fa-solid fa-circle-question" aria-hidden="true"></i></span>
        <span>Need Help? Chat with Us</span>
    </button>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const widget = document.querySelector('[data-portal-help]');
        if (!widget) return;

        const panel = widget.querySelector('#portalHelpPanel');
        const toggle = widget.querySelector('[data-help-toggle]');
        const close = widget.querySelector('[data-help-close]');
        const thread = widget.querySelector('#portalHelpThread');

        function setOpen(open) {
            panel.setAttribute('aria-hidden', String(!open));
            toggle.setAttribute('aria-expanded', String(open));
            if (open) close.focus();
            else toggle.focus();
        }

        toggle.addEventListener('click', function () {
            setOpen(panel.getAttribute('aria-hidden') === 'true');
        });
        close.addEventListener('click', function () { setOpen(false); });

        widget.querySelectorAll('[data-question]').forEach(function (button) {
            button.addEventListener('click', function () {
                const question = document.createElement('div');
                question.className = 'portal-help-message portal-help-message-user';
                question.textContent = button.dataset.question;

                const answer = document.createElement('div');
                answer.className = 'portal-help-message portal-help-message-bot';
                answer.textContent = button.dataset.answer;

                thread.append(question, answer);
                thread.scrollTop = thread.scrollHeight;
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && panel.getAttribute('aria-hidden') === 'false') setOpen(false);
        });
    });
</script>