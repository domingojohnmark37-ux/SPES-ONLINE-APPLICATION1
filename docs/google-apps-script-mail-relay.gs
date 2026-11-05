function doPost(e) {
  let payload;
  try {
    payload = JSON.parse(e.postData.contents);
  } catch (error) {
    return jsonResponse({ ok: false, error: 'unauthorized' });
  }

  try {
    const expectedToken = PropertiesService.getScriptProperties().getProperty('MAIL_RELAY_TOKEN');
    if (!payload || typeof payload !== 'object' || !expectedToken || payload.token !== expectedToken) {
      return jsonResponse({ ok: false, error: 'unauthorized' });
    }

    const recipients = payload.to;
    if (!Array.isArray(recipients)
        || recipients.length < 1
        || recipients.length > 5
        || !recipients.every(isValidEmail)) {
      return jsonResponse({ ok: false, error: 'invalid_recipients' });
    }

    if (typeof payload.subject !== 'string' || payload.subject.trim() === '') {
      return jsonResponse({ ok: false, error: 'invalid_subject' });
    }

    const html = typeof payload.html === 'string' ? payload.html : '';
    const text = typeof payload.text === 'string' ? payload.text : '';
    if (html.trim() === '' && text.trim() === '') {
      return jsonResponse({ ok: false, error: 'missing_body' });
    }

    if (MailApp.getRemainingDailyQuota() < recipients.length) {
      return jsonResponse({ ok: false, error: 'quota' });
    }

    MailApp.sendEmail({
      to: recipients.join(','),
      subject: payload.subject,
      htmlBody: html || undefined,
      body: text || htmlToText(html),
      name: typeof payload.fromName === 'string' ? payload.fromName : undefined,
      replyTo: typeof payload.replyTo === 'string' ? payload.replyTo : undefined,
      noReply: false
    });

    return jsonResponse({ ok: true });
  } catch (error) {
    const message = error && typeof error.message === 'string' ? error.message.toLowerCase() : '';
    return jsonResponse({
      ok: false,
      error: message.indexOf('quota') !== -1 ? 'quota' : 'send_failed'
    });
  }
}

function isValidEmail(value) {
  return typeof value === 'string'
    && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

function htmlToText(html) {
  return html
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<\/(p|div|li)>/gi, '\n')
    .replace(/<[^>]*>/g, '')
    .replace(/&nbsp;/gi, ' ')
    .replace(/&amp;/gi, '&')
    .replace(/&lt;/gi, '<')
    .replace(/&gt;/gi, '>')
    .replace(/&quot;/gi, '"')
    .replace(/&#39;/gi, "'");
}

function jsonResponse(data) {
  return ContentService
    .createTextOutput(JSON.stringify(data))
    .setMimeType(ContentService.MimeType.JSON);
}
