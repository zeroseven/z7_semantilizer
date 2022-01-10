define(['TYPO3/CMS/Backend/Notification', 'TYPO3/CMS/Backend/ActionButton/ImmediateAction', 'TYPO3/CMS/Z7Semantilizer/Backend/Headline', 'TYPO3/CMS/Z7Semantilizer/Backend/Converter', 'TYPO3/CMS/Z7Semantilizer/Backend/Translate'], (TYPO3Notification, ImmediateAction, Headline, Converter, translate) => {
  class IssueMessage {
    constructor(key, headlines) {
      this.headlines = headlines || [];
      this.key = key;
    }

    addHeadline(headline) {
      this.headlines.push(headline);
    }

    render(callback) {
      const fixableHeadlines = this.headlines.filter(headline => headline.issues.get(this.key).fix).length;
      const buttons = [];

      if (fixableHeadlines) {
        buttons.push({
          label: translate('notification.fix') + (fixableHeadlines > 1 ? ' (' + fixableHeadlines + ')' : ''),
          action: new ImmediateAction(() => {
            this.headlines.forEach(headline => headline.issues.fix(this.key));
            Headline.storeHeadlines(this.headlines, () => {
              TYPO3Notification.success(translate('notification.fixed.title'), translate('notification.' + this.key + '.title'), 4);
              typeof callback === 'function' && callback();
            });
          })
        });
      }

      const layout = this.key === 'mainHeadingNumber' ? 'info' : 'warning';
      TYPO3Notification[layout](translate('notification.' + this.key + '.title'), translate('notification.' + this.key + '.description'), 10, buttons);
    }
  }

  class Notification {
    constructor(parent) {
      this.parent = parent;

      return this;
    }

    showIssue(key) {
      new IssueMessage(key, this.parent.headlines.filter(headline => headline.issues.has(key))).render(this.parent.validate);
    }

    showIssues() {
      const keys = {};

      this.parent.headlines.filter(headline => headline.issues.count()).forEach(headline => headline.issues.each(issue => {
        keys[issue.key] = keys[issue.key] || [];
        keys[issue.key].push(headline);
      }));

      Object.keys(keys).forEach(key => new IssueMessage(key, keys[key]).render(this.parent.validate));
    }

    hideAll() {
      const container = TYPO3Notification.messageContainer;
      container && Converter.toArray(container.childNodes).forEach(message => container.removeChild(message));
    }
  }

  return Notification;
});
