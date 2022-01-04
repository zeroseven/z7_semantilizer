define(['TYPO3/CMS/Backend/Notification', 'TYPO3/CMS/Backend/ActionButton/ImmediateAction', 'TYPO3/CMS/Z7Semantilizer/Backend/Converter', 'TYPO3/CMS/Z7Semantilizer/Backend/Headline', 'TYPO3/CMS/Z7Semantilizer/Backend/Module', 'TYPO3/CMS/Z7Semantilizer/Backend/Edit', 'TYPO3/CMS/Z7Semantilizer/Backend/Translate'], (Notification, ImmediateAction, Converter, Headline, Module, Edit, translate) => {
  class Error {
    static mainHeadingRequired(headline, targetType) {
      headline.addError('mainHeadingRequired', 4, targetType);
    }

    static mainHeadingNumber(headline, targetType) {
      headline.addError('mainHeadingNumber', 2, targetType, 'info');
    }

    static mainHeadingPosition(headline, targetType) {
      headline.addError('mainHeadingPosition', 3, targetType);
    }

    static headingStructure(headline, targetType) {
      headline.addError('headingStructure', 1, targetType);
    }
  }

  class Semantilizer {
    headlines = [];
    url;
    contentSelectors;
    module;

    constructor(url, elementId, contentSelectors) {
      this.url = url;
      this.contentSelectors = Converter.toArray(contentSelectors);
      this.module = new Module(document.getElementById(elementId), this);

      this.refresh = this.refresh.bind(this);

      this.init();
    }

    collect(callback) {
      let request = new XMLHttpRequest();

      // Add loader
      this.module.loader();

      // Clear headlines
      this.headlines.length = 0;

      request.onreadystatechange = () => {
        if (request.readyState === 4) {
          if (request.status === 200) {

            // Parse document
            const parser = new DOMParser();
            const doc = parser.parseFromString(request.responseText, 'text/html');

            // Find headlines in contents
            this.contentSelectors.map(selector => doc.querySelector(selector)).filter(container => container).forEach(container => {
              this.headlines.push(...Converter.toArray((container || doc).querySelectorAll('h1, h2, h3, h4, h5, h6')).map(node => new Headline(node)));
            });
          }

          // Run callback function
          if (typeof callback === 'function') {
            callback(request);
          }
        }
      };

      request.open('GET', this.url, true);
      request.send();
    }

    validate() {
      this.headlines.forEach(headline => headline.error.length = 0);

      const validateMainHeadings = () => {
        const firstHeadline = this.headlines[0];
        const mainHeadlines = this.headlines.filter(headline => headline.type === 1);

        if (mainHeadlines.length === 0) {
          Error.mainHeadingRequired(firstHeadline, 1);
        }

        if (mainHeadlines.length > 1) {
          this.headlines.forEach((headline, i) => {
            if (i && headline.type === 1) {
              Error.mainHeadingNumber(headline, 2);
            }
          });
        }

        if (mainHeadlines.length === 1 && firstHeadline.type !== 1) {
          Error.mainHeadingPosition(firstHeadline, 1);

          this.headlines.forEach((headline, i) => {
            if (i && headline.type === 1) {
              Error.mainHeadingPosition(headline, 2);
            }
          });
        }
      };

      const validateStructure = () => {
        this.headlines.forEach((headline, i) => {
          if (i && headline.type > this.headlines[i - 1].type + 1) {
            Error.headingStructure(headline);
          }
        });
      };

      if (this.headlines.length) {
        validateMainHeadings();
        validateStructure();
      }
    }

    showNotifications() {
      const notificationQueue = {};

      // Collect messages
      this.headlines.filter(headline => headline.error.length).forEach(headline => headline.error.forEach(error => {
        notificationQueue[error.code] = notificationQueue[error.code] || {
          layout: error.layout,
          fix: []
        };

        if(error.fix && headline.edit && headline.edit.table && headline.edit.uid && headline.edit.field) {
          notificationQueue[error.code].fix.push([error.fix, headline]);
        }
      }));

      // Print messages
      Object.keys(notificationQueue).forEach(key => {
        const fixLength = notificationQueue[key].fix.length;

        const buttons = [];

        if(fixLength) {
          buttons.push({
            label: translate('notification.fix') + (fixLength > 1 ? ' (' + fixLength + ')' : ''),
            action: new ImmediateAction(() => Edit.updateTypes(notificationQueue[key].fix.map(fix => ({
              type: fix[0],
              headline: fix[1]
            })), () => {
              this.revalidate();

              Notification.success(translate('notification.fixed.title'), translate('notification.' + key + '.title'), 4);
            }))
          });
        }

        Notification[notificationQueue[key].layout](translate('notification.' + key + '.title'), translate('notification.' + key + '.description'), 10, buttons);
      });
    }

    hideAllNotifications() {
      const container = Notification.messageContainer;
      container && Converter.toArray(container.childNodes).forEach(message => container.removeChild(message));
    }

    revalidate() {
      this.validate();
      this.hideAllNotifications();
      this.showNotifications();

      this.module.drawStructure();
    }

    refresh(callback) {
      this.collect(request => {
        if (typeof callback === 'function') {
          callback();
        }

        this.module.setHeadlines(this.headlines);
        this.revalidate();
      });
    }

    init() {
      this.refresh();

      // Watch the sorting of the content elements in the page module
      require(['jquery', 'jquery-ui/droppable'], $ => $('.t3js-page-ce-dropzone-available').on('drop', this.refresh));
    }
  }

  // Return the object
  return Semantilizer;
});
