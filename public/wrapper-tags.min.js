(function () {
  'use strict';

  var tableSelector = 'table[id^="ctrl_wt_opening_tags"], table[id^="ctrl_wt_closing_tags"], table[id^="ctrl_wt_complete_tags"]';
  var mcwPatchAttempts = 0;

  function ensureStyle() {
    if (document.getElementById('wt-backend-label-style')) {
      return;
    }

    var style = document.createElement('style');
    style.id = 'wt-backend-label-style';
    style.textContent = ''
      + '.wt-field-label{display:block;font-weight:600;margin:0 0 4px;line-height:1.2;}'
      + '.multicolumnwizard td.mcwUpdateFields > label{display:none !important;}';

    document.head.appendChild(style);
  }

  function fallbackLabel(td) {
    var firstControl = null;

    Array.prototype.forEach.call(td.children, function (child) {
      if (firstControl) {
        return;
      }

      var tagName = child.tagName ? child.tagName.toLowerCase() : '';

      if (tagName === 'input' || tagName === 'select' || tagName === 'textarea') {
        firstControl = child;
      }
    });

    if (firstControl) {
      var ref = (firstControl.name || firstControl.id || '').toLowerCase();
      var key = '';
      var bracketMatch = ref.match(/\[([a-z0-9_]+)\]$/);

      if (bracketMatch && bracketMatch[1]) {
        key = bracketMatch[1];
      } else {
        var idMatch = ref.match(/_([a-z0-9_]+)$/);

        if (idMatch && idMatch[1]) {
          key = idMatch[1];
        }
      }

      if (key === 'tag') {
        return 'Tag';
      }

      if (key === 'class') {
        return 'Klasse';
      }

      if (key === 'name') {
        return 'Attribut-Name';
      }

      if (key === 'value') {
        return 'Attribut-Wert';
      }
    }

    return '';
  }

  function normalizeLabel(text) {
    var normalized = (text || '').trim();
    var lower = normalized.toLowerCase();

    if (lower === 'name') {
      return 'Attribut-Name';
    }

    if (lower === 'wert') {
      return 'Attribut-Wert';
    }

    return normalized;
  }

  function rebuildLabels(root) {
    var tables = root.querySelectorAll(tableSelector);

    tables.forEach(function (table) {
      table.querySelectorAll('td.mcwUpdateFields').forEach(function (td) {
        td.querySelectorAll('.wt-field-label').forEach(function (oldLabel) {
          oldLabel.remove();
        });

        var h3 = td.querySelector('h3');
        var labelText = '';

        if (h3) {
          labelText = (h3.textContent || '').trim();
          h3.remove();
        }

        labelText = normalizeLabel(labelText);

        if (!labelText) {
          labelText = fallbackLabel(td);
        }

        if (!labelText) {
          return;
        }

        var label = document.createElement('div');
        label.className = 'wt-field-label';
        label.textContent = labelText;

        var firstElement = td.querySelector('select, input, textarea, table');

        if (firstElement) {
          td.insertBefore(label, firstElement);
        } else {
          td.insertBefore(label, td.firstChild);
        }
      });
    });
  }

  function patchNestedMcwNewOperation() {
    if (window.MultiColumnWizard && window.MultiColumnWizard.__wtNestedNewPatched) {
      return;
    }

    if (!window.MultiColumnWizard || typeof window.MultiColumnWizard.insertNewElement !== 'function' || typeof window.MultiColumnWizard.newClick !== 'function') {
      if (mcwPatchAttempts < 20) {
        mcwPatchAttempts += 1;
        window.setTimeout(patchNestedMcwNewOperation, 100);
      }

      return;
    }

    var originalInsertNewElement = window.MultiColumnWizard.insertNewElement;
    var patchedInsertNewElement = function (button, row) {
      var parentMcw = row && row.getParent ? row.getParent('.tl_modulewizard.multicolumnwizard') : null;
      var fieldName = parentMcw && parentMcw.getAttribute ? (parentMcw.getAttribute('data-name') || '') : '';

      if (fieldName.indexOf('][attributes]') !== -1) {
        window.MultiColumnWizard.newClick.call(this, button, row);
        return;
      }

      originalInsertNewElement.call(this, button, row);
    };

    window.MultiColumnWizard.insertNewElement = patchedInsertNewElement;

    if (window.MultiColumnWizard.operationClickCallbacks && window.MultiColumnWizard.operationClickCallbacks.new) {
      var clickCallbacks = window.MultiColumnWizard.operationClickCallbacks.new;
      var filteredCallbacks = [];

      clickCallbacks.forEach(function (callback) {
        if (callback !== originalInsertNewElement && callback !== patchedInsertNewElement) {
          filteredCallbacks.push(callback);
        }
      });

      filteredCallbacks.unshift(patchedInsertNewElement);
      window.MultiColumnWizard.operationClickCallbacks.new = filteredCallbacks;
    }

    Object.keys(window).forEach(function (key) {
      if (key.indexOf('MCW_') !== 0) {
        return;
      }

      var wizard = window[key];

      if (wizard && typeof wizard.updateOperations === 'function') {
        wizard.updateOperations();
      }
    });

    window.MultiColumnWizard.__wtNestedNewPatched = true;
  }

  function run() {
    patchNestedMcwNewOperation();
    ensureStyle();
    rebuildLabels(document);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }

  var observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      mutation.addedNodes.forEach(function (node) {
        if (node && node.nodeType === 1) {
          rebuildLabels(node);
        }
      });
    });
  });

  observer.observe(document.documentElement, { childList: true, subtree: true });
})();
