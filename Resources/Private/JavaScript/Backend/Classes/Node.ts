export class Node {
  private readonly element: HTMLDivElement;

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
