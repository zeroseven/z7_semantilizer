
define(['TYPO3/CMS/Backend/Notification', 'TYPO3/CMS/Backend/ActionButton/ImmediateAction', 'TYPO3/CMS/Z7Semantilizer/Backend/Converter', 'TYPO3/CMS/Z7Semantilizer/Backend/Headline', 'TYPO3/CMS/Z7Semantilizer/Backend/Module', 'TYPO3/CMS/Z7Semantilizer/Backend/ErrorNotification'], (Notification, ImmediateAction, Converter, Headline, Module, ErrorNotification) => {
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

      // Bind methods
      this.validate = this.validate.bind(this);

      this.init();
    }

    collect(callback) {
      let request = new XMLHttpRequest();

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
      request.setRequestHeader('X-Semantilizer', 'true');
      request.send();
    }

    validateStructure() {
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

    validate() {
      this.validateStructure();
      ErrorNotification.hideAll();
      ErrorNotification.showErrors(this.headlines, this.validate);

      this.module.drawStructure();
    }

    refresh(callback) {
      this.collect(() => {
        if (typeof callback === 'function') {
          callback();
        }

        this.module.setHeadlines(this.headlines);
        this.validate();
      });
    }

    init() {
      this.refresh();

      // Add loader
      this.module.loader();

      // Watch the sorting of the content elements in the page module
      require(['jquery', 'jquery-ui/droppable'], $ => $('.t3js-page-ce-dropzone-available').on('drop', () => {
        this.module.lockStructure();
        this.refresh();
      }));
    }
  }

  // Return the object
  return Semantilizer;
});
