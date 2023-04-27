export class Translation {
    static translate(key) {
        return window.TYPO3.lang[key] || key;
    }
}
