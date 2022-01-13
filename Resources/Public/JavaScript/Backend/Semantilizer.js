"use strict";function _toConsumableArray(e){return _arrayWithoutHoles(e)||_iterableToArray(e)||_unsupportedIterableToArray(e)||_nonIterableSpread()}function _nonIterableSpread(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}function _unsupportedIterableToArray(e,t){if(e){if("string"==typeof e)return _arrayLikeToArray(e,t);var r=Object.prototype.toString.call(e).slice(8,-1);return"Map"===(r="Object"===r&&e.constructor?e.constructor.name:r)||"Set"===r?Array.from(e):"Arguments"===r||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(r)?_arrayLikeToArray(e,t):void 0}}function _iterableToArray(e){if("undefined"!=typeof Symbol&&null!=e[Symbol.iterator]||null!=e["@@iterator"])return Array.from(e)}function _arrayWithoutHoles(e){if(Array.isArray(e))return _arrayLikeToArray(e)}function _arrayLikeToArray(e,t){(null==t||t>e.length)&&(t=e.length);for(var r=0,n=new Array(t);r<t;r++)n[r]=e[r];return n}function _classCallCheck(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function _defineProperties(e,t){for(var r=0;r<t.length;r++){var n=t[r];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}function _createClass(e,t,r){return t&&_defineProperties(e.prototype,t),r&&_defineProperties(e,r),Object.defineProperty(e,"prototype",{writable:!1}),e}function _defineProperty(e,t,r){return t in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}define(["TYPO3/CMS/Z7Semantilizer/Backend/Converter","TYPO3/CMS/Z7Semantilizer/Backend/Headline","TYPO3/CMS/Z7Semantilizer/Backend/Module","TYPO3/CMS/Z7Semantilizer/Backend/Notification"],function(i,a,o,s){return function(){function n(e,t,r){_classCallCheck(this,n),_defineProperty(this,"headlines",[]),_defineProperty(this,"url",void 0),_defineProperty(this,"contentSelectors",void 0),_defineProperty(this,"module",void 0),this.url=e,this.contentSelectors=i.toArray(r),this.module=new o(document.getElementById(t),this),this.notifications=new s(this),this.validate=this.validate.bind(this),this.init()}return _createClass(n,[{key:"collect",value:function(e){var n=this,t=new XMLHttpRequest;this.headlines.length=0,t.onreadystatechange=function(){var r;4===t.readyState&&(200===t.status&&(r=(new DOMParser).parseFromString(t.responseText,"text/html"),n.contentSelectors.map(function(e){return r.querySelector(e)}).filter(function(e){return e}).forEach(function(e){var t;(t=n.headlines).push.apply(t,_toConsumableArray(i.toArray((e||r).querySelectorAll("h1, h2, h3, h4, h5, h6")).map(function(e){return new a(e,n)})))})),"function"==typeof e&&e(t))},t.open("GET",(this.url.indexOf("#")<0?this.url:this.url.substr(0,this.url.indexOf("#")))+"#"+Math.random().toString(36).slice(2),!0),t.setRequestHeader("X-Semantilizer","true"),t.send()}},{key:"validateStructure",value:function(){var r=this;this.headlines.forEach(function(e){return e.issues.clear()});function e(){r.headlines.forEach(function(e,t){t&&e.type>r.headlines[t-1].type+1&&e.issues.add("headingStructure")})}var t,n;this.headlines.length&&(t=r.headlines[0],0===(n=r.headlines.filter(function(e){return 1===e.type})).length&&t.issues.add("mainHeadingRequired",1),1<n.length&&r.headlines.forEach(function(e,t){1===e.type&&e.issues.add("mainHeadingNumber",t?2:null)}),1===n.length&&1!==t.type&&(t.issues.add("mainHeadingPosition",1),r.headlines.forEach(function(e,t){t&&1===e.type&&e.issues.add("mainHeadingPosition",2)})),e())}},{key:"validate",value:function(){this.validateStructure(),this.module.drawStructure(),this.notifications.hideAll(),this.notifications.autoload.enabled()&&this.notifications.showIssues()}},{key:"refresh",value:function(){this.collect(this.validate)}},{key:"init",value:function(){var t=this;this.refresh(),this.module.loader(),require(["jquery","jquery-ui/droppable"],function(e){return e(".t3js-page-ce-dropzone-available").on("drop",function(){t.module.lockStructure(),t.refresh()})})}}]),n}()});