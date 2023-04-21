import {Semantilizer} from "../Semantilizer";

export class Translation {
  translate(key) {
    return window.TYPO3.lang[key] || key;
  }
}
