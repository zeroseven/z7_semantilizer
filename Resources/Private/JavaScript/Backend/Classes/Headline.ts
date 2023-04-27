import {Semantilizer} from "@zeroseven/semantilizer/Semantilizer.js";
import {EditConfiguration} from "@zeroseven/semantilizer/EditConfiguration.js";
import {Issues} from "@zeroseven/semantilizer/Issues.js";
import {Cast} from "@zeroseven/semantilizer/Cast.js";
import AjaxDataHandler from "@typo3/backend/ajax-data-handler.js";
import ResponseInterface from '@typo3/backend/ajax-data-handler/response-interface.js';

export class Headline {
  private readonly parent: Semantilizer;
  public readonly edit: EditConfiguration;
  public readonly issues: Issues;
  public _type: number;
  public _text: string;

  constructor(node: HTMLElement, parent: Semantilizer) {
    this.parent = parent;
    this.edit = new EditConfiguration(node);
    this.issues = new Issues(this);
    this.type = node.nodeName.replace(/[^0-9]/, '');
    this.text = Cast.string(node.innerText);

    // Bind methods
    this.showIssues = this.showIssues.bind(this);
  }

  public get type(): number {
    return this._type;
  }

  public set type(type: any) {
    this._type = Math.min(Math.max(Cast.integer(type), 1), 6);
  }

  public get text(): string {
    return this._text;
  }

  public set text(value: string) {
    this._text = value.trim();
  }

  public store(callback?: (response: ResponseInterface, hasRelations: boolean) => any): void {
    if (this.isEditableType()) {
      Headline.storeHeadlines([this], callback);
    }
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

  public showIssues(): void {
    this.issues.count() && this.issues.each((issue, key) => this.parent.notifications.showIssue(key));
  }

  public static storeHeadlines(headlines: Headline[], callback?: (response: ResponseInterface, hasRelations: boolean) => any) {
    let parameters = {data: {} as {[key: string]: any}};
    let hasRelations = false;

    headlines.forEach(headline => {
      if (headline.isEditableType()) {
        const table = headline.edit.table;
        const uid = headline.edit.uid;
        const field = headline.edit.field;

        parameters.data[table] = parameters.data[table] || {};
        parameters.data[table][uid] = {};
        parameters.data[table][uid][field] = headline.type;
      }

      headline.hasRelations() && (hasRelations = true);
    });

    Object.keys(parameters).length && AjaxDataHandler.process(parameters).then(response => typeof callback === 'function' && callback(response, hasRelations));
  }
}
