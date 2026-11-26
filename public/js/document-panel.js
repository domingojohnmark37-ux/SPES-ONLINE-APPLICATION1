(() => {
    if (window.documentPanelReady) return;
    window.documentPanelReady = true;

    const make = (tag, className, text) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined) element.textContent = text;
        return element;
    };

    const openPanel = (button) => {
        let items;
        try {
            items = JSON.parse(button.dataset.documentItems || '[]');
        } catch (error) {
            console.error('Unable to read the application document list.', error);
            return;
        }

        const dialog = make('dialog', 'document-panel-dialog');
        dialog.setAttribute('aria-modal', 'true');
        dialog.setAttribute('aria-label', button.dataset.documentTitle || 'Application documents');
        const pageBody = document.body;
        const pageRoot = document.documentElement;
        const previousBodyOverflow = pageBody.style.overflow;
        const previousRootOverflow = pageRoot.style.overflow;
        const closeDialog = () => {
            if (dialog.classList.contains('is-closing')) return;
            dialog.classList.add('is-closing');
            dialog.addEventListener('animationend', (event) => {
                if (event.target === dialog) dialog.close();
            }, { once: true });
        };

        const header = make('header', 'document-panel-header');
        const heading = make('div', 'document-panel-heading');
        const title = make('h2', 'document-panel-title', button.dataset.documentTitle || 'Application documents');
        const applicant = make('p', 'document-panel-applicant', button.dataset.documentApplicant || '');
        applicant.hidden = !applicant.textContent;
        const close = make('button', 'document-panel-close', 'Close');
        close.type = 'button';
        close.addEventListener('click', () => {
            if (dialog.classList.contains('is-previewing')) {
                showList();
                return;
            }
            closeDialog();
        });
        heading.append(title, applicant);
        header.append(heading, close);

        const body = make('div', 'document-panel-body');
        const footer = make('footer', 'document-panel-footer');
        const listView = make('div', 'document-panel-list-view');
        const previewView = make('div', 'document-panel-preview-view');
        const makeDownloadLink = (item) => {
            const download = make('a', 'document-panel-action is-secondary', 'Download');
            download.href = item.download_url;
            download.setAttribute('download', item.filename || '');
            return download;
        };

        const availableCount = items.filter((item) => item.available).length;
        if (availableCount === 0) {
            listView.append(make('p', 'document-panel-empty', 'No uploaded documents are currently available to view.'));
        }

        if (items.length === 0) {
            listView.append(make('p', 'document-panel-empty', 'No documents have been submitted.'));
        } else {
            const list = make('div', 'document-panel-list');
            items.forEach((item) => {
                const row = make('article', 'document-panel-item');
                const copy = make('div', 'document-panel-copy');
                const extension = (item.filename || '').split('.').pop()?.toLowerCase();
                copy.append(make('span', 'document-file-icon', extension === 'pdf' ? 'PDF' : 'FILE'));

                const details = make('div', 'document-panel-details');
                details.append(make('strong', 'document-panel-category', item.category || 'Document'));
                if (item.filename) details.append(make('span', 'document-panel-filename', item.filename));
                if (item.submitted_by) details.append(make('span', 'document-panel-filename', `Submitted by ${item.submitted_by}`));

                const status = make(
                    'span',
                    `document-panel-status${item.available ? '' : ' is-missing'}`,
                    item.available ? (item.status_label || 'On file') : (item.status_label || 'File missing')
                );
                details.append(status);
                copy.append(details);
                row.append(copy);

                const actions = make('div', 'document-panel-actions');
                if (item.available && item.url) {
                    const view = make('button', 'document-panel-action', 'View');
                    view.type = 'button';
                    view.addEventListener('click', () => showPreview(item));
                    actions.append(view);
                    if (item.download_url) {
                        actions.append(makeDownloadLink(item));
                    }
                } else if (item.upload_url) {
                    const upload = make('a', 'document-panel-action is-secondary', 'Upload');
                    upload.href = item.upload_url;
                    actions.append(upload);
                }
                row.append(actions);
                list.append(row);
            });
            listView.append(list);
        }

        const showList = () => {
            dialog.classList.remove('is-previewing');
            title.textContent = button.dataset.documentTitle || 'Application documents';
            applicant.textContent = button.dataset.documentApplicant || '';
            applicant.hidden = !applicant.textContent;
            previewView.replaceChildren();
            footer.replaceChildren();
            body.replaceChildren(listView);
            close.focus();
        };

        const showPreview = (item) => {
            const extension = (item.filename || '').split('.').pop()?.toLowerCase();
            const supportedImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(extension);
            const isPdf = extension === 'pdf';

            dialog.classList.add('is-previewing');
            title.textContent = item.category || 'Document Preview';
            applicant.textContent = button.dataset.documentApplicant || '';
            applicant.hidden = !applicant.textContent;
            previewView.replaceChildren();

            if (isPdf) {
                const frame = make('iframe', 'document-panel-pdf');
                frame.src = item.url;
                frame.title = `${item.category || 'Document'} PDF preview`;
                frame.loading = 'eager';
                previewView.append(frame);
            } else if (supportedImage) {
                const image = make('img', 'document-panel-image');
                image.src = item.url;
                image.alt = item.category || item.filename || 'Uploaded document';
                previewView.append(image);
            } else {
                const message = make('p', 'document-panel-unsupported', 'This file type cannot be previewed here. Download the file to view it.');
                previewView.append(message);
            }

            footer.replaceChildren();
            if (item.download_url) footer.append(makeDownloadLink(item));
            const back = make('button', 'document-panel-action is-secondary', 'Close');
            back.type = 'button';
            back.addEventListener('click', showList);
            footer.append(back);
            body.replaceChildren(previewView);
            close.focus();
        };

        close.textContent = 'Close';
        close.setAttribute('aria-label', 'Close documents');
        dialog.addEventListener('cancel', (event) => {
            if (dialog.classList.contains('is-previewing')) {
                event.preventDefault();
                showList();
                return;
            }
            event.preventDefault();
            closeDialog();
        });
        dialog.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            event.preventDefault();
            if (dialog.classList.contains('is-previewing')) {
                showList();
                return;
            }
            closeDialog();
        });

        body.append(listView);
        dialog.append(header, body);
        dialog.append(footer);
        dialog.addEventListener('click', (event) => {
            if (event.target !== dialog) return;
            if (dialog.classList.contains('is-previewing')) {
                showList();
                return;
            }
            closeDialog();
        });
        dialog.addEventListener('close', () => {
            pageBody.style.overflow = previousBodyOverflow;
            pageRoot.style.overflow = previousRootOverflow;
            dialog.remove();
        }, { once: true });
        document.body.append(dialog);
        pageBody.style.overflow = 'hidden';
        pageRoot.style.overflow = 'hidden';
        dialog.showModal();
        close.focus();
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-document-panel]');
        if (!button || button.disabled) return;
        openPanel(button);
    });
})();
