import {Headline} from "@zeroseven/semantilizer/Headline.js";
import {Module} from "@zeroseven/semantilizer/Module.js";
import {Notification} from "@zeroseven/semantilizer/Notification.js";
import {Cast} from "@zeroseven/semantilizer/Cast.js";
import {Issues} from "@zeroseven/semantilizer/Issues.js";

declare global {
  interface Window {
    TYPO3: any[]
    list_frame: any[]
  }
}

export class Semantilizer {
  public readonly url: string;
  public readonly contentSelectors: string[];
  public readonly module: Module;
  public readonly notification: Notification;
  public readonly headlines: Headline[];

  constructor(url: string, elementId: string, ...contentSelectors: string[]) {
    this.url = url;
    this.contentSelectors = Cast.array(contentSelectors || 'body');
    this.module = new Module(document.getElementById(elementId), this);
    this.notification = new Notification(this);
    this.headlines = [];

    // Bind methods
    this.validate = this.validate.bind(this);

    this.init();
  }

  private collect(callback: (request: XMLHttpRequest) => any): void {
    let request = new XMLHttpRequest();

    // Clear headlines
    this.headlines.length = 0;

    request.onreadystatechange = () => {
      if (request.readyState === 4) {
        if (request.status === 200) {

          // Parse document
          const parser = new DOMParser();
          const doc = parser.parseFromString(request.responseText, 'text/html');

          // Find headlines in contents
          this.contentSelectors.map(selector => doc.querySelector(selector)).filter(container => container).forEach(container => {
            this.headlines.push(...Cast.array((container || doc).querySelectorAll('h1, h2, h3, h4, h5, h6')).map(node => new Headline(node, this)));
          });
        }

        // Run callback function
        if (typeof callback === 'function') {
          callback(request);
        }
      }
    };

    request.open('GET', (this.url.indexOf('#') < 0 ? this.url : this.url.substr(0, this.url.indexOf('#'))) + '#' + Math.random().toString(36).slice(2), true);
    request.setRequestHeader('X-Semantilizer', 'true');
    request.send();
  }

  private validateStructure(): void {
    this.headlines.forEach(headline => headline.issues.clear());

    const validateMainHeadings = () => {
      const firstHeadline = this.headlines[0];
      const mainHeadlines = this.headlines.filter(headline => headline.type === 1);

      if (mainHeadlines.length === 0) {
        firstHeadline.issues.add(Issues.mainHeadingRequired, 1);
      }

      if (mainHeadlines.length > 1) {
        this.headlines.forEach((headline, i) => {
          if (headline.type === 1) {
            headline.issues.add(Issues.mainHeadingNumber, i ? 2 : null);
          }
        });
      }

      if (mainHeadlines.length === 1 && firstHeadline.type !== 1) {
        firstHeadline.issues.add(Issues.mainHeadingPosition, 1);

        this.headlines.forEach((headline, i) => {
          if (i && headline.type === 1) {
            headline.issues.add(Issues.mainHeadingPosition, 2);
          }
        });
      }
    };

    const validateStructure = () => {
      this.headlines.forEach((headline, i) => {
        if (i && headline.type > this.headlines[i - 1].type + 1) {
          headline.issues.add(Issues.headingStructure);
        }
      });
    };

    if (this.headlines.length) {
      validateMainHeadings();
      validateStructure();
    }
  }

  private validate(): void {
    this.validateStructure();
    this.module.drawStructure();
    this.notification.hideAll();
    this.notification.autoload.enabled() && this.notification.showIssues();
  }

  private refresh(lock?: boolean): void {
    lock === true && this.module.lockStructure();

    this.collect(request => {
      if (request.status === 200) {
        this.validate();
      } else {
        this.notification.hideAll();
        this.module.drawError();
      }
    });
  }

  public revalidate(hard?: boolean): void {
    if (hard === true) {
      this.module.drawStructure();
      this.notification.hideAll();
      this.refresh(true);
    } else {
      this.validate();
    }
  }

  private init(): void {
    this.refresh();

    // Add loader
    this.module.loader();

    // Watch the sorting of the content elements in the page module
    // TODO: require(['jquery', 'jquery-ui/droppable'], $ => $('.t3js-page-ce-dropzone-available').on('drop', () => this.refresh(true)));
  }
}
