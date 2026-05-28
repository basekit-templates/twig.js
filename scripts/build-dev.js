#!/usr/bin/env node
/**
 * Build script for twig.dev.js
 *
 * Concatenates the twig.js source files with a Google Closure Library polyfill
 * to produce twig.dev.js without requiring Java or plovr.
 *
 * The Closure Compiler (used by plovr in SIMPLE mode) strips goog.provide /
 * goog.require calls and pre-declares each namespace as a plain JS variable.
 * We replicate that here so that the output works in both browser eval contexts
 * and Node.js vm.runInContext contexts where window is not the global scope.
 */

'use strict';

const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');

// Google Closure Library polyfill – only the functions actually used by the
// twig.js sources (goog.provide / goog.require are handled via pre-declaration
// below, so they are stubs here).
const googPolyfill = `var goog = {};
goog.DEBUG = true;
goog.provide = function(ns) {};
goog.require = function(ns) {};

// Type checks
goog.isArray   = Array.isArray || function(v) { return Object.prototype.toString.call(v) === '[object Array]'; };
goog.isDef     = function(v) { return v !== undefined; };
goog.isFunction = function(v) { return typeof v === 'function'; };
goog.isNumber  = function(v) { return typeof v === 'number'; };
goog.isObject  = function(v) { return v !== null && (typeof v === 'object' || typeof v === 'function'); };
goog.isString  = function(v) { return typeof v === 'string'; };

// Unique IDs
goog.UID_PROPERTY_ = 'twig_uid_' + Math.floor(Math.random() * 2147483648).toString(36) + '_';
goog.uidCounter_   = 0;
goog.getUid = function(obj) {
    return obj[goog.UID_PROPERTY_] || (obj[goog.UID_PROPERTY_] = ++goog.uidCounter_);
};

// Prototype-chain inheritance
goog.inherits = function(childCtor, parentCtor) {
    function TempCtor() {}
    TempCtor.prototype = parentCtor.prototype;
    childCtor.superClass_ = parentCtor.prototype;
    childCtor.prototype = new TempCtor();
    childCtor.prototype.constructor = childCtor;
};

// Function binding
goog.bind = function(fn, self) {
    var args = Array.prototype.slice.call(arguments, 2);
    return function() { return fn.apply(self, args.concat(Array.prototype.slice.call(arguments))); };
};

// Exports – write both to window and to the plain namespace variable
goog.exportSymbol = function(publicPath, object) {
    var parts = publicPath.split('.');
    var cur = window;
    for (var i = 0; i < parts.length - 1; i++) {
        cur[parts[i]] = cur[parts[i]] || {};
        cur = cur[parts[i]];
    }
    cur[parts[parts.length - 1]] = object;
};
goog.exportProperty = function(object, publicName, symbol) { object[publicName] = symbol; };

// goog.string
goog.string = {};
goog.string.StringBuffer = function(opt_a1) {
    this.buffer_ = [];
    if (opt_a1 != null) { this.append(opt_a1); }
};
goog.string.StringBuffer.prototype.append = function(a1) {
    this.buffer_.push(a1 == null ? '' : String(a1));
    return this;
};
goog.string.StringBuffer.prototype.toString = function() { return this.buffer_.join(''); };
goog.string.trim     = function(str) { return str.replace(/^[\\s\\xa0]+|[\\s\\xa0]+$/g, ''); };
goog.string.contains = function(str, sub) { return str.indexOf(sub) !== -1; };
goog.string.htmlEscape = function(str) {
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
};
goog.string.makeSafe = function(obj) { return obj == null ? '' : String(obj); };
goog.string.quote = function(s) {
    s = String(s);
    var sb = ['"'];
    for (var i = 0; i < s.length; i++) {
        var ch = s.charAt(i);
        var cc = s.charCodeAt(i);
        if      (ch === '"')  { sb.push('\\\\"'); }
        else if (ch === '\\\\') { sb.push('\\\\\\\\'); }
        else if (ch === '\\n') { sb.push('\\\\n'); }
        else if (ch === '\\r') { sb.push('\\\\r'); }
        else if (ch === '\\t') { sb.push('\\\\t'); }
        else if (cc > 31 && cc < 127) { sb.push(ch); }
        else {
            var hex = cc.toString(16);
            sb.push('\\\\u' + '0000'.slice(hex.length) + hex);
        }
    }
    sb.push('"');
    return sb.join('');
};

// goog.object
goog.object = {};
goog.object.forEach = function(obj, f, opt_obj) {
    for (var k in obj) { f.call(opt_obj, obj[k], k, obj); }
};
goog.object.getCount = function(obj) {
    var n = 0;
    for (var k in obj) { if (Object.prototype.hasOwnProperty.call(obj, k)) { n++; } }
    return n;
};
goog.object.getKeys   = function(obj) { return Object.keys ? Object.keys(obj) : (function(r){ for(var k in obj){ if(Object.prototype.hasOwnProperty.call(obj,k)) r.push(k); } return r; })([]); };
goog.object.getValues = function(obj) { var r = []; for (var k in obj) { if (Object.prototype.hasOwnProperty.call(obj,k)) r.push(obj[k]); } return r; };
goog.object.containsKey = function(obj, key) { return key in obj; };
goog.object.contains    = function(obj, val) { for (var k in obj) { if (obj[k] === val) return true; } return false; };
goog.object.findKey     = function(obj, f, opt_this) { for (var k in obj) { if (f.call(opt_this, obj[k], k, obj)) return k; } return undefined; };
goog.object.clone       = function(obj) { var r = {}; for (var k in obj) { if (Object.prototype.hasOwnProperty.call(obj,k)) r[k] = obj[k]; } return r; };
goog.object.extend      = function(target) { for (var i = 1; i < arguments.length; i++) { var s = arguments[i]; for (var k in s) { if (Object.prototype.hasOwnProperty.call(s,k)) target[k] = s[k]; } } };

// goog.array
goog.array = {};
goog.array.contains    = function(arr, obj) { return Array.prototype.indexOf.call(arr, obj) >= 0; };
goog.array.forEach     = function(arr, f, opt_obj) { var l = arr.length; for (var i = 0; i < l; i++) { if (i in arr) f.call(opt_obj, arr[i], i, arr); } };
goog.array.removeDuplicates = function(arr, opt_rv) {
    var out = opt_rv || arr, seen = {}, j = 0;
    for (var i = 0; i < arr.length; i++) {
        var cur = arr[i];
        var key = (typeof cur === 'object') ? 'o' + goog.getUid(cur) : (typeof cur).charAt(0) + cur;
        if (!Object.prototype.hasOwnProperty.call(seen, key)) { seen[key] = true; out[j++] = cur; }
    }
    out.length = j;
};
`;

