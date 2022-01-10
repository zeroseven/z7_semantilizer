define(['TYPO3/CMS/Backend/Notification', 'TYPO3/CMS/Backend/ActionButton/ImmediateAction', 'TYPO3/CMS/Z7Semantilizer/Backend/Converter', 'TYPO3/CMS/Z7Semantilizer/Backend/Module', 'TYPO3/CMS/Z7Semantilizer/Backend/Edit', 'TYPO3/CMS/Z7Semantilizer/Backend/Translate'], (Notification, ImmediateAction, Converter, Module, Edit, translate) => {
  class ErrorNotification {
    static show(key, fixes, layout, fixCallback) {
      const fixesArray = Array.isArray(fixes) ? fixes : [fixes];
      const fixesLength = fixesArray.length;
      const buttons = [];

      if (fixesLength) {
        buttons.push({
          label: translate('notification.fix') + (fixesLength > 1 ? ' (' + fixesLength + ')' : ''),
          action: new ImmediateAction(() => Edit.updateTypes(fixesArray, () => {
            typeof fixCallback === 'function' && fixCallback();

            Notification.success(translate('notification.fixed.title'), translate('notification.' + key + '.title'), 4);
          }))
        });
      }

      Notification[layout](translate('notification.' + key + '.title'), translate('notification.' + key + '.description'), 10, buttons);
    }

    static showErrors(headlines, fixCallback) {
      const notificationQueue = {};

      // Collect messages
      headlines.filter(headline => headline.error.length).forEach(headline => headline.error.forEach(error => {
        notificationQueue[error.code] = notificationQueue[error.code] || {
          layout: error.layout,
          fixes: []
        };

        if (error.fix && headline.edit && headline.edit.table && headline.edit.uid && headline.edit.field) {
          notificationQueue[error.code].fixes.push({
            type: error.fix,
            headline: headline
          });
        }
      }));

      // Print messages
      Object.keys(notificationQueue).forEach(key => this.show(key, notificationQueue[key].fixes, notificationQueue[key].layout, fixCallback));
    }

    static hideAll() {
      const container = Notification.messageContainer;
      container && Converter.toArray(container.childNodes).forEach(message => container.removeChild(message));
    }
  }

  return ErrorNotification;
});
