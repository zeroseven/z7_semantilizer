import {Headline} from "@zeroseven/semantilizer/Headline.js";

class Issue {
  public readonly key: string;
  public readonly fix: number;

  constructor(key: string, fix?: number) {
    this.key = key;
    this.fix = fix || 0;
  }
}

type Key = 'mainHeadingRequired' | 'mainHeadingNumber' | 'mainHeadingPosition' | 'headingStructure';

export class Issues {
  private readonly parent: Headline;
  private readonly list: { [index: string]: Issue | null };

  constructor(parent: Headline) {
    this.list = {};

    this.parent = parent;
  }

  public count(): number {
    let count = 0;

    Object.keys(this.list).forEach((key => (count += this.list[key] === null ? 0 : 1)));

    return count;
  }

  private empty(): boolean {
    return this.count() === 0;
  }

  public add(key: Key, fix?: number): void {
    this.list[key] = new Issue(key, fix);
  }

  public each(callback: (issue: Issue, key: string) => any): void {
    return Object.keys(this.list).filter(key => this.list[key]).forEach(key => callback(this.list[key], key));
  }

  public get(key: Key): Issue {
    return this.list[key] || null;
  }

  public has(key: Key): boolean {
    return this.get(key) !== null;
  }

  public remove(key: Key): void {
    this.list[key] = null;
  }

  public clear(): void {
    Object.keys(this.list).forEach(key => this.list[key] = null);
  }

  public fix(key: Key, store?: boolean): void {
    const issue = this.get(key);

    if (issue && issue.fix && this.parent.isEditableType()) {
      this.parent.type = issue.fix;
      this.remove(key);

      store === true && this.parent.store();
    }
  }
}