// Pre-declare all top-level namespace variables so bare references like
// `twig.X = ...` work in eval / vm contexts where window ≠ global.
// We also alias window.twig = twig so that goog.exportSymbol('twig.*', ...)
// and direct `twig.X = ...` assignments both write to the same object.
const namespaceDecls = `var twig = window.twig = {};
twig.filter = {};
twig.functions = {};
twig.Template = {};
twig.Template.Block = {};
`;

const sourceFiles = [
    'src-js/twig.js',
    'src-js/twig/markup.js',
    'src-js/twig/extension_interface.js',
    'src-js/twig/extension.js',
    'src-js/twig/template.js',
    'src-js/twig/filter.js',
    'src-js/twig/functions.js',
    'src-js/twig/environment.js',
    'src-js/export.js',
];

// Strip goog.provide / goog.require lines; they are replaced by the explicit
// namespace declarations above.
function stripClosureCalls(src) {
    return src.replace(/^goog\.(provide|require)\([^)]*\);\n?/gm, '');
}

// When the output is eval()'d in Node.js (as done by bootstrap.js and
// json-rpc.js), `window` is a plain local object – not the global scope.
// We expose `twig` on the Node.js `global` object so that test modules
// loaded by Mocha can reference it without a window. prefix.
const nodeCompat = `
if (typeof module !== 'undefined' && typeof global !== 'undefined') {
    global['twig'] = twig;
}
`;

const parts = [googPolyfill, namespaceDecls];

for (const file of sourceFiles) {
    const filePath = path.join(ROOT, file);
    const src = stripClosureCalls(fs.readFileSync(filePath, 'utf8'));
    parts.push('\n// Source: ' + file + '\n');
    parts.push(src);
}

parts.push(nodeCompat);

const output = parts.join('\n');
const outputPath = path.join(ROOT, 'twig.dev.js');
fs.writeFileSync(outputPath, output, 'utf8');
console.log('Built ' + outputPath + ' (' + output.length + ' bytes)');
