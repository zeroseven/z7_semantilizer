export class Translation {
  static translate(key: string) {
    // @ts-ignore
    return window.TYPO3.lang[key] || key;
  }
}
