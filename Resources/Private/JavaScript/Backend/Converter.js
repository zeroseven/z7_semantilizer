define(() => {
  class Convert {
    static toArray(list) {
      return Array.prototype.slice.call(list);
    }

    static toInteger(value) {
      const int = parseInt(value);

      return isNaN(int) ? parseInt((value || '').replace(/[^0-9]/i, '')) : int;
    }
  }

  return Convert;
});
