define(['TYPO3/CMS/Backend/AjaxDataHandler', 'TYPO3/CMS/Z7Semantilizer/Backend/Converter'], (AjaxDataHandler, Converter) => {
  class EditConfiguration {
    constructor(table, uid, field) {
      this._table = table;
      this._uid = uid;
      this._field = field;
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

    isEditableRecord() {
      return this.table && this.uid;
    }

    isEditableType() {
      return this.isEditableRecord() && this.field;
    }
  }

  class Error {
    static mainHeadingRequired = 'mainHeadingRequired';
    static mainHeadingNumber = 'mainHeadingNumber';
    static mainHeadingPosition = 'mainHeadingPosition';
    static headingStructure = 'headingStructure';

    constructor(key, fix) {
      this.key = key;
      this.fix = fix;
    }

    static isValidKey(key) {
      return [Error.mainHeadingRequired, Error.mainHeadingNumber, Error.mainHeadingPosition, Error.headingStructure].indexOf(key) > -1;
    }

    get key() {
      return this._key;
    }

    set key(key) {
      if (Error.isValidKey(key)) {
        this._key = key;
      } else {
        console.warn('Not allowed error key "' + key + '"', 1641814278);
      }

      return this;
    }

    get fix() {
      return this._fix;
    }

    set fix(fix) {
      this._fix = fix || null;

      return this;
    }

    get layout() {
      return this.key === Error.mainHeadingNumber ? 'info' : 'warning';
    }
  }

  class ErrorList {
    constructor() {
      this.list = [];
    }

    clear() {
      this.list.length = 0;
    }

    count() {
      return this.list.length;
    }

    empty() {
      return this.count() === 0;
    }

    getFirst() {
      return this.empty() ? null : this.list[0];
    }

    getAll() {
      return this.list;
    }

    add(key, fix) {
      this.list.push(new Error(key, fix));

      return this;
    }
  }

  class Headline {
    constructor(node) {
      this.type = node.nodeName;
      this.text = node.innerText;
      this.edit = new EditConfiguration();
      this.errors = new ErrorList();

      if (node.dataset.semantilizer) {
        try {
          const editConfigData = JSON.parse(node.dataset.semantilizer);
          this.edit.table = editConfigData.table;
          this.edit.uid = editConfigData.uid;
          this.edit.field = editConfigData.field;
        } catch (e) {
          typeof console.log === 'function' && console.log(e, 1640904719);
        }
      }
    }

    static parseType(type) {
      return Math.min(Math.max(Converter.toInteger(type), 1), 6);
    }

    get type() {
      return this._type;
    }

    set type(type) {
      this._type = Headline.parseType(type);

      return this;
    }

    get text() {
      return this._text;
    }

    set text(value) {
      this._text = value.trim();

      return this;
    }

    fix(callback) {
      if(this.edit.isEditableType() && this.errors.count()) {
        this.type = this.errors.getFirst().fix;
        this.updateType(callback);
      }
    }

    update(callback) {
      if(this.edit.isEditableType()) {
        return Headline.updateHeadlines(callback, this);
      }
    }

    getEditUrl() {
      if (this.edit.isEditableRecord()) {
        const returnUrl = encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
        return top.TYPO3.settings.FormEngine.moduleUrl + '&edit[' + this.edit.table + '][' + this.edit.uid + ']=edit&returnUrl=' + returnUrl;
      }

      return null;
    }

    static updateHeadlines(callback, ...headlines) {
      const parameters = {data: {}};

      headlines.forEach(headline => {
        if (headline.edit.isEditableType()) {
          const table = headline.edit.table;
          const uid = headline.edit.uid;
          const field = headline.edit.field;

          parameters.data[table] = parameters.data[table] || {};
          parameters.data[table][uid] = {};
          parameters.data[table][uid][field] = headline.type;
        }
      });

      Object.keys(parameters).length && AjaxDataHandler.process(parameters).done(response => typeof callback === 'function' && callback(response));
    }
  }

  return Headline;
});
