"use strict";function _classCallCheck(e,r){if(!(e instanceof r))throw new TypeError("Cannot call a class as a function")}function _defineProperties(e,r){for(var n=0;n<r.length;n++){var t=r[n];t.enumerable=t.enumerable||!1,t.configurable=!0,"value"in t&&(t.writable=!0),Object.defineProperty(e,t.key,t)}}function _createClass(e,r,n){return r&&_defineProperties(e.prototype,r),n&&_defineProperties(e,n),Object.defineProperty(e,"prototype",{writable:!1}),e}define(function(){return function(){function e(){_classCallCheck(this,e)}return _createClass(e,null,[{key:"toArray",value:function(e){return Array.prototype.slice.call(e)}},{key:"toInteger",value:function(e){var r=parseInt(e);return isNaN(r)?parseInt((e||"").replace(/[^0-9]/i,"")):r}}]),e}()});