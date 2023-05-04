import {Headline} from "@zeroseven/semantilizer/Headline.js";
import {Module} from "@zeroseven/semantilizer/Module.js";
import {Notification} from "@zeroseven/semantilizer/Notification.js";
import {Cast} from "@zeroseven/semantilizer/Cast.js";
import {Issue} from "@zeroseven/semantilizer/Issue.js";
import AjaxDataHandler from "@typo3/backend/ajax-data-handler.js";
import ResponseInterface from '@typo3/backend/ajax-data-handler/response-interface.js';
import interact from 'interactjs';

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
  public readonly issues: Issue[];

  constructor(url: string, elementId: string, ...contentSelectors: string[]) {
    this.url = url;
    this.contentSelectors = Cast.array(contentSelectors || 'body');
    this.module = new Module(document.getElementById(elementId), this);
    this.notification = new Notification(this);
    this.headlines = [];
    this.issues = [];

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
    this.issues.length = 0; // Reset issues

    const validateMainHeadings = () => {
      const firstHeadline = this.headlines[0];
      const mainHeadlines = this.headlines.filter(headline => headline.type === 1);

      if (mainHeadlines.length === 0) {
        this.issues.push(Issue.mainHeadingRequired(firstHeadline, 1));
      }

      if (mainHeadlines.length > 1) {
        this.headlines.forEach((headline, i) => {
          if (headline.type === 1) {
            this.issues.push(Issue.mainHeadingNumber(headline, i ? 2 : null));
          }
        });
      }

      if (mainHeadlines.length === 1 && firstHeadline.type !== 1) {
        this.issues.push(Issue.mainHeadingPosition(firstHeadline, 1));

        this.headlines.forEach((headline, i) => {
          if (i && headline.type === 1) {
            this.issues.push(Issue.mainHeadingPosition(headline, 2));
          }
        });
      }
    };

    const validateStructure = () => {
      this.headlines.forEach((headline, i) => {
        if (i && headline.type > this.headlines[i - 1].type + 1) {
          this.issues.push(Issue.headingStructure(headline));
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

  public refresh(lock?: boolean): void {
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

  public update(callback?: (response: ResponseInterface) => any) {
    const headlines = this.headlines.filter(headline => headline.isModified() && headline.isEditableType());
    const parameters = {data: {} as { [key: string]: any }};

    let hasRelations = false;

    headlines.forEach(headline => {
      const table = headline.edit.table;
      const uid = headline.edit.uid;
      const field = headline.edit.field;

      parameters.data[table] = parameters.data[table] || {};
      parameters.data[table][uid] = {};
      parameters.data[table][uid][field] = headline.type;

      headline.hasRelations() && (hasRelations = true);
    });

    Object.keys(parameters).length && AjaxDataHandler.process(parameters).then(response => {

      // Revalidate
      !response.hasErrors && this.revalidate(hasRelations);

      // Update heading properties
      headlines.forEach(headline => headline.hasStored());

      // Run callback function
      typeof callback === 'function' && callback(response);
    });
  }

  private init(): void {
    this.refresh();

    // Add loader
    this.module.loader();

    // Watch the sorting of the content elements in the page module
    interact('.t3js-page-ce-sortable').draggable({onend: () => this.refresh(true)})
  }
}
