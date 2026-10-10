(() => {
    if (window.requestLoading) return;

    const loadingWords = /\b(save|saving|update|updating|send|sending|delete|deleting|remove|removing|submit|submitting|apply|applying|approve|approving|reject|den(y|ying)|upload|uploading|import|importing|export|exporting|report|backup|restore|restoring|verify|verifying|login|log in|logout|log out|filter|search|refresh|comment|publish|unpublish|schedule|create|add|confirm|mark as read)\b/i;
    const confirmations = /\bconfirm\s*\(/i;
    const activeActions = new Set();
    let clickAction = null;
    let submitAction = null;
    let feedback;

    function feedbackNode() {
        if (feedback?.isConnected) return feedback;
        feedback = document.createElement('div');
        feedback.className = 'request-loading-feedback';
        feedback.setAttribute('role', 'status');
        feedback.setAttribute('aria-live', 'polite');
        feedback.hidden = true;
        document.body.append(feedback);
        return feedback;
    }

    function actionLabel(element) {
        const customLabel = element?.dataset?.loadingLabel;
        if (customLabel) return customLabel;

        const label = (element?.innerText || element?.value || element?.getAttribute('aria-label') || '').trim();
        if (/\bapprove\b/i.test(label)) return 'Approving...';
        if (/\b(reject|deny)\b/i.test(label)) return 'Processing...';
        if (/\b(send|email|notify|message)\b/i.test(label)) return 'Sending...';
        if (/\b(delete|remove)\b/i.test(label)) return 'Deleting...';
        if (/\bupload\b/i.test(label)) return 'Uploading...';
        if (/\bdownload\b/i.test(label)) return 'Downloading...';
        if (/\b(export|report)\b/i.test(label)) return 'Generating report...';
        if (/\bimport\b/i.test(label)) return 'Importing...';
        if (/\bbackup\b/i.test(label)) return 'Creating backup...';
        if (/\brestore\b/i.test(label)) return 'Restoring...';
        if (/\bverify\b/i.test(label)) return 'Verifying...';
        if (/\b(log ?in|login)\b/i.test(label)) return 'Signing in...';
        if (/\b(log ?out|logout)\b/i.test(label)) return 'Signing out...';
        if (/\b(filter|search|refresh)\b/i.test(label)) return 'Loading results...';
        if (/\b(comment|reply)\b/i.test(label)) return 'Saving...';
        if (/\b(publish|unpublish|schedule)\b/i.test(label)) return 'Updating...';
        if (/\b(create|add)\b/i.test(label)) return 'Creating...';
        if (/\b(update|edit)\b/i.test(label)) return 'Updating...';
        if (/\b(submit|apply)\b/i.test(label)) return 'Submitting...';
        if (/\b(save|confirm|mark as read)\b/i.test(label)) return 'Saving...';
        return 'Processing...';
    }

    function messageFor(label) {
        return label.replace(/\.{3}$/, '') + ' Please wait.';
    }

    function setBusy(action) {
        if (action.busy) return;
        action.busy = true;
        activeActions.add(action);

        const element = action.element;
        if (element instanceof HTMLButtonElement) {
            action.originalHtml = element.innerHTML;
            action.wasDisabled = element.disabled;
            element.disabled = true;
            element.classList.add('request-loading-button');
            element.setAttribute('aria-busy', 'true');
            element.innerHTML = '<span class="request-loading-spinner" aria-hidden="true"></span><span></span>';
            element.lastElementChild.textContent = action.label;
            action.labelNode = element.lastElementChild;
        } else if (element instanceof HTMLInputElement && ['submit', 'image'].includes(element.type)) {
            action.originalValue = element.value;
            action.wasDisabled = element.disabled;
            element.disabled = true;
            element.classList.add('request-loading-button');
            element.setAttribute('aria-busy', 'true');
            element.value = action.label;
        } else if (element instanceof HTMLAnchorElement) {
            action.originalLabel = element.getAttribute('aria-label');
            element.setAttribute('aria-busy', 'true');
            element.setAttribute('aria-disabled', 'true');
            element.classList.add('request-loading-button');
            element.insertAdjacentHTML('afterbegin', '<span class="request-loading-spinner" aria-hidden="true"></span>');
        } else if (element instanceof HTMLElement) {
            element.classList.add('request-loading-button');
            element.setAttribute('aria-busy', 'true');
            element.setAttribute('aria-disabled', 'true');
        }

        if (action.form) {
            action.form.dataset.requestPending = 'true';
            action.form.setAttribute('aria-busy', 'true');
            if (action.element instanceof HTMLButtonElement || action.element instanceof HTMLInputElement) {
                const name = action.element.name;
                if (name && action.element.type !== 'image') {
                    action.submitterProxy = document.createElement('input');
                    action.submitterProxy.type = 'hidden';
                    action.submitterProxy.name = name;
                    action.submitterProxy.value = action.element.value;
                    action.form.append(action.submitterProxy);
                }
            }
        }
        announce(messageFor(action.label), 'pending');
    }

    function clearBusy(action) {
        if (!action || !action.busy) return;
        action.busy = false;
        activeActions.delete(action);

        const element = action.element;
        if (element instanceof HTMLButtonElement) {
            element.innerHTML = action.originalHtml;
            element.disabled = action.wasDisabled;
            element.classList.remove('request-loading-button');
            element.removeAttribute('aria-busy');
        } else if (element instanceof HTMLInputElement && ['submit', 'image'].includes(element.type)) {
            element.value = action.originalValue;
            element.disabled = action.wasDisabled;
            element.classList.remove('request-loading-button');
            element.removeAttribute('aria-busy');
        } else if (element instanceof HTMLAnchorElement) {
            element.querySelector('.request-loading-spinner')?.remove();
            element.classList.remove('request-loading-button');
            element.removeAttribute('aria-busy');
            element.removeAttribute('aria-disabled');
            if (action.originalLabel === null) element.removeAttribute('aria-label');
            else if (action.originalLabel !== undefined) element.setAttribute('aria-label', action.originalLabel);
        } else if (element instanceof HTMLElement) {
            element.classList.remove('request-loading-button');
            element.removeAttribute('aria-busy');
            element.removeAttribute('aria-disabled');
        }

        action.submitterProxy?.remove();
        if (action.form) {
            delete action.form.dataset.requestPending;
            action.form.removeAttribute('aria-busy');
        }
    }

    function announce(message, kind) {
        if (kind !== 'uncertain' && Array.from(activeActions).some(action => action.uncertain && action.busy)) return;
        const node = feedbackNode();
        node.dataset.kind = kind;
        if (kind === 'pending') {
            const spinner = document.createElement('span');
            spinner.className = 'request-loading-spinner';
            spinner.setAttribute('aria-hidden', 'true');
            const text = document.createElement('span');
            text.textContent = message;
            node.replaceChildren(spinner, text);
        } else {
            node.replaceChildren(document.createTextNode(message));
        }
        if (kind === 'uncertain') {
            const reload = document.createElement('button');
            reload.type = 'button';
            reload.dataset.noRequestLoading = '';
            reload.textContent = 'Reload to check status';
            reload.addEventListener('click', () => window.location.reload(), { once: true });
            node.append(' ', reload);
        }
        node.hidden = message === '';
    }

    function complete(action, success, message) {
        if (!action) return;
        clearBusy(action);
        if (message) {
            announce(message, success ? 'success' : 'error');
        } else {
            announce(
                success ? 'The request completed successfully.' : 'The request could not be completed. Please try again.',
                success ? 'success' : 'error'
            );
        }
        action.form?.dispatchEvent(new CustomEvent('requestloading:complete', {
            bubbles: true,
            detail: { success },
        }));
        action.element?.dispatchEvent(new CustomEvent('requestloading:complete', {
            bubbles: true,
            detail: { success },
        }));
    }

    function isRequestControl(element) {
        if (!(element instanceof HTMLElement) || element.closest('[data-no-request-loading]')) return false;
        if (element.matches(':disabled,[aria-disabled="true"]')) return false;
        if (element instanceof HTMLAnchorElement) {
            if (element.hasAttribute('download') || (element.target && element.target !== '_self')) return false;
            const url = new URL(element.href, window.location.href);
            if (url.origin !== window.location.origin || url.href === window.location.href) return false;
            if (url.hash && url.pathname === window.location.pathname && url.search === window.location.search) return false;
        }
        if (element instanceof HTMLButtonElement && element.type === 'submit') return false;
        if (element instanceof HTMLInputElement && ['submit', 'image'].includes(element.type)) return false;

        const text = element.dataset.loadingLabel || element.innerText || element.getAttribute('aria-label') || '';
        return element.hasAttribute('data-request-loading') || loadingWords.test(text);
    }

    function formAction(form, submitter) {
        if (submitter instanceof HTMLElement && submitter.matches('button,input[type="submit"],input[type="image"]')) {
            return submitter;
        }
        return form.querySelector('button:not([type]):not(:disabled),button[type="submit"]:not(:disabled),input[type="submit"]:not(:disabled),input[type="image"]:not(:disabled)')
            || form.querySelector('button:not([type]),button[type="submit"],input[type="submit"],input[type="image"]');
    }

    async function readDownload(response, action) {
        const reader = response.body?.getReader();
        const expectedBytes = Number(response.headers.get('Content-Length'));
        if (!reader) return response.blob();

        const chunks = [];
        let receivedBytes = 0;
        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            chunks.push(value);
            receivedBytes += value.byteLength;
            if (Number.isFinite(expectedBytes) && expectedBytes > 0 && action.labelNode) {
                const percent = Math.min(100, Math.floor(receivedBytes / expectedBytes * 100));
                action.labelNode.textContent = `Downloading ${percent}%...`;
            }
        }

        return new Blob(chunks, { type: response.headers.get('Content-Type') || '' });
    }

    async function submitDownloadForm(form, submitter) {
        const action = makeAction(submitter || formAction(form, null), form);
        setBusy(action);

        try {
            const response = await originalFetch(form.action, {
                method: (form.method || 'get').toUpperCase(),
                body: ['get', 'head'].includes((form.method || 'get').toLowerCase()) ? undefined : new FormData(form),
                credentials: 'same-origin',
                headers: { Accept: 'application/octet-stream, application/zip' },
            });
            const disposition = response.headers.get('Content-Disposition');
            const contentType = response.headers.get('Content-Type') || '';

            if (!response.ok || response.redirected || !disposition || /text\/html/i.test(contentType)) {
                throw new Error(`The download could not be prepared (${response.status}).`);
            }

            const blob = await readDownload(response, action);
            if (!blob.size) throw new Error('The server returned an empty download.');

            const filenameMatch = disposition.match(/filename\*=UTF-8''([^;]+)|filename="?([^";]+)"?/i);
            const filename = decodeURIComponent(filenameMatch?.[1] || filenameMatch?.[2] || 'download.zip');
            const objectUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = objectUrl;
            link.download = filename;
            link.hidden = true;
            document.body.append(link);
            link.click();
            link.remove();
            window.setTimeout(() => URL.revokeObjectURL(objectUrl), 0);

            complete(action, true, 'The backup is ready and the download has started.');
        } catch (error) {
            console.error('Download request failed.', error);
            complete(action, false, error.message || 'The download could not be prepared. Please try again.');
        }
    }

    function makeAction(element, form = null) {
        return {
            element,
            form,
            label: actionLabel(element),
            busy: false,
            requests: 0,
            nativeSubmit: false,
        };
    }

    function requestStarted(action) {
        if (!action) return;
        action.requests += 1;
        setBusy(action);
    }

    function isWriteRequest(action, input, init) {
        const method = init?.method
            || (input instanceof Request ? input.method : null)
            || action?.form?.method
            || 'GET';
        return !['get', 'head'].includes(method.toLowerCase());
    }

    function requestEnded(action, success, message = '') {
        if (!action) return;
        if (!success) {
            action.failed = true;
            action.failureMessage = message;
        }
        action.requests = Math.max(0, action.requests - 1);
        if (action.requests !== 0) return;
        if (action.uncertain) {
            if (action.uncertaintyAnnounced) return;
            action.uncertaintyAnnounced = true;
            announce(
                message || 'The connection was interrupted, so we could not confirm whether this change was saved. Check the current record before retrying.',
                'uncertain'
            );
            action.form?.dispatchEvent(new CustomEvent('requestloading:complete', {
                bubbles: true,
                detail: { success: false, uncertain: true },
            }));
            return;
        }
        complete(action, !action.failed, action.failureMessage || message);
    }

    document.addEventListener('click', function(event) {
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const element = event.target.closest('button,a[href],[data-request-loading]');
        if (element?.matches('[aria-busy="true"],[aria-disabled="true"]')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }
        if (event.defaultPrevented) return;
        if (!isRequestControl(element)) return;
        if (element.getAttribute('onclick') && confirmations.test(element.getAttribute('onclick'))) return;

        const action = makeAction(element);
        clickAction = action;
        setBusy(action);
        queueMicrotask(() => {
            if (clickAction === action) clickAction = null;
            if (action.requests === 0) clearBusy(action);
        });
    }, true);

    document.addEventListener('submit', function(event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented || form.hasAttribute('data-no-request-loading')) return;
        if (form.hasAttribute('data-request-download')) {
            if (form.dataset.requestPending === 'true') {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
            return;
        }
        const submitter = event.submitter || formAction(form, null);
        if (submitter instanceof HTMLElement && submitter.getAttribute('onclick') && confirmations.test(submitter.getAttribute('onclick'))) {
            if (event.defaultPrevented) return;
        }

        if (form.dataset.requestPending === 'true') {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }

        const action = makeAction(submitter, form);
        const isAuthForm = form.hasAttribute('data-auth-form');
        if (isAuthForm) return;

        action.nativeSubmit = true;
        submitAction = action;
        setBusy(action);

        queueMicrotask(() => {
            if (submitAction === action) submitAction = null;
            if (event.defaultPrevented && action.requests === 0) clearBusy(action);
        });
    }, true);

    document.addEventListener('submit', function(event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)
            || !form.hasAttribute('data-request-download')
            || event.defaultPrevented
            || form.dataset.requestPending === 'true') return;

        event.preventDefault();
        submitDownloadForm(form, event.submitter || formAction(form, null));
    });

    const originalFetch = window.fetch.bind(window);
    window.fetch = function(...args) {
        const action = clickAction || submitAction;
        requestStarted(action);

        return originalFetch(...args).then(response => {
            if (action) {
                const isWrite = isWriteRequest(action, args[0], args[1]);
                if (response.status === 401 || response.status === 419) {
                    requestEnded(action, false, 'Your session has expired. Sign in again and retry.');
                } else if (response.status >= 500 && isWrite) {
                    action.uncertain = true;
                    requestEnded(action, false, 'The server returned an error, so we could not confirm whether this change was saved. Check the current record before retrying.');
                } else if (!response.ok) {
                    requestEnded(action, false, `The request failed (${response.status}). Please review the details and try again.`);
                } else {
                    requestEnded(action, true);
                }
            }
            return response;
        }, error => {
            if (action) {
                const isWrite = isWriteRequest(action, args[0], args[1]);
                if (isWrite) action.uncertain = true;
                requestEnded(action, false, isWrite
                    ? 'The connection was interrupted, so we could not confirm whether this change was saved. Check the current record before retrying.'
                    : 'The request could not reach the server. Check your connection and try again.');
            }
            throw error;
        });
    };

    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, ...args) {
        this.requestLoadingMethod = String(method || 'GET').toLowerCase();
        return originalOpen.call(this, method, ...args);
    };

    const originalSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.send = function(...args) {
        const action = clickAction || submitAction;
        requestStarted(action);
        if (action && this.upload) {
            this.upload.addEventListener('progress', event => {
                if (!event.lengthComputable || !action.labelNode || !/\bupload/i.test(action.label)) return;
                const percent = Math.floor(event.loaded / event.total * 100);
                action.labelNode.textContent = `Uploading ${percent}%...`;
            });
        }
        this.addEventListener('loadend', () => {
            if (action) {
                const success = this.status >= 200 && this.status < 400;
                const isWrite = !['get', 'head'].includes(this.requestLoadingMethod || 'get');
                if ((this.status === 0 || this.status >= 500) && isWrite) action.uncertain = true;
                const message = this.status === 401 || this.status === 419
                    ? 'Your session has expired. Sign in again and retry.'
                    : success
                        ? ''
                        : (this.status === 0 || this.status >= 500) && isWrite
                            ? 'The connection or server was interrupted, so we could not confirm whether this change was saved. Check the current record before retrying.'
                            : `The request failed (${this.status || 'network error'}). Please review the details and try again.`;
                requestEnded(action, success, message);
            }
        }, { once: true });
        return originalSend.apply(this, args);
    };

    window.requestLoading = {
        start(element, form = null) {
            const action = makeAction(element, form);
            setBusy(action);
            return {
                success: message => complete(action, true, message),
                error: message => complete(action, false, message),
                uncertain: message => {
                    action.uncertain = true;
                    action.requests = Math.max(action.requests, 1);
                    requestEnded(action, false, message);
                },
            };
        },
        complete,
    };

    window.addEventListener('pageshow', () => {
        activeActions.forEach(clearBusy);
        clickAction = null;
        submitAction = null;
    });
})();
