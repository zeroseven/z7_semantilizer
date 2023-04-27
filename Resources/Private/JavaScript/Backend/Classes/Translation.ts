export class Translation {
  static translate(key: string) {
    return window.TYPO3.lang[key] || key;
  }
}
