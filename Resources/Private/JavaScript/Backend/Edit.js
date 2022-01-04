define(['TYPO3/CMS/Backend/AjaxDataHandler', 'TYPO3/CMS/Z7Semantilizer/Backend/Converter'], (AjaxDataHandler, Converter) => {
  class Edit {
    constructor(headline) {
      this.headline = headline;

      return this;
    }

    updateType(type, callback) {
      Edit.updateTypes([{
        type: type,
        headline: this.headline
      }], callback);
    }

    static updateTypes(tasks, callback) {
      const parameters = {data: {}};

      tasks.forEach(task => {
        const headline = task.headline;
        const table = headline.edit ? headline.edit.table : null;
        const uid = headline.edit ? headline.edit.uid : null;
        const field = headline.edit ? headline.edit.field : null;
        const type = Converter.toInteger(task.type);

        if (table && uid && field && type) {
          parameters.data[table] = parameters.data[table] || {};
          parameters.data[table][uid] = {};
          parameters.data[table][uid][field] = type;

          // Update heading
          headline && headline.setType(type);
        }
      });

      console.log(parameters);

      Object.keys(parameters).length && AjaxDataHandler.process(parameters).done(response => typeof callback === 'function' && callback(response));
    }

    getEditUrl() {
      if (this.headline.edit && this.headline.edit.table && this.headline.edit.uid) {
        const returnUrl = encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
        return top.TYPO3.settings.FormEngine.moduleUrl + '&edit[' + this.headline.edit.table + '][' + this.headline.edit.uid + ']=edit&returnUrl=' + returnUrl;
      }

      return null;
    }
  }

  return Edit;
});
