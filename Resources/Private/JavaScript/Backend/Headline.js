define(['TYPO3/CMS/Z7Semantilizer/Backend/Converter'], Converter => {
  class Headline {
    type = 0;
    text = '';
    error = [];
    edit = null;

    constructor(node) {
      this.setType(node.nodeName);
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

    setType(type) {
      return (this.type = Math.max(Math.min(Converter.toInteger(type), 6), 1));
    }

    addError(code, priority, fix, layout) {
      this.error.push({
        code: code,
        priority: priority,
        fix: fix,
        layout: layout || 'warning'
      });
    }
  }

  return Headline;
});
