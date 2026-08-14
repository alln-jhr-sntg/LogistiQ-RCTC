/**
 * searchable_select.js — LVMS searchable select (combobox)
 *
 * Progressive enhancement over a real <select>. The <select> stays in the DOM
 * as the single source of truth: it keeps name="…", it always submits (empty
 * string when nothing is chosen), and it is still what other view scripts read
 * (.value) and listen to ('change'). This module only hides it and drives it
 * from a text input + a filtered listbox.
 *
 * Why not <input type="hidden">:
 *   - ReservationController::store() reads $_POST['project_id'] with no
 *     isset()/?? guard, so the control must always submit.
 *   - views/reservations/create.php already does
 *       document.getElementById('projectSelect').addEventListener('change', …)
 *     and reads this.value inside it. A hidden input never fires 'change' on a
 *     programmatic write; a <select> keeps that contract intact.
 *   - select.value = x refuses unknown values (falls back to ''), which is a
 *     free guard against writing a bogus project_id and hitting the FK.
 *   - With JS off the user still gets a working native picker.
 *
 * Usage, per view, AFTER this file is loaded:
 *   var combo = SearchableSelect.attach('projectSelect', {
 *       placeholder: 'Search project name or code…',
 *       emptyText:   'No matching projects'
 *   });
 *
 * <option> rows may carry data-code (searched alongside the label, shown on the
 * right of the row) and data-note (muted suffix, e.g. "inactive").
 *
 * Instance API:
 *   setOptions(items)        items = [{ value, label, code, note }]
 *   setValue(value[, fire])  fire === true dispatches 'change' on the <select>
 *   getValue()
 *   setRequired(bool)
 *   refresh()                re-read the <select> if other code mutated it
 *   open() / close() / focus()
 *
 * Validation: native `required` is deliberately NOT used. On the text input it
 * would only demand SEARCH TEXT, not a chosen id; on the hidden <select> Chrome
 * refuses to submit ("An invalid form control with name='project_id' is not
 * focusable"). setRequired(true) instead puts a setCustomValidity() message on
 * the visible input whenever the <select> value is '', so the browser's own
 * bubble points at a control the user can see. Same rule as native required:
 * a required field must stay visible — call setRequired(false) before hiding.
 */

