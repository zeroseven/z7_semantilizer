import {Semantilizer} from "@zeroseven/semantilizer/Semantilizer.js";
import {Translation} from "@zeroseven/semantilizer/Translation.js";
import {Cast} from "@zeroseven/semantilizer/Cast.js";
import {Issue} from "@zeroseven/semantilizer/Issue.js";
import TYPO3Notification from "@typo3/backend/notification.js";
import ImmediateAction from "@typo3/backend/action-button/immediate-action.js";

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

  public showIssue(issue: Issue): void {
    this.hideAll();

    const relatedIssues = this.parent.issues.filter(relation => relation.key === issue.key);
    const buttons = [];

    if (relatedIssues.filter(issue => issue.suggestion && issue.headline.isEditableType()).length) {
      buttons.push({
        label: Translation.translate('notification.fix'),
        action: new ImmediateAction(() => {
            relatedIssues.forEach(issue => issue.fix(false));

            this.parent.update(response => TYPO3Notification.success(Translation.translate('notification.fixed.title'), Translation.translate('notification.' + issue.key + '.title'), 4));
          }
        )
      });
    }

    const layout = issue.key === 'mainHeadingNumber' ? 'info' : 'warning';
    TYPO3Notification[layout](Translation.translate('notification.' + issue.key + '.title'), Translation.translate('notification.' + issue.key + '.description'), 10, buttons);
  }

  public showIssues(): void {
    const firstIssue = this.parent.issues[0];
    firstIssue && this.showIssue(firstIssue);
  }

  public hideAll(): void {
    // @ts-ignore
    let container = (window.top.TYPO3 && window.top.TYPO3.Notification && window.top.TYPO3.Notification.messageContainer) ? window.top.TYPO3.Notification.messageContainer : 'null';

    container && Cast.array(container.childNodes).forEach(message => container.removeChild(message));
  }
}
