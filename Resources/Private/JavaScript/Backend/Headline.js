define(['TYPO3/CMS/Z7Semantilizer/Backend/Converter'], Converter => {
  class Headline {
    type = 0;
    text = '';
    error = [];
    edit = null;

    constructor(node) {
      this.type = Converter.toInteger(node.nodeName);
      this.text = node.innerText.trim();

      const editSetup = node.dataset.semantilizer;

      if(editSetup) {
        try {
          this.edit = JSON.parse(editSetup);
        } catch (e) {
          typeof console.log === 'function' && console.log(e, 1640904719);
        }
      }
    }

    addError(code, priority, fix) {
      this.error.push({
        code: code,
        priority: priority,
        fix: fix
      });
    }
  }

  return Headline;
});