var SearchableSelect = (function () {
    'use strict';

    var instances = {};
    var seq = 0;

    // ── helpers ───────────────────────────────────────────────
    function lower(v) {
        return (v === null || v === undefined ? '' : String(v)).toLowerCase();
    }

    function tidy(v) {
        return (v === null || v === undefined ? '' : String(v)).replace(/\s+/g, ' ').trim();
    }

    function el(tag, className) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        return node;
    }

    function fireChange(node) {
        var evt;
        if (typeof Event === 'function') {
            evt = new Event('change', { bubbles: true });
        } else {
            evt = document.createEvent('HTMLEvents');
            evt.initEvent('change', true, false);
        }
        node.dispatchEvent(evt);
    }

    function readItems(select) {
        var items = [];
        var i, opt;
        for (i = 0; i < select.options.length; i++) {
            opt = select.options[i];
            if (opt.value === '') continue;          // placeholder is handled separately
            items.push({
                value: String(opt.value),
                label: tidy(opt.textContent),
                code:  opt.getAttribute('data-code') || '',
                note:  opt.getAttribute('data-note') || ''
            });
        }
        return items;
    }

    function normaliseItems(list) {
        var out = [];
        var i, it;
        list = list || [];
        for (i = 0; i < list.length; i++) {
            it = list[i];
            out.push({
                value: String(it.value),
                label: tidy(it.label),
                code:  it.code ? String(it.code) : '',
                note:  it.note ? String(it.note) : ''
            });
        }
        return out;
    }

    function findItem(w, value) {
        var i;
        value = String(value);
        for (i = 0; i < w.items.length; i++) {
            if (w.items[i].value === value) return w.items[i];
        }
        return null;
    }

    function labelFor(w, value) {
        var item = (value === '' ? null : findItem(w, value));
        return item ? item.label : '';
    }

    // ── the widget DOM ────────────────────────────────────────
    function build(w) {
        var select = w.select;
        var group  = select.closest ? select.closest('.form-group') : null;
        var label  = group ? group.querySelector('.form-label') : null;

        w.wrap  = el('div', 'searchable-select');
        w.input = el('input', 'form-input searchable-select-input');
        w.input.type         = 'text';
        w.input.id           = w.id + '__input';
        w.input.autocomplete = 'off';
        w.input.placeholder  = w.placeholder;
        w.input.setAttribute('role', 'combobox');
        w.input.setAttribute('aria-haspopup', 'listbox');
        w.input.setAttribute('aria-autocomplete', 'list');
        w.input.setAttribute('aria-expanded', 'false');
        w.input.setAttribute('aria-controls', w.id + '__list');

        w.caret = el('button', 'searchable-select-caret');
        w.caret.type     = 'button';
        w.caret.tabIndex = -1;
        w.caret.setAttribute('aria-label', 'Show options');
        w.caret.innerHTML =
            '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10l5 5 5-5z"/></svg>';

        w.list = el('ul', 'searchable-select-list');
        w.list.id = w.id + '__list';
        w.list.setAttribute('role', 'listbox');

        w.wrap.appendChild(w.input);
        w.wrap.appendChild(w.caret);
        w.wrap.appendChild(w.list);
        select.parentNode.insertBefore(w.wrap, select);
        select.classList.add('searchable-select-native');

        if (label) {
            if (!label.id) label.id = w.id + '__label';
            label.setAttribute('for', w.input.id);
            w.input.setAttribute('aria-labelledby', label.id);
            w.list.setAttribute('aria-label', tidy(label.textContent).replace('*', '').trim());
        }
    }

    // ── rendering ─────────────────────────────────────────────
    function buildRow(w, item, index) {
        var row = el('li', 'searchable-select-option');
        var lab = el('span', 'searchable-select-option-label');
        var note, code;

        row.id = w.id + '__opt' + index;
        row.setAttribute('role', 'option');
        row.setAttribute('data-value', item.value);

        if (w.select.value === item.value) {
            row.className += ' is-selected';
            row.setAttribute('aria-selected', 'true');
        } else {
            row.setAttribute('aria-selected', 'false');
        }

        lab.textContent = item.label;               // textContent, never innerHTML
        row.appendChild(lab);

        if (item.note) {
            note = el('span', 'searchable-select-option-note');
            note.textContent = item.note;
            row.appendChild(note);
        }
        if (item.code) {
            code = el('span', 'searchable-select-option-code');
            code.textContent = item.code;
            row.appendChild(code);
        }
        return row;
    }

    function render(w) {
        var q     = lower(w.query);
        var frag  = document.createDocumentFragment();
        var shown = [];
        var i, it, row;

        if (q === '') shown.push({ value: '', label: w.noneLabel, code: '', note: '' });

        for (i = 0; i < w.items.length; i++) {
            it = w.items[i];
            if (q === '' ||
                lower(it.label).indexOf(q) !== -1 ||
                lower(it.code).indexOf(q) !== -1) {
                shown.push(it);
            }
        }

        w.rows = [];
        if (shown.length === 0) {
            row = el('li', 'searchable-select-empty');
            row.setAttribute('role', 'presentation');
            row.textContent = w.emptyText;
            frag.appendChild(row);
        } else {
            for (i = 0; i < shown.length; i++) {
                row = buildRow(w, shown[i], i);
                w.rows.push(row);
                frag.appendChild(row);
            }
        }

        w.list.innerHTML = '';
        w.list.appendChild(frag);
        setActive(w, defaultActive(w));
    }

    function defaultActive(w) {
        var i;
        if (!w.rows.length) return -1;
        if (w.query === '') {                       // unfiltered: start on the current value
            for (i = 0; i < w.rows.length; i++) {
                if (w.rows[i].getAttribute('data-value') === w.select.value) return i;
            }
        }
        return 0;                                   // filtered: Enter picks the top match
    }

    function setActive(w, index) {
        var i, row;
        for (i = 0; i < w.rows.length; i++) w.rows[i].classList.remove('is-active');
        w.active = index;
        if (index < 0 || index >= w.rows.length) {
            w.input.removeAttribute('aria-activedescendant');
            return;
        }
        row = w.rows[index];
        row.classList.add('is-active');
        w.input.setAttribute('aria-activedescendant', row.id);
        scrollRowIntoView(w, row);
    }

    function scrollRowIntoView(w, row) {
        var top    = row.offsetTop;                 // the <ul> is the offsetParent
        var bottom = top + row.offsetHeight;
        if (top < w.list.scrollTop) {
            w.list.scrollTop = top;
        } else if (bottom > w.list.scrollTop + w.list.clientHeight) {
            w.list.scrollTop = bottom - w.list.clientHeight;
        }
    }

    function move(w, delta) {
        var next;
        if (!w.rows.length) return;
        next = w.active + delta;
        if (next < 0) next = w.rows.length - 1;
        if (next >= w.rows.length) next = 0;
        setActive(w, next);
    }

    // ── open / close / commit ─────────────────────────────────
    function openList(w, keepQuery) {
        if (!keepQuery) w.query = '';
        if (!w.isOpen) {
            w.isOpen = true;
            w.wrap.classList.add('is-open');
            w.input.setAttribute('aria-expanded', 'true');
        }
        render(w);
    }

    function closeList(w, settleText) {
        if (!w.isOpen) return;
        w.isOpen = false;
        w.active = -1;
        w.wrap.classList.remove('is-open');
        w.input.setAttribute('aria-expanded', 'false');
        w.input.removeAttribute('aria-activedescendant');
        if (settleText) settle(w);
    }

    // Text that was typed but never committed is NOT a value. Blanking the
    // field clears the selection (same as picking the placeholder row);
    // anything else snaps back to the label of whatever is actually selected.
    function settle(w) {
        if (tidy(w.input.value) === '') {
            if (w.select.value !== '') applyValue(w, '', true);
            else w.input.value = '';
            return;
        }
        w.input.value = labelFor(w, w.select.value);
        w.query = '';
    }

    function applyValue(w, value, fire) {
        var changed;
        value = (value === null || value === undefined) ? '' : String(value);
        if (value !== '' && !findItem(w, value)) value = '';
        changed = w.select.value !== value;
        w.select.value = value;
        w.input.value  = labelFor(w, value);
        w.query = '';
        syncValidity(w);
        if (fire && changed) fireChange(w.select);
    }

    function commitRow(w, row) {
        if (!row) return;
        applyValue(w, row.getAttribute('data-value'), true);
        closeList(w, false);
        w.input.focus();
    }

    function syncValidity(w) {
        if (!w.input.setCustomValidity) return;
        w.input.setCustomValidity(
            w.required && w.select.value === '' ? w.requiredMessage : ''
        );
    }

    // ── events ────────────────────────────────────────────────
    function wire(w) {
        w.input.addEventListener('focus', function () {
            openList(w, false);
            w.input.select();
        });

        w.input.addEventListener('input', function () {
            w.query = w.input.value;
            openList(w, true);
        });

        w.input.addEventListener('blur', function () {
            closeList(w, true);
        });

        w.input.addEventListener('keydown', function (e) {
            var key = e.key;

            if (key === 'ArrowDown' || key === 'Down') {
                e.preventDefault();
                if (!w.isOpen) openList(w, false); else move(w, 1);

            } else if (key === 'ArrowUp' || key === 'Up') {
                e.preventDefault();
                if (!w.isOpen) { openList(w, false); move(w, -1); } else { move(w, -1); }

            } else if (key === 'Enter') {
                if (w.isOpen) {
                    e.preventDefault();             // never submit the form on a pick
                    if (w.active >= 0) commitRow(w, w.rows[w.active]);
                    else closeList(w, true);
                }

            } else if (key === 'Escape' || key === 'Esc') {
                if (w.isOpen) {
                    e.preventDefault();
                    closeList(w, false);            // revert, never clear
                    w.input.value = labelFor(w, w.select.value);
                    w.query = '';
                }

            } else if (key === 'Tab') {
                closeList(w, true);
            }
        });

        // Keep focus on the input while the pointer is inside the popup, so
        // blur never beats the click that picks a row.
        w.wrap.addEventListener('mousedown', function (e) {
            if (e.target !== w.input) e.preventDefault();
        });

        w.list.addEventListener('click', function (e) {
            var row = e.target.closest ? e.target.closest('.searchable-select-option') : null;
            if (row) commitRow(w, row);
        });

        w.list.addEventListener('mousemove', function (e) {
            var row = e.target.closest ? e.target.closest('.searchable-select-option') : null;
            var i   = row ? w.rows.indexOf(row) : -1;
            if (i !== -1 && i !== w.active) setActive(w, i);
        });

        w.caret.addEventListener('click', function () {
            if (w.isOpen) { closeList(w, true); }
            else { w.input.focus(); openList(w, false); }
        });
    }

    function rebuildNativeOptions(w) {
        var i, it, opt;
        w.select.innerHTML = '';
        opt = document.createElement('option');
        opt.value = '';
        opt.text  = w.noneLabel;
        w.select.appendChild(opt);
        for (i = 0; i < w.items.length; i++) {
            it  = w.items[i];
            opt = document.createElement('option');
            opt.value = it.value;
            opt.text  = it.label;                   // .text sets textContent — XSS-safe
            if (it.code) opt.setAttribute('data-code', it.code);
            if (it.note) opt.setAttribute('data-note', it.note);
            w.select.appendChild(opt);
        }
    }

    // ── public ────────────────────────────────────────────────
    function attach(target, options) {
        var select = (typeof target === 'string') ? document.getElementById(target) : target;
        var w;

        if (!select || select.tagName !== 'SELECT') return null;
        if (select.id && instances[select.id]) return instances[select.id].api;
        options = options || {};

        w = {
            select:  select,
            id:      select.id || ('searchableSelect' + (++seq)),
            items:   [],
            rows:    [],
            active:  -1,
            isOpen:  false,
            query:   '',
            required: false,
            placeholder: options.placeholder || 'Search…',
            emptyText:   options.emptyText   || 'No matches',
            requiredMessage: options.requiredMessage || 'Please choose one of the listed options.',
            noneLabel:   options.noneLabel ||
                         (select.options.length && select.options[0].value === ''
                             ? tidy(select.options[0].text) : '— None —')
        };

        w.items = readItems(select);
        build(w);
        wire(w);
        applyValue(w, select.value, false);   // seed display from the server-rendered <option selected>

        w.api = {
            input:  w.input,
            select: w.select,
            setOptions: function (items) {
                var keep = w.select.value;
                w.items = normaliseItems(items);
                rebuildNativeOptions(w);
                applyValue(w, findItem(w, keep) ? keep : '', false);
                if (w.isOpen) render(w);
                return this;
            },
            setValue: function (value, fire) {
                applyValue(w, value, fire === true);
                return this;
            },
            getValue:  function () { return w.select.value; },
            setRequired: function (flag) {
                w.required = !!flag;
                w.input.setAttribute('aria-required', w.required ? 'true' : 'false');
                syncValidity(w);
                return this;
            },
            refresh: function () {
                w.items = readItems(w.select);
                applyValue(w, w.select.value, false);
                if (w.isOpen) render(w);
                return this;
            },
            open:  function () { w.input.focus(); openList(w, false); return this; },
            close: function () { closeList(w, true); return this; },
            focus: function () { w.input.focus(); return this; }
        };

        instances[w.id] = w;
        return w.api;
    }

    function get(id) {
        return instances[id] ? instances[id].api : null;
    }

    return { attach: attach, get: get };
})();
