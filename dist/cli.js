#!/usr/bin/env bun
// @bun
var __defProp = Object.defineProperty;
var __commonJS = (cb, mod) => () => (mod || cb((mod = { exports: {} }).exports, mod), mod.exports);
var __returnValue = (v) => v;
function __exportSetter(name, newValue) {
  this[name] = __returnValue.bind(null, newValue);
}
var __export = (target, all) => {
  for (var name in all)
    __defProp(target, name, {
      get: all[name],
      enumerable: true,
      configurable: true,
      set: __exportSetter.bind(all, name)
    });
};
var __esm = (fn, res, err) => () => {
  if (fn)
    try {
      res = fn(fn = 0);
    } catch (e) {
      err = [e];
    }
  if (err)
    throw err[0];
  return res;
};
var __require = import.meta.require;

// cli/lib/logger.ts
var log;
var init_logger = __esm(() => {
  log = {
    info: (msg) => console.log(`\x1B[36m[\xB7]\x1B[0m ${msg}`),
    success: (msg) => console.log(`\x1B[32m[\u2713]\x1B[0m ${msg}`),
    warn: (msg) => console.log(`\x1B[33m[!]\x1B[0m ${msg}`),
    error: (msg) => console.log(`\x1B[31m[\u2717]\x1B[0m ${msg}`)
  };
});

// node_modules/ansi-colors/symbols.js
var require_symbols = __commonJS(function(exports, module) {
  var isHyper = typeof process !== "undefined" && process.env.TERM_PROGRAM === "Hyper";
  var isWindows = typeof process !== "undefined" && process.platform === "win32";
  var isLinux = typeof process !== "undefined" && process.platform === "linux";
  var common = {
    ballotDisabled: "\u2612",
    ballotOff: "\u2610",
    ballotOn: "\u2611",
    bullet: "\u2022",
    bulletWhite: "\u25E6",
    fullBlock: "\u2588",
    heart: "\u2764",
    identicalTo: "\u2261",
    line: "\u2500",
    mark: "\u203B",
    middot: "\xB7",
    minus: "\uFF0D",
    multiplication: "\xD7",
    obelus: "\xF7",
    pencilDownRight: "\u270E",
    pencilRight: "\u270F",
    pencilUpRight: "\u2710",
    percent: "%",
    pilcrow2: "\u2761",
    pilcrow: "\xB6",
    plusMinus: "\xB1",
    question: "?",
    section: "\xA7",
    starsOff: "\u2606",
    starsOn: "\u2605",
    upDownArrow: "\u2195"
  };
  var windows = Object.assign({}, common, {
    check: "\u221A",
    cross: "\xD7",
    ellipsisLarge: "...",
    ellipsis: "...",
    info: "i",
    questionSmall: "?",
    pointer: ">",
    pointerSmall: "\xBB",
    radioOff: "( )",
    radioOn: "(*)",
    warning: "\u203C"
  });
  var other = Object.assign({}, common, {
    ballotCross: "\u2718",
    check: "\u2714",
    cross: "\u2716",
    ellipsisLarge: "\u22EF",
    ellipsis: "\u2026",
    info: "\u2139",
    questionFull: "\uFF1F",
    questionSmall: "\uFE56",
    pointer: isLinux ? "\u25B8" : "\u276F",
    pointerSmall: isLinux ? "\u2023" : "\u203A",
    radioOff: "\u25EF",
    radioOn: "\u25C9",
    warning: "\u26A0"
  });
  module.exports = isWindows && !isHyper ? windows : other;
  Reflect.defineProperty(module.exports, "common", { enumerable: false, value: common });
  Reflect.defineProperty(module.exports, "windows", { enumerable: false, value: windows });
  Reflect.defineProperty(module.exports, "other", { enumerable: false, value: other });
});

