import {Semantilizer} from "@zeroseven/semantilizer/Semantilizer.js";
import {Notification} from "@zeroseven/semantilizer/Notification.js";
import {EditConfiguration} from "@zeroseven/semantilizer/EditConfiguration.js";
import {Issue} from "@zeroseven/semantilizer/Issue.js";
import {Cast} from "@zeroseven/semantilizer/Cast.js";

export class Headline {
  private readonly parent: Semantilizer;
  public readonly edit: EditConfiguration;
  private _isModified: boolean;
  private _type: number;
  private _text: string;

  constructor(node: HTMLElement, parent: Semantilizer) {
    this.parent = parent;
    this.edit = new EditConfiguration(node);
    
    this._type = Cast.integer(node.nodeName.replace(/[^0-9]/, ''));
    this._text = Cast.string(node.innerText);
    this._isModified = false

    this.showIssue = this.showIssue.bind(this);
  }

  public get type(): number {
    return this._type;
  }

  public set type(type: any) {
    this._type = Math.min(Math.max(Cast.integer(type), 1), 6);
    this._isModified = true;
  }

  public get text(): string {
    return this._text;
  }

  public set text(value: string) {
    this._text = value.trim();
  }

  public getEditUrl(): string | null {
    if (this.isEditableRecord()) {
      // @ts-ignore
      const returnUrl = encodeURIComponent(window.top.list_frame.document.location.pathname + window.top.list_frame.document.location.search);

      // @ts-ignore
      return window.top.TYPO3.settings.FormEngine.moduleUrl + '&edit[' + this.edit.table + '][' + this.edit.uid + ']=edit&returnUrl=' + returnUrl;
    }

    return null;
  }

  public getIssues(): Issue[] {
    return this.parent.issues.filter(issue => issue.headline === this);
  }

  public hasIssues(): boolean {
    return this.getIssues().length > 0;
  }

  public showIssue(): void {
    const issues = this.getIssues().forEach(issue => this.parent.notification.showIssue(issue));
  }

  public hasRelations(): boolean {
    return this.edit.relationId && this.parent.headlines.filter((headline: Headline) => headline.edit.relatedTo === this.edit.relationId).length > 0;
  }

  public relatedHeadline(): Headline | null {
    if (this.edit.relatedTo) {
      const filtered = this.parent.headlines.filter((headline: Headline) => headline.edit.relationId === this.edit.relatedTo);

      // Return the last matched headline
      if (filtered.length) {
        return filtered[filtered.length - 1];
      }
    }

    return null;
  }

  public isRelated(): boolean {
    return this.relatedHeadline() !== null;
  }

  public isEditableRecord(): boolean {
    return this.edit.table && this.edit.uid > 0;
  }

  public isEditableType(): boolean {
    return this.isEditableRecord() && this.edit.field && !this.isRelated();
  }

  public isModified(): boolean {
    return this._isModified;
  }

  public hasStored(): void {
    this._isModified = false;
  }

  public update(type?: any): void {
    type && (this.type = type);

    this.parent.update();
  }
}
