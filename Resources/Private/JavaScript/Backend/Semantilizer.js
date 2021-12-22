define(['TYPO3/CMS/Backend/Notification', 'TYPO3/CMS/Backend/ActionButton/ImmediateAction', 'TYPO3/CMS/Z7Semantilizer/Backend/Converter', 'TYPO3/CMS/Z7Semantilizer/Backend/Headline', 'TYPO3/CMS/Z7Semantilizer/Backend/Module'], (Notification, ImmediateAction, Converter, Headline, Module) => {
  class Error {
    static mainHeadingRequired(headline, targetType) {
      headline.error.push({code: 'mainHeadingRequired', priority: 4, fix: targetType});
    }

    static mainHeadingNumber(headline, targetType) {
      headline.error.push({code: 'mainHeadingNumber', priority: 2, fix: targetType});
    }

    static mainHeadingPosition(headline, targetType) {
      headline.error.push({code: 'mainHeadingPosition', priority: 3, fix: targetType});
    }

    static headingStructure(headline, targetType) {
      headline.error.push({code: 'headingStructure', priority: 1, fix: targetType});
    }
  }

  class Semantilizer {
    headlines;
    url;
    containerSelector;
    module;

    constructor(url, elementId, containerSelector) {
      this.url = url;
      this.containerSelector = containerSelector;
      this.module = new Module(document.getElementById(elementId));

      this.init();
    }

    collect(callback) {
      let request = new XMLHttpRequest();

      this.module.loader();

      request.onreadystatechange = () => {
        if (request.readyState === 4) {
          if (request.status === 200) {

            // Parse document
            const parser = new DOMParser();
            const doc = parser.parseFromString(request.responseText, 'text/html');
            const container = this.containerSelector ? doc.querySelector(this.containerSelector) : null;

            // Find headlines
            this.headlines = Converter.toArray((container || doc).querySelectorAll('h1, h2, h3, h4, h5, h6')).map(node => new Headline(node));

            // Run callback action
            if (typeof callback === 'function') {
              callback(request);
            }
          } else {
            this.error = {request: request};
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

      validateMainHeadings();
      validateStructure();
    }

    showNotifications() {
      this.headlines.filter(headline => headline.error.length).forEach(headline => {
        headline.error.forEach(error => {
          Notification.warning(error.code, headline.text, 5, [
            {label: 'Close message', action: new ImmediateAction(() => true)},
            {label: 'Fix error', action: new ImmediateAction(() => Notification.success('fixed!', '', 1))}
          ]);
        });
      });
    }

    revalidate() {
      this.headlines.forEach(headline => headline.error.length = 0);
      this.validate();
    }

    refresh(callback) {
      this.collect(() => {
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
