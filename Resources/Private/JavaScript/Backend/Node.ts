import {Cast} from "@zeroseven/semantilizer/Cast.js";

export class Node {
  private readonly element: HTMLElement;

  constructor(nodeType?: string) {
    this.element = document.createElement(nodeType || 'div');

    return this;
  }

  public addAttribute(attribute: string, value: string | number | boolean): this {
    if (value !== null && typeof value !== 'undefined') {
      this.element.setAttribute(attribute, Cast.string(value));
    }

    return this;
  }

  public addAttributes(object: object): this {
    Object.keys(object).forEach(key => this.addAttribute(key, (object as any)[key]));

    return this;
  }

  public addEventListener(event: string, func: EventListenerOrEventListenerObject): this {
    this.element.addEventListener(event, func);

    return this;
  }

  public setStyle(attribute: string, value: any): this {
    this.element.style[attribute] = Cast.string(value);

    return this;
  }

  public setStyles(styles: { [attribute: string]: string }): this {
    styles && Object.keys(styles).forEach(attribute => {
      this.setStyle(attribute, styles[attribute]);
    });

    return this;
  }

  public addClassName(className: string): this {
    this.element.classList.add(className);

    return this;
  }

  public setClassName(className: string): this {
    this.element.className = className;

    return this;
  }

  public setBemClassName(element?: string, modifier?: string): this {
    return this.setClassName('semantilizer' + (element ? ('__' + element) : '') + (modifier ? ('--' + modifier) : ''));
  }

  public setContent(content: string | number, nl2br?: boolean): this {
    let text = Cast.string(content);

    if (nl2br === true) {
      text = text.replace(/(?:\r\n|\r|\n)/g, '<br>');
    }

    this.element.innerHTML = text;

    return this;
  }

  public prependTo(parent: Element | HTMLElement): HTMLElement {
    return parent.insertBefore(this.render(), parent.firstChild);
  }

  public appendTo(parent: Element | HTMLElement): HTMLElement {
    return parent.appendChild(this.render());
  }

  public insertBefore(target: Element | HTMLElement): HTMLElement {
    return (target.parentElement || document.body).insertBefore(this.render(), target);
  }

  public render(): HTMLElement {
    return this.element;
  }

  public static create(nodeType?: string): Node {
    return new Node(nodeType);
  }
}
