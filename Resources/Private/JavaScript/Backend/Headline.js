define(['TYPO3/CMS/Backend/AjaxDataHandler', 'TYPO3/CMS/Z7Semantilizer/Backend/Converter'], (AjaxDataHandler, Converter) => {
  class EditConfiguration {
    constructor(node) {
      let editConfigData = {};

      if (node.dataset.semantilizer) {
        try {
          editConfigData = JSON.parse(node.dataset.semantilizer);
        } catch (e) {
          typeof console.log === 'function' && console.log(e, 1640904719);
        }
      }

      ['table', 'uid', 'field', 'referenceId', 'relatedTo'].forEach(key => this['_' + key] = editConfigData[key] || null);
    }

    get table() {
      return this._table;
    }

    set table(value) {
      this._table = (value || '').trim();
    }

    get uid() {
      return this._uid;
    }

    set uid(value) {
      this._uid = Converter.toInteger(value);
    }

    get field() {
      return this._field;
    }

    set field(value) {
      this._field = (value || '').trim();
    }

    get referenceId() {
      return this._referenceId;
    }

    set referenceId(value) {
      this._referenceId = (value || '').trim();
    }

    get relatedTo() {
      return this._relatedTo;
    }

    set relatedTo(value) {
      this._relatedTo = (value || '').trim();
    }
  }

  class Issue {
    constructor(key, fix) {
      this.key = key;
      this.fix = fix;
    }
  }

  class Issues {
    static mainHeadingRequired = 'mainHeadingRequired';
    static mainHeadingNumber = 'mainHeadingNumber';
    static mainHeadingPosition = 'mainHeadingPosition';
    static headingStructure = 'headingStructure';

    constructor(parent) {
      this.list = {};
      this.list[Issues.mainHeadingRequired] = null;
      this.list[Issues.mainHeadingNumber] = null;
      this.list[Issues.mainHeadingPosition] = null;
      this.list[Issues.headingStructure] = null;

      this.parent = parent;
    }

    count() {
      let count = 0;

      Object.keys(this.list).forEach(key => (count += this.list[key] ? 1 : 0));

      return count;
    }

    empty() {
      return this.count() === 0;
    }

    add(key, fix) {
      if (this.list[key] !== 'undefined') {
        this.list[key] = new Issue(key, fix);
      } else {
        console.warn('Not allowed error key "' + key + '"', 1641814278);
      }
    }

    each(callback) {
      return Object.keys(this.list).filter(key => this.list[key]).forEach(key => callback(this.list[key], key));
    }

    get(key) {
      return this.list[key] || null;
    }

    has(key) {
      return this.get(key) !== null;
    }

    remove(key) {
      this.list[key] = null;
    }

    clear() {
      Object.keys(this.list).forEach(key => this.list[key] = null);
    }

    fix(key, store) {
      const issue = this.get(key);

      if (issue && issue.fix && this.parent.isEditableType()) {
        this.parent.type = issue.fix;
        this.remove(key);

        store === true && this.parent.store();
      }
    }
  }

  class Headline {
    constructor(node, parent) {
      this.type = node.nodeName;
      this.text = node.innerText;
      this.parent = parent;
      this.edit = new EditConfiguration(node);
      this.issues = new Issues(this);

      // Bind methods
      this.showIssues = this.showIssues.bind(this);
    }

    get type() {
      return this._type;
    }

    set type(type) {
      this._type = Math.min(Math.max(Converter.toInteger(type), 1), 6);

      return this;
    }

    get text() {
      return this._text;
    }

    set text(value) {
      this._text = value.trim();

      return this;
    }

    store(callback) {
      if (this.isEditableType()) {
        Headline.storeHeadlines([this], callback);
      }
    }

    getEditUrl() {
      if (this.isEditableRecord()) {
        const returnUrl = encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
        return top.TYPO3.settings.FormEngine.moduleUrl + '&edit[' + this.edit.table + '][' + this.edit.uid + ']=edit&returnUrl=' + returnUrl;
      }

      return null;
    }

    hasRelations() {
      return this.edit.referenceId && this.parent.headlines.filter(headline => headline.edit.relatedTo === this.edit.referenceId).length > 0;
    }

    isRelated() {
      return this.edit.relatedTo;
    }

    isEditableRecord() {
      return this.edit.table && this.edit.uid;
    }

    isEditableType() {
      return this.isEditableRecord() && this.edit.field && !this.isRelated();
    }

    showIssues() {
      this.issues.count() && this.issues.each((issue, key) => this.parent.notifications.showIssue(key));
    }

    static storeHeadlines(headlines, callback) {
      let parameters = {data: {}};
      let hasRelations = false;

      headlines.forEach(headline => {
        if (headline.isEditableType()) {
          const table = headline.edit.table;
          const uid = headline.edit.uid;
          const field = headline.edit.field;

          parameters.data[table] = parameters.data[table] || {};
          parameters.data[table][uid] = {};
          parameters.data[table][uid][field] = headline.type;
        }

        headline.hasRelations() && (hasRelations = true);
      });

      Object.keys(parameters).length && AjaxDataHandler.process(parameters).done(response => typeof callback === 'function' && callback(response, hasRelations));
    }
  }

  return Headline;
});
