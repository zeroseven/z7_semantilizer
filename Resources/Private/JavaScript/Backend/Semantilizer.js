define(['TYPO3/CMS/Backend/Notification', 'TYPO3/CMS/Backend/ActionButton/ImmediateAction', 'TYPO3/CMS/Z7Semantilizer/Backend/Converter', 'TYPO3/CMS/Z7Semantilizer/Backend/Headline', 'TYPO3/CMS/Z7Semantilizer/Backend/Module', 'TYPO3/CMS/Z7Semantilizer/Backend/Translate'], (Notification, ImmediateAction, Converter, Headline, Module, translate) => {
  class Error {
    static mainHeadingRequired(headline, targetType) {
      headline.addError('mainHeadingRequired', 4, targetType);
    }

    static mainHeadingNumber(headline, targetType) {
      headline.addError('mainHeadingNumber', 2, targetType);
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
      const notificationCodes = [];

      this.headlines.filter(headline => headline.error.length).forEach(headline => headline.error.forEach(error => {
        if (notificationCodes.indexOf(error.code) < 0) {
          Notification.warning(translate('notification.' + error.code + '.title'), translate('notification.' + error.code + '.description'), 10, [
            {label: 'Close message', action: new ImmediateAction(() => true)},
            {label: 'Fix error', action: new ImmediateAction(() => Notification.success('fixed!', '', 1))}
          ]);

          notificationCodes.push(error.code);
        }
      }));
    }

    hideAllNotifications() {
      const container = top.TYPO3.Notification.messageContainer;
      container && Converter.toArray(container.childNodes).forEach(message => container.removeChild(message));
    }

    revalidate() {
      this.headlines.forEach(headline => headline.error.length = 0);
      this.validate();
    }

    refresh(callback) {
      this.collect(request => {
        this.validate();

        if (typeof callback === 'function') {
          callback();
        }

        this.module.setHeadlines(this.headlines);
        this.module.drawStructure();

        this.showNotifications();
      });
    }

    init() {
      this.refresh();
    }
  }

  // Return the object
  return Semantilizer;
});
