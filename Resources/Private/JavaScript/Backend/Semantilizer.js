/**
 * Module: TYPO3/CMS/Z7Semantilizer/Backend/Semantilizer
 */
define(['TYPO3/CMS/Backend/Notification', 'TYPO3/CMS/Backend/ActionButton/ImmediateAction'], (Notification, ImmediateAction) => {

  class Convert {
    static toArray(list) {
      return Array.prototype.slice.call(list);
    }

    static toInteger(value) {
      const int = parseInt(value);

      return isNaN(int) ? parseInt((value || '').replace(/[^0-9]/i, '')) : int;
    }
  }

  class Node {
    constructor(nodeType) {
      this.element = document.createElement(nodeType || 'div');

      return this;
    }

    setAttribute(attribute, value) {
      this.element.setAttribute(attribute, value);

      return this;
    }

    setAttributes(object) {
      Object.keys(object).forEach(key => this.setAttribute(key, object[key]));

      return this;
    }

    setStyles(styles) {
      if (this.element && styles) {
        Object.keys(styles).forEach(attribute => {
          this.element.style[attribute] = styles[attribute];
        });
      }

      return this;
    }

    setClassName(className) {
      this.element.className = className;

      return this;
    }

    setBemClassName(element, modifier, block) {
      return this.setClassName((block || 'semantilizer') + (element ? ('__' + element) : '') + (modifier ? ('--' + modifier) : ''));
    }

    setContent(string) {
      this.element.innerHTML = string;

      return this;
    }

    appendTo(parent) {
      return parent.appendChild(this.render());
    }

    render() {
      return this.element;
    }
  }


  class Module {
    element = null;
    headlines = [];

    constructor(element) {
      this.element = element;

      this.init();
    }

    setHeadlines(headlines) {
      this.headlines = headlines;
    }

    clearContent(node) {
      let firstChild;
      while (firstChild = (node || this.element).firstElementChild) {
        (node || this.element).removeChild(firstChild);
      }
    }

    drawList() {
      const wrap = new Node('div').setBemClassName('listwrap').appendTo(this.element);
      const list = new Node('ul').setBemClassName('list').appendTo(wrap);

      this.headlines.forEach(headline => {
        const item = new Node('li').setBemClassName('item', headline.type).appendTo(list);
        const select = new Node('select').setBemClassName('select').appendTo(item);

        for (let i = 1; i <= 6; i++) {
          let option = new Node('option').setAttributes({value: 'url'}).setContent('H' + i).appendTo(select);

          if(headline.type === i) {
            option.selected = true;
          }
        }

        const link = new Node('a').setAttribute('href', '#').setContent(headline.text).appendTo(item);
      });
    }

    draw() {
      this.clearContent();
      this.drawList();
    }

    init() {

    }
  }

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

  class Headline {
    type = 0;
    text = '';
    error = [];
    table = '';
    id = 0;

    constructor(node) {
      this.type = Convert.toInteger(node.nodeName);
      this.text = node.innerText.trim();
      this.table = node.dataset.semantilizerTable;
      this.id = Convert.toInteger(node.dataset.semantilizerUid);
    }
  }

  class Semantilizer {
    headlines = [];
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

      request.onreadystatechange = () => {
        if (request.readyState === 4) {
          if (request.status === 200) {

            // Parse document
            const parser = new DOMParser();
            const doc = parser.parseFromString(request.responseText, 'text/html');
            const container = this.containerSelector ? doc.querySelector(this.containerSelector) : null;

            // Find headlines
            this.headlines = Convert.toArray((container || doc).querySelectorAll('h1, h2, h3, h4, h5, h6')).map(node => new Headline(node));

            // Run callback action
            if (typeof callback === 'function') {
              callback(request);
            }
          } else {
            this.error = {request: request}
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
          Error.mainHeadingPosition(firstHeadline, 1)

          this.headlines.forEach((headline, i) => {
            if (i && headline.type === 1) {
              Error.mainHeadingPosition(headline, 2)
            }
          });
        }
      };

      const validateStructure = () => {
        this.headlines.forEach((headline, i) => {
          if (i && headline.type > this.headlines[i - 1].type + 1) {
            Error.headingStructure(headline)
          }
        })
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
        this.module.draw();

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
