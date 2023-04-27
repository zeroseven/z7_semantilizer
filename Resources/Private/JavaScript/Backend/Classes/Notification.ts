import {Semantilizer} from "@zeroseven/semantilizer/Semantilizer.js";
import {Headline} from "@zeroseven/semantilizer/Headline.js";
import {Translation} from "@zeroseven/semantilizer/Translation.js";
import {Cast} from "@zeroseven/semantilizer/Cast.js";
import TYPO3Notification from "@typo3/backend/notification.js";
import ImmediateAction from "@typo3/backend/action-button/immediate-action.js";

class IssueMessage {
  private readonly key: string;
  private readonly headlines: Headline[];

  constructor(key: string, headlines: Headline[]) {
    this.key = key;
    this.headlines = headlines || [];
  }

  addHeadline(headline: Headline) {
    this.headlines.push(headline);
  }

  render(callback: (hasRealtions: boolean) => any) {
    const fixableHeadlines = this.headlines.filter(headline => headline.isEditableType() && headline.issues.get(this.key).fix).length;
    const buttons = [];

    if (fixableHeadlines) {
      buttons.push({
        label: Translation.translate('notification.fix') + (fixableHeadlines > 1 ? ' (' + fixableHeadlines + ')' : ''),
        action: new ImmediateAction(() => {
          this.headlines.forEach(headline => headline.issues.fix(this.key));
          Headline.storeHeadlines(this.headlines, (response, hasRelations) => {
            typeof callback === 'function' && callback(hasRelations);
            TYPO3Notification.success(Translation.translate('notification.fixed.title'), Translation.translate('notification.' + this.key + '.title'), 4);
          });
        })
      });
    }

    const layout = this.key === 'mainHeadingNumber' ? 'info' : 'warning';
    TYPO3Notification[layout](Translation.translate('notification.' + this.key + '.title'), Translation.translate('notification.' + this.key + '.description'), 10, buttons);
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

  public get(): number {
    return Cast.integer(localStorage.getItem(this.key));
  }

  public set(state: boolean): void {
    localStorage.setItem(this.key, state ? '1' : '0');
  }

  public enabled(): boolean {
    return this.get() === 1;
  }

  public disabled(): boolean {
    return !this.enabled();
  }

  public enable(): void {
    this.set(true);
  }

  public disable(): void {
    this.set(false);
  }
}

export class Notification {
  private parent: Semantilizer;
  public autoload: State;

  constructor(parent: Semantilizer) {
    this.parent = parent;
    this.autoload = new State('semantilizer-notification', true);

    return this;
  }

  public showIssue(key: string): void {
    this.parent.notification.hideAll();
    new IssueMessage(key, this.parent.headlines.filter(headline => headline.issues.has(key))).render(hasRelations => this.parent.revalidate(hasRelations));
  }

  public showIssues(): void {
    const keys = {};

    this.parent.headlines.filter(headline => headline.issues.count()).forEach(headline => headline.issues.each(issue => {
      keys[issue.key] = keys[issue.key] || [];
      keys[issue.key].push(headline);
    }));

    Object.keys(keys).forEach(key => new IssueMessage(key, keys[key]).render(hasRelations => this.parent.revalidate(hasRelations)));
  }

  public hideAll(): void {
    let container = window.TYPO3 && window.TYPO3.Notification && window.TYPO3.Notification.messageContainer ? window.TYPO3.Notification.messageContainer : 'null';

    container && Cast.array(container.childNodes).forEach(message => container.removeChild(message));
  }
}
