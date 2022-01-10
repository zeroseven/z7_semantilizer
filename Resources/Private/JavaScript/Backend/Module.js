define(['TYPO3/CMS/Backend/Icons', 'TYPO3/CMS/Z7Semantilizer/Backend/ErrorNotification', 'TYPO3/CMS/Z7Semantilizer/Backend/Node', 'TYPO3/CMS/Z7Semantilizer/Backend/Edit', 'TYPO3/CMS/Z7Semantilizer/Backend/Translate'], (Icons, ErrorNotification, Node, Edit, translate) => {
  class Module {
    element;
    parent;
    headlines = [];

    constructor(element, parent) {
      this.element = element;
      this.parent = parent;

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
      if (this.headlines.length) {
        new Node('p').setContent(translate('overview.description')).appendTo(this.element);
        const wrap = new Node('div').setBemClassName('listwrap').appendTo(this.element);
        const list = new Node('ul').setBemClassName('list').appendTo(wrap);

        this.headlines.forEach(headline => {
          const item = new Node('li').setBemClassName('item', 'level' + headline.type).appendTo(list);
          const select = new Node('select').setBemClassName('select').appendTo(item);

          if (headline.edit.isEditableType()) {
            for (let i = 1; i <= 6; i++) {
              let option = new Node('option').setAttributes({value: i}).setContent('H' + i).appendTo(select);

              if (headline.type === i) {
                option.selected = true;
              }
            }

            select.addEventListener('change', event => {
              headline.type = event.target.options[event.target.selectedIndex].value;
              headline.update(response => {
                if (!response.hasErrors) {
                  this.parent.validate();
                }
              });
            });
          } else {
            new Node('option').setContent('H' + headline.type).appendTo(select);
            select.disabled = 'disabled';
          }

          const text = headline.edit.isEditableRecord() ? new Node('a').setAttribute('href', headline.getEditUrl()) : new Node('span');

          text.setContent(headline.text).setBemClassName('headline', headline.errors.count() ? 'error' : '').appendTo(item);
        });

        this.wrap = wrap;
      } else {
        new Node('p').setContent(translate('overview.empty')).appendTo(this.element);
      }
    }

    lockStructure() {
      ErrorNotification.hideAll();

      const overlay = new Node('div').setBemClassName('lock').appendTo(this.wrap);
      new Node('span').setBemClassName('lock-message').setContent(translate('overview.update')).appendTo(overlay);
    }

    drawStructure() {
      this.clearContent();
      this.drawList();
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
