define(['TYPO3/CMS/Z7Semantilizer/Backend/Converter'], Converter => {
  class Headline {
    type = 0;
    text = '';
    error = [];
    table = '';
    id = 0;

    constructor(node) {
      this.type = Converter.toInteger(node.nodeName);
      this.text = node.innerText.trim();
      this.table = node.dataset.semantilizerTable;
      this.id = Converter.toInteger(node.dataset.semantilizerUid);
    }
  }

  return Headline;
});
