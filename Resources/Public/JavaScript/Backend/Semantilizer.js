"use strict";

function _classPrivateFieldInitSpec(obj, privateMap, value) {
    _checkPrivateRedeclaration(obj, privateMap);
    privateMap.set(obj, value);
}

function _checkPrivateRedeclaration(obj, privateCollection) {
    if (privateCollection.has(obj)) {
        throw new TypeError("Cannot initialize the same private elements twice on an object");
    }
}

function _classPrivateFieldGet(receiver, privateMap) {
    var descriptor = _classExtractFieldDescriptor(receiver, privateMap, "get");
    return _classApplyDescriptorGet(receiver, descriptor);
}

function _classApplyDescriptorGet(receiver, descriptor) {
    if (descriptor.get) {
        return descriptor.get.call(receiver);
    }
    return descriptor.value;
}

function _classPrivateFieldSet(receiver, privateMap, value) {
    var descriptor = _classExtractFieldDescriptor(receiver, privateMap, "set");
    _classApplyDescriptorSet(receiver, descriptor, value);
    return value;
}

function _classExtractFieldDescriptor(receiver, privateMap, action) {
    if (!privateMap.has(receiver)) {
        throw new TypeError("attempted to " + action + " private field on non-instance");
    }
    return privateMap.get(receiver);
}

function _classApplyDescriptorSet(receiver, descriptor, value) {
    if (descriptor.set) {
        descriptor.set.call(receiver, value);
    } else {
        if (!descriptor.writable) {
            throw new TypeError("attempted to set read only private field");
        }
        descriptor.value = value;
    }
}

function _defineProperty(obj, key, value) {
    if (key in obj) {
        Object.defineProperty(obj, key, {
            value: value,
            enumerable: true,
            configurable: true,
            writable: true
        });
    } else {
        obj[key] = value;
    }
    return obj;
}

function _classCallCheck(instance, Constructor) {
    if (!(instance instanceof Constructor)) {
        throw new TypeError("Cannot call a class as a function");
    }
}

function _defineProperties(target, props) {
    for (var i = 0; i < props.length; i++) {
        var descriptor = props[i];
        descriptor.enumerable = descriptor.enumerable || false;
        descriptor.configurable = true;
        if ("value" in descriptor) descriptor.writable = true;
        Object.defineProperty(target, descriptor.key, descriptor);
    }
}

function _createClass(Constructor, protoProps, staticProps) {
    if (protoProps) _defineProperties(Constructor.prototype, protoProps);
    if (staticProps) _defineProperties(Constructor, staticProps);
    return Constructor;
}

