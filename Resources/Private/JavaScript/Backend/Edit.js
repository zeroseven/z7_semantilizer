define(['TYPO3/CMS/Backend/AjaxDataHandler'], (AjaxDataHandler) => {
  class Edit {
    constructor(headline) {
      this.headline = headline;
      this.table = headline.edit ? headline.edit.table : null;
      this.uid = headline.edit ? headline.edit.uid : null;
      this.field = headline.edit ? headline.edit.field : null;

      return this;
    }

    updateType(type, callback) {
      Edit.updateTypes([{
        type: type,
        config: this
      }], callback);
    }

    static updateTypes(tasks, callback) {
      const parameters = {data: {}};

      tasks.forEach(task => {
        const table = task.config && task.config.table;
        const uid = task.config && task.config.uid;
        const field = task.config && task.config.field;
        const type = task.type;

        if (table && uid && field && type) {
          parameters.data[table] = {};
          parameters.data[table][uid] = {};
          parameters.data[table][uid][field] = type;
        }
      });

      console.log(tasks,parameters)

      Object.keys(parameters).length && AjaxDataHandler.process(parameters).done(response => typeof callback === 'function' && callback(response));
    }

    getEditUrl() {
      if(this.table && this.uid) {
        const returnUrl = encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
        return top.TYPO3.settings.FormEngine.moduleUrl + '&edit[' + this.table + '][' + this.uid + ']=edit&returnUrl=' + returnUrl;
      }

      return null;
    }
  }

  return Edit;
});
