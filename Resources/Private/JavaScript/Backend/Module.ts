import {Semantilizer} from "@zeroseven/semantilizer/Semantilizer.js";
import {Node} from "@zeroseven/semantilizer/Node.js";
import {Translation} from "@zeroseven/semantilizer/Translation.js";
import Icons from "@typo3/backend/Icons.js";

export class Module {
  private readonly element: HTMLElement;
  private readonly parent: Semantilizer;
  private wrap: HTMLElement;

  constructor(element: HTMLElement, parent: Semantilizer) {
    this.element = element;
    this.parent = parent;

    this.init();
  }

  private clearContent(node?: HTMLElement): void {
    let firstChild;
    while (firstChild = (node || this.element).firstElementChild) {
      (node || this.element).removeChild(firstChild);
    }
  }

  private drawDescription(): void {
    if (this.parent.headlines.length) {
      Node.create('p').setContent(Translation.translate('overview.description')).appendTo(this.element);
    }
  }

  private drawList(): void {
    if (this.parent.headlines.length) {
      const wrap = Node.create('div').setBemClassName('listwrap').appendTo(this.element);
      const list = Node.create('ul').setBemClassName('list').appendTo(wrap);

      this.parent.headlines.forEach((headline, i) => {
        const item = Node.create('li').setBemClassName('item', 'level' + headline.type).appendTo(list);

        if (headline.isEditableType()) {
          const select = Node.create('select').setBemClassName('control', 'level' + headline.type).addAttribute('data-index', i)
            .addEventListener('change', (event: Event) => {
              const type = (event.target as HTMLSelectElement).options[(event.target as HTMLSelectElement).selectedIndex].value;
              headline.update(type);
            }).appendTo(item) as HTMLSelectElement;

          for (let i = 1; i <= 6; i++) {
            let option = Node.create('option').addAttributes({value: i}).setContent('H' + i).appendTo(select) as HTMLOptionElement;

            if (headline.type === i) {
              option.selected = true;
            }
          }
        } else {
          const button = Node.create('button').setBemClassName('control', 'level' + headline.type).addAttribute('type', 'button').setContent('H' + headline.type).appendTo(item) as HTMLButtonElement;

          if (headline.isRelated() && headline.relatedHeadline().isEditableType()) {
            button.setAttribute('data-related-to', headline.edit.relatedTo);
            button.addEventListener('click', (event: MouseEvent) => {
              const relations = list.querySelectorAll('[data-index="' + this.parent.headlines.indexOf(headline.relatedHeadline()) + '"]');
              relations && (relations[relations.length - 1] as HTMLElement).focus();
              event.preventDefault();
            });
          } else {
            button.disabled = true;
          }
        }

        const text = headline.isEditableRecord() ? Node.create('a').addAttribute('href', headline.getEditUrl()) : Node.create('span');

        text.setContent(headline.text).setBemClassName('headline', headline.hasIssues() ? 'error' : '').appendTo(item);

        if (headline.hasIssues()) {
          const issueInfo = Node.create('button').addAttributes({
            'type': 'button',
            'title': Translation.translate('overview.notification.show')
          }).setBemClassName('issue-info').appendTo(item);
          issueInfo.addEventListener('click', headline.showIssue);
        }
      });

      Icons.getIcon('actions-refresh', Icons.sizes.small).then(icon => Node.create('button').addAttribute('type', 'button')
        .addAttribute('title', Translation.translate('overview.refresh')).setContent(icon).setBemClassName('refresh')
        .addEventListener('click', () => this.parent.refresh(true)).appendTo(this.element));

      this.wrap = wrap;
    } else {
      Node.create('p').setContent(Translation.translate('overview.empty')).appendTo(this.element);
    }
  }

  private drawNotificationToggle(): void {
    const enabled = this.parent.notification.autoload.enabled();

    Icons.getIcon(enabled ? 'actions-toggle-on' : 'actions-toggle-off', Icons.sizes.small).then(icon => {
      const toggle = Node.create('button').addAttribute('type', 'button').setContent(Translation.translate(enabled ? 'overview.notifications.on' : 'overview.notifications.off')).setBemClassName('notifications-toggle').appendTo(this.element);
      toggle.insertAdjacentHTML('afterbegin', icon + ' ');
      toggle.addEventListener('click', () => {
        if (enabled) {
          this.parent.notification.hideAll();
          this.parent.notification.autoload.disable();
        } else {
          this.parent.notification.showIssues();
          this.parent.notification.autoload.enable();
        }

        this.element.removeChild(toggle);
        this.drawNotificationToggle();
      });
    });
  }

  public lockStructure(): void {
    this.parent.notification.hideAll();

    const overlay = Node.create('div').setBemClassName('lock').appendTo(this.wrap);
    Node.create('span').setBemClassName('lock-message').setContent(Translation.translate('overview.update')).appendTo(overlay);
  }

  public drawStructure(): void {
    this.clearContent();
    this.drawDescription();
    this.drawList();
    this.drawNotificationToggle();
  }

  public drawError(): void {
    this.clearContent();
    Node.create('p').setContent(Translation.translate('overview.error')).appendTo(this.element);
  }

  public loader(): void {
    Icons.getIcon('spinner-circle', Icons.sizes.small).then(icon => {
      this.element.insertAdjacentHTML('beforeend', icon + '<span style="margin-left: 0.3em">' + Translation.translate('overview.loading') + '</span>');
    });
  }

  private init(): void {
    this.clearContent();
  }
}