define([ "TYPO3/CMS/Backend/Notification", "TYPO3/CMS/Backend/ActionButton/ImmediateAction" ], function(Notification, ImmediateAction) {
    var Convert = function() {
        function Convert() {
            _classCallCheck(this, Convert);
        }
        _createClass(Convert, null, [ {
            key: "toArray",
            value: function toArray(list) {
                return Array.prototype.slice.call(list);
            }
        }, {
            key: "toInteger",
            value: function toInteger(value) {
                var _int = parseInt(value);
                return isNaN(_int) ? parseInt((value || "").replace(/[^0-9]/i, "")) : _int;
            }
        } ]);
        return Convert;
    }();
    var Module = function Module() {
        _classCallCheck(this, Module);
    };
    var Error = function() {
        function Error() {
            _classCallCheck(this, Error);
        }
        _createClass(Error, null, [ {
            key: "mainHeadingRequired",
            value: function mainHeadingRequired(headline, targetType) {
                headline.error.push({
                    code: "mainHeadingRequired",
                    priority: 4,
                    fix: targetType
                });
            }
        }, {
            key: "mainHeadingNumber",
            value: function mainHeadingNumber(headline, targetType) {
                headline.error.push({
                    code: "mainHeadingNumber",
                    priority: 2,
                    fix: targetType
                });
            }
        }, {
            key: "mainHeadingPosition",
            value: function mainHeadingPosition(headline, targetType) {
                headline.error.push({
                    code: "mainHeadingPosition",
                    priority: 3,
                    fix: targetType
                });
            }
        }, {
            key: "headingStructure",
            value: function headingStructure(headline, targetType) {
                headline.error.push({
                    code: "headingStructure",
                    priority: 1,
                    fix: targetType
                });
            }
        } ]);
        return Error;
    }();
    var Headline = function Headline(node) {
        _classCallCheck(this, Headline);
        _defineProperty(this, "type", 0);
        _defineProperty(this, "text", "");
        _defineProperty(this, "error", []);
        _defineProperty(this, "table", "");
        _defineProperty(this, "id", 0);
        this.type = Convert.toInteger(node.nodeName);
        this.text = node.innerText.trim();
        this.table = node.dataset.semantilizerTable;
        this.id = Convert.toInteger(node.dataset.semantilizerUid);
    };
    var _url = new WeakMap();
    var _containerSelector = new WeakMap();
    var Semantilizer = function() {
        function Semantilizer(url, elementId, containerSelector) {
            _classCallCheck(this, Semantilizer);
            _defineProperty(this, "headlines", []);
            _defineProperty(this, "element", void 0);
            _classPrivateFieldInitSpec(this, _url, {
                writable: true,
                value: void 0
            });
            _classPrivateFieldInitSpec(this, _containerSelector, {
                writable: true,
                value: void 0
            });
            _classPrivateFieldSet(this, _url, url);
            this.element = document.getElementById(elementId);
            _classPrivateFieldSet(this, _containerSelector, containerSelector);
            this.init();
        }
        _createClass(Semantilizer, [ {
            key: "collect",
            value: function collect(callback) {
                var _this = this;
                var request = new XMLHttpRequest();
                request.onreadystatechange = function() {
                    if (request.readyState === 4) {
                        if (request.status === 200) {
                            var parser = new DOMParser();
                            var doc = parser.parseFromString(request.responseText, "text/html");
                            var container = _classPrivateFieldGet(_this, _containerSelector) ? doc.querySelector(_classPrivateFieldGet(_this, _containerSelector)) : null;
                            _this.headlines = Convert.toArray((container || doc).querySelectorAll("h1, h2, h3, h4, h5, h6")).map(function(node) {
                                return new Headline(node);
                            });
                            if (typeof callback === "function") {
                                callback(request);
                            }
                        } else {
                            _this.error = {
                                request: request
                            };
                        }
                    }
                };
                request.open("GET", _classPrivateFieldGet(this, _url), true);
                request.send();
            }
        }, {
            key: "validate",
            value: function validate() {
                var _this2 = this;
                var validateMainHeadings = function validateMainHeadings() {
                    var firstHeadline = _this2.headlines[0];
                    var mainHeadlines = _this2.headlines.filter(function(headline) {
                        return headline.type === 1;
                    });
                    if (mainHeadlines.length === 0) {
                        Error.mainHeadingRequired(firstHeadline, 1);
                    }
                    if (mainHeadlines.length > 1) {
                        _this2.headlines.forEach(function(headline, i) {
                            if (i && headline.type === 1) {
                                Error.mainHeadingNumber(headline, 2);
                            }
                        });
                    }
                    if (mainHeadlines.length === 1 && firstHeadline.type !== 1) {
                        Error.mainHeadingPosition(firstHeadline, 1);
                        _this2.headlines.forEach(function(headline, i) {
                            if (i && headline.type === 1) {
                                Error.mainHeadingPosition(headline, 2);
                            }
                        });
                    }
                };
                var validateStructure = function validateStructure() {
                    _this2.headlines.forEach(function(headline, i) {
                        if (i && headline.type > _this2.headlines[i - 1].type + 1) {
                            Error.headingStructure(headline);
                        }
                    });
                };
                validateMainHeadings();
                validateStructure();
            }
        }, {
            key: "showNotifications",
            value: function showNotifications() {
                this.headlines.filter(function(headline) {
                    return headline.error.length;
                }).forEach(function(headline) {
                    headline.error.forEach(function(error) {
                        Notification.warning(error.code, headline.text, 5, [ {
                            label: "Close message",
                            action: new ImmediateAction(function() {
                                return true;
                            })
                        }, {
                            label: "Fix error",
                            action: new ImmediateAction(function() {
                                return Notification.success("fixed!", "", 1);
                            })
                        } ]);
                    });
                });
            }
        }, {
            key: "revalidate",
            value: function revalidate() {
                this.headlines.forEach(function(headline) {
                    return headline.error.length = 0;
                });
                this.validate();
            }
        }, {
            key: "refresh",
            value: function refresh(callback) {
                var _this3 = this;
                this.collect(function() {
                    _this3.validate();
                    if (typeof callback === "function") {
                        callback();
                    }
                    _this3.element.innerHTML = _this3.headlines.map(function(headline) {
                        return "h" + headline.type + ": " + headline.text + (headline.error.length ? " | ERROR-CODE: " + headline.error.map(function(error) {
                            return error.code;
                        }).join("|") : "");
                    }).join("<br />");
                    _this3.showNotifications();
                });
            }
        }, {
            key: "init",
            value: function init() {
                this.refresh();
            }
        } ]);
        return Semantilizer;
    }();
    return Semantilizer;
});