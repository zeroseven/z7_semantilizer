import {Headline} from "@zeroseven/semantilizer/Headline.js";
import {Semantilizer} from "@zeroseven/semantilizer/Semantilizer.js";

export class Issue {
  public readonly key: string;
  public readonly headline: Headline;
  public readonly suggestion: number;

  constructor(key: string, headline: Headline, suggestion?: number) {
    this.key = key;
    this.headline = headline;
    this.suggestion = suggestion || 0;
  }

  public fix(store?: boolean): void {
    if (this.suggestion && this.headline.isEditableType()) {
      this.headline.type = this.suggestion;

      store === true && this.headline.update();
    }
  }

  public static mainHeadingRequired(headline: Headline, suggestion?: number): Issue {
    return new Issue('mainHeadingRequired', headline, suggestion);
  }

  public static mainHeadingNumber(headline: Headline, suggestion?: number): Issue {
    return new Issue('mainHeadingNumber', headline, suggestion);
  }

  public static mainHeadingPosition(headline: Headline, suggestion?: number): Issue {
    return new Issue('mainHeadingPosition', headline, suggestion);
  }

  public static headingStructure(headline: Headline, suggestion?: number): Issue {
    return new Issue('headingStructure', headline, suggestion);
  }
}