// node_modules/ansi-colors/index.js
var require_ansi_colors = __commonJS(function(exports, module) {
  var isObject = (val) => val !== null && typeof val === "object" && !Array.isArray(val);
  var ANSI_REGEX = /[\u001b\u009b][[\]#;?()]*(?:(?:(?:[^\W_]*;?[^\W_]*)\u0007)|(?:(?:[0-9]{1,4}(;[0-9]{0,4})*)?[~0-9=<>cf-nqrtyA-PRZ]))/g;
  var hasColor = () => {
    if (typeof process !== "undefined") {
      return process.env.FORCE_COLOR !== "0";
    }
    return false;
  };
  var create = () => {
    const colors = {
      enabled: hasColor(),
      visible: true,
      styles: {},
      keys: {}
    };
    const ansi = (style) => {
      let open = style.open = `\x1B[${style.codes[0]}m`;
      let close = style.close = `\x1B[${style.codes[1]}m`;
      let regex = style.regex = new RegExp(`\\u001b\\[${style.codes[1]}m`, "g");
      style.wrap = (input, newline) => {
        if (input.includes(close))
          input = input.replace(regex, close + open);
        let output = open + input + close;
        return newline ? output.replace(/\r*\n/g, `${close}$&${open}`) : output;
      };
      return style;
    };
    const wrap = (style, input, newline) => {
      return typeof style === "function" ? style(input) : style.wrap(input, newline);
    };
    const style = (input, stack) => {
      if (input === "" || input == null)
        return "";
      if (colors.enabled === false)
        return input;
      if (colors.visible === false)
        return "";
      let str = "" + input;
      let nl = str.includes(`
`);
      let n = stack.length;
      if (n > 0 && stack.includes("unstyle")) {
        stack = [...new Set(["unstyle", ...stack])].reverse();
      }
      while (n-- > 0)
        str = wrap(colors.styles[stack[n]], str, nl);
      return str;
    };
    const define = (name, codes, type) => {
      colors.styles[name] = ansi({ name, codes });
      let keys = colors.keys[type] || (colors.keys[type] = []);
      keys.push(name);
      Reflect.defineProperty(colors, name, {
        configurable: true,
        enumerable: true,
        set(value) {
          colors.alias(name, value);
        },
        get() {
          let color = (input) => style(input, color.stack);
          Reflect.setPrototypeOf(color, colors);
          color.stack = this.stack ? this.stack.concat(name) : [name];
          return color;
        }
      });
    };
    define("reset", [0, 0], "modifier");
    define("bold", [1, 22], "modifier");
    define("dim", [2, 22], "modifier");
    define("italic", [3, 23], "modifier");
    define("underline", [4, 24], "modifier");
    define("inverse", [7, 27], "modifier");
    define("hidden", [8, 28], "modifier");
    define("strikethrough", [9, 29], "modifier");
    define("black", [30, 39], "color");
    define("red", [31, 39], "color");
    define("green", [32, 39], "color");
    define("yellow", [33, 39], "color");
    define("blue", [34, 39], "color");
    define("magenta", [35, 39], "color");
    define("cyan", [36, 39], "color");
    define("white", [37, 39], "color");
    define("gray", [90, 39], "color");
    define("grey", [90, 39], "color");
    define("bgBlack", [40, 49], "bg");
    define("bgRed", [41, 49], "bg");
    define("bgGreen", [42, 49], "bg");
    define("bgYellow", [43, 49], "bg");
    define("bgBlue", [44, 49], "bg");
    define("bgMagenta", [45, 49], "bg");
    define("bgCyan", [46, 49], "bg");
    define("bgWhite", [47, 49], "bg");
    define("blackBright", [90, 39], "bright");
    define("redBright", [91, 39], "bright");
    define("greenBright", [92, 39], "bright");
    define("yellowBright", [93, 39], "bright");
    define("blueBright", [94, 39], "bright");
    define("magentaBright", [95, 39], "bright");
    define("cyanBright", [96, 39], "bright");
    define("whiteBright", [97, 39], "bright");
    define("bgBlackBright", [100, 49], "bgBright");
    define("bgRedBright", [101, 49], "bgBright");
    define("bgGreenBright", [102, 49], "bgBright");
    define("bgYellowBright", [103, 49], "bgBright");
    define("bgBlueBright", [104, 49], "bgBright");
    define("bgMagentaBright", [105, 49], "bgBright");
    define("bgCyanBright", [106, 49], "bgBright");
    define("bgWhiteBright", [107, 49], "bgBright");
    colors.ansiRegex = ANSI_REGEX;
    colors.hasColor = colors.hasAnsi = (str) => {
      colors.ansiRegex.lastIndex = 0;
      return typeof str === "string" && str !== "" && colors.ansiRegex.test(str);
    };
    colors.alias = (name, color) => {
      let fn = typeof color === "string" ? colors[color] : color;
      if (typeof fn !== "function") {
        throw new TypeError("Expected alias to be the name of an existing color (string) or a function");
      }
      if (!fn.stack) {
        Reflect.defineProperty(fn, "name", { value: name });
        colors.styles[name] = fn;
        fn.stack = [name];
      }
      Reflect.defineProperty(colors, name, {
        configurable: true,
        enumerable: true,
        set(value) {
          colors.alias(name, value);
        },
        get() {
          let color = (input) => style(input, color.stack);
          Reflect.setPrototypeOf(color, colors);
          color.stack = this.stack ? this.stack.concat(fn.stack) : fn.stack;
          return color;
        }
      });
    };
    colors.theme = (custom) => {
      if (!isObject(custom))
        throw new TypeError("Expected theme to be an object");
      for (let name of Object.keys(custom)) {
        colors.alias(name, custom[name]);
      }
      return colors;
    };
    colors.alias("unstyle", (str) => {
      if (typeof str === "string" && str !== "") {
        colors.ansiRegex.lastIndex = 0;
        return str.replace(colors.ansiRegex, "");
      }
      return "";
    });
    colors.alias("noop", (str) => str);
    colors.none = colors.clear = colors.noop;
    colors.stripColor = colors.unstyle;
    colors.symbols = require_symbols();
    colors.define = define;
    return colors;
  };
  module.exports = create();
  module.exports.create = create;
});

// node_modules/enquirer/lib/utils.js
var require_utils = __commonJS(function(exports) {
  var toString = Object.prototype.toString;
  var colors = require_ansi_colors();
  var onExitCalled = false;
  var onExitCallbacks = new Set;
  var complements = {
    yellow: "blue",
    cyan: "red",
    green: "magenta",
    black: "white",
    blue: "yellow",
    red: "cyan",
    magenta: "green",
    white: "black"
  };
  exports.longest = (arr, prop) => {
    return arr.reduce((a, v) => Math.max(a, prop ? v[prop].length : v.length), 0);
  };
  exports.hasColor = (str) => !!str && colors.hasColor(str);
  var isObject = exports.isObject = (val) => {
    return val !== null && typeof val === "object" && !Array.isArray(val);
  };
  exports.nativeType = (val) => {
    return toString.call(val).slice(8, -1).toLowerCase().replace(/\s/g, "");
  };
  exports.isAsyncFn = (val) => {
    return exports.nativeType(val) === "asyncfunction";
  };
  exports.isPrimitive = (val) => {
    return val != null && typeof val !== "object" && typeof val !== "function";
  };
  exports.resolve = (context, value, ...rest) => {
    if (typeof value === "function") {
      return value.call(context, ...rest);
    }
    return value;
  };
  exports.scrollDown = (choices = []) => [...choices.slice(1), choices[0]];
  exports.scrollUp = (choices = []) => [choices.pop(), ...choices];
  exports.reorder = (arr = []) => {
    let res = arr.slice();
    res.sort((a, b) => {
      if (a.index > b.index)
        return 1;
      if (a.index < b.index)
        return -1;
      return 0;
    });
    return res;
  };
  exports.swap = (arr, index, pos) => {
    let len = arr.length;
    let idx = pos === len ? 0 : pos < 0 ? len - 1 : pos;
    let choice = arr[index];
    arr[index] = arr[idx];
    arr[idx] = choice;
  };
  exports.width = (stream, fallback = 80) => {
    let columns = stream && stream.columns ? stream.columns : fallback;
    if (stream && typeof stream.getWindowSize === "function") {
      columns = stream.getWindowSize()[0];
    }
    if (process.platform === "win32") {
      return columns - 1;
    }
    return columns;
  };
  exports.height = (stream, fallback = 20) => {
    let rows = stream && stream.rows ? stream.rows : fallback;
    if (stream && typeof stream.getWindowSize === "function") {
      rows = stream.getWindowSize()[1];
    }
    return rows;
  };
  exports.wordWrap = (str, options = {}) => {
    if (!str)
      return str;
    if (typeof options === "number") {
      options = { width: options };
    }
    let { indent = "", newline = `
` + indent, width = 80 } = options;
    let spaces = (newline + indent).match(/[^\S\n]/g) || [];
    width -= spaces.length;
    let source = `.{1,${width}}([\\s\\u200B]+|$)|[^\\s\\u200B]+?([\\s\\u200B]+|$)`;
    let output = str.trim();
    let regex = new RegExp(source, "g");
    let lines = output.match(regex) || [];
    lines = lines.map((line) => line.replace(/\n$/, ""));
    if (options.padEnd)
      lines = lines.map((line) => line.padEnd(width, " "));
    if (options.padStart)
      lines = lines.map((line) => line.padStart(width, " "));
    return indent + lines.join(newline);
  };
  exports.unmute = (color) => {
    let name = color.stack.find((n) => colors.keys.color.includes(n));
    if (name) {
      return colors[name];
    }
    let bg = color.stack.find((n) => n.slice(2) === "bg");
    if (bg) {
      return colors[name.slice(2)];
    }
    return (str) => str;
  };
  exports.pascal = (str) => str ? str[0].toUpperCase() + str.slice(1) : "";
  exports.inverse = (color) => {
    if (!color || !color.stack)
      return color;
    let name = color.stack.find((n) => colors.keys.color.includes(n));
    if (name) {
      let col = colors["bg" + exports.pascal(name)];
      return col ? col.black : color;
    }
    let bg = color.stack.find((n) => n.slice(0, 2) === "bg");
    if (bg) {
      return colors[bg.slice(2).toLowerCase()] || color;
    }
    return colors.none;
  };
  exports.complement = (color) => {
    if (!color || !color.stack)
      return color;
    let name = color.stack.find((n) => colors.keys.color.includes(n));
    let bg = color.stack.find((n) => n.slice(0, 2) === "bg");
    if (name && !bg) {
      return colors[complements[name] || name];
    }
    if (bg) {
      let lower = bg.slice(2).toLowerCase();
      let comp = complements[lower];
      if (!comp)
        return color;
      return colors["bg" + exports.pascal(comp)] || color;
    }
    return colors.none;
  };
  exports.meridiem = (date) => {
    let hours = date.getHours();
    let minutes = date.getMinutes();
    let ampm = hours >= 12 ? "pm" : "am";
    hours = hours % 12;
    let hrs = hours === 0 ? 12 : hours;
    let min = minutes < 10 ? "0" + minutes : minutes;
    return hrs + ":" + min + " " + ampm;
  };
  exports.set = (obj = {}, prop = "", val) => {
    return prop.split(".").reduce((acc, k, i, arr) => {
      let value = arr.length - 1 > i ? acc[k] || {} : val;
      if (!exports.isObject(value) && i < arr.length - 1)
        value = {};
      return acc[k] = value;
    }, obj);
  };
  exports.get = (obj = {}, prop = "", fallback) => {
    let value = obj[prop] == null ? prop.split(".").reduce((acc, k) => acc && acc[k], obj) : obj[prop];
    return value == null ? fallback : value;
  };
  exports.mixin = (target, b) => {
    if (!isObject(target))
      return b;
    if (!isObject(b))
      return target;
    for (let key of Object.keys(b)) {
      let desc = Object.getOwnPropertyDescriptor(b, key);
      if (hasOwnProperty.call(desc, "value")) {
        if (hasOwnProperty.call(target, key) && isObject(desc.value)) {
          let existing = Object.getOwnPropertyDescriptor(target, key);
          if (isObject(existing.value) && existing.value !== desc.value) {
            target[key] = exports.merge({}, target[key], b[key]);
          } else {
            Reflect.defineProperty(target, key, desc);
          }
        } else {
          Reflect.defineProperty(target, key, desc);
        }
      } else {
        Reflect.defineProperty(target, key, desc);
      }
    }
    return target;
  };
  exports.merge = (...args) => {
    let target = {};
    for (let ele of args)
      exports.mixin(target, ele);
    return target;
  };
  exports.mixinEmitter = (obj, emitter) => {
    let proto = emitter.constructor.prototype;
    for (let key of Object.keys(proto)) {
      let val = proto[key];
      if (typeof val === "function") {
        exports.define(obj, key, val.bind(emitter));
      } else {
        exports.define(obj, key, val);
      }
    }
  };
  var onExit = (quit, code) => {
    if (onExitCalled)
      return;
    onExitCalled = true;
    onExitCallbacks.forEach((fn) => fn());
    if (quit === true) {
      process.exit(128 + code);
    }
  };
  var onSigTerm = onExit.bind(null, true, 15);
  var onSigInt = onExit.bind(null, true, 2);
  exports.onExit = (callback) => {
    if (onExitCallbacks.size === 0) {
      process.once("SIGTERM", onSigTerm);
      process.once("SIGINT", onSigInt);
      process.once("exit", onExit);
    }
    onExitCallbacks.add(callback);
    return () => {
      onExitCallbacks.delete(callback);
      if (onExitCallbacks.size === 0) {
        process.off("SIGTERM", onSigTerm);
        process.off("SIGINT", onSigInt);
        process.off("exit", onExit);
      }
    };
  };
  exports.define = (obj, key, value) => {
    Reflect.defineProperty(obj, key, { value });
  };
  exports.defineExport = (obj, key, fn) => {
    let custom;
    Reflect.defineProperty(obj, key, {
      enumerable: true,
      configurable: true,
      set(val) {
        custom = val;
      },
      get() {
        return custom ? custom() : fn();
      }
    });
  };
});

// node_modules/ansi-regex/index.js
var require_ansi_regex = __commonJS(function(exports, module) {
  module.exports = ({ onlyFirst = false } = {}) => {
    const pattern = [
      "[\\u001B\\u009B][[\\]()#;?]*(?:(?:(?:(?:;[-a-zA-Z\\d\\/#&.:=?%@~_]+)*|[a-zA-Z\\d]+(?:;[-a-zA-Z\\d\\/#&.:=?%@~_]*)*)?\\u0007)",
      "(?:(?:\\d{1,4}(?:;\\d{0,4})*)?[\\dA-PR-TZcf-ntqry=><~]))"
    ].join("|");
    return new RegExp(pattern, onlyFirst ? undefined : "g");
  };
});

// node_modules/strip-ansi/index.js
var require_strip_ansi = __commonJS(function(exports, module) {
  var ansiRegex = require_ansi_regex();
  module.exports = (string) => typeof string === "string" ? string.replace(ansiRegex(), "") : string;
});

// node_modules/enquirer/lib/combos.js
var require_combos = __commonJS(function(exports) {
  exports.ctrl = {
    a: "first",
    b: "backward",
    c: "cancel",
    d: "deleteForward",
    e: "last",
    f: "forward",
    g: "reset",
    i: "tab",
    k: "cutForward",
    l: "reset",
    n: "newItem",
    m: "cancel",
    j: "submit",
    p: "search",
    r: "remove",
    s: "save",
    u: "undo",
    w: "cutLeft",
    x: "toggleCursor",
    v: "paste"
  };
  exports.shift = {
    up: "shiftUp",
    down: "shiftDown",
    left: "shiftLeft",
    right: "shiftRight",
    tab: "prev"
  };
  exports.fn = {
    up: "pageUp",
    down: "pageDown",
    left: "pageLeft",
    right: "pageRight",
    delete: "deleteForward"
  };
  exports.option = {
    b: "backward",
    f: "forward",
    d: "cutRight",
    left: "cutLeft",
    up: "altUp",
    down: "altDown"
  };
  exports.keys = {
    pageup: "pageUp",
    pagedown: "pageDown",
    home: "home",
    end: "end",
    cancel: "cancel",
    delete: "deleteForward",
    backspace: "delete",
    down: "down",
    enter: "submit",
    escape: "cancel",
    left: "left",
    space: "space",
    number: "number",
    return: "submit",
    right: "right",
    tab: "next",
    up: "up"
  };
});

// node_modules/enquirer/lib/queue.js
var require_queue = __commonJS(function(exports, module) {
  module.exports = class Queue {
    _queue = [];
    _executing = false;
    _jobRunner = null;
    constructor(jobRunner) {
      this._jobRunner = jobRunner;
    }
    enqueue = (...args) => {
      this._queue.push(args);
      this._dequeue();
    };
    destroy() {
      this._queue.length = 0;
      this._jobRunner = null;
    }
    _dequeue() {
      if (this._executing || !this._queue.length)
        return;
      this._executing = true;
      this._jobRunner(...this._queue.shift());
      setTimeout(() => {
        this._executing = false;
        this._dequeue();
      });
    }
  };
});

// node_modules/enquirer/lib/keypress.js
var require_keypress = __commonJS(function(exports, module) {
  var readline = __require("readline");
  var combos = require_combos();
  var Queue = require_queue();
  var metaKeyCodeRe = /^(?:\x1b)([a-zA-Z0-9])$/;
  var fnKeyRe = /^(?:\x1b+)(O|N|\[|\[\[)(?:(\d+)(?:;(\d+))?([~^$])|(?:1;)?(\d+)?([a-zA-Z]))/;
  var keyName = {
    OP: "f1",
    OQ: "f2",
    OR: "f3",
    OS: "f4",
    "[11~": "f1",
    "[12~": "f2",
    "[13~": "f3",
    "[14~": "f4",
    "[[A": "f1",
    "[[B": "f2",
    "[[C": "f3",
    "[[D": "f4",
    "[[E": "f5",
    "[15~": "f5",
    "[17~": "f6",
    "[18~": "f7",
    "[19~": "f8",
    "[20~": "f9",
    "[21~": "f10",
    "[23~": "f11",
    "[24~": "f12",
    "[A": "up",
    "[B": "down",
    "[C": "right",
    "[D": "left",
    "[E": "clear",
    "[F": "end",
    "[H": "home",
    OA: "up",
    OB: "down",
    OC: "right",
    OD: "left",
    OE: "clear",
    OF: "end",
    OH: "home",
    "[1~": "home",
    "[2~": "insert",
    "[3~": "delete",
    "[4~": "end",
    "[5~": "pageup",
    "[6~": "pagedown",
    "[[5~": "pageup",
    "[[6~": "pagedown",
    "[7~": "home",
    "[8~": "end",
    "[a": "up",
    "[b": "down",
    "[c": "right",
    "[d": "left",
    "[e": "clear",
    "[2$": "insert",
    "[3$": "delete",
    "[5$": "pageup",
    "[6$": "pagedown",
    "[7$": "home",
    "[8$": "end",
    Oa: "up",
    Ob: "down",
    Oc: "right",
    Od: "left",
    Oe: "clear",
    "[2^": "insert",
    "[3^": "delete",
    "[5^": "pageup",
    "[6^": "pagedown",
    "[7^": "home",
    "[8^": "end",
    "[Z": "tab"
  };
  function isShiftKey(code) {
    return ["[a", "[b", "[c", "[d", "[e", "[2$", "[3$", "[5$", "[6$", "[7$", "[8$", "[Z"].includes(code);
  }
  function isCtrlKey(code) {
    return ["Oa", "Ob", "Oc", "Od", "Oe", "[2^", "[3^", "[5^", "[6^", "[7^", "[8^"].includes(code);
  }
  var keypress = (s = "", event = {}) => {
    let parts;
    let key = {
      name: event.name,
      ctrl: false,
      meta: false,
      shift: false,
      option: false,
      sequence: s,
      raw: s,
      ...event
    };
    if (Buffer.isBuffer(s)) {
      if (s[0] > 127 && s[1] === undefined) {
        s[0] -= 128;
        s = "\x1B" + String(s);
      } else {
        s = String(s);
      }
    } else if (s !== undefined && typeof s !== "string") {
      s = String(s);
    } else if (!s) {
      s = key.sequence || "";
    }
    key.sequence = key.sequence || s || key.name;
    if (s === "\r") {
      key.raw = undefined;
      key.name = "return";
    } else if (s === `
`) {
      key.name = "enter";
    } else if (s === "\t") {
      key.name = "tab";
    } else if (s === "\b" || s === "\x7F" || s === "\x1B\x7F" || s === "\x1B\b") {
      key.name = "backspace";
      key.meta = s.charAt(0) === "\x1B";
    } else if (s === "\x1B" || s === "\x1B\x1B") {
      key.name = "escape";
      key.meta = s.length === 2;
    } else if (s === " " || s === "\x1B ") {
      key.name = "space";
      key.meta = s.length === 2;
    } else if (s <= "\x1A") {
      key.name = String.fromCharCode(s.charCodeAt(0) + 97 - 1);
      key.ctrl = true;
    } else if (s.length === 1 && s >= "0" && s <= "9") {
      key.name = "number";
    } else if (s.length === 1 && s >= "a" && s <= "z") {
      key.name = s;
    } else if (s.length === 1 && s >= "A" && s <= "Z") {
      key.name = s.toLowerCase();
      key.shift = true;
    } else if (parts = metaKeyCodeRe.exec(s)) {
      key.meta = true;
      key.shift = /^[A-Z]$/.test(parts[1]);
    } else if (parts = fnKeyRe.exec(s)) {
      let segs = [...s];
      if (segs[0] === "\x1B" && segs[1] === "\x1B") {
        key.option = true;
      }
      let code = [parts[1], parts[2], parts[4], parts[6]].filter(Boolean).join("");
      let modifier = (parts[3] || parts[5] || 1) - 1;
      key.ctrl = !!(modifier & 4);
      key.meta = !!(modifier & 10);
      key.shift = !!(modifier & 1);
      key.code = code;
      key.name = keyName[code];
      key.shift = isShiftKey(code) || key.shift;
      key.ctrl = isCtrlKey(code) || key.ctrl;
    }
    return key;
  };
  keypress.listen = (options = {}, onKeypress) => {
    let { stdin } = options;
    if (!stdin || stdin !== process.stdin && !stdin.isTTY) {
      throw new Error("Invalid stream passed");
    }
    let rl = readline.createInterface({ terminal: true, input: stdin });
    readline.emitKeypressEvents(stdin, rl);
    const queue = new Queue((buf, key) => onKeypress(buf, keypress(buf, key), rl));
    let isRaw = stdin.isRaw;
    if (stdin.isTTY)
      stdin.setRawMode(true);
    stdin.on("keypress", queue.enqueue);
    rl.resume();
    let off = () => {
      if (stdin.isTTY)
        stdin.setRawMode(isRaw);
      stdin.removeListener("keypress", queue.enqueue);
      queue.destroy();
      rl.pause();
      rl.close();
    };
    return off;
  };
  keypress.action = (buf, key, customActions) => {
    let obj = { ...combos, ...customActions };
    if (key.ctrl) {
      key.action = obj.ctrl[key.name];
      return key;
    }
    if (key.option && obj.option) {
      key.action = obj.option[key.name];
      return key;
    }
    if (key.shift) {
      key.action = obj.shift[key.name];
      return key;
    }
    key.action = obj.keys[key.name];
    return key;
  };
  module.exports = keypress;
});

// node_modules/enquirer/lib/timer.js
var require_timer = __commonJS(function(exports, module) {
  module.exports = (prompt) => {
    prompt.timers = prompt.timers || {};
    let timers = prompt.options.timers;
    if (!timers)
      return;
    for (let key of Object.keys(timers)) {
      let opts = timers[key];
      if (typeof opts === "number") {
        opts = { interval: opts };
      }
      create(prompt, key, opts);
    }
  };
  function create(prompt, name, options = {}) {
    let timer = prompt.timers[name] = { name, start: Date.now(), ms: 0, tick: 0 };
    let ms = options.interval || 120;
    timer.frames = options.frames || [];
    timer.loading = true;
    let interval = setInterval(() => {
      timer.ms = Date.now() - timer.start;
      timer.tick++;
      prompt.render();
    }, ms);
    timer.stop = () => {
      timer.loading = false;
      clearInterval(interval);
    };
    Reflect.defineProperty(timer, "interval", { value: interval });
    prompt.once("close", () => timer.stop());
    return timer.stop;
  }
});

// node_modules/enquirer/lib/state.js
var require_state = __commonJS(function(exports, module) {
  var { define, width } = require_utils();

  class State {
    constructor(prompt) {
      let options = prompt.options;
      define(this, "_prompt", prompt);
      this.type = prompt.type;
      this.name = prompt.name;
      this.message = "";
      this.header = "";
      this.footer = "";
      this.error = "";
      this.hint = "";
      this.input = "";
      this.cursor = 0;
      this.index = 0;
      this.lines = 0;
      this.tick = 0;
      this.prompt = "";
      this.buffer = "";
      this.width = width(options.stdout || process.stdout);
      Object.assign(this, options);
      this.name = this.name || this.message;
      this.message = this.message || this.name;
      this.symbols = prompt.symbols;
      this.styles = prompt.styles;
      this.required = new Set;
      this.cancelled = false;
      this.submitted = false;
    }
    clone() {
      let state = { ...this };
      state.status = this.status;
      state.buffer = Buffer.from(state.buffer);
      delete state.clone;
      return state;
    }
    set color(val) {
      this._color = val;
    }
    get color() {
      let styles = this.prompt.styles;
      if (this.cancelled)
        return styles.cancelled;
      if (this.submitted)
        return styles.submitted;
      let color = this._color || styles[this.status];
      return typeof color === "function" ? color : styles.pending;
    }
    set loading(value) {
      this._loading = value;
    }
    get loading() {
      if (typeof this._loading === "boolean")
        return this._loading;
      if (this.loadingChoices)
        return "choices";
      return false;
    }
    get status() {
      if (this.cancelled)
        return "cancelled";
      if (this.submitted)
        return "submitted";
      return "pending";
    }
  }
  module.exports = State;
});

// node_modules/enquirer/lib/styles.js
var require_styles = __commonJS(function(exports, module) {
  var utils = require_utils();
  var colors = require_ansi_colors();
  var styles = {
    default: colors.noop,
    noop: colors.noop,
    set inverse(custom) {
      this._inverse = custom;
    },
    get inverse() {
      return this._inverse || utils.inverse(this.primary);
    },
    set complement(custom) {
      this._complement = custom;
    },
    get complement() {
      return this._complement || utils.complement(this.primary);
    },
    primary: colors.cyan,
    success: colors.green,
    danger: colors.magenta,
    strong: colors.bold,
    warning: colors.yellow,
    muted: colors.dim,
    disabled: colors.gray,
    dark: colors.dim.gray,
    underline: colors.underline,
    set info(custom) {
      this._info = custom;
    },
    get info() {
      return this._info || this.primary;
    },
    set em(custom) {
      this._em = custom;
    },
    get em() {
      return this._em || this.primary.underline;
    },
    set heading(custom) {
      this._heading = custom;
    },
    get heading() {
      return this._heading || this.muted.underline;
    },
    set pending(custom) {
      this._pending = custom;
    },
    get pending() {
      return this._pending || this.primary;
    },
    set submitted(custom) {
      this._submitted = custom;
    },
    get submitted() {
      return this._submitted || this.success;
    },
    set cancelled(custom) {
      this._cancelled = custom;
    },
    get cancelled() {
      return this._cancelled || this.danger;
    },
    set typing(custom) {
      this._typing = custom;
    },
    get typing() {
      return this._typing || this.dim;
    },
    set placeholder(custom) {
      this._placeholder = custom;
    },
    get placeholder() {
      return this._placeholder || this.primary.dim;
    },
    set highlight(custom) {
      this._highlight = custom;
    },
    get highlight() {
      return this._highlight || this.inverse;
    }
  };
  styles.merge = (options = {}) => {
    if (options.styles && typeof options.styles.enabled === "boolean") {
      colors.enabled = options.styles.enabled;
    }
    if (options.styles && typeof options.styles.visible === "boolean") {
      colors.visible = options.styles.visible;
    }
    let result = utils.merge({}, styles, options.styles);
    delete result.merge;
    for (let key of Object.keys(colors)) {
      if (!hasOwnProperty.call(result, key)) {
        Reflect.defineProperty(result, key, { get: () => colors[key] });
      }
    }
    for (let key of Object.keys(colors.styles)) {
      if (!hasOwnProperty.call(result, key)) {
        Reflect.defineProperty(result, key, { get: () => colors[key] });
      }
    }
    return result;
  };
  module.exports = styles;
});

// node_modules/enquirer/lib/symbols.js
var require_symbols2 = __commonJS(function(exports, module) {
  var isWindows = process.platform === "win32";
  var colors = require_ansi_colors();
  var utils = require_utils();
  var symbols = {
    ...colors.symbols,
    upDownDoubleArrow: "\u21D5",
    upDownDoubleArrow2: "\u2B0D",
    upDownArrow: "\u2195",
    asterisk: "*",
    asterism: "\u2042",
    bulletWhite: "\u25E6",
    electricArrow: "\u2301",
    ellipsisLarge: "\u22EF",
    ellipsisSmall: "\u2026",
    fullBlock: "\u2588",
    identicalTo: "\u2261",
    indicator: colors.symbols.check,
    leftAngle: "\u2039",
    mark: "\u203B",
    minus: "\u2212",
    multiplication: "\xD7",
    obelus: "\xF7",
    percent: "%",
    pilcrow: "\xB6",
    pilcrow2: "\u2761",
    pencilUpRight: "\u2710",
    pencilDownRight: "\u270E",
    pencilRight: "\u270F",
    plus: "+",
    plusMinus: "\xB1",
    pointRight: "\u261E",
    rightAngle: "\u203A",
    section: "\xA7",
    hexagon: { off: "\u2B21", on: "\u2B22", disabled: "\u2B22" },
    ballot: { on: "\u2611", off: "\u2610", disabled: "\u2612" },
    stars: { on: "\u2605", off: "\u2606", disabled: "\u2606" },
    folder: { on: "\u25BC", off: "\u25B6", disabled: "\u25B6" },
    prefix: {
      pending: colors.symbols.question,
      submitted: colors.symbols.check,
      cancelled: colors.symbols.cross
    },
    separator: {
      pending: colors.symbols.pointerSmall,
      submitted: colors.symbols.middot,
      cancelled: colors.symbols.middot
    },
    radio: {
      off: isWindows ? "( )" : "\u25EF",
      on: isWindows ? "(*)" : "\u25C9",
      disabled: isWindows ? "(|)" : "\u24BE"
    },
    numbers: ["\u24EA", "\u2460", "\u2461", "\u2462", "\u2463", "\u2464", "\u2465", "\u2466", "\u2467", "\u2468", "\u2469", "\u246A", "\u246B", "\u246C", "\u246D", "\u246E", "\u246F", "\u2470", "\u2471", "\u2472", "\u2473", "\u3251", "\u3252", "\u3253", "\u3254", "\u3255", "\u3256", "\u3257", "\u3258", "\u3259", "\u325A", "\u325B", "\u325C", "\u325D", "\u325E", "\u325F", "\u32B1", "\u32B2", "\u32B3", "\u32B4", "\u32B5", "\u32B6", "\u32B7", "\u32B8", "\u32B9", "\u32BA", "\u32BB", "\u32BC", "\u32BD", "\u32BE", "\u32BF"]
  };
  symbols.merge = (options) => {
    let result = utils.merge({}, colors.symbols, symbols, options.symbols);
    delete result.merge;
    return result;
  };
  module.exports = symbols;
});

// node_modules/enquirer/lib/theme.js
var require_theme = __commonJS(function(exports, module) {
  var styles = require_styles();
  var symbols = require_symbols2();
  var utils = require_utils();
  module.exports = (prompt) => {
    prompt.options = utils.merge({}, prompt.options.theme, prompt.options);
    prompt.symbols = symbols.merge(prompt.options);
    prompt.styles = styles.merge(prompt.options);
  };
});

// node_modules/enquirer/lib/ansi.js
var require_ansi = __commonJS(function(exports, module) {
  var isTerm = process.env.TERM_PROGRAM === "Apple_Terminal";
  var stripAnsi = require_strip_ansi();
  var utils = require_utils();
  var ansi = module.exports = exports;
  var ESC = "\x1B[";
  var BEL = "\x07";
  var hidden = false;
  var code = ansi.code = {
    bell: BEL,
    beep: BEL,
    beginning: `${ESC}G`,
    down: `${ESC}J`,
    esc: ESC,
    getPosition: `${ESC}6n`,
    hide: `${ESC}?25l`,
    line: `${ESC}2K`,
    lineEnd: `${ESC}K`,
    lineStart: `${ESC}1K`,
    restorePosition: ESC + (isTerm ? "8" : "u"),
    savePosition: ESC + (isTerm ? "7" : "s"),
    screen: `${ESC}2J`,
    show: `${ESC}?25h`,
    up: `${ESC}1J`
  };
  var cursor = ansi.cursor = {
    get hidden() {
      return hidden;
    },
    hide() {
      hidden = true;
      return code.hide;
    },
    show() {
      hidden = false;
      return code.show;
    },
    forward: (count = 1) => `${ESC}${count}C`,
    backward: (count = 1) => `${ESC}${count}D`,
    nextLine: (count = 1) => `${ESC}E`.repeat(count),
    prevLine: (count = 1) => `${ESC}F`.repeat(count),
    up: (count = 1) => count ? `${ESC}${count}A` : "",
    down: (count = 1) => count ? `${ESC}${count}B` : "",
    right: (count = 1) => count ? `${ESC}${count}C` : "",
    left: (count = 1) => count ? `${ESC}${count}D` : "",
    to(x, y) {
      return y ? `${ESC}${y + 1};${x + 1}H` : `${ESC}${x + 1}G`;
    },
    move(x = 0, y = 0) {
      let res = "";
      res += x < 0 ? cursor.left(-x) : x > 0 ? cursor.right(x) : "";
      res += y < 0 ? cursor.up(-y) : y > 0 ? cursor.down(y) : "";
      return res;
    },
    strLen(str) {
      var realLength = 0, len = str.length, charCode = -1;
      for (var i = 0;i < len; i++) {
        charCode = str.charCodeAt(i);
        if (charCode >= 0 && charCode <= 128)
          realLength += 1;
        else
          realLength += 2;
      }
      return realLength;
    },
    restore(state = {}) {
      let { after, cursor, initial, input, prompt, size, value } = state;
      initial = utils.isPrimitive(initial) ? String(initial) : "";
      input = utils.isPrimitive(input) ? String(input) : "";
      value = utils.isPrimitive(value) ? String(value) : "";
      if (size) {
        let codes = ansi.cursor.up(size) + ansi.cursor.to(this.strLen(prompt));
        let diff = input.length - cursor;
        if (diff > 0) {
          codes += ansi.cursor.left(diff);
        }
        return codes;
      }
      if (value || after) {
        let pos = !input && !!initial ? -this.strLen(initial) : -this.strLen(input) + cursor;
        if (after)
          pos -= this.strLen(after);
        if (input === "" && initial && !prompt.includes(initial)) {
          pos += this.strLen(initial);
        }
        return ansi.cursor.move(pos);
      }
    }
  };
  var erase = ansi.erase = {
    screen: code.screen,
    up: code.up,
    down: code.down,
    line: code.line,
    lineEnd: code.lineEnd,
    lineStart: code.lineStart,
    lines(n) {
      let str = "";
      for (let i = 0;i < n; i++) {
        str += ansi.erase.line + (i < n - 1 ? ansi.cursor.up(1) : "");
      }
      if (n)
        str += ansi.code.beginning;
      return str;
    }
  };
  ansi.clear = (input = "", columns = process.stdout.columns) => {
    if (!columns)
      return erase.line + cursor.to(0);
    let width = (str) => [...stripAnsi(str)].length;
    let lines = input.split(/\r?\n/);
    let rows = 0;
    for (let line of lines) {
      rows += 1 + Math.floor(Math.max(width(line) - 1, 0) / columns);
    }
    return (erase.line + cursor.prevLine()).repeat(rows - 1) + erase.line + cursor.to(0);
  };
});

// node_modules/enquirer/lib/prompt.js
var require_prompt = __commonJS(function(exports, module) {
  var Events = __require("events");
  var stripAnsi = require_strip_ansi();
  var keypress = require_keypress();
  var timer = require_timer();
  var State = require_state();
  var theme = require_theme();
  var utils = require_utils();
  var ansi = require_ansi();

  class Prompt extends Events {
    constructor(options = {}) {
      super();
      this.name = options.name;
      this.type = options.type;
      this.options = options;
      theme(this);
      timer(this);
      this.state = new State(this);
      this.initial = [options.initial, options.default].find((v) => v != null);
      this.stdout = options.stdout || process.stdout;
      this.stdin = options.stdin || process.stdin;
      this.scale = options.scale || 1;
      this.term = this.options.term || process.env.TERM_PROGRAM;
      this.margin = margin(this.options.margin);
      this.setMaxListeners(0);
      setOptions(this);
    }
    async keypress(input, event = {}) {
      this.keypressed = true;
      let key = keypress.action(input, keypress(input, event), this.options.actions);
      this.state.keypress = key;
      this.emit("keypress", input, key);
      this.emit("state", this.state.clone());
      const fn = this.options[key.action] || this[key.action] || this.dispatch;
      if (typeof fn === "function") {
        return await fn.call(this, input, key);
      }
      this.alert();
    }
    alert() {
      delete this.state.alert;
      if (this.options.show === false) {
        this.emit("alert");
      } else {
        this.stdout.write(ansi.code.beep);
      }
    }
    cursorHide() {
      this.stdout.write(ansi.cursor.hide());
      const releaseOnExit = utils.onExit(() => this.cursorShow());
      this.on("close", () => {
        this.cursorShow();
        releaseOnExit();
      });
    }
    cursorShow() {
      this.stdout.write(ansi.cursor.show());
    }
    write(str) {
      if (!str)
        return;
      if (this.stdout && this.state.show !== false) {
        this.stdout.write(str);
      }
      this.state.buffer += str;
    }
    clear(lines = 0) {
      let buffer = this.state.buffer;
      this.state.buffer = "";
      if (!buffer && !lines || this.options.show === false)
        return;
      this.stdout.write(ansi.cursor.down(lines) + ansi.clear(buffer, this.width));
    }
    restore() {
      if (this.state.closed || this.options.show === false)
        return;
      let { prompt, after, rest } = this.sections();
      let { cursor: cursor2, initial = "", input = "", value = "" } = this;
      let size = this.state.size = rest.length;
      let state = { after, cursor: cursor2, initial, input, prompt, size, value };
      let codes = ansi.cursor.restore(state);
      if (codes) {
        this.stdout.write(codes);
      }
    }
    sections() {
      let { buffer, input, prompt } = this.state;
      prompt = stripAnsi(prompt);
      let buf = stripAnsi(buffer);
      let idx = buf.indexOf(prompt);
      let header = buf.slice(0, idx);
      let rest = buf.slice(idx);
      let lines = rest.split(`
`);
      let first = lines[0];
      let last = lines[lines.length - 1];
      let promptLine = prompt + (input ? " " + input : "");
      let len = promptLine.length;
      let after = len < first.length ? first.slice(len + 1) : "";
      return { header, prompt: first, after, rest: lines.slice(1), last };
    }
    async submit() {
      this.state.submitted = true;
      this.state.validating = true;
      if (this.options.onSubmit) {
        await this.options.onSubmit.call(this, this.name, this.value, this);
      }
      let result = this.state.error || await this.validate(this.value, this.state);
      if (result !== true) {
        let error = `
` + this.symbols.pointer + " ";
        if (typeof result === "string") {
          error += result.trim();
        } else {
          error += "Invalid input";
        }
        this.state.error = `
` + this.styles.danger(error);
        this.state.submitted = false;
        await this.render();
        await this.alert();
        this.state.validating = false;
        this.state.error = undefined;
        return;
      }
      this.state.validating = false;
      await this.render();
      await this.close();
      this.value = await this.result(this.value);
      this.emit("submit", this.value);
    }
    async cancel(err) {
      this.state.cancelled = this.state.submitted = true;
      await this.render();
      await this.close();
      if (typeof this.options.onCancel === "function") {
        await this.options.onCancel.call(this, this.name, this.value, this);
      }
      this.emit("cancel", await this.error(err));
    }
    async close() {
      this.state.closed = true;
      try {
        let sections = this.sections();
        let lines = Math.ceil(sections.prompt.length / this.width);
        if (sections.rest) {
          this.write(ansi.cursor.down(sections.rest.length));
        }
        this.write(`
`.repeat(lines));
      } catch (err) {}
      this.emit("close");
    }
    start() {
      if (!this.stop && this.options.show !== false) {
        this.stop = keypress.listen(this, this.keypress.bind(this));
        this.once("close", this.stop);
        this.emit("start", this);
      }
    }
    async skip() {
      this.skipped = this.options.skip === true;
      if (typeof this.options.skip === "function") {
        this.skipped = await this.options.skip.call(this, this.name, this.value);
      }
      return this.skipped;
    }
    async initialize() {
      let { format, options, result } = this;
      this.format = () => format.call(this, this.value);
      this.result = () => result.call(this, this.value);
      if (typeof options.initial === "function") {
        this.initial = await options.initial.call(this, this);
      }
      if (typeof options.onRun === "function") {
        await options.onRun.call(this, this);
      }
      if (typeof options.onSubmit === "function") {
        let onSubmit = options.onSubmit.bind(this);
        let submit = this.submit.bind(this);
        delete this.options.onSubmit;
        this.submit = async () => {
          await onSubmit(this.name, this.value, this);
          return submit();
        };
      }
      await this.start();
      await this.render();
    }
    render() {
      throw new Error("expected prompt to have a custom render method");
    }
    run() {
      return new Promise(async (resolve, reject) => {
        this.once("submit", resolve);
        this.once("cancel", reject);
        if (await this.skip()) {
          this.render = () => {};
          return this.submit();
        }
        await this.initialize();
        this.emit("run");
      });
    }
    async element(name, choice, i) {
      let { options, state, symbols, timers } = this;
      let timer = timers && timers[name];
      state.timer = timer;
      let value = options[name] || state[name] || symbols[name];
      let val = choice && choice[name] != null ? choice[name] : await value;
      if (val === "")
        return val;
      let res = await this.resolve(val, state, choice, i);
      if (!res && choice && choice[name]) {
        return this.resolve(value, state, choice, i);
      }
      return res;
    }
    async prefix() {
      let element = await this.element("prefix") || this.symbols;
      let timer = this.timers && this.timers.prefix;
      let state = this.state;
      state.timer = timer;
      if (utils.isObject(element))
        element = element[state.status] || element.pending;
      if (!utils.hasColor(element)) {
        let style = this.styles[state.status] || this.styles.pending;
        return style(element);
      }
      return element;
    }
    async message() {
      let message = await this.element("message");
      if (!utils.hasColor(message)) {
        return this.styles.strong(message);
      }
      return message;
    }
    async separator() {
      let element = await this.element("separator") || this.symbols;
      let timer = this.timers && this.timers.separator;
      let state = this.state;
      state.timer = timer;
      let value = element[state.status] || element.pending || state.separator;
      let ele = await this.resolve(value, state);
      if (utils.isObject(ele))
        ele = ele[state.status] || ele.pending;
      if (!utils.hasColor(ele)) {
        return this.styles.muted(ele);
      }
      return ele;
    }
    async pointer(choice, i) {
      let val = await this.element("pointer", choice, i);
      if (typeof val === "string" && utils.hasColor(val)) {
        return val;
      }
      if (val) {
        let styles = this.styles;
        let focused = this.index === i;
        let style = focused ? styles.primary : (val) => val;
        let ele = await this.resolve(val[focused ? "on" : "off"] || val, this.state);
        let styled = !utils.hasColor(ele) ? style(ele) : ele;
        return focused ? styled : " ".repeat(ele.length);
      }
    }
    async indicator(choice, i) {
      let val = await this.element("indicator", choice, i);
      if (typeof val === "string" && utils.hasColor(val)) {
        return val;
      }
      if (val) {
        let styles = this.styles;
        let enabled = choice.enabled === true;
        let style = enabled ? styles.success : styles.dark;
        let ele = val[enabled ? "on" : "off"] || val;
        return !utils.hasColor(ele) ? style(ele) : ele;
      }
      return "";
    }
    body() {
      return null;
    }
    footer() {
      if (this.state.status === "pending") {
        return this.element("footer");
      }
    }
    header() {
      if (this.state.status === "pending") {
        return this.element("header");
      }
    }
    async hint() {
      if (this.state.status === "pending" && !this.isValue(this.state.input)) {
        let hint = await this.element("hint");
        if (!utils.hasColor(hint)) {
          return this.styles.muted(hint);
        }
        return hint;
      }
    }
    error(err) {
      return !this.state.submitted ? err || this.state.error : "";
    }
    format(value) {
      return value;
    }
    result(value) {
      return value;
    }
    validate(value) {
      if (this.options.required === true) {
        return this.isValue(value);
      }
      return true;
    }
    isValue(value) {
      return value != null && value !== "";
    }
    resolve(value, ...args) {
      return utils.resolve(this, value, ...args);
    }
    get base() {
      return Prompt.prototype;
    }
    get style() {
      return this.styles[this.state.status];
    }
    get height() {
      return this.options.rows || utils.height(this.stdout, 25);
    }
    get width() {
      return this.options.columns || utils.width(this.stdout, 80);
    }
    get size() {
      return { width: this.width, height: this.height };
    }
    set cursor(value) {
      this.state.cursor = value;
    }
    get cursor() {
      return this.state.cursor;
    }
    set input(value) {
      this.state.input = value;
    }
    get input() {
      return this.state.input;
    }
    set value(value) {
      this.state.value = value;
    }
    get value() {
      let { input, value } = this.state;
      let result = [value, input].find(this.isValue.bind(this));
      return this.isValue(result) ? result : this.initial;
    }
    static get prompt() {
      return (options) => new this(options).run();
    }
  }
  function setOptions(prompt) {
    let isValidKey = (key) => {
      return prompt[key] === undefined || typeof prompt[key] === "function";
    };
    let ignore = [
      "actions",
      "choices",
      "initial",
      "margin",
      "roles",
      "styles",
      "symbols",
      "theme",
      "timers",
      "value"
    ];
    let ignoreFn = [
      "body",
      "footer",
      "error",
      "header",
      "hint",
      "indicator",
      "message",
      "prefix",
      "separator",
      "skip"
    ];
    for (let key of Object.keys(prompt.options)) {
      if (ignore.includes(key))
        continue;
      if (/^on[A-Z]/.test(key))
        continue;
      let option = prompt.options[key];
      if (typeof option === "function" && isValidKey(key)) {
        if (!ignoreFn.includes(key)) {
          prompt[key] = option.bind(prompt);
        }
      } else if (typeof prompt[key] !== "function") {
        prompt[key] = option;
      }
    }
  }
  function margin(value) {
    if (typeof value === "number") {
      value = [value, value, value, value];
    }
    let arr = [].concat(value || []);
    let pad = (i) => i % 2 === 0 ? `
` : " ";
    let res = [];
    for (let i = 0;i < 4; i++) {
      let char = pad(i);
      if (arr[i]) {
        res.push(char.repeat(arr[i]));
      } else {
        res.push("");
      }
    }
    return res;
  }
  module.exports = Prompt;
});

// node_modules/enquirer/lib/roles.js
var require_roles = __commonJS(function(exports, module) {
  var utils = require_utils();
  var roles = {
    default(prompt, choice) {
      return choice;
    },
    checkbox(prompt, choice) {
      throw new Error("checkbox role is not implemented yet");
    },
    editable(prompt, choice) {
      throw new Error("editable role is not implemented yet");
    },
    expandable(prompt, choice) {
      throw new Error("expandable role is not implemented yet");
    },
    heading(prompt, choice) {
      choice.disabled = "";
      choice.indicator = [choice.indicator, " "].find((v) => v != null);
      choice.message = choice.message || "";
      return choice;
    },
    input(prompt, choice) {
      throw new Error("input role is not implemented yet");
    },
    option(prompt, choice) {
      return roles.default(prompt, choice);
    },
    radio(prompt, choice) {
      throw new Error("radio role is not implemented yet");
    },
    separator(prompt, choice) {
      choice.disabled = "";
      choice.indicator = [choice.indicator, " "].find((v) => v != null);
      choice.message = choice.message || prompt.symbols.line.repeat(5);
      return choice;
    },
    spacer(prompt, choice) {
      return choice;
    }
  };
  module.exports = (name, options = {}) => {
    let role = utils.merge({}, roles, options.roles);
    return role[name] || role.default;
  };
});

// node_modules/enquirer/lib/types/array.js
var require_array = __commonJS(function(exports, module) {
  var stripAnsi = require_strip_ansi();
  var Prompt = require_prompt();
  var roles = require_roles();
  var utils = require_utils();
  var { reorder, scrollUp, scrollDown, isObject, swap } = utils;

  class ArrayPrompt extends Prompt {
    constructor(options) {
      super(options);
      this.cursorHide();
      this.maxSelected = options.maxSelected || Infinity;
      this.multiple = options.multiple || false;
      this.initial = options.initial || 0;
      this.delay = options.delay || 0;
      this.longest = 0;
      this.num = "";
    }
    async initialize() {
      if (typeof this.options.initial === "function") {
        this.initial = await this.options.initial.call(this);
      }
      await this.reset(true);
      await super.initialize();
    }
    async reset() {
      let { choices, initial, autofocus, suggest } = this.options;
      this.state._choices = [];
      this.state.choices = [];
      this.choices = await Promise.all(await this.toChoices(choices));
      this.choices.forEach((ch) => ch.enabled = false);
      if (typeof suggest !== "function" && this.selectable.length === 0) {
        throw new Error("At least one choice must be selectable");
      }
      if (isObject(initial))
        initial = Object.keys(initial);
      if (Array.isArray(initial)) {
        if (autofocus != null)
          this.index = this.findIndex(autofocus);
        initial.forEach((v) => this.enable(this.find(v)));
        await this.render();
      } else {
        if (autofocus != null)
          initial = autofocus;
        if (typeof initial === "string")
          initial = this.findIndex(initial);
        if (typeof initial === "number" && initial > -1) {
          this.index = Math.max(0, Math.min(initial, this.choices.length));
          this.enable(this.find(this.index));
        }
      }
      if (this.isDisabled(this.focused)) {
        await this.down();
      }
    }
    async toChoices(value, parent) {
      this.state.loadingChoices = true;
      let choices = [];
      let index = 0;
      let toChoices = async (items, parent) => {
        if (typeof items === "function")
          items = await items.call(this);
        if (items instanceof Promise)
          items = await items;
        for (let i = 0;i < items.length; i++) {
          let choice = items[i] = await this.toChoice(items[i], index++, parent);
          choices.push(choice);
          if (choice.choices) {
            await toChoices(choice.choices, choice);
          }
        }
        return choices;
      };
      return toChoices(value, parent).then((choices) => {
        this.state.loadingChoices = false;
        return choices;
      });
    }
    async toChoice(ele, i, parent) {
      if (typeof ele === "function")
        ele = await ele.call(this, this);
      if (ele instanceof Promise)
        ele = await ele;
      if (typeof ele === "string")
        ele = { name: ele };
      if (ele.normalized)
        return ele;
      ele.normalized = true;
      let origVal = ele.value;
      let role = roles(ele.role, this.options);
      ele = role(this, ele);
      if (typeof ele.disabled === "string" && !ele.hint) {
        ele.hint = ele.disabled;
        ele.disabled = true;
      }
      if (ele.disabled === true && ele.hint == null) {
        ele.hint = "(disabled)";
      }
      if (ele.index != null)
        return ele;
      ele.name = ele.name || ele.key || ele.title || ele.value || ele.message;
      ele.message = ele.message || ele.name || "";
      ele.value = [ele.value, ele.name].find(this.isValue.bind(this));
      ele.input = "";
      ele.index = i;
      ele.cursor = 0;
      utils.define(ele, "parent", parent);
      ele.level = parent ? parent.level + 1 : 1;
      if (ele.indent == null) {
        ele.indent = parent ? parent.indent + "  " : ele.indent || "";
      }
      ele.path = parent ? parent.path + "." + ele.name : ele.name;
      ele.enabled = !!(this.multiple && !this.isDisabled(ele) && (ele.enabled || this.isSelected(ele)));
      if (!this.isDisabled(ele)) {
        this.longest = Math.max(this.longest, stripAnsi(ele.message).length);
      }
      let choice = { ...ele };
      ele.reset = (input = choice.input, value = choice.value) => {
        for (let key of Object.keys(choice))
          ele[key] = choice[key];
        ele.input = input;
        ele.value = value;
      };
      if (origVal == null && typeof ele.initial === "function") {
        ele.input = await ele.initial.call(this, this.state, ele, i);
      }
      return ele;
    }
    async onChoice(choice, i) {
      this.emit("choice", choice, i, this);
      if (typeof choice.onChoice === "function") {
        await choice.onChoice.call(this, this.state, choice, i);
      }
    }
    async addChoice(ele, i, parent) {
      let choice = await this.toChoice(ele, i, parent);
      this.choices.push(choice);
      this.index = this.choices.length - 1;
      this.limit = this.choices.length;
      return choice;
    }
    async newItem(item, i, parent) {
      let ele = { name: "New choice name?", editable: true, newChoice: true, ...item };
      let choice = await this.addChoice(ele, i, parent);
      choice.updateChoice = () => {
        delete choice.newChoice;
        choice.name = choice.message = choice.input;
        choice.input = "";
        choice.cursor = 0;
      };
      return this.render();
    }
    indent(choice) {
      if (choice.indent == null) {
        return choice.level > 1 ? "  ".repeat(choice.level - 1) : "";
      }
      return choice.indent;
    }
    dispatch(s, key) {
      if (this.multiple && this[key.name])
        return this[key.name]();
      this.alert();
    }
    focus(choice, enabled) {
      if (typeof enabled !== "boolean")
        enabled = choice.enabled;
      if (enabled && !choice.enabled && this.selected.length >= this.maxSelected) {
        return this.alert();
      }
      this.index = choice.index;
      choice.enabled = enabled && !this.isDisabled(choice);
      return choice;
    }
    space() {
      if (!this.multiple)
        return this.alert();
      if (!this.focused)
        return;
      this.toggle(this.focused);
      return this.render();
    }
    a() {
      if (this.maxSelected < this.choices.length)
        return this.alert();
      let enabled = this.selectable.every((ch) => ch.enabled);
      this.choices.forEach((ch) => ch.enabled = !enabled);
      return this.render();
    }
    i() {
      if (this.choices.length - this.selected.length > this.maxSelected) {
        return this.alert();
      }
      this.choices.forEach((ch) => ch.enabled = !ch.enabled);
      return this.render();
    }
    g() {
      if (!this.choices.some((ch) => !!ch.parent))
        return this.a();
      const focused = this.focused;
      this.toggle(focused.parent && !focused.choices ? focused.parent : focused);
      return this.render();
    }
    toggle(choice, enabled) {
      if (!choice.enabled && this.selected.length >= this.maxSelected) {
        return this.alert();
      }
      if (typeof enabled !== "boolean")
        enabled = !choice.enabled;
      choice.enabled = enabled;
      if (choice.choices) {
        choice.choices.forEach((ch) => this.toggle(ch, enabled));
      }
      let parent = choice.parent;
      while (parent) {
        let choices = parent.choices.filter((ch) => this.isDisabled(ch));
        parent.enabled = choices.every((ch) => ch.enabled === true);
        parent = parent.parent;
      }
      reset(this, this.choices);
      this.emit("toggle", choice, this);
      return choice;
    }
    enable(choice) {
      if (this.selected.length >= this.maxSelected)
        return this.alert();
      choice.enabled = !this.isDisabled(choice);
      choice.choices && choice.choices.forEach(this.enable.bind(this));
      return choice;
    }
    disable(choice) {
      choice.enabled = false;
      choice.choices && choice.choices.forEach(this.disable.bind(this));
      return choice;
    }
    number(n) {
      this.num += n;
      let number = (num) => {
        let i = Number(num);
        if (i > this.choices.length - 1)
          return this.alert();
        let focused = this.focused;
        let choice = this.choices.find((ch) => i === ch.index);
        if (!choice.enabled && this.selected.length >= this.maxSelected) {
          return this.alert();
        }
        if (this.visible.indexOf(choice) === -1) {
          let choices = reorder(this.choices);
          let actualIdx = choices.indexOf(choice);
          if (focused.index > actualIdx) {
            let start = choices.slice(actualIdx, actualIdx + this.limit);
            let end = choices.filter((ch) => !start.includes(ch));
            this.choices = start.concat(end);
          } else {
            let pos = actualIdx - this.limit + 1;
            this.choices = choices.slice(pos).concat(choices.slice(0, pos));
          }
        }
        this.index = this.choices.indexOf(choice);
        this.toggle(this.focused);
        return this.render();
      };
      clearTimeout(this.numberTimeout);
      return new Promise((resolve) => {
        let len = this.choices.length;
        let num = this.num;
        let handle = (val = false, res) => {
          clearTimeout(this.numberTimeout);
          if (val)
            res = number(num);
          this.num = "";
          resolve(res);
        };
        if (num === "0" || num.length === 1 && Number(num + "0") > len) {
          return handle(true);
        }
        if (Number(num) > len) {
          return handle(false, this.alert());
        }
        this.numberTimeout = setTimeout(() => handle(true), this.delay);
      });
    }
    home() {
      this.choices = reorder(this.choices);
      this.index = 0;
      return this.render();
    }
    end() {
      let pos = this.choices.length - this.limit;
      let choices = reorder(this.choices);
      this.choices = choices.slice(pos).concat(choices.slice(0, pos));
      this.index = this.limit - 1;
      return this.render();
    }
    first() {
      this.index = 0;
      return this.render();
    }
    last() {
      this.index = this.visible.length - 1;
      return this.render();
    }
    prev() {
      if (this.visible.length <= 1)
        return this.alert();
      return this.up();
    }
    next() {
      if (this.visible.length <= 1)
        return this.alert();
      return this.down();
    }
    right() {
      if (this.cursor >= this.input.length)
        return this.alert();
      this.cursor++;
      return this.render();
    }
    left() {
      if (this.cursor <= 0)
        return this.alert();
      this.cursor--;
      return this.render();
    }
    up() {
      let len = this.choices.length;
      let vis = this.visible.length;
      let idx = this.index;
      if (this.options.scroll === false && idx === 0) {
        return this.alert();
      }
      if (len > vis && idx === 0) {
        return this.scrollUp();
      }
      this.index = (idx - 1 % len + len) % len;
      if (this.isDisabled() && !this.allChoicesAreDisabled()) {
        return this.up();
      }
      return this.render();
    }
    down() {
      let len = this.choices.length;
      let vis = this.visible.length;
      let idx = this.index;
      if (this.options.scroll === false && idx === vis - 1) {
        return this.alert();
      }
      if (len > vis && idx === vis - 1) {
        return this.scrollDown();
      }
      this.index = (idx + 1) % len;
      if (this.isDisabled() && !this.allChoicesAreDisabled()) {
        return this.down();
      }
      return this.render();
    }
    scrollUp(i = 0) {
      this.choices = scrollUp(this.choices);
      this.index = i;
      if (this.isDisabled()) {
        return this.up();
      }
      return this.render();
    }
    scrollDown(i = this.visible.length - 1) {
      this.choices = scrollDown(this.choices);
      this.index = i;
      if (this.isDisabled()) {
        return this.down();
      }
      return this.render();
    }
    async shiftUp() {
      if (this.options.sort === true) {
        this.sorting = true;
        this.swap(this.index - 1);
        await this.up();
        this.sorting = false;
        return;
      }
      return this.scrollUp(this.index);
    }
    async shiftDown() {
      if (this.options.sort === true) {
        this.sorting = true;
        this.swap(this.index + 1);
        await this.down();
        this.sorting = false;
        return;
      }
      return this.scrollDown(this.index);
    }
    pageUp() {
      if (this.visible.length <= 1)
        return this.alert();
      this.limit = Math.max(this.limit - 1, 0);
      this.index = Math.min(this.limit - 1, this.index);
      this._limit = this.limit;
      if (this.isDisabled()) {
        return this.up();
      }
      return this.render();
    }
    pageDown() {
      if (this.visible.length >= this.choices.length)
        return this.alert();
      this.index = Math.max(0, this.index);
      this.limit = Math.min(this.limit + 1, this.choices.length);
      this._limit = this.limit;
      if (this.isDisabled()) {
        return this.down();
      }
      return this.render();
    }
    swap(pos) {
      swap(this.choices, this.index, pos);
    }
    allChoicesAreDisabled(choices = this.choices) {
      return choices.every((choice) => this.isDisabled(choice));
    }
    isDisabled(choice = this.focused) {
      let keys = ["disabled", "collapsed", "hidden", "completing", "readonly"];
      if (choice && keys.some((key) => choice[key] === true)) {
        return true;
      }
      return choice && choice.role === "heading";
    }
    isEnabled(choice = this.focused) {
      if (Array.isArray(choice))
        return choice.every((ch) => this.isEnabled(ch));
      if (choice.choices) {
        let choices = choice.choices.filter((ch) => !this.isDisabled(ch));
        return choice.enabled && choices.every((ch) => this.isEnabled(ch));
      }
      return choice.enabled && !this.isDisabled(choice);
    }
    isChoice(choice, value) {
      return choice.name === value || choice.index === Number(value);
    }
    isSelected(choice) {
      if (Array.isArray(this.initial)) {
        return this.initial.some((value) => this.isChoice(choice, value));
      }
      return this.isChoice(choice, this.initial);
    }
    map(names = [], prop = "value") {
      return [].concat(names || []).reduce((acc, name) => {
        acc[name] = this.find(name, prop);
        return acc;
      }, {});
    }
    filter(value, prop) {
      let isChoice = (ele, i) => [ele.name, i].includes(value);
      let fn = typeof value === "function" ? value : isChoice;
      let choices = this.options.multiple ? this.state._choices : this.choices;
      let result = choices.filter(fn);
      if (prop) {
        return result.map((ch) => ch[prop]);
      }
      return result;
    }
    find(value, prop) {
      if (isObject(value))
        return prop ? value[prop] : value;
      let isChoice = (ele, i) => [ele.name, i].includes(value);
      let fn = typeof value === "function" ? value : isChoice;
      let choice = this.choices.find(fn);
      if (choice) {
        return prop ? choice[prop] : choice;
      }
    }
    findIndex(value) {
      return this.choices.indexOf(this.find(value));
    }
    async submit() {
      let choice = this.focused;
      if (!choice)
        return this.alert();
      if (choice.newChoice) {
        if (!choice.input)
          return this.alert();
        choice.updateChoice();
        return this.render();
      }
      if (this.choices.some((ch) => ch.newChoice)) {
        return this.alert();
      }
      let { reorder, sort } = this.options;
      let multi = this.multiple === true;
      let value = this.selected;
      if (value === undefined) {
        return this.alert();
      }
      if (Array.isArray(value) && reorder !== false && sort !== true) {
        value = utils.reorder(value);
      }
      this.value = multi ? value.map((ch) => ch.name) : value.name;
      return super.submit();
    }
    set choices(choices = []) {
      this.state._choices = this.state._choices || [];
      this.state.choices = choices;
      for (let choice of choices) {
        if (!this.state._choices.some((ch) => ch.name === choice.name)) {
          this.state._choices.push(choice);
        }
      }
      if (!this._initial && this.options.initial) {
        this._initial = true;
        let init = this.initial;
        if (typeof init === "string" || typeof init === "number") {
          let choice = this.find(init);
          if (choice) {
            this.initial = choice.index;
            this.focus(choice, true);
          }
        }
      }
    }
    get choices() {
      return reset(this, this.state.choices || []);
    }
    set visible(visible) {
      this.state.visible = visible;
    }
    get visible() {
      return (this.state.visible || this.choices).slice(0, this.limit);
    }
    set limit(num) {
      this.state.limit = num;
    }
    get limit() {
      let { state, options, choices } = this;
      let limit = state.limit || this._limit || options.limit || choices.length;
      return Math.min(limit, this.height);
    }
    set value(value) {
      super.value = value;
    }
    get value() {
      if (typeof super.value !== "string" && super.value === this.initial) {
        return this.input;
      }
      return super.value;
    }
    set index(i) {
      this.state.index = i;
    }
    get index() {
      return Math.max(0, this.state ? this.state.index : 0);
    }
    get enabled() {
      return this.filter(this.isEnabled.bind(this));
    }
    get focused() {
      let choice = this.choices[this.index];
      if (choice && this.state.submitted && this.multiple !== true) {
        choice.enabled = true;
      }
      return choice;
    }
    get selectable() {
      return this.choices.filter((choice) => !this.isDisabled(choice));
    }
    get selected() {
      return this.multiple ? this.enabled : this.focused;
    }
  }
  function reset(prompt, choices) {
    if (choices instanceof Promise)
      return choices;
    if (typeof choices === "function") {
      if (utils.isAsyncFn(choices))
        return choices;
      choices = choices.call(prompt, prompt);
    }
    for (let choice of choices) {
      if (Array.isArray(choice.choices)) {
        let items = choice.choices.filter((ch) => !prompt.isDisabled(ch));
        choice.enabled = items.every((ch) => ch.enabled === true);
      }
      if (prompt.isDisabled(choice) === true) {
        delete choice.enabled;
      }
    }
    return choices;
  }
  module.exports = ArrayPrompt;
});

// node_modules/enquirer/lib/prompts/select.js
var require_select = __commonJS(function(exports, module) {
  var ArrayPrompt = require_array();
  var utils = require_utils();

  class SelectPrompt extends ArrayPrompt {
    constructor(options) {
      super(options);
      this.emptyError = this.options.emptyError || "No items were selected";
    }
    async dispatch(s, key) {
      if (this.multiple) {
        return this[key.name] ? await this[key.name](s, key) : await super.dispatch(s, key);
      }
      this.alert();
    }
    separator() {
      if (this.options.separator)
        return super.separator();
      let sep = this.styles.muted(this.symbols.ellipsis);
      return this.state.submitted ? super.separator() : sep;
    }
    pointer(choice, i) {
      return !this.multiple || this.options.pointer ? super.pointer(choice, i) : "";
    }
    indicator(choice, i) {
      return this.multiple ? super.indicator(choice, i) : "";
    }
    choiceMessage(choice, i) {
      let message = this.resolve(choice.message, this.state, choice, i);
      if (choice.role === "heading" && !utils.hasColor(message)) {
        message = this.styles.strong(message);
      }
      return this.resolve(message, this.state, choice, i);
    }
    choiceSeparator() {
      return ":";
    }
    async renderChoice(choice, i) {
      await this.onChoice(choice, i);
      let focused = this.index === i;
      let pointer = await this.pointer(choice, i);
      let check = await this.indicator(choice, i) + (choice.pad || "");
      let hint = await this.resolve(choice.hint, this.state, choice, i);
      if (hint && !utils.hasColor(hint)) {
        hint = this.styles.muted(hint);
      }
      let ind = this.indent(choice);
      let msg = await this.choiceMessage(choice, i);
      let line = () => [this.margin[3], ind + pointer + check, msg, this.margin[1], hint].filter(Boolean).join(" ");
      if (choice.role === "heading") {
        return line();
      }
      if (choice.disabled) {
        if (!utils.hasColor(msg)) {
          msg = this.styles.disabled(msg);
        }
        return line();
      }
      if (focused) {
        msg = this.styles.em(msg);
      }
      return line();
    }
    async renderChoices() {
      if (this.state.loading === "choices") {
        return this.styles.warning("Loading choices");
      }
      if (this.state.submitted)
        return "";
      let choices = this.visible.map(async (ch, i) => await this.renderChoice(ch, i));
      let visible = await Promise.all(choices);
      if (!visible.length)
        visible.push(this.styles.danger("No matching choices"));
      let result = this.margin[0] + visible.join(`
`);
      let header;
      if (this.options.choicesHeader) {
        header = await this.resolve(this.options.choicesHeader, this.state);
      }
      return [header, result].filter(Boolean).join(`
`);
    }
    format() {
      if (!this.state.submitted || this.state.cancelled)
        return "";
      if (Array.isArray(this.selected)) {
        return this.selected.map((choice) => this.styles.primary(choice.name)).join(", ");
      }
      return this.styles.primary(this.selected.name);
    }
    async render() {
      let { submitted, size } = this.state;
      let prompt = "";
      let header = await this.header();
      let prefix = await this.prefix();
      let separator = await this.separator();
      let message = await this.message();
      if (this.options.promptLine !== false) {
        prompt = [prefix, message, separator, ""].join(" ");
        this.state.prompt = prompt;
      }
      let output = await this.format();
      let help = await this.error() || await this.hint();
      let body = await this.renderChoices();
      let footer = await this.footer();
      if (output)
        prompt += output;
      if (help && !prompt.includes(help))
        prompt += " " + help;
      if (submitted && !output && !body.trim() && this.multiple && this.emptyError != null) {
        prompt += this.styles.danger(this.emptyError);
      }
      this.clear(size);
      this.write([header, prompt, body, footer].filter(Boolean).join(`
`));
      this.write(this.margin[2]);
      this.restore();
    }
  }
  module.exports = SelectPrompt;
});

// node_modules/enquirer/lib/prompts/autocomplete.js
var require_autocomplete = __commonJS(function(exports, module) {
  var Select = require_select();
  var highlight = (input, color) => {
    const regex = input ? new RegExp(input, "ig") : /$^/;
    return (str) => {
      return input ? str.replace(regex, (match) => color(match)) : str;
    };
  };

  class AutoComplete extends Select {
    constructor(options) {
      super(options);
      this.cursorShow();
    }
    moveCursor(n) {
      this.state.cursor += n;
    }
    dispatch(ch) {
      return this.append(ch);
    }
    space(ch) {
      return this.options.multiple ? super.space(ch) : this.append(ch);
    }
    append(ch) {
      let { cursor, input } = this.state;
      this.input = input.slice(0, cursor) + ch + input.slice(cursor);
      this.moveCursor(1);
      return this.complete();
    }
    delete() {
      let { cursor, input } = this.state;
      if (!input)
        return this.alert();
      this.input = input.slice(0, cursor - 1) + input.slice(cursor);
      this.moveCursor(-1);
      return this.complete();
    }
    deleteForward() {
      let { cursor, input } = this.state;
      if (input[cursor] === undefined)
        return this.alert();
      this.input = `${input}`.slice(0, cursor) + `${input}`.slice(cursor + 1);
      return this.complete();
    }
    number(ch) {
      return this.append(ch);
    }
    async complete() {
      this.completing = true;
      this.choices = await this.suggest(this.input, this.state._choices);
      this.state.limit = undefined;
      this.index = Math.min(Math.max(this.visible.length - 1, 0), this.index);
      await this.render();
      this.completing = false;
    }
    suggest(input = this.input, choices = this.state._choices) {
      if (typeof this.options.suggest === "function") {
        return this.options.suggest.call(this, input, choices);
      }
      let str = input.toLowerCase();
      return choices.filter((ch) => ch.message.toLowerCase().includes(str));
    }
    pointer() {
      return "";
    }
    format() {
      if (!this.focused)
        return this.input;
      if (this.options.multiple && this.state.submitted) {
        return this.selected.map((ch) => this.styles.primary(ch.message)).join(", ");
      }
      if (this.state.submitted) {
        let value = this.value = this.input = this.focused.value;
        return this.styles.primary(value);
      }
      return this.input;
    }
    async render() {
      if (this.state.status !== "pending")
        return super.render();
      const hl = this.options.highlight || this.styles.complement;
      const style = (input, color) => {
        if (!input)
          return input;
        if (hl.stack)
          return hl(input);
        return hl.call(this, input);
      };
      const color = highlight(this.input, style);
      const choices = this.choices;
      this.choices = choices.map((ch) => ({ ...ch, message: color(ch.message) }));
      await super.render();
      this.choices = choices;
    }
    submit() {
      if (this.options.multiple) {
        this.value = this.selected.map((ch) => ch.name);
      }
      return super.submit();
    }
  }
  module.exports = AutoComplete;
});

// node_modules/enquirer/lib/placeholder.js
var require_placeholder = __commonJS(function(exports, module) {
  var utils = require_utils();
  module.exports = (prompt, options = {}) => {
    prompt.cursorHide();
    let { input = "", initial = "", pos, showCursor = true, color } = options;
    let style = color || prompt.styles.placeholder;
    let inverse2 = utils.inverse(prompt.styles.primary);
    let blinker = (str) => inverse2(prompt.styles.black(str));
    let output = input;
    let char = " ";
    let reverse = blinker(char);
    if (prompt.blink && prompt.blink.off === true) {
      blinker = (str) => str;
      reverse = "";
    }
    if (showCursor && pos === 0 && initial === "" && input === "") {
      return blinker(char);
    }
    if (showCursor && pos === 0 && (input === initial || input === "")) {
      return blinker(initial[0]) + style(initial.slice(1));
    }
    initial = utils.isPrimitive(initial) ? `${initial}` : "";
    input = utils.isPrimitive(input) ? `${input}` : "";
    let placeholder = initial && initial.startsWith(input) && initial !== input;
    let cursor = placeholder ? blinker(initial[input.length]) : reverse;
    if (pos !== input.length && showCursor === true) {
      output = input.slice(0, pos) + blinker(input[pos]) + input.slice(pos + 1);
      cursor = "";
    }
    if (showCursor === false) {
      cursor = "";
    }
    if (placeholder) {
      let raw = prompt.styles.unstyle(output + cursor);
      return output + cursor + style(initial.slice(raw.length));
    }
    return output + cursor;
  };
});

// node_modules/enquirer/lib/prompts/form.js
var require_form = __commonJS(function(exports, module) {
  var stripAnsi = require_strip_ansi();
  var SelectPrompt = require_select();
  var placeholder = require_placeholder();

  class FormPrompt extends SelectPrompt {
    constructor(options) {
      super({ ...options, multiple: true });
      this.type = "form";
      this.initial = this.options.initial;
      this.align = [this.options.align, "right"].find((v) => v != null);
      this.emptyError = "";
      this.values = {};
    }
    async reset(first) {
      await super.reset();
      if (first === true)
        this._index = this.index;
      this.index = this._index;
      this.values = {};
      this.choices.forEach((choice) => choice.reset && choice.reset());
      return this.render();
    }
    dispatch(char) {
      return !!char && this.append(char);
    }
    append(char) {
      let choice = this.focused;
      if (!choice)
        return this.alert();
      let { cursor, input } = choice;
      choice.value = choice.input = input.slice(0, cursor) + char + input.slice(cursor);
      choice.cursor++;
      return this.render();
    }
    delete() {
      let choice = this.focused;
      if (!choice || choice.cursor <= 0)
        return this.alert();
      let { cursor, input } = choice;
      choice.value = choice.input = input.slice(0, cursor - 1) + input.slice(cursor);
      choice.cursor--;
      return this.render();
    }
    deleteForward() {
      let choice = this.focused;
      if (!choice)
        return this.alert();
      let { cursor, input } = choice;
      if (input[cursor] === undefined)
        return this.alert();
      let str = `${input}`.slice(0, cursor) + `${input}`.slice(cursor + 1);
      choice.value = choice.input = str;
      return this.render();
    }
    right() {
      let choice = this.focused;
      if (!choice)
        return this.alert();
      if (choice.cursor >= choice.input.length)
        return this.alert();
      choice.cursor++;
      return this.render();
    }
    left() {
      let choice = this.focused;
      if (!choice)
        return this.alert();
      if (choice.cursor <= 0)
        return this.alert();
      choice.cursor--;
      return this.render();
    }
    space(ch, key) {
      return this.dispatch(ch, key);
    }
    number(ch, key) {
      return this.dispatch(ch, key);
    }
    next() {
      let ch = this.focused;
      if (!ch)
        return this.alert();
      let { initial, input } = ch;
      if (initial && initial.startsWith(input) && input !== initial) {
        ch.value = ch.input = initial;
        ch.cursor = ch.value.length;
        return this.render();
      }
      return super.next();
    }
    prev() {
      let ch = this.focused;
      if (!ch)
        return this.alert();
      if (ch.cursor === 0)
        return super.prev();
      ch.value = ch.input = "";
      ch.cursor = 0;
      return this.render();
    }
    separator() {
      return "";
    }
    format(value) {
      return !this.state.submitted ? super.format(value) : "";
    }
    pointer() {
      return "";
    }
    indicator(choice) {
      return choice.input ? "\u29BF" : "\u2299";
    }
    async choiceSeparator(choice, i) {
      let sep = await this.resolve(choice.separator, this.state, choice, i) || ":";
      return sep ? " " + this.styles.disabled(sep) : "";
    }
    async renderChoice(choice, i) {
      await this.onChoice(choice, i);
      let { state, styles } = this;
      let { cursor, initial = "", name, input = "" } = choice;
      let { muted, submitted, primary, danger } = styles;
      let focused = this.index === i;
      let validate = choice.validate || (() => true);
      let sep = await this.choiceSeparator(choice, i);
      let msg = choice.message;
      if (this.align === "right")
        msg = msg.padStart(this.longest + 1, " ");
      if (this.align === "left")
        msg = msg.padEnd(this.longest + 1, " ");
      let value = this.values[name] = input || initial;
      let color = input ? "success" : "dark";
      if (await validate.call(choice, value, this.state) !== true) {
        color = "danger";
      }
      let style = styles[color];
      let indicator = style(await this.indicator(choice, i)) + (choice.pad || "");
      let indent = this.indent(choice);
      let line = () => [indent, indicator, msg + sep, input].filter(Boolean).join(" ");
      if (state.submitted) {
        msg = stripAnsi(msg);
        input = submitted(input);
        return line();
      }
      if (choice.format) {
        input = await choice.format.call(this, input, choice, i);
      } else {
        let color = this.styles.muted;
        let options = { input, initial, pos: cursor, showCursor: focused, color };
        input = placeholder(this, options);
      }
      if (!this.isValue(input)) {
        input = this.styles.muted(this.symbols.ellipsis);
      }
      if (choice.result) {
        this.values[name] = await choice.result.call(this, value, choice, i);
      }
      if (focused) {
        msg = primary(msg);
      }
      if (choice.error) {
        input += (input ? " " : "") + danger(choice.error.trim());
      } else if (choice.hint) {
        input += (input ? " " : "") + muted(choice.hint.trim());
      }
      return line();
    }
    async submit() {
      this.value = this.values;
      return super.base.submit.call(this);
    }
  }
  module.exports = FormPrompt;
});

// node_modules/enquirer/lib/types/auth.js
var require_auth = __commonJS(function(exports, module) {
  var FormPrompt = require_form();
  var defaultAuthenticate = () => {
    throw new Error("expected prompt to have a custom authenticate method");
  };
  var factory = (authenticate = defaultAuthenticate) => {

    class AuthPrompt extends FormPrompt {
      constructor(options) {
        super(options);
      }
      async submit() {
        this.value = await authenticate.call(this, this.values, this.state);
        super.base.submit.call(this);
      }
      static create(authenticate) {
        return factory(authenticate);
      }
    }
    return AuthPrompt;
  };
  module.exports = factory();
});

// node_modules/enquirer/lib/prompts/basicauth.js
var require_basicauth = __commonJS(function(exports, module) {
  var AuthPrompt = require_auth();
  function defaultAuthenticate(value, state) {
    if (value.username === this.options.username && value.password === this.options.password) {
      return true;
    }
    return false;
  }
  var factory = (authenticate = defaultAuthenticate) => {
    const choices = [
      { name: "username", message: "username" },
      {
        name: "password",
        message: "password",
        format(input) {
          if (this.options.showPassword) {
            return input;
          }
          let color = this.state.submitted ? this.styles.primary : this.styles.muted;
          return color(this.symbols.asterisk.repeat(input.length));
        }
      }
    ];

    class BasicAuthPrompt extends AuthPrompt.create(authenticate) {
      constructor(options) {
        super({ ...options, choices });
      }
      static create(authenticate) {
        return factory(authenticate);
      }
    }
    return BasicAuthPrompt;
  };
  module.exports = factory();
});

// node_modules/enquirer/lib/types/boolean.js
var require_boolean = __commonJS(function(exports, module) {
  var Prompt = require_prompt();
  var { isPrimitive, hasColor } = require_utils();

  class BooleanPrompt extends Prompt {
    constructor(options) {
      super(options);
      this.cursorHide();
    }
    async initialize() {
      let initial = await this.resolve(this.initial, this.state);
      this.input = await this.cast(initial);
      await super.initialize();
    }
    dispatch(ch) {
      if (!this.isValue(ch))
        return this.alert();
      this.input = ch;
      return this.submit();
    }
    format(value) {
      let { styles, state } = this;
      return !state.submitted ? styles.primary(value) : styles.success(value);
    }
    cast(input) {
      return this.isTrue(input);
    }
    isTrue(input) {
      return /^[ty1]/i.test(input);
    }
    isFalse(input) {
      return /^[fn0]/i.test(input);
    }
    isValue(value) {
      return isPrimitive(value) && (this.isTrue(value) || this.isFalse(value));
    }
    async hint() {
      if (this.state.status === "pending") {
        let hint = await this.element("hint");
        if (!hasColor(hint)) {
          return this.styles.muted(hint);
        }
        return hint;
      }
    }
    async render() {
      let { input, size } = this.state;
      let prefix = await this.prefix();
      let sep = await this.separator();
      let msg = await this.message();
      let hint = this.styles.muted(this.default);
      let promptLine = [prefix, msg, hint, sep].filter(Boolean).join(" ");
      this.state.prompt = promptLine;
      let header = await this.header();
      let value = this.value = this.cast(input);
      let output = await this.format(value);
      let help = await this.error() || await this.hint();
      let footer = await this.footer();
      if (help && !promptLine.includes(help))
        output += " " + help;
      promptLine += " " + output;
      this.clear(size);
      this.write([header, promptLine, footer].filter(Boolean).join(`
`));
      this.restore();
    }
    set value(value) {
      super.value = value;
    }
    get value() {
      return this.cast(super.value);
    }
  }
  module.exports = BooleanPrompt;
});

// node_modules/enquirer/lib/prompts/confirm.js
var require_confirm = __commonJS(function(exports, module) {
  var BooleanPrompt = require_boolean();

  class ConfirmPrompt extends BooleanPrompt {
    constructor(options) {
      super(options);
      this.default = this.options.default || (this.initial ? "(Y/n)" : "(y/N)");
    }
  }
  module.exports = ConfirmPrompt;
});

// node_modules/enquirer/lib/prompts/editable.js
var require_editable = __commonJS(function(exports, module) {
  var Select = require_select();
  var Form = require_form();
  var form = Form.prototype;

  class Editable extends Select {
    constructor(options) {
      super({ ...options, multiple: true });
      this.align = [this.options.align, "left"].find((v) => v != null);
      this.emptyError = "";
      this.values = {};
    }
    dispatch(char, key) {
      let choice = this.focused;
      let parent = choice.parent || {};
      if (!choice.editable && !parent.editable) {
        if (char === "a" || char === "i")
          return super[char]();
      }
      return form.dispatch.call(this, char, key);
    }
    append(char, key) {
      return form.append.call(this, char, key);
    }
    delete(char, key) {
      return form.delete.call(this, char, key);
    }
    space(char) {
      return this.focused.editable ? this.append(char) : super.space();
    }
    number(char) {
      return this.focused.editable ? this.append(char) : super.number(char);
    }
    next() {
      return this.focused.editable ? form.next.call(this) : super.next();
    }
    prev() {
      return this.focused.editable ? form.prev.call(this) : super.prev();
    }
    async indicator(choice, i) {
      let symbol = choice.indicator || "";
      let value = choice.editable ? symbol : super.indicator(choice, i);
      return await this.resolve(value, this.state, choice, i) || "";
    }
    indent(choice) {
      return choice.role === "heading" ? "" : choice.editable ? " " : "  ";
    }
    async renderChoice(choice, i) {
      choice.indent = "";
      if (choice.editable)
        return form.renderChoice.call(this, choice, i);
      return super.renderChoice(choice, i);
    }
    error() {
      return "";
    }
    footer() {
      return this.state.error;
    }
    async validate() {
      let result = true;
      for (let choice of this.choices) {
        if (typeof choice.validate !== "function") {
          continue;
        }
        if (choice.role === "heading") {
          continue;
        }
        let val = choice.parent ? this.value[choice.parent.name] : this.value;
        if (choice.editable) {
          val = choice.value === choice.name ? choice.initial || "" : choice.value;
        } else if (!this.isDisabled(choice)) {
          val = choice.enabled === true;
        }
        result = await choice.validate(val, this.state);
        if (result !== true) {
          break;
        }
      }
      if (result !== true) {
        this.state.error = typeof result === "string" ? result : "Invalid Input";
      }
      return result;
    }
    submit() {
      if (this.focused.newChoice === true)
        return super.submit();
      if (this.choices.some((ch) => ch.newChoice)) {
        return this.alert();
      }
      this.value = {};
      for (let choice of this.choices) {
        let val = choice.parent ? this.value[choice.parent.name] : this.value;
        if (choice.role === "heading") {
          this.value[choice.name] = {};
          continue;
        }
        if (choice.editable) {
          val[choice.name] = choice.value === choice.name ? choice.initial || "" : choice.value;
        } else if (!this.isDisabled(choice)) {
          val[choice.name] = choice.enabled === true;
        }
      }
      return this.base.submit.call(this);
    }
  }
  module.exports = Editable;
});

// node_modules/enquirer/lib/types/string.js
var require_string = __commonJS(function(exports, module) {
  var Prompt = require_prompt();
  var keypress = require_keypress();
  var placeholder = require_placeholder();
  var { isPrimitive } = require_utils();

  class StringPrompt extends Prompt {
    constructor(options) {
      super(options);
      this.initial = isPrimitive(this.initial) ? String(this.initial) : "";
      if (this.initial)
        this.cursorHide();
      this.state.prevCursor = 0;
      this.state.clipboard = [];
      this.keypressTimeout = this.options.keypressTimeout !== undefined ? this.options.keypressTimeout : null;
    }
    async keypress(input, key = input ? keypress(input, {}) : {}) {
      const now = Date.now();
      const elapsed = now - this.lastKeypress;
      this.lastKeypress = now;
      const isEnterKey = key.name === "return" || key.name === "enter";
      let prev = this.state.prevKeypress;
      let append;
      this.state.prevKeypress = key;
      if (this.keypressTimeout != null && isEnterKey) {
        if (elapsed < this.keypressTimeout) {
          return this.submit();
        }
        this.state.multilineBuffer = this.state.multilineBuffer || "";
        this.state.multilineBuffer += input;
        append = true;
        prev = null;
      }
      if (append || this.options.multiline && isEnterKey) {
        if (!prev || prev.name !== "return") {
          return this.append(`
`, key);
        }
      }
      return super.keypress(input, key);
    }
    moveCursor(n) {
      this.cursor += n;
    }
    reset() {
      this.input = this.value = "";
      this.cursor = 0;
      return this.render();
    }
    dispatch(ch, key) {
      if (!ch || key.ctrl || key.code)
        return this.alert();
      this.append(ch);
    }
    append(ch) {
      let { cursor, input } = this.state;
      this.input = `${input}`.slice(0, cursor) + ch + `${input}`.slice(cursor);
      this.moveCursor(String(ch).length);
      this.render();
    }
    insert(str) {
      this.append(str);
    }
    delete() {
      let { cursor, input } = this.state;
      if (cursor <= 0)
        return this.alert();
      this.input = `${input}`.slice(0, cursor - 1) + `${input}`.slice(cursor);
      this.moveCursor(-1);
      this.render();
    }
    deleteForward() {
      let { cursor, input } = this.state;
      if (input[cursor] === undefined)
        return this.alert();
      this.input = `${input}`.slice(0, cursor) + `${input}`.slice(cursor + 1);
      this.render();
    }
    cutForward() {
      let pos = this.cursor;
      if (this.input.length <= pos)
        return this.alert();
      this.state.clipboard.push(this.input.slice(pos));
      this.input = this.input.slice(0, pos);
      this.render();
    }
    cutLeft() {
      let pos = this.cursor;
      if (pos === 0)
        return this.alert();
      let before = this.input.slice(0, pos);
      let after = this.input.slice(pos);
      let words = before.split(" ");
      this.state.clipboard.push(words.pop());
      this.input = words.join(" ");
      this.cursor = this.input.length;
      this.input += after;
      this.render();
    }
    paste() {
      if (!this.state.clipboard.length)
        return this.alert();
      this.insert(this.state.clipboard.pop());
      this.render();
    }
    toggleCursor() {
      if (this.state.prevCursor) {
        this.cursor = this.state.prevCursor;
        this.state.prevCursor = 0;
      } else {
        this.state.prevCursor = this.cursor;
        this.cursor = 0;
      }
      this.render();
    }
    first() {
      this.cursor = 0;
      this.render();
    }
    last() {
      this.cursor = this.input.length - 1;
      this.render();
    }
    next() {
      let init = this.initial != null ? String(this.initial) : "";
      if (!init || !init.startsWith(this.input))
        return this.alert();
      this.input = this.initial;
      this.cursor = this.initial.length;
      this.render();
    }
    prev() {
      if (!this.input)
        return this.alert();
      this.reset();
    }
    backward() {
      return this.left();
    }
    forward() {
      return this.right();
    }
    right() {
      if (this.cursor >= this.input.length)
        return this.alert();
      this.moveCursor(1);
      return this.render();
    }
    left() {
      if (this.cursor <= 0)
        return this.alert();
      this.moveCursor(-1);
      return this.render();
    }
    isValue(value) {
      return !!value;
    }
    async format(input = this.value) {
      let initial = await this.resolve(this.initial, this.state);
      if (!this.state.submitted) {
        return placeholder(this, { input, initial, pos: this.cursor });
      }
      return this.styles.submitted(input || initial);
    }
    async render() {
      let size = this.state.size;
      let prefix = await this.prefix();
      let separator = await this.separator();
      let message = await this.message();
      let prompt = [prefix, message, separator].filter(Boolean).join(" ");
      this.state.prompt = prompt;
      let header = await this.header();
      let output = await this.format();
      let help = await this.error() || await this.hint();
      let footer = await this.footer();
      if (help && !output.includes(help))
        output += " " + help;
      prompt += " " + output;
      this.clear(size);
      this.write([header, prompt, footer].filter(Boolean).join(`
`));
      this.restore();
    }
  }
  module.exports = StringPrompt;
});

// node_modules/enquirer/lib/completer.js
var require_completer = __commonJS(function(exports, module) {
  var unique = (arr) => arr.filter((v, i) => arr.lastIndexOf(v) === i);
  var compact = (arr) => unique(arr).filter(Boolean);
  module.exports = (action, data = {}, value = "") => {
    let { past = [], present = "" } = data;
    let rest, prev;
    switch (action) {
      case "prev":
      case "undo":
        rest = past.slice(0, past.length - 1);
        prev = past[past.length - 1] || "";
        return {
          past: compact([value, ...rest]),
          present: prev
        };
      case "next":
      case "redo":
        rest = past.slice(1);
        prev = past[0] || "";
        return {
          past: compact([...rest, value]),
          present: prev
        };
      case "save":
        return {
          past: compact([...past, value]),
          present: ""
        };
      case "remove":
        prev = compact(past.filter((v) => v !== value));
        present = "";
        if (prev.length) {
          present = prev.pop();
        }
        return {
          past: prev,
          present
        };
      default: {
        throw new Error(`Invalid action: "${action}"`);
      }
    }
  };
});

// node_modules/enquirer/lib/prompts/input.js
var require_input = __commonJS(function(exports, module) {
  var Prompt = require_string();
  var completer = require_completer();

  class Input extends Prompt {
    constructor(options) {
      super(options);
      let history = this.options.history;
      if (history && history.store) {
        let initial = history.values || this.initial;
        this.autosave = !!history.autosave;
        this.store = history.store;
        this.data = this.store.get("values") || { past: [], present: initial };
        this.initial = this.data.present || this.data.past[this.data.past.length - 1];
      }
    }
    completion(action) {
      if (!this.store)
        return this.alert();
      this.data = completer(action, this.data, this.input);
      if (!this.data.present)
        return this.alert();
      this.input = this.data.present;
      this.cursor = this.input.length;
      return this.render();
    }
    altUp() {
      return this.completion("prev");
    }
    altDown() {
      return this.completion("next");
    }
    prev() {
      this.save();
      return super.prev();
    }
    save() {
      if (!this.store)
        return;
      this.data = completer("save", this.data, this.input);
      this.store.set("values", this.data);
    }
    submit() {
      if (this.store && this.autosave === true) {
        this.save();
      }
      return super.submit();
    }
  }
  module.exports = Input;
});

// node_modules/enquirer/lib/prompts/invisible.js
var require_invisible = __commonJS(function(exports, module) {
  var StringPrompt = require_string();

  class InvisiblePrompt extends StringPrompt {
    format() {
      return "";
    }
  }
  module.exports = InvisiblePrompt;
});

// node_modules/enquirer/lib/prompts/list.js
var require_list = __commonJS(function(exports, module) {
  var StringPrompt = require_string();

  class ListPrompt extends StringPrompt {
    constructor(options = {}) {
      super(options);
      this.sep = this.options.separator || /, */;
      this.initial = options.initial || "";
    }
    split(input = this.value) {
      return input ? String(input).split(this.sep) : [];
    }
    format() {
      let style = this.state.submitted ? this.styles.primary : (val) => val;
      return this.list.map(style).join(", ");
    }
    async submit(value) {
      let result = this.state.error || await this.validate(this.list, this.state);
      if (result !== true) {
        this.state.error = result;
        return super.submit();
      }
      this.value = this.list;
      return super.submit();
    }
    get list() {
      return this.split();
    }
  }
  module.exports = ListPrompt;
});

// node_modules/enquirer/lib/prompts/multiselect.js
var require_multiselect = __commonJS(function(exports, module) {
  var Select = require_select();

  class MultiSelect extends Select {
    constructor(options) {
      super({ ...options, multiple: true });
    }
  }
  module.exports = MultiSelect;
});

// node_modules/enquirer/lib/types/number.js
var require_number = __commonJS(function(exports, module) {
  var StringPrompt = require_string();

  class NumberPrompt extends StringPrompt {
    constructor(options = {}) {
      super({ style: "number", ...options });
      this.min = this.isValue(options.min) ? this.toNumber(options.min) : -Infinity;
      this.max = this.isValue(options.max) ? this.toNumber(options.max) : Infinity;
      this.delay = options.delay != null ? options.delay : 1000;
      this.float = options.float !== false;
      this.round = options.round === true || options.float === false;
      this.major = options.major || 10;
      this.minor = options.minor || 1;
      this.initial = options.initial != null ? options.initial : "";
      this.input = String(this.initial);
      this.cursor = this.input.length;
      this.cursorShow();
    }
    append(ch) {
      if (!/[-+.]/.test(ch) || ch === "." && this.input.includes(".")) {
        return this.alert("invalid number");
      }
      return super.append(ch);
    }
    number(ch) {
      return super.append(ch);
    }
    next() {
      if (this.input && this.input !== this.initial)
        return this.alert();
      if (!this.isValue(this.initial))
        return this.alert();
      this.input = this.initial;
      this.cursor = String(this.initial).length;
      return this.render();
    }
    up(number) {
      let step = number || this.minor;
      let num = this.toNumber(this.input);
      if (num > this.max + step)
        return this.alert();
      this.input = `${num + step}`;
      return this.render();
    }
    down(number) {
      let step = number || this.minor;
      let num = this.toNumber(this.input);
      if (num < this.min - step)
        return this.alert();
      this.input = `${num - step}`;
      return this.render();
    }
    shiftDown() {
      return this.down(this.major);
    }
    shiftUp() {
      return this.up(this.major);
    }
    format(input = this.input) {
      if (typeof this.options.format === "function") {
        return this.options.format.call(this, input);
      }
      return this.styles.info(input);
    }
    toNumber(value = "") {
      return this.float ? +value : Math.round(+value);
    }
    isValue(value) {
      return /^[-+]?[0-9]+((\.)|(\.[0-9]+))?$/.test(value);
    }
    submit() {
      let value = [this.input, this.initial].find((v) => this.isValue(v));
      this.value = this.toNumber(value || 0);
      return super.submit();
    }
  }
  module.exports = NumberPrompt;
});

// node_modules/enquirer/lib/prompts/password.js
var require_password = __commonJS(function(exports, module) {
  var StringPrompt = require_string();

  class PasswordPrompt extends StringPrompt {
    constructor(options) {
      super(options);
      this.cursorShow();
    }
    format(input = this.input) {
      if (!this.keypressed)
        return "";
      let color = this.state.submitted ? this.styles.primary : this.styles.muted;
      return color(this.symbols.asterisk.repeat(input.length));
    }
  }
  module.exports = PasswordPrompt;
});

// node_modules/enquirer/lib/prompts/scale.js
var require_scale = __commonJS(function(exports, module) {
  var stripAnsi = require_strip_ansi();
  var ArrayPrompt = require_array();
  var utils = require_utils();

  class LikertScale extends ArrayPrompt {
    constructor(options = {}) {
      super(options);
      this.widths = [].concat(options.messageWidth || 50);
      this.align = [].concat(options.align || "left");
      this.linebreak = options.linebreak || false;
      this.edgeLength = options.edgeLength || 3;
      this.newline = options.newline || `
   `;
      let start = options.startNumber || 1;
      if (typeof this.scale === "number") {
        this.scaleKey = false;
        this.scale = Array(this.scale).fill(0).map((v, i) => ({ name: i + start }));
      }
    }
    async reset() {
      this.tableized = false;
      await super.reset();
      return this.render();
    }
    tableize() {
      if (this.tableized === true)
        return;
      this.tableized = true;
      let longest = 0;
      for (let ch of this.choices) {
        longest = Math.max(longest, ch.message.length);
        ch.scaleIndex = ch.initial || 2;
        ch.scale = [];
        for (let i = 0;i < this.scale.length; i++) {
          ch.scale.push({ index: i });
        }
      }
      this.widths[0] = Math.min(this.widths[0], longest + 3);
    }
    async dispatch(s, key) {
      if (this.multiple) {
        return this[key.name] ? await this[key.name](s, key) : await super.dispatch(s, key);
      }
      this.alert();
    }
    heading(msg, item, i) {
      return this.styles.strong(msg);
    }
    separator() {
      return this.styles.muted(this.symbols.ellipsis);
    }
    right() {
      let choice = this.focused;
      if (choice.scaleIndex >= this.scale.length - 1)
        return this.alert();
      choice.scaleIndex++;
      return this.render();
    }
    left() {
      let choice = this.focused;
      if (choice.scaleIndex <= 0)
        return this.alert();
      choice.scaleIndex--;
      return this.render();
    }
    indent() {
      return "";
    }
    format() {
      if (this.state.submitted) {
        let values = this.choices.map((ch) => this.styles.info(ch.index));
        return values.join(", ");
      }
      return "";
    }
    pointer() {
      return "";
    }
    renderScaleKey() {
      if (this.scaleKey === false)
        return "";
      if (this.state.submitted)
        return "";
      let scale = this.scale.map((item) => `   ${item.name} - ${item.message}`);
      let key = ["", ...scale].map((item) => this.styles.muted(item));
      return key.join(`
`);
    }
    renderScaleHeading(max) {
      let keys = this.scale.map((ele) => ele.name);
      if (typeof this.options.renderScaleHeading === "function") {
        keys = this.options.renderScaleHeading.call(this, max);
      }
      let diff = this.scaleLength - keys.join("").length;
      let spacing = Math.round(diff / (keys.length - 1));
      let names = keys.map((key) => this.styles.strong(key));
      let headings = names.join(" ".repeat(spacing));
      let padding = " ".repeat(this.widths[0]);
      return this.margin[3] + padding + this.margin[1] + headings;
    }
    scaleIndicator(choice, item, i) {
      if (typeof this.options.scaleIndicator === "function") {
        return this.options.scaleIndicator.call(this, choice, item, i);
      }
      let enabled = choice.scaleIndex === item.index;
      if (item.disabled)
        return this.styles.hint(this.symbols.radio.disabled);
      if (enabled)
        return this.styles.success(this.symbols.radio.on);
      return this.symbols.radio.off;
    }
    renderScale(choice, i) {
      let scale = choice.scale.map((item) => this.scaleIndicator(choice, item, i));
      let padding = this.term === "Hyper" ? "" : " ";
      return scale.join(padding + this.symbols.line.repeat(this.edgeLength));
    }
    async renderChoice(choice, i) {
      await this.onChoice(choice, i);
      let focused = this.index === i;
      let pointer = await this.pointer(choice, i);
      let hint = await choice.hint;
      if (hint && !utils.hasColor(hint)) {
        hint = this.styles.muted(hint);
      }
      let pad = (str) => this.margin[3] + str.replace(/\s+$/, "").padEnd(this.widths[0], " ");
      let newline = this.newline;
      let ind = this.indent(choice);
      let message = await this.resolve(choice.message, this.state, choice, i);
      let scale = await this.renderScale(choice, i);
      let margin = this.margin[1] + this.margin[3];
      this.scaleLength = stripAnsi(scale).length;
      this.widths[0] = Math.min(this.widths[0], this.width - this.scaleLength - margin.length);
      let msg = utils.wordWrap(message, { width: this.widths[0], newline });
      let lines = msg.split(`
`).map((line) => pad(line) + this.margin[1]);
      if (focused) {
        scale = this.styles.info(scale);
        lines = lines.map((line) => this.styles.info(line));
      }
      lines[0] += scale;
      if (this.linebreak)
        lines.push("");
      return [ind + pointer, lines.join(`
`)].filter(Boolean);
    }
    async renderChoices() {
      if (this.state.submitted)
        return "";
      this.tableize();
      let choices = this.visible.map(async (ch, i) => await this.renderChoice(ch, i));
      let visible = await Promise.all(choices);
      let heading = await this.renderScaleHeading();
      return this.margin[0] + [heading, ...visible.map((v) => v.join(" "))].join(`
`);
    }
    async render() {
      let { submitted, size } = this.state;
      let prefix = await this.prefix();
      let separator = await this.separator();
      let message = await this.message();
      let prompt = "";
      if (this.options.promptLine !== false) {
        prompt = [prefix, message, separator, ""].join(" ");
        this.state.prompt = prompt;
      }
      let header = await this.header();
      let output = await this.format();
      let key = await this.renderScaleKey();
      let help = await this.error() || await this.hint();
      let body = await this.renderChoices();
      let footer = await this.footer();
      let err = this.emptyError;
      if (output)
        prompt += output;
      if (help && !prompt.includes(help))
        prompt += " " + help;
      if (submitted && !output && !body.trim() && this.multiple && err != null) {
        prompt += this.styles.danger(err);
      }
      this.clear(size);
      this.write([header, prompt, key, body, footer].filter(Boolean).join(`
`));
      if (!this.state.submitted) {
        this.write(this.margin[2]);
      }
      this.restore();
    }
    submit() {
      this.value = {};
      for (let choice of this.choices) {
        this.value[choice.name] = choice.scaleIndex;
      }
      return this.base.submit.call(this);
    }
  }
  module.exports = LikertScale;
});

// node_modules/enquirer/lib/interpolate.js
var require_interpolate = __commonJS(function(exports, module) {
  var stripAnsi = require_strip_ansi();
  var clean = (str = "") => {
    return typeof str === "string" ? str.replace(/^['"]|['"]$/g, "") : "";
  };

  class Item {
    constructor(token) {
      this.name = token.key;
      this.field = token.field || {};
      this.value = clean(token.initial || this.field.initial || "");
      this.message = token.message || this.name;
      this.cursor = 0;
      this.input = "";
      this.lines = [];
    }
  }
  var tokenize = async (options = {}, defaults = {}, fn = (token) => token) => {
    let unique = new Set;
    let fields = options.fields || [];
    let input = options.template;
    let tabstops = [];
    let items = [];
    let keys = [];
    let line = 1;
    if (typeof input === "function") {
      input = await input();
    }
    let i = -1;
    let next = () => input[++i];
    let peek = () => input[i + 1];
    let push = (token) => {
      token.line = line;
      tabstops.push(token);
    };
    push({ type: "bos", value: "" });
    while (i < input.length - 1) {
      let value = next();
      if (/^[^\S\n ]$/.test(value)) {
        push({ type: "text", value });
        continue;
      }
      if (value === `
`) {
        push({ type: "newline", value });
        line++;
        continue;
      }
      if (value === "\\") {
        value += next();
        push({ type: "text", value });
        continue;
      }
      if ((value === "$" || value === "#" || value === "{") && peek() === "{") {
        let n = next();
        value += n;
        let token = { type: "template", open: value, inner: "", close: "", value };
        let ch;
        while (ch = next()) {
          if (ch === "}") {
            if (peek() === "}")
              ch += next();
            token.value += ch;
            token.close = ch;
            break;
          }
          if (ch === ":") {
            token.initial = "";
            token.key = token.inner;
          } else if (token.initial !== undefined) {
            token.initial += ch;
          }
          token.value += ch;
          token.inner += ch;
        }
        token.template = token.open + (token.initial || token.inner) + token.close;
        token.key = token.key || token.inner;
        if (hasOwnProperty.call(defaults, token.key)) {
          token.initial = defaults[token.key];
        }
        token = fn(token);
        push(token);
        keys.push(token.key);
        unique.add(token.key);
        let item = items.find((item) => item.name === token.key);
        token.field = fields.find((ch) => ch.name === token.key);
        if (!item) {
          item = new Item(token);
          items.push(item);
        }
        item.lines.push(token.line - 1);
        continue;
      }
      let last = tabstops[tabstops.length - 1];
      if (last.type === "text" && last.line === line) {
        last.value += value;
      } else {
        push({ type: "text", value });
      }
    }
    push({ type: "eos", value: "" });
    return { input, tabstops, unique, keys, items };
  };
  module.exports = async (prompt) => {
    let options = prompt.options;
    let required = new Set(options.required === true ? [] : options.required || []);
    let defaults = { ...options.values, ...options.initial };
    let { tabstops, items, keys } = await tokenize(options, defaults);
    let result = createFn("result", prompt, options);
    let format = createFn("format", prompt, options);
    let isValid = createFn("validate", prompt, options, true);
    let isVal = prompt.isValue.bind(prompt);
    return async (state = {}, submitted = false) => {
      let index = 0;
      state.required = required;
      state.items = items;
      state.keys = keys;
      state.output = "";
      let validate = async (value, state, item, index) => {
        let error = await isValid(value, state, item, index);
        if (error === false) {
          return "Invalid field " + item.name;
        }
        return error;
      };
      for (let token of tabstops) {
        let value = token.value;
        let key = token.key;
        if (token.type !== "template") {
          if (value)
            state.output += value;
          continue;
        }
        if (token.type === "template") {
          let item = items.find((ch) => ch.name === key);
          if (options.required === true) {
            state.required.add(item.name);
          }
          let val = [item.input, state.values[item.value], item.value, value].find(isVal);
          let field = item.field || {};
          let message = field.message || token.inner;
          if (submitted) {
            let error = await validate(state.values[key], state, item, index);
            if (error && typeof error === "string" || error === false) {
              state.invalid.set(key, error);
              continue;
            }
            state.invalid.delete(key);
            let res = await result(state.values[key], state, item, index);
            state.output += stripAnsi(res);
            continue;
          }
          item.placeholder = false;
          let before = value;
          value = await format(value, state, item, index);
          if (val !== value) {
            state.values[key] = val;
            value = prompt.styles.typing(val);
            state.missing.delete(message);
          } else {
            state.values[key] = undefined;
            val = `<${message}>`;
            value = prompt.styles.primary(val);
            item.placeholder = true;
            if (state.required.has(key)) {
              state.missing.add(message);
            }
          }
          if (state.missing.has(message) && state.validating) {
            value = prompt.styles.warning(val);
          }
          if (state.invalid.has(key) && state.validating) {
            value = prompt.styles.danger(val);
          }
          if (index === state.index) {
            if (before !== value) {
              value = prompt.styles.underline(value);
            } else {
              value = prompt.styles.heading(stripAnsi(value));
            }
          }
          index++;
        }
        if (value) {
          state.output += value;
        }
      }
      let lines = state.output.split(`
`).map((l) => " " + l);
      let len = items.length;
      let done = 0;
      for (let item of items) {
        if (state.invalid.has(item.name)) {
          item.lines.forEach((i) => {
            if (lines[i][0] !== " ")
              return;
            lines[i] = state.styles.danger(state.symbols.bullet) + lines[i].slice(1);
          });
        }
        if (prompt.isValue(state.values[item.name])) {
          done++;
        }
      }
      state.completed = (done / len * 100).toFixed(0);
      state.output = lines.join(`
`);
      return state.output;
    };
  };
  function createFn(prop, prompt, options, fallback) {
    return (value, state, item, index) => {
      if (typeof item.field[prop] === "function") {
        return item.field[prop].call(prompt, value, state, item, index);
      }
      return [fallback, value].find((v) => prompt.isValue(v));
    };
  }
});

// node_modules/enquirer/lib/prompts/snippet.js
var require_snippet = __commonJS(function(exports, module) {
  var stripAnsi = require_strip_ansi();
  var interpolate = require_interpolate();
  var Prompt = require_prompt();

  class SnippetPrompt extends Prompt {
    constructor(options) {
      super(options);
      this.cursorHide();
      this.reset(true);
    }
    async initialize() {
      this.interpolate = await interpolate(this);
      await super.initialize();
    }
    async reset(first) {
      this.state.keys = [];
      this.state.invalid = new Map;
      this.state.missing = new Set;
      this.state.completed = 0;
      this.state.values = {};
      if (first !== true) {
        await this.initialize();
        await this.render();
      }
    }
    moveCursor(n) {
      let item = this.getItem();
      this.cursor += n;
      item.cursor += n;
    }
    dispatch(ch, key) {
      if (!key.code && !key.ctrl && ch != null && this.getItem()) {
        this.append(ch, key);
        return;
      }
      this.alert();
    }
    append(ch, key) {
      let item = this.getItem();
      let prefix = item.input.slice(0, this.cursor);
      let suffix = item.input.slice(this.cursor);
      this.input = item.input = `${prefix}${ch}${suffix}`;
      this.moveCursor(1);
      this.render();
    }
    delete() {
      let item = this.getItem();
      if (this.cursor <= 0 || !item.input)
        return this.alert();
      let suffix = item.input.slice(this.cursor);
      let prefix = item.input.slice(0, this.cursor - 1);
      this.input = item.input = `${prefix}${suffix}`;
      this.moveCursor(-1);
      this.render();
    }
    increment(i) {
      return i >= this.state.keys.length - 1 ? 0 : i + 1;
    }
    decrement(i) {
      return i <= 0 ? this.state.keys.length - 1 : i - 1;
    }
    first() {
      this.state.index = 0;
      this.render();
    }
    last() {
      this.state.index = this.state.keys.length - 1;
      this.render();
    }
    right() {
      if (this.cursor >= this.input.length)
        return this.alert();
      this.moveCursor(1);
      this.render();
    }
    left() {
      if (this.cursor <= 0)
        return this.alert();
      this.moveCursor(-1);
      this.render();
    }
    prev() {
      this.state.index = this.decrement(this.state.index);
      this.getItem();
      this.render();
    }
    next() {
      this.state.index = this.increment(this.state.index);
      this.getItem();
      this.render();
    }
    up() {
      this.prev();
    }
    down() {
      this.next();
    }
    format(value) {
      let color = this.state.completed < 100 ? this.styles.warning : this.styles.success;
      if (this.state.submitted === true && this.state.completed !== 100) {
        color = this.styles.danger;
      }
      return color(`${this.state.completed}% completed`);
    }
    async render() {
      let { index, keys = [], submitted, size } = this.state;
      let newline = [this.options.newline, `
`].find((v) => v != null);
      let prefix = await this.prefix();
      let separator = await this.separator();
      let message = await this.message();
      let prompt = [prefix, message, separator].filter(Boolean).join(" ");
      this.state.prompt = prompt;
      let header = await this.header();
      let error = await this.error() || "";
      let hint = await this.hint() || "";
      let body = submitted ? "" : await this.interpolate(this.state);
      let key = this.state.key = keys[index] || "";
      let input = await this.format(key);
      let footer = await this.footer();
      if (input)
        prompt += " " + input;
      if (hint && !input && this.state.completed === 0)
        prompt += " " + hint;
      this.clear(size);
      let lines = [header, prompt, body, footer, error.trim()];
      this.write(lines.filter(Boolean).join(newline));
      this.restore();
    }
    getItem(name) {
      let { items, keys, index } = this.state;
      let item = items.find((ch) => ch.name === keys[index]);
      if (item && item.input != null) {
        this.input = item.input;
        this.cursor = item.cursor;
      }
      return item;
    }
    async submit() {
      if (typeof this.interpolate !== "function")
        await this.initialize();
      await this.interpolate(this.state, true);
      let { invalid, missing, output, values } = this.state;
      if (invalid.size) {
        let err = "";
        for (let [key, value] of invalid)
          err += `Invalid ${key}: ${value}
`;
        this.state.error = err;
        return super.submit();
      }
      if (missing.size) {
        this.state.error = "Required: " + [...missing.keys()].join(", ");
        return super.submit();
      }
      let lines = stripAnsi(output).split(`
`);
      let result = lines.map((v) => v.slice(1)).join(`
`);
      this.value = { values, result };
      return super.submit();
    }
  }
  module.exports = SnippetPrompt;
});

// node_modules/enquirer/lib/prompts/sort.js
var require_sort = __commonJS(function(exports, module) {
  var hint = "(Use <shift>+<up/down> to sort)";
  var Prompt = require_select();

  class Sort extends Prompt {
    constructor(options) {
      super({ ...options, reorder: false, sort: true, multiple: true });
      this.state.hint = [this.options.hint, hint].find(this.isValue.bind(this));
    }
    indicator() {
      return "";
    }
    async renderChoice(choice, i) {
      let str = await super.renderChoice(choice, i);
      let sym = this.symbols.identicalTo + " ";
      let pre = this.index === i && this.sorting ? this.styles.muted(sym) : "  ";
      if (this.options.drag === false)
        pre = "";
      if (this.options.numbered === true) {
        return pre + `${i + 1} - ` + str;
      }
      return pre + str;
    }
    get selected() {
      return this.choices;
    }
    submit() {
      this.value = this.choices.map((choice) => choice.value);
      return super.submit();
    }
  }
  module.exports = Sort;
});

// node_modules/enquirer/lib/prompts/survey.js
var require_survey = __commonJS(function(exports, module) {
  var ArrayPrompt = require_array();

  class Survey extends ArrayPrompt {
    constructor(options = {}) {
      super(options);
      this.emptyError = options.emptyError || "No items were selected";
      this.term = process.env.TERM_PROGRAM;
      if (!this.options.header) {
        let header = ["", "4 - Strongly Agree", "3 - Agree", "2 - Neutral", "1 - Disagree", "0 - Strongly Disagree", ""];
        header = header.map((ele) => this.styles.muted(ele));
        this.state.header = header.join(`
   `);
      }
    }
    async toChoices(...args) {
      if (this.createdScales)
        return false;
      this.createdScales = true;
      let choices = await super.toChoices(...args);
      for (let choice of choices) {
        choice.scale = createScale(5, this.options);
        choice.scaleIdx = 2;
      }
      return choices;
    }
    dispatch() {
      this.alert();
    }
    space() {
      let choice = this.focused;
      let ele = choice.scale[choice.scaleIdx];
      let selected = ele.selected;
      choice.scale.forEach((e) => e.selected = false);
      ele.selected = !selected;
      return this.render();
    }
    indicator() {
      return "";
    }
    pointer() {
      return "";
    }
    separator() {
      return this.styles.muted(this.symbols.ellipsis);
    }
    right() {
      let choice = this.focused;
      if (choice.scaleIdx >= choice.scale.length - 1)
        return this.alert();
      choice.scaleIdx++;
      return this.render();
    }
    left() {
      let choice = this.focused;
      if (choice.scaleIdx <= 0)
        return this.alert();
      choice.scaleIdx--;
      return this.render();
    }
    indent() {
      return "   ";
    }
    async renderChoice(item, i) {
      await this.onChoice(item, i);
      let focused = this.index === i;
      let isHyper = this.term === "Hyper";
      let n = !isHyper ? 8 : 9;
      let s = !isHyper ? " " : "";
      let ln = this.symbols.line.repeat(n);
      let sp = " ".repeat(n + (isHyper ? 0 : 1));
      let dot = (enabled) => (enabled ? this.styles.success("\u25C9") : "\u25EF") + s;
      let num = i + 1 + ".";
      let color = focused ? this.styles.heading : this.styles.noop;
      let msg = await this.resolve(item.message, this.state, item, i);
      let indent = this.indent(item);
      let scale = indent + item.scale.map((e, i) => dot(i === item.scaleIdx)).join(ln);
      let val = (i) => i === item.scaleIdx ? color(i) : i;
      let next = indent + item.scale.map((e, i) => val(i)).join(sp);
      let line = () => [num, msg].filter(Boolean).join(" ");
      let lines = () => [line(), scale, next, " "].filter(Boolean).join(`
`);
      if (focused) {
        scale = this.styles.cyan(scale);
        next = this.styles.cyan(next);
      }
      return lines();
    }
    async renderChoices() {
      if (this.state.submitted)
        return "";
      let choices = this.visible.map(async (ch, i) => await this.renderChoice(ch, i));
      let visible = await Promise.all(choices);
      if (!visible.length)
        visible.push(this.styles.danger("No matching choices"));
      return visible.join(`
`);
    }
    format() {
      if (this.state.submitted) {
        let values = this.choices.map((ch) => this.styles.info(ch.scaleIdx));
        return values.join(", ");
      }
      return "";
    }
    async render() {
      let { submitted, size } = this.state;
      let prefix = await this.prefix();
      let separator = await this.separator();
      let message = await this.message();
      let prompt = [prefix, message, separator].filter(Boolean).join(" ");
      this.state.prompt = prompt;
      let header = await this.header();
      let output = await this.format();
      let help = await this.error() || await this.hint();
      let body = await this.renderChoices();
      let footer = await this.footer();
      if (output || !help)
        prompt += " " + output;
      if (help && !prompt.includes(help))
        prompt += " " + help;
      if (submitted && !output && !body && this.multiple && this.type !== "form") {
        prompt += this.styles.danger(this.emptyError);
      }
      this.clear(size);
      this.write([prompt, header, body, footer].filter(Boolean).join(`
`));
      this.restore();
    }
    submit() {
      this.value = {};
      for (let choice of this.choices) {
        this.value[choice.name] = choice.scaleIdx;
      }
      return this.base.submit.call(this);
    }
  }
  function createScale(n, options = {}) {
    if (Array.isArray(options.scale)) {
      return options.scale.map((ele) => ({ ...ele }));
    }
    let scale = [];
    for (let i = 1;i < n + 1; i++)
      scale.push({ i, selected: false });
    return scale;
  }
  module.exports = Survey;
});

// node_modules/enquirer/lib/prompts/toggle.js
var require_toggle = __commonJS(function(exports, module) {
  var BooleanPrompt = require_boolean();

  class TogglePrompt extends BooleanPrompt {
    async initialize() {
      await super.initialize();
      this.value = this.initial = this.resolve(this.options.initial);
      this.disabled = this.options.disabled || "no";
      this.enabled = this.options.enabled || "yes";
      await this.render();
    }
    reset() {
      this.value = this.initial;
      this.render();
    }
    delete() {
      this.alert();
    }
    toggle() {
      this.value = !this.value;
      this.render();
    }
    enable() {
      if (this.value === true)
        return this.alert();
      this.value = true;
      this.render();
    }
    disable() {
      if (this.value === false)
        return this.alert();
      this.value = false;
      this.render();
    }
    up() {
      this.toggle();
    }
    down() {
      this.toggle();
    }
    right() {
      this.toggle();
    }
    left() {
      this.toggle();
    }
    next() {
      this.toggle();
    }
    prev() {
      this.toggle();
    }
    dispatch(ch = "", key) {
      switch (ch.toLowerCase()) {
        case " ":
          return this.toggle();
        case "1":
        case "y":
        case "t":
          return this.enable();
        case "0":
        case "n":
        case "f":
          return this.disable();
        default: {
          return this.alert();
        }
      }
    }
    format() {
      let active = (str) => this.styles.primary.underline(str);
      let value = [
        this.value ? this.disabled : active(this.disabled),
        this.value ? active(this.enabled) : this.enabled
      ];
      return value.join(this.styles.muted(" / "));
    }
    async render() {
      let { size } = this.state;
      let header = await this.header();
      let prefix = await this.prefix();
      let separator = await this.separator();
      let message = await this.message();
      let output = await this.format();
      let help = await this.error() || await this.hint();
      let footer = await this.footer();
      let prompt = [prefix, message, separator, output].join(" ");
      this.state.prompt = prompt;
      if (help && !prompt.includes(help))
        prompt += " " + help;
      this.clear(size);
      this.write([header, prompt, footer].filter(Boolean).join(`
`));
      this.write(this.margin[2]);
      this.restore();
    }
  }
  module.exports = TogglePrompt;
});

// node_modules/enquirer/lib/prompts/quiz.js
var require_quiz = __commonJS(function(exports, module) {
  var SelectPrompt = require_select();

  class Quiz extends SelectPrompt {
    constructor(options) {
      super(options);
      if (typeof this.options.correctChoice !== "number" || this.options.correctChoice < 0) {
        throw new Error("Please specify the index of the correct answer from the list of choices");
      }
    }
    async toChoices(value, parent) {
      let choices = await super.toChoices(value, parent);
      if (choices.length < 2) {
        throw new Error("Please give at least two choices to the user");
      }
      if (this.options.correctChoice > choices.length) {
        throw new Error("Please specify the index of the correct answer from the list of choices");
      }
      return choices;
    }
    check(state) {
      return state.index === this.options.correctChoice;
    }
    async result(selected) {
      return {
        selectedAnswer: selected,
        correctAnswer: this.options.choices[this.options.correctChoice].value,
        correct: await this.check(this.state)
      };
    }
  }
  module.exports = Quiz;
});

// node_modules/enquirer/lib/prompts/index.js
var require_prompts = __commonJS(function(exports) {
  var utils = require_utils();
  var define = (key, fn) => {
    utils.defineExport(exports, key, fn);
    utils.defineExport(exports, key.toLowerCase(), fn);
  };
  define("AutoComplete", () => require_autocomplete());
  define("BasicAuth", () => require_basicauth());
  define("Confirm", () => require_confirm());
  define("Editable", () => require_editable());
  define("Form", () => require_form());
  define("Input", () => require_input());
  define("Invisible", () => require_invisible());
  define("List", () => require_list());
  define("MultiSelect", () => require_multiselect());
  define("Numeral", () => require_number());
  define("Password", () => require_password());
  define("Scale", () => require_scale());
  define("Select", () => require_select());
  define("Snippet", () => require_snippet());
  define("Sort", () => require_sort());
  define("Survey", () => require_survey());
  define("Text", () => require_input());
  define("Toggle", () => require_toggle());
  define("Quiz", () => require_quiz());
});

// node_modules/enquirer/lib/types/index.js
var require_types = __commonJS(function(exports, module) {
  module.exports = {
    ArrayPrompt: require_array(),
    AuthPrompt: require_auth(),
    BooleanPrompt: require_boolean(),
    NumberPrompt: require_number(),
    StringPrompt: require_string()
  };
});

// node_modules/enquirer/index.js
var require_enquirer = __commonJS(function(exports, module) {
  var assert = __require("assert");
  var Events = __require("events");
  var utils = require_utils();

  class Enquirer extends Events {
    constructor(options, answers) {
      super();
      this.options = utils.merge({}, options);
      this.answers = { ...answers };
    }
    register(type, fn) {
      if (utils.isObject(type)) {
        for (let key of Object.keys(type))
          this.register(key, type[key]);
        return this;
      }
      assert.equal(typeof fn, "function", "expected a function");
      const name = type.toLowerCase();
      if (fn.prototype instanceof this.Prompt) {
        this.prompts[name] = fn;
      } else {
        this.prompts[name] = fn(this.Prompt, this);
      }
      return this;
    }
    async prompt(questions = []) {
      for (let question of [].concat(questions)) {
        try {
          if (typeof question === "function")
            question = await question.call(this);
          await this.ask(utils.merge({}, this.options, question));
        } catch (err) {
          return Promise.reject(err);
        }
      }
      return this.answers;
    }
    async ask(question) {
      if (typeof question === "function") {
        question = await question.call(this);
      }
      let opts = utils.merge({}, this.options, question);
      let { type, name } = question;
      let { set, get } = utils;
      if (typeof type === "function") {
        type = await type.call(this, question, this.answers);
      }
      if (!type)
        return this.answers[name];
      if (type === "number")
        type = "numeral";
      assert(this.prompts[type], `Prompt "${type}" is not registered`);
      let prompt = new this.prompts[type](opts);
      let value = get(this.answers, name);
      prompt.state.answers = this.answers;
      prompt.enquirer = this;
      if (name) {
        prompt.on("submit", (value) => {
          this.emit("answer", name, value, prompt);
          set(this.answers, name, value);
        });
      }
      let emit = prompt.emit.bind(prompt);
      prompt.emit = (...args) => {
        this.emit.call(this, ...args);
        return emit(...args);
      };
      this.emit("prompt", prompt, this);
      if (opts.autofill && value != null) {
        prompt.value = prompt.input = value;
        if (opts.autofill === "show") {
          await prompt.submit();
        }
      } else {
        value = prompt.value = await prompt.run();
      }
      return value;
    }
    use(plugin) {
      plugin.call(this, this);
      return this;
    }
    set Prompt(value) {
      this._Prompt = value;
    }
    get Prompt() {
      return this._Prompt || this.constructor.Prompt;
    }
    get prompts() {
      return this.constructor.prompts;
    }
    static set Prompt(value) {
      this._Prompt = value;
    }
    static get Prompt() {
      return this._Prompt || require_prompt();
    }
    static get prompts() {
      return require_prompts();
    }
    static get types() {
      return require_types();
    }
    static get prompt() {
      const fn = (questions, ...rest) => {
        let enquirer = new this(...rest);
        let emit = enquirer.emit.bind(enquirer);
        enquirer.emit = (...args) => {
          fn.emit(...args);
          return emit(...args);
        };
        return enquirer.prompt(questions);
      };
      utils.mixinEmitter(fn, new Events);
      return fn;
    }
  }
  utils.mixinEmitter(Enquirer, new Events);
  var prompts = Enquirer.prompts;
  for (let name of Object.keys(prompts)) {
    let key = name.toLowerCase();
    let run = (options) => new prompts[name](options).run();
    Enquirer.prompt[key] = run;
    Enquirer[key] = run;
    if (!Enquirer[name]) {
      Reflect.defineProperty(Enquirer, name, { get: () => prompts[name] });
    }
  }
  var define = (name) => {
    utils.defineExport(Enquirer, name, () => Enquirer.types[name]);
  };
  define("ArrayPrompt");
  define("AuthPrompt");
  define("BooleanPrompt");
  define("NumberPrompt");
  define("StringPrompt");
  module.exports = Enquirer;
});

// cli/lib/env.ts
import { readFileSync, existsSync, writeFileSync, appendFileSync } from "fs";
async function promptKey(key, currentVal = "") {
  const isPassword = PASSWORD_KEYS.includes(key);
  if (!isPassword) {
    const response = await prompt({
      type: "input",
      name: key,
      message: `Enter value for ${key}:`,
      initial: currentVal
    });
    return response[key] ?? "";
  }
  while (true) {
    const response = await prompt({
      type: "password",
      name: key,
      message: `Enter value for ${key}:`,
      initial: currentVal
    });
    const val = response[key] ?? "";
    if (val && val !== currentVal) {
      const confirmResponse = await prompt({
        type: "password",
        name: "confirm",
        message: `Confirm value for ${key}:`
      });
      if (confirmResponse.confirm !== val) {
        log.error(`Values for ${key} do not match. Please try again.`);
        continue;
      }
    }
    return val;
  }
}
async function getLocalEnv() {
  const localEnv = parseEnv(".env");
  const autoDetectExecutable = (name, commonPaths) => {
    const inPath = Bun.which(name);
    if (inPath)
      return inPath;
    for (const p of commonPaths) {
      if (existsSync(p))
        return p;
    }
    return name;
  };
  if (!localEnv["PROJECT_SLUG"]) {
    localEnv["PROJECT_SLUG"] = "og-of-wp";
  }
  if (!localEnv["MYSQLDUMP_PATH"]) {
    localEnv["MYSQLDUMP_PATH"] = autoDetectExecutable("mysqldump", [
      "C:\\xampp\\mysql\\bin\\mysqldump.exe",
      "/usr/bin/mysqldump",
      "/usr/local/bin/mysqldump"
    ]);
  }
  if (!localEnv["MYSQL_PATH"]) {
    localEnv["MYSQL_PATH"] = autoDetectExecutable("mysql", [
      "C:\\xampp\\mysql\\bin\\mysql.exe",
      "/usr/bin/mysql",
      "/usr/local/bin/mysql"
    ]);
  }
  if (!localEnv["SEVENZIP_PATH"]) {
    localEnv["SEVENZIP_PATH"] = autoDetectExecutable("7z", [
      "C:\\Program Files\\7-Zip\\7z.exe",
      "C:\\Program Files (x86)\\7-Zip\\7z.exe",
      "/usr/bin/7z",
      "/usr/local/bin/7z",
      "zip"
    ]);
  }
  if (!process.env.CI) {
    log.info("Please verify your local environment configuration (Press Enter to accept defaults):");
    for (const key of LOCAL_KEYS) {
      if (key === "LOCAL_WP_PATH") {
        let wpPath = localEnv[key] || "../wordpress";
        while (true) {
          const choice = await prompt({
            type: "select",
            name: "pathType",
            message: `Select WordPress installation source (Current: ${wpPath}):`,
            choices: [`Default (${wpPath})`, "Custom Path"]
          });
          if (choice.pathType === "Custom Path") {
            const customPathResponse = await prompt({
              type: "input",
              name: "LOCAL_WP_PATH",
              message: "Enter custom path for LOCAL_WP_PATH:",
              initial: wpPath
            });
            wpPath = customPathResponse.LOCAL_WP_PATH;
          }
          const configPath = __require("path").join(process.cwd(), wpPath, "wp-config.php");
          if (existsSync(configPath)) {
            const content = readFileSync(configPath, "utf-8");
            const getDefine = (k) => {
              const regex = new RegExp(`define\\s*\\(\\s*['"]${k}['"]\\s*,\\s*['"]([^'"]*)['"]\\s*\\)`, "i");
              const match = content.match(regex);
              return match ? match[1] : "";
            };
            localEnv["LOCAL_DB_NAME"] = getDefine("DB_NAME") || localEnv["LOCAL_DB_NAME"] || "";
            localEnv["LOCAL_DB_USER"] = getDefine("DB_USER") || localEnv["LOCAL_DB_USER"] || "";
            localEnv["LOCAL_DB_PASS"] = getDefine("DB_PASSWORD") || localEnv["LOCAL_DB_PASS"] || "";
            localEnv["LOCAL_DB_HOST"] = getDefine("DB_HOST") || localEnv["LOCAL_DB_HOST"] || "localhost";
            localEnv["LOCAL_URL"] = getDefine("WP_HOME") || getDefine("WP_SITEURL") || localEnv["LOCAL_URL"] || "";
            localEnv[key] = wpPath;
            break;
          } else {
            log.error(`wp-config.php not found at ${configPath}. Please provide a valid WordPress path.`);
          }
        }
      } else {
        localEnv[key] = await promptKey(key, localEnv[key] || "");
      }
    }
  } else {
    log.info("CI environment detected, skipping interactive prompts for local configuration.");
  }
  writeFileSync(".env", Object.entries(localEnv).map(([k, v]) => `${k}=${v}`).join(`
`) + `
`);
  return localEnv;
}
async function getProdEnv() {
  const prodEnv = parseEnv(".env.production");
  if (!prodEnv["PROD_WP_ADMIN_USER"])
    prodEnv["PROD_WP_ADMIN_USER"] = "admin_zsec";
  if (!prodEnv["PROD_WP_ADMIN_PASS"]) {
    const crypto = __require("crypto");
    prodEnv["PROD_WP_ADMIN_PASS"] = crypto.randomBytes(16).toString("base64").replace(/[^a-zA-Z0-9]/g, "").substring(0, 24);
    log.info("Generated new secure password for PROD_WP_ADMIN_PASS");
  }
  if (!prodEnv["PROD_WP_ADMIN_EMAIL"])
    prodEnv["PROD_WP_ADMIN_EMAIL"] = "admin@example.com";
  if (!process.env.CI) {
    log.info("Please verify your production environment configuration (Press Enter to accept defaults):");
    for (const key of PROD_KEYS) {
      prodEnv[key] = await promptKey(key, prodEnv[key] || "");
    }
  } else {
    log.info("CI environment detected, skipping interactive prompts for production configuration.");
  }
  writeFileSync(".env.production", Object.entries(prodEnv).map(([k, v]) => `${k}=${v}`).join(`
`) + `
`);
  return prodEnv;
}
async function getEnv() {
  const local = await getLocalEnv();
  const prod = await getProdEnv();
  return { ...local, ...prod };
}
var prompt, LOCAL_KEYS, PROD_KEYS, PASSWORD_KEYS, parseEnv = (path) => {
  if (!existsSync(path)) {
    appendFileSync(path, "");
    return {};
  }
  const content = readFileSync(path, "utf-8");
  const lines = content.split(`
`);
  const config = {};
  for (const line of lines) {
    const match = line.match(/^([^#=]+)=(.*)$/);
    if (match) {
      config[match[1].trim()] = match[2].trim();
    }
  }
  return config;
};
var init_env = __esm(() => {
  init_logger();
  ({ prompt } = require_enquirer());
  LOCAL_KEYS = [
    "PROJECT_SLUG",
    "LOCAL_WP_PATH",
    "LOCAL_URL",
    "LOCAL_DB_NAME",
    "LOCAL_DB_USER",
    "LOCAL_DB_PASS",
    "LOCAL_DB_HOST",
    "MYSQLDUMP_PATH",
    "MYSQL_PATH",
    "SEVENZIP_PATH"
  ];
  PROD_KEYS = [
    "PROD_URL",
    "PROD_DB_NAME",
    "PROD_DB_USER",
    "PROD_DB_PASS",
    "PROD_DB_HOST",
    "WP_TABLE_PREFIX",
    "SMTP_HOST",
    "SMTP_PORT",
    "SMTP_USER",
    "SMTP_PASS",
    "PROD_WP_ADMIN_USER",
    "PROD_WP_ADMIN_PASS",
    "PROD_WP_ADMIN_EMAIL"
  ];
  PASSWORD_KEYS = ["LOCAL_DB_PASS", "PROD_DB_PASS", "SMTP_PASS", "PROD_WP_ADMIN_PASS"];
});

// cli/lib/db.ts
var {spawn } = globalThis.Bun;
import { createReadStream, createWriteStream } from "fs";
import { rename, appendFile } from "fs/promises";
import { createInterface } from "readline";
async function exportDb(config, outputPath) {
  const args = [
    config.MYSQLDUMP_PATH,
    "-u",
    config.LOCAL_DB_USER,
    ...config.LOCAL_DB_PASS ? [`-p${config.LOCAL_DB_PASS}`] : [],
    "-h",
    config.LOCAL_DB_HOST,
    config.LOCAL_DB_NAME
  ];
  log.info(`Exporting local database to ${outputPath}...`);
  const proc = spawn(args, { stdout: Bun.file(outputPath), stderr: "pipe" });
  const exitCode = await proc.exited;
  if (exitCode !== 0) {
    const stderr = await new Response(proc.stderr).text();
    throw new Error(`mysqldump failed: ${stderr}`);
  }
  return outputPath;
}
async function importDb(config, sqlPath) {
  const args = [
    config.MYSQL_PATH,
    "-u",
    config.LOCAL_DB_USER,
    ...config.LOCAL_DB_PASS ? [`-p${config.LOCAL_DB_PASS}`] : [],
    "-h",
    config.LOCAL_DB_HOST,
    config.LOCAL_DB_NAME
  ];
  log.info(`Importing ${sqlPath} into local database...`);
  const proc = spawn(args, { stdin: Bun.file(sqlPath), stdout: "pipe", stderr: "pipe" });
  const exitCode = await proc.exited;
  if (exitCode !== 0) {
    const stderr = await new Response(proc.stderr).text();
    throw new Error(`mysql import failed: ${stderr}`);
  }
}
async function searchReplaceSQL(filePath, from, to) {
  const tempPath = `${filePath}.tmp-${Date.now()}`;
  const readStream = createReadStream(filePath, { encoding: "utf-8" });
  const writeStream = createWriteStream(tempPath, { encoding: "utf-8" });
  const rl = createInterface({
    input: readStream,
    crlfDelay: Infinity
  });
  const regex = new RegExp(`s:\\d+:"${from.replace(/[.*+?^$\\{}()|[\\]\\\\]/g, "\\$&")}"`, "g");
  const toLength = Buffer.byteLength(to, "utf8");
  const toStr = `s:${toLength}:"${to}"`;
  for await (const line of rl) {
    const createTableMatch = line.match(/^CREATE TABLE (?:IF NOT EXISTS )?`([^`]+)`/i);
    if (createTableMatch) {
      writeStream.write(`DROP TABLE IF EXISTS \`${createTableMatch[1]}\`;
`);
    }
    let processed = line.replace(regex, toStr);
    processed = processed.split(from).join(to);
    writeStream.write(processed + `
`);
  }
  await new Promise((resolve, reject) => {
    writeStream.end(() => resolve());
    writeStream.on("error", reject);
  });
  await rename(tempPath, filePath);
}
async function hardenAdminCredentialsSQL(filePath, config) {
  const { PROD_WP_ADMIN_USER, PROD_WP_ADMIN_PASS, PROD_WP_ADMIN_EMAIL, WP_TABLE_PREFIX } = config;
  if (!PROD_WP_ADMIN_USER || !PROD_WP_ADMIN_PASS) {
    log.warn("Missing production admin credentials. Skipping credential hardening.");
    return;
  }
  const emailUpdate = PROD_WP_ADMIN_EMAIL ? `, \`user_email\` = '${PROD_WP_ADMIN_EMAIL}'` : "";
  const sqlAppend = `

-- Secure Admin Credentials Injection
UPDATE \`${WP_TABLE_PREFIX || "wp_"}users\` 
SET \`user_login\` = '${PROD_WP_ADMIN_USER}', 
    \`user_pass\` = MD5('${PROD_WP_ADMIN_PASS}')${emailUpdate}
WHERE \`user_login\` = 'admin' OR \`ID\` = 1;
`;
  log.info(`Hardening admin credentials in ${filePath}...`);
  await appendFile(filePath, sqlAppend, "utf-8");
}
async function createDatabase(config) {
  const args = [
    config.MYSQL_PATH,
    "-u",
    config.LOCAL_DB_USER,
    ...config.LOCAL_DB_PASS ? [`-p${config.LOCAL_DB_PASS}`] : [],
    "-h",
    config.LOCAL_DB_HOST,
    "-e",
    `CREATE DATABASE IF NOT EXISTS \`${config.LOCAL_DB_NAME}\`;`
  ];
  log.info(`Ensuring database '${config.LOCAL_DB_NAME}' exists...`);
  const proc = spawn(args, { stdout: "pipe", stderr: "pipe" });
  const exitCode = await proc.exited;
  if (exitCode !== 0) {
    const stderr = await new Response(proc.stderr).text();
    throw new Error(`mysql database creation failed: ${stderr}`);
  }
}
async function getLocalTablePrefix(wpPath) {
  const { join } = __require("path");
  const { readFile } = __require("fs/promises");
  const wpConfigPath = join(wpPath, "wp-config.php");
  try {
    const content = await readFile(wpConfigPath, "utf-8");
    const match = content.match(/\$table_prefix\s*=\s*['"]([^'"]+)['"]/);
    return match ? match[1] : "wp_";
  } catch (err) {
    log.warn(`Could not read wp-config.php at ${wpConfigPath}. Defaulting local prefix to 'wp_'.`);
    return "wp_";
  }
}
async function renameDatabasePrefixSQL(filePath, oldPrefix, newPrefix) {
  if (oldPrefix === newPrefix)
    return;
  log.info(`Migrating database prefixes from '${oldPrefix}' to '${newPrefix}'...`);
  const tempPath = `${filePath}.prefix-${Date.now()}`;
  const readStream = createReadStream(filePath, { encoding: "utf-8" });
  const writeStream = createWriteStream(tempPath, { encoding: "utf-8" });
  const rl = createInterface({
    input: readStream,
    crlfDelay: Infinity
  });
  const prefixRegex = new RegExp(`\\\`${oldPrefix}`, "g");
  for await (const line of rl) {
    const processed = line.replace(prefixRegex, `\`${newPrefix}`);
    writeStream.write(processed + `
`);
  }
  const sqlAppend = `

-- Meta Key Prefix Migrations
UPDATE \`${newPrefix}options\` SET option_name = REPLACE(option_name, '${oldPrefix}', '${newPrefix}') WHERE option_name LIKE '${oldPrefix}%';
UPDATE \`${newPrefix}usermeta\` SET meta_key = REPLACE(meta_key, '${oldPrefix}', '${newPrefix}') WHERE meta_key LIKE '${oldPrefix}%';
`;
  writeStream.write(sqlAppend);
  await new Promise((resolve, reject) => {
    writeStream.end(() => resolve());
    writeStream.on("error", reject);
  });
  await rename(tempPath, filePath);
}
var init_db = __esm(() => {
  init_logger();
});

// cli/lib/zip.ts
var {spawn: spawn2 } = globalThis.Bun;
import { resolve } from "path";
async function createZip(sevenZipPath, sourceDir, outputZip) {
  log.info(`Creating ZIP archive: ${outputZip}...`);
  const absOutput = resolve(outputZip);
  const absSource = resolve(sourceDir, "*");
  const args = [sevenZipPath, "a", "-tzip", "-mx=6", absOutput, absSource];
  const proc = spawn2(args, { stdout: "pipe", stderr: "pipe" });
  const exitCode = await proc.exited;
  if (exitCode !== 0) {
    const stderr = await new Response(proc.stderr).text();
    throw new Error(`7-Zip failed: ${stderr}`);
  }
}
async function extractZip(sevenZipPath, zipPath, outputDir) {
  log.info(`Extracting ZIP archive: ${zipPath}...`);
  const absZip = resolve(zipPath);
  const absOutput = resolve(outputDir);
  const args = [sevenZipPath, "x", absZip, `-o${absOutput}`, "-y"];
  const proc = spawn2(args, { stdout: "pipe", stderr: "pipe" });
  const exitCode = await proc.exited;
  if (exitCode !== 0) {
    const stderr = await new Response(proc.stderr).text();
    throw new Error(`7-Zip extraction failed: ${stderr}`);
  }
}
var init_zip = __esm(() => {
  init_logger();
});

// cli/commands/init.ts
var exports_init = {};
__export(exports_init, {
  default: () => init_default
});
import { existsSync as existsSync2 } from "fs";
import { mkdir, writeFile, cp, rm } from "fs/promises";
import { join } from "path";
import { randomBytes } from "crypto";
async function init_default(args) {
  log.info("Starting OG WP Ecosystem Initialization...");
  const env = await getLocalEnv();
  const wpDir = resolvePath(env.LOCAL_WP_PATH);
  if (!existsSync2(wpDir)) {
    await mkdir(wpDir, { recursive: true });
  }
  await createDatabase(env);
  const tempDir = join(process.cwd(), ".wp-temp-" + Date.now());
  await mkdir(tempDir, { recursive: true });
  const coreZip = join(tempDir, "latest.zip");
  log.info("Downloading latest WordPress core...");
  const wpRes = await fetch("https://wordpress.org/latest.zip");
  if (!wpRes.ok)
    throw new Error("Failed to download WordPress core");
  await Bun.write(coreZip, await wpRes.arrayBuffer());
  await extractZip(env.SEVENZIP_PATH, coreZip, tempDir);
  log.info("Scaffolding core into target directory...");
  const extractedWpDir = join(tempDir, "wordpress");
  await cp(extractedWpDir, wpDir, { recursive: true, force: true });
  const cf7Zip = join(tempDir, "cf7.zip");
  log.info("Downloading Contact Form 7...");
  const cf7Res = await fetch("https://downloads.wordpress.org/plugin/contact-form-7.latest-stable.zip");
  if (cf7Res.ok) {
    await Bun.write(cf7Zip, await cf7Res.arrayBuffer());
    const pluginsDir = join(wpDir, "wp-content", "plugins");
    await extractZip(env.SEVENZIP_PATH, cf7Zip, pluginsDir);
  } else {
    log.warn("Failed to download Contact Form 7. Skipping.");
  }
  const myPluginSource = join(process.cwd());
  const myPluginDest = join(wpDir, "wp-content", "plugins", "og-of-wp");
  log.info("Linking og-of-wp master plugin...");
  if (myPluginSource !== myPluginDest) {
    await mkdir(myPluginDest, { recursive: true });
    const includeDirs = ["admin", "includes", "languages", "og-of-wp.php", "index.php"];
    for (const item of includeDirs) {
      const src = join(myPluginSource, item);
      if (existsSync2(src)) {
        await cp(src, join(myPluginDest, item), { recursive: true, force: true });
      }
    }
  }
  log.info("Scaffolding OG Starter Theme...");
  const themeSource = join(process.cwd(), "cli", "scaffolds", "og-starter-theme");
  const themeDest = join(wpDir, "wp-content", "themes", "og-starter-theme");
  if (existsSync2(themeSource)) {
    await cp(themeSource, themeDest, { recursive: true, force: true });
  }
  log.info("Generating secure wp-config.php...");
  const saltKeys = [
    "AUTH_KEY",
    "SECURE_AUTH_KEY",
    "LOGGED_IN_KEY",
    "NONCE_KEY",
    "AUTH_SALT",
    "SECURE_AUTH_SALT",
    "LOGGED_IN_SALT",
    "NONCE_SALT"
  ];
  let salts = "";
  for (const key of saltKeys) {
    const salt = randomBytes(48).toString("base64");
    salts += `define( '${key}', '${salt}' );
`;
  }
  const wpConfigContent = `<?php
define( 'DB_NAME', '${env.LOCAL_DB_NAME}' );
define( 'DB_USER', '${env.LOCAL_DB_USER}' );
define( 'DB_PASSWORD', '${env.LOCAL_DB_PASS}' );
define( 'DB_HOST', '${env.LOCAL_DB_HOST}' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

${salts}

$table_prefix = 'wp_';

define( 'WP_HOME', '${env.LOCAL_URL}' );
define( 'WP_SITEURL', '${env.LOCAL_URL}' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
`;
  await writeFile(join(wpDir, "wp-config.php"), wpConfigContent, "utf-8");
  log.info("Cleaning up temporary files...");
  await rm(tempDir, { recursive: true, force: true });
  log.success("OG WP Ecosystem Initialized Successfully!");
  log.info(`Core Path: ${wpDir}`);
  log.info(`Database: ${env.LOCAL_DB_NAME} (Created via MySQL)`);
  log.info(`Active Theme: og-starter-theme`);
  log.info(`You can now log into your local WordPress at ${env.LOCAL_URL}/wp-admin`);
}
function resolvePath(p) {
  const { resolve, isAbsolute } = __require("path");
  return isAbsolute(p) ? p : resolve(process.cwd(), p);
}
var init_init = __esm(() => {
  init_env();
  init_db();
  init_zip();
  init_logger();
});

// cli/commands/package.ts
var exports_package = {};
__export(exports_package, {
  default: () => package_default
});
import { existsSync as existsSync3 } from "fs";
import { rm as rm2, mkdir as mkdir2, cp as cp2, writeFile as writeFile2 } from "fs/promises";
import { join as join2 } from "path";
import crypto from "crypto";
async function package_default() {
  const env = await getEnv();
  log.info(`Starting ${env.PROJECT_SLUG} package build...`);
  const stagingDir = join2(process.cwd(), ".staging");
  const stagingWpDir = join2(stagingDir, "wordpress");
  if (existsSync3(stagingDir)) {
    await rm2(stagingDir, { recursive: true, force: true });
  }
  await mkdir2(stagingDir, { recursive: true });
  log.info("Copying WordPress files to staging...");
  await cp2(env.LOCAL_WP_PATH, stagingWpDir, { recursive: true });
  const sampleConfig = join2(stagingWpDir, "wp-config-sample.php");
  if (existsSync3(sampleConfig)) {
    await rm2(sampleConfig, { force: true });
  }
  log.info("Generating production wp-config.php...");
  const saltKeys = [
    "AUTH_KEY",
    "SECURE_AUTH_KEY",
    "LOGGED_IN_KEY",
    "NONCE_KEY",
    "AUTH_SALT",
    "SECURE_AUTH_SALT",
    "LOGGED_IN_SALT",
    "NONCE_SALT"
  ];
  const salts = saltKeys.map((key) => `define( '${key}', '${crypto.randomBytes(48).toString("base64").replace(/['\\]/g, "")}' );`).join(`
`);
  const wpConfigContent = `<?php
/**
 * Production WordPress Configuration
 * Auto-generated by ${env.PROJECT_SLUG}-cli
 */

define( 'DB_NAME', '${env.PROD_DB_NAME}' );
define( 'DB_USER', '${env.PROD_DB_USER}' );
define( 'DB_PASSWORD', '${env.PROD_DB_PASS}' );
define( 'DB_HOST', '${env.PROD_DB_HOST}' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

${salts}

$table_prefix = '${env.WP_TABLE_PREFIX}';

define( 'WP_HOME', '${env.PROD_URL}' );
define( 'WP_SITEURL', '${env.PROD_URL}' );
define( 'WP_DEBUG', false );
define( 'DISALLOW_FILE_EDIT', true );

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
`;
  await writeFile2(join2(stagingWpDir, "wp-config.php"), wpConfigContent, "utf-8");
  log.info("Generating production .htaccess...");
  const htaccessContent = `# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress

# Security rules
<Files wp-config.php>
    order allow,deny
    deny from all
</Files>
Options -Indexes
`;
  await writeFile2(join2(stagingWpDir, ".htaccess"), htaccessContent, "utf-8");
  log.info("Configuring Zero-Touch SMTP plugin...");
  const muPluginsDir = join2(stagingWpDir, "wp-content", "mu-plugins");
  if (!existsSync3(muPluginsDir)) {
    await mkdir2(muPluginsDir, { recursive: true });
  }
  const smtpConfigContent = `<?php
/**
 * Plugin Name: ${env.PROJECT_SLUG} Sovereign Mail Delivery
 * Description: Zero-touch PHPMailer SMTP auto-configuration.
 * Version: 1.0.0
 */
add_action( 'phpmailer_init', function( $phpmailer ) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = '${env.SMTP_HOST}';
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = ${env.SMTP_PORT};
    $phpmailer->SMTPSecure = 'tls';
    $phpmailer->Username   = '${env.SMTP_USER}';
    $phpmailer->Password   = '${env.SMTP_PASS}';
    $phpmailer->From       = '${env.SMTP_USER}';
    $phpmailer->FromName   = '${env.PROJECT_SLUG}';
} );
`;
  await writeFile2(join2(muPluginsDir, `${env.PROJECT_SLUG}-smtp-config.php`), smtpConfigContent, "utf-8");
  const dbPrompt = await prompt2({
    type: "confirm",
    name: "exportDb",
    message: "Do you want to export the current local DB along with the zip file?"
  });
  const dateStr = new Date().toISOString().split("T")[0];
  let sqlFile = null;
  if (dbPrompt.exportDb) {
    log.info("Exporting and preparing database...");
    sqlFile = join2(process.cwd(), `${env.PROJECT_SLUG}-import-${dateStr}.sql`);
    await exportDb(env, sqlFile);
    const localPrefix = await getLocalTablePrefix(env.LOCAL_WP_PATH);
    if (localPrefix !== env.WP_TABLE_PREFIX) {
      await renameDatabasePrefixSQL(sqlFile, localPrefix, env.WP_TABLE_PREFIX);
    }
    await searchReplaceSQL(sqlFile, env.LOCAL_URL, env.PROD_URL);
    await hardenAdminCredentialsSQL(sqlFile, env);
    log.success(`Database exported to separate SQL file: ${sqlFile}`);
  } else {
    log.info("Skipping database export...");
  }
  const zipName = `${env.PROJECT_SLUG}-cpanel-ready-${dateStr}.zip`;
  await createZip(env.SEVENZIP_PATH, stagingWpDir, zipName);
  log.info("Cleaning up staging...");
  await rm2(stagingDir, { recursive: true, force: true });
  log.success(`Package created successfully: ${zipName}`);
  if (sqlFile) {
    log.success(`Side-by-side SQL import file created: ${sqlFile}`);
    log.info(`Upload ${zipName} via cPanel File Manager and import ${sqlFile} via phpMyAdmin.`);
  }
}
var prompt2;
var init_package = __esm(() => {
  init_env();
  init_zip();
  init_db();
  init_logger();
  ({ prompt: prompt2 } = require_enquirer());
});

// cli/commands/db-push.ts
var exports_db_push = {};
__export(exports_db_push, {
  default: () => db_push_default
});
async function db_push_default() {
  const env = await getEnv();
  log.info(`Starting ${env.PROJECT_SLUG} local DB export...`);
  const dateStr = new Date().toISOString().split("T")[0];
  const outputFile = `${env.PROJECT_SLUG}-push-${dateStr}.sql`;
  await exportDb(env, outputFile);
  const localPrefix = await getLocalTablePrefix(env.LOCAL_WP_PATH);
  if (localPrefix !== env.WP_TABLE_PREFIX) {
    await renameDatabasePrefixSQL(outputFile, localPrefix, env.WP_TABLE_PREFIX);
  }
  log.info("Rewriting URLs for production...");
  await searchReplaceSQL(outputFile, env.LOCAL_URL, env.PROD_URL);
  await hardenAdminCredentialsSQL(outputFile, env);
  log.success(`SQL ready: ${outputFile}`);
  log.info(`Import this file via cPanel > phpMyAdmin into database: ${env.PROD_DB_NAME}`);
}
var init_db_push = __esm(() => {
  init_env();
  init_db();
  init_logger();
});

// cli/commands/db-pull.ts
var exports_db_pull = {};
__export(exports_db_pull, {
  default: () => db_pull_default
});
import { existsSync as existsSync4, readFileSync as readFileSync2 } from "fs";
import { cp as cp3, rm as rm3 } from "fs/promises";
async function db_pull_default(args) {
  if (args.length === 0) {
    log.error("Please provide the path to the production SQL dump.");
    log.info("Usage: og-deploy db:pull <path/to/dump.sql>");
    return;
  }
  const inputSql = args[0];
  if (!existsSync4(inputSql)) {
    log.error(`File not found: ${inputSql}`);
    return;
  }
  log.info("Starting production DB import...");
  const env = await getLocalEnv();
  let initialProdUrl = "";
  if (existsSync4(".env.production")) {
    const prodEnvStr = readFileSync2(".env.production", "utf-8");
    const match = prodEnvStr.match(/^PROD_URL=(.*)$/m);
    if (match)
      initialProdUrl = match[1].trim();
  }
  const { prompt } = require_enquirer();
  const response = await prompt({
    type: "input",
    name: "PROD_URL",
    message: "Enter the production URL to search and replace:",
    initial: initialProdUrl
  });
  const PROD_URL = response.PROD_URL;
  const tempSql = `temp-pull-${Date.now()}.sql`;
  log.info("Creating temporary copy...");
  await cp3(inputSql, tempSql);
  log.info("Rewriting URLs for local environment...");
  await searchReplaceSQL(tempSql, PROD_URL, env.LOCAL_URL);
  await importDb(env, tempSql);
  log.info("Cleaning up temp file...");
  await rm3(tempSql, { force: true });
  log.success(`Production database imported to local: ${env.LOCAL_DB_NAME}`);
  log.info(`All URLs replaced: ${PROD_URL} -> ${env.LOCAL_URL}`);
}
var init_db_pull = __esm(() => {
  init_env();
  init_db();
  init_logger();
});

// cli/commands/backup.ts
var exports_backup = {};
__export(exports_backup, {
  default: () => backup_default
});
import { existsSync as existsSync5 } from "fs";
import { mkdir as mkdir3 } from "fs/promises";
import { join as join3 } from "path";
async function backup_default() {
  log.info("Starting local backup...");
  const env = await getLocalEnv();
  const backupsDir = join3(process.cwd(), "backups");
  if (!existsSync5(backupsDir)) {
    await mkdir3(backupsDir, { recursive: true });
  }
  const date = new Date;
  const timestamp = date.toISOString().replace(/[:.]/g, "-").replace("T", "-").slice(0, 16);
  const sqlFile = join3(backupsDir, `${env.PROJECT_SLUG}-db-${timestamp}.sql`);
  const zipFile = join3(backupsDir, `${env.PROJECT_SLUG}-files-${timestamp}.zip`);
  await exportDb(env, sqlFile);
  await createZip(env.SEVENZIP_PATH, env.LOCAL_WP_PATH, zipFile);
  log.success("Backup completed successfully.");
  log.info(`Database: ${sqlFile}`);
  log.info(`Files: ${zipFile}`);
}
var init_backup = __esm(() => {
  init_env();
  init_db();
  init_zip();
  init_logger();
});

// cli/cli.ts
init_logger();
var command = process.argv[2];
var args = process.argv.slice(3);
async function main() {
  switch (command) {
    case "init":
      await (await Promise.resolve().then(() => (init_init(), exports_init))).default(args);
      break;
    case "package":
      await (await Promise.resolve().then(() => (init_package(), exports_package))).default(args);
      break;
    case "db:push":
      await (await Promise.resolve().then(() => (init_db_push(), exports_db_push))).default(args);
      break;
    case "db:pull":
      await (await Promise.resolve().then(() => (init_db_pull(), exports_db_pull))).default(args);
      break;
    case "backup":
      await (await Promise.resolve().then(() => (init_backup(), exports_backup))).default(args);
      break;
    default:
      log.info("Usage: og-deploy <command> [options]");
      log.info("Commands:");
      log.info("  init     - Bootstraps a fresh WordPress ecosystem with the OG Starter Theme");
      log.info("  package  - Builds a ZIP for cPanel upload (includes DB & SMTP)");
      log.info("  db:push  - Exports local DB -> production-ready SQL");
      log.info("  db:pull  - Converts a provided prod SQL dump -> local-ready and imports");
      log.info("  backup   - Creates a timestamped local backup");
      break;
  }
}
main().catch((err) => {
  log.error(`Error: ${err.message}`);
  process.exit(1);
});
