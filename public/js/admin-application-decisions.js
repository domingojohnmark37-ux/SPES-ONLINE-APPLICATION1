(() => {
    if (window.adminApplicationDecisionsReady) return;
    window.adminApplicationDecisionsReady = true;

    let toast;
    const confirmedForms = new WeakSet();

    const showToast = (message, success) => {
        if (window.spesAlerts?.toast) {
            window.spesAlerts.toast[success ? 'success' : 'error'](message);
            return;
        }

        if (!toast?.isConnected) {
            toast = document.createElement('div');
            toast.className = 'application-decision-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            document.body.append(toast);
        }
        toast.dataset.kind = success ? 'success' : 'error';
        toast.textContent = message;
        toast.hidden = false;
        toast.focus?.();
    };

    const statusClass = (status) => `badge badge-${status}`;

    const updateRowStatus = (row, application) => {
        const statusCell = row?.querySelector('[data-application-status]');
        if (statusCell) {
            statusCell.className = statusClass(application.status);
            const icon = document.createElement('i');
            icon.className = `fa-solid ${application.status === 'approved' ? 'fa-circle-check' : (application.status === 'denied' ? 'fa-circle-xmark' : 'fa-clock')}`;
            icon.setAttribute('aria-hidden', 'true');
            statusCell.replaceChildren(icon, document.createTextNode(` ${application.status_label}`));
        }
        if (application.status !== 'pending') {
            row?.querySelectorAll('[data-application-decision], .js-deny-application').forEach((control) => control.remove());
        }
    };

    const updatePage = (form, payload) => {
        const row = form.closest('tr');
        updateRowStatus(row, payload.application);

        if (payload.stats) {
            Object.entries(payload.stats).forEach(([name, count]) => {
                document.querySelectorAll(`[data-application-stat="${name}"]`).forEach((counter) => {
                    counter.textContent = String(count);
                });
            });
        }

        (payload.auto_denied_ids || []).forEach((id) => {
            const autoDeniedRow = document.querySelector(`tr[data-application-id="${CSS.escape(String(id))}"]`);
            updateRowStatus(autoDeniedRow, { status: 'denied', status_label: 'Denied' });
        });

        if (payload.auto_denied_ids?.length && payload.stats) {
            document.querySelectorAll('[data-application-stat="denied"]').forEach((counter) => {
                counter.textContent = String(payload.stats.denied);
            });
        }

        const detailStatus = document.querySelector('[data-application-status-label]');
        if (detailStatus) {
            detailStatus.className = statusClass(payload.application.status);
            detailStatus.textContent = payload.application.status_label;
        }
        const detailActions = document.querySelector('[data-application-decision-actions]');
        if (detailActions && payload.application.status !== 'pending') detailActions.hidden = true;
    };

    const responseMessage = async (response) => {
        let body = {};
        let isJson = false;
        try {
            body = await response.json();
            isJson = true;
        } catch {
        }

        if (response.status === 403) return { body, message: 'Your admin session is not authorized to make this decision.' };
        if (response.status === 404) return { body, message: 'This application or one of its records no longer exists. Refresh the page.' };
        const destination = response.redirected ? new URL(response.url, window.location.href) : null;
        const redirectedToLogin = destination?.origin === window.location.origin
            && (destination.pathname === '/login' || destination.pathname.startsWith('/login/'));
        if (response.status === 401 || response.status === 419 || redirectedToLogin) {
            return { body, message: 'Your session expired. Refresh the page and submit the decision again.' };
        }
        if (response.status === 422) {
            const validationErrors = Object.values(body.errors || {}).flat();
            return { body, message: validationErrors.join(' ') || body.message || 'Please correct the decision form and try again.' };
        }
        if (response.status === 429) return { body, message: 'Too many requests. Wait a moment, then try again.' };
        if (response.status >= 500) return { body, message: body.message || 'The server could not complete the request. Check the application status before retrying.' };
        if (!response.ok) return { body, message: body.message || `The request failed (${response.status}). Please try again.` };
        if (!isJson || typeof body.success !== 'boolean') {
            return { body, message: 'The server returned an unexpected response. Refresh the page to verify the decision.' };
        }
        return { body, message: body.message || 'The decision was saved.' };
    };

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-application-decision]');
        if (!form) return;

        if (confirmedForms.has(form)) {
            confirmedForms.delete(form);
        } else {
            const confirmation = form.dataset.swalConfirm || event.submitter?.dataset.swalConfirm;
            if (confirmation) {
                event.preventDefault();
                event.stopImmediatePropagation();
                if (form.dataset.decisionState === 'confirming') return;

                const submitter = event.submitter;
                form.dataset.decisionState = 'confirming';
                try {
                    const isConfirmed = window.spesAlerts?.confirm
                        ? await window.spesAlerts.confirm(confirmation)
                        : window.confirm(confirmation);
                    if (!isConfirmed || !form.reportValidity()) return;

                    confirmedForms.add(form);
                    window.spesAlerts?.authorizeSubmit(form);
                    if (submitter) {
                        form.requestSubmit(submitter);
                    } else {
                        form.requestSubmit();
                    }
                } catch (error) {
                    console.error('Could not confirm the application decision.', error);
                    window.spesAlerts?.toast.error('Could not confirm the application decision. Please try again.');
                } finally {
                    if (form.dataset.decisionState === 'confirming') {
                        delete form.dataset.decisionState;
                    }
                }
                return;
            }
        }

        event.preventDefault();
        if (form.dataset.decisionState === 'submitting') return;

        const submitButton = form.querySelector('button[type="submit"]');
        const cancelButton = document.querySelector('#denyApplicationModal [data-denial-cancel]');
        const feedback = form.querySelector('[data-decision-feedback]');
        const spinner = form.querySelector('[data-decision-spinner]');
        if (!form.reportValidity()) {
            const invalidField = form.querySelector(':invalid');
            if (feedback && invalidField) {
                feedback.textContent = invalidField.validationMessage || 'Complete the required fields before submitting.';
                feedback.hidden = false;
            }
            return;
        }
        const originalButtonContent = submitButton?.innerHTML;

        form.dataset.decisionState = 'submitting';
        form.setAttribute('aria-busy', 'true');
        if (feedback) {
            feedback.hidden = true;
            feedback.textContent = '';
        }
        if (spinner) spinner.hidden = false;
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Processing...';
        }
        if (cancelButton) cancelButton.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const result = await responseMessage(response);
            if (!response.ok || !result.body.success) {
                if (result.body.application && result.body.stats) updatePage(form, result.body);
                throw new Error(result.message);
            }

            updatePage(form, result.body);
            const modal = document.getElementById('denyApplicationModal');
            if (modal) {
                modal.hidden = true;
                modal.style.display = 'none';
            }
            showToast(result.message, true);
        } catch (error) {
            const message = error instanceof TypeError
                ? 'The connection was interrupted. Refresh the page to verify the decision before retrying.'
                : error.message || 'The request could not be completed. Check your connection and try again.';
            if (feedback) {
                feedback.textContent = message;
                feedback.hidden = false;
            }
            showToast(message, false);
        } finally {
            delete form.dataset.decisionState;
            form.removeAttribute('aria-busy');
            if (spinner) spinner.hidden = true;
            if (submitButton) {
                submitButton.disabled = false;
                if (originalButtonContent !== undefined) submitButton.innerHTML = originalButtonContent;
            }
            if (cancelButton) cancelButton.disabled = false;
        }
    }, true);
})();
