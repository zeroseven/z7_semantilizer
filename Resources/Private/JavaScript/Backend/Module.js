define(['TYPO3/CMS/Z7Semantilizer/Backend/Node', 'TYPO3/CMS/Backend/Icons'], (Node, Icons) => {
  class Module {
    element;
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

    drawStructure() {
      this.clearContent();
      this.drawList();
    }

    loader(content) {
      this.clearContent();

      Icons.getIcon('spinner-circle', Icons.sizes.small).then(icon => {
        this.element.insertAdjacentHTML('beforeend', icon + '<span>' + (content || 'Loading') + '</span>');
      });
    }

    init() {
      this.clearContent();
    }
  }

  return Module;
});
