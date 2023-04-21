import {Semantilizer} from "../Semantilizer";
import {Headline} from "./Headline";
import Notification from "@typo3/backend/notification.js";

class IssueMessage {
  constructor(key, headlines) {
    this.headlines = headlines || [];
    this.key = key;
  }

  addHeadline(headline) {
    this.headlines.push(headline);
  }

  render(callback) {
    const fixableHeadlines = this.headlines.filter(headline => headline.isEditableType() && headline.issues.get(this.key).fix).length;
    const buttons = [];

    if (fixableHeadlines) {
      buttons.push({
        label: translate('notification.fix') + (fixableHeadlines > 1 ? ' (' + fixableHeadlines + ')' : ''),
        action: new ImmediateAction(() => {
          this.headlines.forEach(headline => headline.issues.fix(this.key));
          Headline.storeHeadlines(this.headlines, (response, hasRelations) => {
            typeof callback === 'function' && callback(hasRelations);
            TYPO3Notification.success(translate('notification.fixed.title'), translate('notification.' + this.key + '.title'), 4);
          });
        })
      });
    }

    const layout = this.key === 'mainHeadingNumber' ? 'info' : 'warning';
    TYPO3Notification[layout](translate('notification.' + this.key + '.title'), translate('notification.' + this.key + '.description'), 10, buttons);
  }
}

class State {
  private readonly key: string;

  constructor(key: string, defaultState: boolean) {
    this.key = key;

    if (this.get() === null) {
      this.set(defaultState);
    }
  }

  get() {
    const value = localStorage.getItem(this.key);
    return value === null ? null : parseInt(value);
  }

  set(state) {
    localStorage.setItem(this.key, state ? '1' : '0');
  }

  enabled() {
    return this.get() === 1;
  }

  disabled() {
    return !this.enabled();
  }

  enable() {
    this.set(1);
  }

  disable() {
    this.set(0);
  }
}

export class Notification {
  private parent: Semantilizer;
  private autoload: State;

  constructor(parent: Semantilizer) {
    this.parent = parent;
    this.autoload = new State('semantilizer-notification', true);

    return this;
  }

  showIssue(key) {
    this.parent.notification.hideAll();
    new IssueMessage(key, this.parent.headlines.filter(headline => headline.issues.has(key))).render(hasRelations => this.parent.revalidate(hasRelations));
  }

  showIssues() {
    const keys = {};

    this.parent.headlines.filter(headline => headline.issues.count()).forEach(headline => headline.issues.each(issue => {
      keys[issue.key] = keys[issue.key] || [];
      keys[issue.key].push(headline);
    }));

    Object.keys(keys).forEach(key => new IssueMessage(key, keys[key]).render(hasRelations => this.parent.revalidate(hasRelations)));
  }

  hideAll() {
    let container = TYPO3Notification.messageContainer;

    // Workaround for TYPO3 10
    if (container && typeof container[0] !== 'undefined') {
      container = container[0];
    }

    container && Converter.toArray(container.childNodes).forEach(message => container.removeChild(message));
  }
}
