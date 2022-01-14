define(['TYPO3/CMS/Backend/Icons', 'TYPO3/CMS/Z7Semantilizer/Backend/Translate'], (Icons, translate) => {
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
    element;
    parent;

    constructor(element, parent) {
      this.element = element;
      this.parent = parent;

      this.init();
    }

    clearContent(node) {
      let firstChild;
      while (firstChild = (node || this.element).firstElementChild) {
        (node || this.element).removeChild(firstChild);
      }
    }

    drawDescription() {
      if (this.parent.headlines.length) {
        new Node('p').setContent(translate('overview.description')).appendTo(this.element);
      }
    }

    drawList() {
      if (this.parent.headlines.length) {
        const wrap = new Node('div').setBemClassName('listwrap').appendTo(this.element);
        const list = new Node('ul').setBemClassName('list').appendTo(wrap);

        this.parent.headlines.forEach(headline => {
          const item = new Node('li').setBemClassName('item', 'level' + headline.type).appendTo(list);
          const select = new Node('select').setBemClassName('select', 'level' + headline.type).appendTo(item);

          if (headline.isEditableType()) {
            for (let i = 1; i <= 6; i++) {
              let option = new Node('option').setAttributes({value: i}).setContent('H' + i).appendTo(select);

              if (headline.type === i) {
                option.selected = true;
              }
            }

            select.addEventListener('change', event => {
              headline.type = event.target.options[event.target.selectedIndex].value;
              headline.store(response => !response.hasErrors && this.parent.validate());
            });
          } else {
            new Node('option').setContent('H' + headline.type).appendTo(select);
            select.disabled = 'disabled';
          }

          const hasIssues = headline.issues.count();
          const text = headline.isEditableRecord() ? new Node('a').setAttribute('href', headline.getEditUrl()) : new Node('span');

          text.setContent(headline.text).setBemClassName('headline', hasIssues ? 'error' : '').appendTo(item);

          if (hasIssues) {
            const issueInfo = new Node('button').setAttributes({'type': 'button', 'title': translate('overview.notification.show')}).setBemClassName('issue-info').appendTo(item);
            issueInfo.addEventListener('click', headline.showIssues);
          }
        });

        this.wrap = wrap;
      } else {
        new Node('p').setContent(translate('overview.empty')).appendTo(this.element);
      }
    }

    drawNotificationToggle() {
      const enabled = this.parent.notifications.autoload.enabled();

      Icons.getIcon(enabled ? 'actions-toggle-on' : 'actions-toggle-off', Icons.sizes.small).then(icon => {
        const toggle = new Node('button').setAttribute('type', 'button').setContent(translate(enabled ? 'overview.notifications.on' : 'overview.notifications.off')).setBemClassName('notifications-toggle').appendTo(this.element);
        toggle.insertAdjacentHTML('afterbegin', icon + ' ');
        toggle.addEventListener('click', () => {
          if (enabled) {
            this.parent.notifications.hideAll();
            this.parent.notifications.autoload.disable();
          } else {
            this.parent.notifications.showIssues();
            this.parent.notifications.autoload.enable();
          }

          this.element.removeChild(toggle);
          this.drawNotificationToggle();
        });
      });
    }

    lockStructure() {
      this.parent.notifications.hideAll();

      const overlay = new Node('div').setBemClassName('lock').appendTo(this.wrap);
      new Node('span').setBemClassName('lock-message').setContent(translate('overview.update')).appendTo(overlay);
    }

    drawStructure() {
      this.clearContent();
      this.drawDescription();
      this.drawList();
      this.drawNotificationToggle();
    }

    drawError() {
      this.clearContent();
      new Node('p').setContent(translate('overview.error')).appendTo(this.element);
    }

    loader() {
      Icons.getIcon('spinner-circle', Icons.sizes.small).then(icon => {
        this.element.insertAdjacentHTML('beforeend', icon + '<span style="margin-left: 0.3em">' + translate('overview.loading') + '</span>');
      });
    }

    init() {
      this.clearContent();
    }
  }

  return Module;
});
