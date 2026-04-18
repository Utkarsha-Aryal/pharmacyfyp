(function () {
  "use strict";

  function byId(id) {
    return document.getElementById(id);
  }

  function hideLoader() {
    var loader = byId("loadingOverlay");
    if (loader) {
      loader.style.display = "none";
    }
  }

  function showLoader() {
    var loader = byId("loadingOverlay");
    if (loader) {
      loader.style.display = "block";
    }
  }

  function showNotification(message, type) {
    if (!window.toastr) {
      window.alert(message || "Something happened.");
      return;
    }

    // One small helper is enough here because many pages already call showNotification().
    window.toastr.options = {
      closeButton: true,
      progressBar: true,
      newestOnTop: true,
      positionClass: "toast-top-right",
      timeOut: 3200,
      extendedTimeOut: 900,
      preventDuplicates: true,
      closeDuration: 180,
      showDuration: 180,
      hideDuration: 180,
    };

    var toastType = "error";
    if (type === "success" || type === "warning" || type === "info") {
      toastType = type;
    }

    window.toastr[toastType](message || "Something happened.");
  }

  function escapeHtml(value) {
    return $("<div>").text(value == null ? "" : String(value)).html();
  }

  function getAjaxErrorMessage(xhr, fallbackMessage) {
    if (xhr && xhr.status === 419) {
      return "Your session expired. Refresh the page and try again.";
    }

    var response = xhr && xhr.responseJSON ? xhr.responseJSON : {};

    if (response.message) {
      return response.message;
    }

    if (response.errors && typeof response.errors === "object") {
      var firstKey = Object.keys(response.errors)[0];
      if (firstKey && Array.isArray(response.errors[firstKey]) && response.errors[firstKey].length) {
        return response.errors[firstKey][0];
      }
    }

    return fallbackMessage || "Something went wrong.";
  }

  function getCsrfToken() {
    var metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken && metaToken.getAttribute("content")) {
      return metaToken.getAttribute("content");
    }

    var hiddenToken = document.querySelector('input[name="_token"]');
    return hiddenToken ? hiddenToken.value : "";
  }

  function syncCsrfInputs(context) {
    var token = getCsrfToken();
    if (!token) {
      return;
    }

    var root = context || document;
    root.querySelectorAll('input[name="_token"]').forEach(function (input) {
      input.value = token;
    });
  }

  function initBootstrapUi() {
    if (typeof bootstrap === "undefined") {
      return;
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
      if (!bootstrap.Tooltip.getInstance(element)) {
        new bootstrap.Tooltip(element);
      }
    });

    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (element) {
      if (!bootstrap.Popover.getInstance(element)) {
        new bootstrap.Popover(element);
      }
    });
  }

  function resolveDropdownParent($element) {
    var $parent = $element.closest(".modal, .card, .main-content, .page");
    return $parent.length ? $parent : $(document.body);
  }

  function getNotificationReadStorageKey() {
    var notificationAnchor = byId("mainHeaderNotification");
    var userId = notificationAnchor ? notificationAnchor.dataset.notificationUser : "guest";

    return "pharmacy:notifications:read:" + (userId || "guest");
  }

  function getReadNotificationIds() {
    try {
      return JSON.parse(window.localStorage.getItem(getNotificationReadStorageKey()) || "[]").map(function (id) {
        return String(id);
      });
    } catch (error) {
      return [];
    }
  }

  function setReadNotificationIds(ids) {
    var uniqueIds = Array.from(new Set((ids || []).map(function (id) {
      return String(id);
    })));

    try {
      window.localStorage.setItem(getNotificationReadStorageKey(), JSON.stringify(uniqueIds));
    } catch (error) {
      // localStorage can be blocked in private sessions, so fail quietly.
    }
  }

  function markNotificationAsRead(notificationId) {
    if (!notificationId) {
      return;
    }

    var readIds = getReadNotificationIds();
    var normalizedId = String(notificationId);

    if (readIds.indexOf(normalizedId) === -1) {
      readIds.push(normalizedId);
      setReadNotificationIds(readIds);
    }
  }

  function updateNotificationTrayState() {
    var notificationItems = Array.from(document.querySelectorAll(".notification-item-card[data-notification-id]"));
    var readIds = getReadNotificationIds();
    var unreadCount = 0;

    notificationItems.forEach(function (item) {
      var notificationId = item.dataset.notificationId;
      var isRead = readIds.indexOf(String(notificationId)) !== -1;

      item.classList.toggle("is-read", isRead);
      item.classList.toggle("is-unread", !isRead);

      if (!isRead) {
        unreadCount += 1;
      }
    });

    var countBadge = byId("headerNotificationCount");
    if (countBadge) {
      countBadge.classList.remove("notification-count-pending");

      if (unreadCount > 0) {
        countBadge.textContent = unreadCount;
        countBadge.classList.remove("d-none");
      } else {
        countBadge.classList.add("d-none");
      }
    }

    var summaryLabel = byId("notificationStateLabel");
    if (summaryLabel) {
      summaryLabel.textContent = unreadCount > 0 ? unreadCount + " unread" : (notificationItems.length > 0 ? "All caught up" : "No notifications");
    }

    var markAllButton = byId("notificationMarkAllRead");
    if (markAllButton) {
      markAllButton.disabled = unreadCount === 0;
    }
  }

  function initEnhancedSelects(context) {
    if (!window.jQuery || !$.fn.select2) {
      return;
    }

    var $context = context ? $(context) : $(document);

    $context.find(".js-select2").each(function () {
      var $element = $(this);

      if ($element.hasClass("select2-hidden-accessible")) {
        return;
      }

      $element.select2({
        width: "100%",
        dropdownParent: resolveDropdownParent($element),
        placeholder: $element.data("placeholder") || "Select option",
        allowClear: $element.is("[data-allow-clear]"),
      });
    });

    $context.find(".js-select2-ajax").each(function () {
      var $element = $(this);
      var ajaxUrl = $element.data("ajax-url");

      if (!ajaxUrl || $element.hasClass("select2-hidden-accessible")) {
        return;
      }

      // ajax select is easier to manage when product and supplier list becomes large
      $element.select2({
        width: "100%",
        dropdownParent: resolveDropdownParent($element),
        placeholder: $element.data("placeholder") || "Search item",
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
          url: ajaxUrl,
          dataType: "json",
          delay: 250,
          data: function (params) {
            return {
              q: params.term || "",
              page: params.page || 1,
            };
          },
          processResults: function (data) {
            return data;
          },
          cache: true,
        },
      });
    });
  }

  function initChoices() {
    if (typeof Choices === "undefined") {
      return;
    }

    document.querySelectorAll("[data-trigger]").forEach(function (element) {
      if (element.dataset.choiceReady === "true") {
        return;
      }

      new Choices(element, {
        allowHTML: true,
        searchPlaceholderValue: "Search",
      });

      element.dataset.choiceReady = "true";
    });
  }

  function initWaves() {
    if (typeof Waves === "undefined") {
      return;
    }

    Waves.attach(".btn-wave", ["waves-light"]);
    Waves.init();
  }

  function initFooterYear() {
    var footerYear = byId("year");
    if (footerYear) {
      footerYear.textContent = new Date().getFullYear();
    }
  }

  function initScrollToTop() {
    var scrollToTop = document.querySelector(".scrollToTop");
    if (!scrollToTop) {
      return;
    }

    function toggleButton() {
      scrollToTop.style.display = window.scrollY > 100 ? "flex" : "none";
    }

    toggleButton();
    window.addEventListener("scroll", toggleButton);

    scrollToTop.addEventListener("click", function () {
      window.scrollTo({
        top: 0,
        behavior: "smooth",
      });
    });
  }

  function initHeaderScroll() {
    if (typeof SimpleBar === "undefined") {
      return;
    }

    ["header-notification-scroll", "header-cart-items-scroll"].forEach(function (id) {
      var element = byId(id);

      if (!element || element.dataset.simplebarReady === "true" || element.dataset.nativeScroll === "true") {
        return;
      }

      new SimpleBar(element, { autoHide: true });
      element.dataset.simplebarReady = "true";
    });
  }

  function initNotificationReadState() {
    updateNotificationTrayState();

    if (window.__notificationReadStateBound) {
      return;
    }

    $(document).on("click.notificationRead", ".notification-item-card[data-notification-id]", function () {
      markNotificationAsRead(this.dataset.notificationId);
      updateNotificationTrayState();
    });

    $(document).on("click.notificationRead", "#notificationMarkAllRead", function (event) {
      event.preventDefault();
      event.stopPropagation();

      var allNotificationIds = Array.from(document.querySelectorAll(".notification-item-card[data-notification-id]")).map(function (item) {
        return item.dataset.notificationId;
      });

      setReadNotificationIds(allNotificationIds);
      updateNotificationTrayState();
    });

    window.__notificationReadStateBound = true;
  }

  function initImagePreviewInput() {
    document.querySelectorAll("[data-image-preview-input]").forEach(function (input) {
      if (input.dataset.previewReady === "true") {
        return;
      }

      input.addEventListener("change", function (event) {
        var selectedFile = event.target.files[0];
        var previewSelector = input.getAttribute("data-image-preview-input");
        var previewImage = previewSelector ? document.querySelector(previewSelector) : null;

        if (!selectedFile || !previewImage) {
          return;
        }

        previewImage.src = URL.createObjectURL(selectedFile);
      });

      input.dataset.previewReady = "true";
    });
  }

  function initRememberedTabs() {
    if (typeof bootstrap === "undefined") {
      return;
    }

    ["settingsTab", "roleAccessTab", "dashboardTab"].forEach(function (tabId) {
      var tabList = byId(tabId);
      if (!tabList || tabList.dataset.rememberReady === "true") {
        return;
      }

      var storageKey = "tab:" + tabId;
      var savedTarget = window.localStorage.getItem(storageKey);

      if (savedTarget) {
        var targetTab = tabList.querySelector('[data-bs-target="' + savedTarget + '"]');
        if (targetTab) {
          bootstrap.Tab.getOrCreateInstance(targetTab).show();
        }
      }

      tabList.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (button) {
        button.addEventListener("shown.bs.tab", function (event) {
          var currentTarget = event.target.getAttribute("data-bs-target");
          if (currentTarget) {
            window.localStorage.setItem(storageKey, currentTarget);
          }
        });
      });

      tabList.dataset.rememberReady = "true";
    });
  }

  function initNotificationLoadMore() {
    var loadMoreButton = byId("notificationLoadMore");
    if (!loadMoreButton || loadMoreButton.dataset.bound === "true") {
      return;
    }

    var scrollBox = byId("header-notification-scroll");

    function updateButtonLabel() {
      var remainingCount = document.querySelectorAll(".notification-more.d-none").length;

      if (remainingCount <= 0) {
        loadMoreButton.classList.add("d-none");
        return;
      }

      var step = Math.min(4, remainingCount);
      loadMoreButton.textContent = "Load more (" + step + ")";
      loadMoreButton.classList.remove("d-none");
    }

    loadMoreButton.addEventListener("click", function (event) {
      // keep the dropdown open while revealing more rows
      event.preventDefault();
      event.stopPropagation();

      var hiddenItems = Array.from(document.querySelectorAll(".notification-more.d-none"));

      hiddenItems.slice(0, 4).forEach(function (item) {
        item.classList.remove("d-none");
      });

      if (scrollBox) {
        window.requestAnimationFrame(function () {
          scrollBox.scrollTop = scrollBox.scrollHeight;
        });
      }

      updateButtonLabel();
      updateNotificationTrayState();
    });

    loadMoreButton.dataset.bound = "true";
    updateButtonLabel();
  }

  function getSharedTableDom(searchable) {
    if (searchable === false) {
      return "<'row align-items-center g-2 mb-3'<'col-md-12 dataTables-left'l>>" +
        "t" +
        "<'row align-items-center g-2 mt-3'<'col-md-6'i><'col-md-6'p>>";
    }

    return "<'row align-items-center g-2 mb-3'<'col-md-4 dataTables-left'l><'col-md-8 dataTables-search-wrap'f>>" +
      "t" +
      "<'row align-items-center g-2 mt-3'<'col-md-6'i><'col-md-6'p>>";
  }

  function decorateDataTableUi($wrapper) {
    if (!$wrapper || !$wrapper.length) {
      return;
    }

    $wrapper.find(".dataTables_filter input").attr("placeholder", "Search here").addClass("datatable-search-input");
    $wrapper.find(".dataTables_length select").addClass("datatable-length-select");
    $wrapper.find(".dataTables_info").addClass("datatable-info-text");
  }

  function initConfirmForms() {
    if (!window.jQuery || typeof Swal === "undefined") {
      return;
    }

    $(document).off("submit.confirm", ".js-confirm-submit");
    $(document).on("submit.confirm", ".js-confirm-submit", function (event) {
      event.preventDefault();

      var form = this;
      var title = form.dataset.confirmTitle || "Are you sure?";
      var text = form.dataset.confirmText || "This action cannot be undone.";
      var buttonText = form.dataset.confirmButton || "Yes, continue";

      Swal.fire({
        title: title,
        text: text,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DB1F48",
        cancelButtonColor: "#6b7280",
        confirmButtonText: buttonText,
      }).then(function (result) {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  }

  function initDataTableTabAdjust() {
    if (!window.jQuery || !$.fn.DataTable || window.__datatableTabAdjustBound) {
      return;
    }

    $(document).on("shown.bs.tab", '[data-bs-toggle="tab"]', function () {
      window.requestAnimationFrame(function () {
        if ($.fn.DataTable.tables) {
          $.fn.DataTable.tables({ visible: true, api: true }).columns.adjust();
        }
      });
    });

    window.__datatableTabAdjustBound = true;
  }

  function initStandardDataTables() {
    if (!window.jQuery || !$.fn.DataTable) {
      return;
    }

    $("table.js-datatable, table[data-datatable='true']").each(function () {
      var tableElement = this;
      var $table = $(tableElement);

      if ($.fn.DataTable.isDataTable(tableElement)) {
        return;
      }

      var pageLength = parseInt($table.data("pageLength"), 10);
      var orderColumn = parseInt($table.data("orderColumn"), 10);
      var orderDirection = String($table.data("orderDirection") || "desc").toLowerCase();
      var searchable = $table.data("searchable");

      if (Number.isNaN(pageLength) || pageLength <= 0) {
        pageLength = 10;
      }

      if (searchable === undefined) {
        searchable = false;
      }

      // DataTables breaks when Blade prints one fallback row with colspan for empty state.
      // We remove that placeholder first and let DataTables show its own empty message.
      $table.find("tbody tr").each(function () {
        var $cells = $(this).children("td, th");

        if ($cells.length === 1 && $cells.eq(0).is("[colspan]")) {
          $(this).remove();
        }
      });

      $table.DataTable({
        sPaginationType: "full_numbers",
        lengthMenu: [
          [10, 15, 25, 50, -1],
          [10, 15, 25, 50, "All"],
        ],
        iDisplayLength: pageLength,
        sDom: getSharedTableDom(searchable),
        bAutoWidth: false,
        aaSorting: Number.isNaN(orderColumn) ? [] : [[orderColumn, orderDirection]],
        bSort: true,
        bProcessing: false,
        searchDelay: 300,
        oLanguage: {
          sSearch: "",
          sSearchPlaceholder: "Search here",
          sLengthMenu: "Show _MENU_ entries",
          sEmptyTable: "<p class='no_data_message'>No data available.</p>",
        },
        initComplete: function () {
          decorateDataTableUi($(this.api().table().container()));
        },
        drawCallback: function () {
          decorateDataTableUi($(this.api().table().container()));
        },
      });
    });
  }

  function debounceDataTableSearch(callback, wait) {
    var timeoutId = null;

    return function () {
      var context = this;
      var args = arguments;

      window.clearTimeout(timeoutId);
      timeoutId = window.setTimeout(function () {
        callback.apply(context, args);
      }, wait);
    };
  }

  function bindServerSideSearch(api, tableContainer, wait) {
    var $input = $(tableContainer).find("div.dataTables_filter input[type='search']");
    var timeoutId = null;

    if ($input.length === 0) {
      return;
    }

    var runSearch = function (value) {
      window.clearTimeout(timeoutId);
      timeoutId = window.setTimeout(function () {
        if (api.search() !== value) {
          api.search(value).draw();
        }
      }, wait);
    };

    var flushSearch = function (value) {
      window.clearTimeout(timeoutId);

      if (api.search() !== value) {
        api.search(value).draw();
      }
    };

    $input.off(".DT");
    $input.on("input.datatableDebounce", function () {
      runSearch(this.value);
    });
    $input.on("keydown.datatableDebounce", function (event) {
      if (event.key === "Enter") {
        event.preventDefault();
        flushSearch(this.value);
      }
    });
  }

  function addColumnSearch(api, columns, wait) {
    columns.forEach(function (index) {
      api.columns(index).every(function () {
        var column = this;
        var columnHeader = column.header();
        var columnName = columnHeader.innerText.trim();
        var input = document.createElement("input");
        var runColumnSearch = debounceDataTableSearch(function (value) {
          if (column.search() !== value) {
            column.search(value).draw();
          }
        }, wait);

        $(input)
          .appendTo($(columnHeader).empty())
          .attr("placeholder", columnName)
          .css("width", "100%")
          .addClass("search-input-highlight")
          .on("input change", function () {
            runColumnSearch(this.value);
          });
      });
    });
  }

  function initServerSideDataTable(options) {
    if (!window.jQuery || !$.fn.DataTable || !options) {
      return null;
    }

    var tableElement = typeof options.selector === "string" ? document.querySelector(options.selector) : options.selector;

    if (!tableElement || $.fn.DataTable.isDataTable(tableElement)) {
      return null;
    }

    var $table = $(tableElement);
    var ajaxData = options.ajaxData;
    var searchColumns = Array.isArray(options.searchColumns) ? options.searchColumns : [];
    var searchable = options.searchable === true;
    var searchDelay = Number.isFinite(parseInt(options.searchDelay, 10))
      ? parseInt(options.searchDelay, 10)
      : 450;

    // Some older Blade pages still print one empty fallback row.
    // Removing that row first keeps server-side DataTables from complaining about column counts.
    $table.find("tbody tr").each(function () {
      var $cells = $(this).children("td, th");

      if ($cells.length === 1 && $cells.eq(0).is("[colspan]")) {
        $(this).remove();
      }
    });

    return $table.DataTable({
      sPaginationType: options.paginationType || "full_numbers",
      bSearchable: false,
      lengthMenu: options.lengthMenu || [
        [10, 15, 25, 50, -1],
        [10, 15, 25, 50, "All"],
      ],
      iDisplayLength: options.pageLength || 15,
      sDom: options.dom || getSharedTableDom(searchable),
      bAutoWidth: false,
      aaSorting: Array.isArray(options.order) ? options.order : [],
      bSort: options.sort !== false,
      bProcessing: options.processing !== false,
      bServerSide: options.serverSide !== false,
      searchDelay: searchDelay,
      oLanguage: {
        sSearch: "",
        sSearchPlaceholder: "Search here",
        sLengthMenu: "Show _MENU_ entries",
        sEmptyTable: "<p class='no_data_message'>No data available.</p>",
      },
      aoColumns: options.columns || [],
      aoColumnDefs: options.columnDefs || [],
      ajax: {
        url: options.ajaxUrl,
        type: options.ajaxType || "POST",
        headers: options.headers || {},
        data: function (request) {
          if (typeof ajaxData === "function") {
            ajaxData(request);
          }
        },
      },
      initComplete: function () {
        var api = this.api();
        var $container = $(api.table().container());

        decorateDataTableUi($container);

        bindServerSideSearch(api, $container, searchDelay);

        if (searchColumns.length > 0) {
          addColumnSearch(api, searchColumns, searchDelay);
        }

        if (typeof options.afterInit === "function") {
          options.afterInit.call(this, api);
        }
      },
      drawCallback: function () {
        decorateDataTableUi($(this.api().table().container()));
      },
    });
  }

  function initPurchaseTable() {
    var tableElement = byId("purchaseTable");

    if (!tableElement || !window.jQuery || !$.fn.DataTable || $.fn.DataTable.isDataTable(tableElement)) {
      return;
    }

    window.initServerSideDataTable({
      selector: tableElement,
      pageLength: 15,
      sort: false,
      searchColumns: [1, 3],
      columns: [
        { data: "sno" },
        { data: "reference_no" },
        { data: "invoice_no" },
        { data: "supplier" },
        { data: "items_count" },
        { data: "g_total" },
        { data: "paid" },
        { data: "due" },
        { data: "order_status" },
        { data: "added_date" },
        { data: "action" },
      ],
      ajaxUrl: tableElement.dataset.listUrl,
      ajaxData: function (request) {
        request.supplier_id = byId("current_supplier_id") ? byId("current_supplier_id").value : "";
        request.order_status = byId("current_order_status") ? byId("current_order_status").value : "";
      },
    });
  }

  function updatePurchaseRow(row) {
    var qty = parseFloat($(row).find(".qty-input").val()) || 0;
    var freeQty = parseFloat($(row).find(".free-qty-input").val()) || 0;
    var mrp = parseFloat($(row).find(".mrp-input").val()) || 0;
    var price = parseFloat($(row).find(".price-input").val()) || 0;
    var ccRate = parseFloat($(row).find(".cc-rate-input").val()) || 0;
    var discountPercent = parseFloat($(row).find(".discount-input").val()) || 0;
    var lineAmount = qty * price;
    var discountAmount = lineAmount * discountPercent / 100;
    var freeGoodsValue = freeQty * (mrp * ccRate / 100);

    $(row).data("discountAmount", discountAmount);
    $(row).find(".free-goods-input").val(freeGoodsValue.toFixed(2));
    $(row).find(".subtotal-input").val(lineAmount.toFixed(2));
  }

  function updatePurchaseTotal() {
    var subtotal = 0;
    var discountTotal = 0;
    var freeGoodsTotal = 0;

    $("#purchaseItemsTable .subtotal-input").each(function () {
      subtotal += parseFloat($(this).val()) || 0;
    });

    $("#purchaseItemsTable tbody tr").each(function () {
      discountTotal += parseFloat($(this).data("discountAmount")) || 0;
      freeGoodsTotal += parseFloat($(this).find(".free-goods-input").val()) || 0;
    });

    $("#purchaseSubtotal").val(subtotal.toFixed(2));
    $("#purchaseDiscountTotal").val(discountTotal.toFixed(2));
    $("#purchaseFreeGoodsTotal").val(freeGoodsTotal.toFixed(2));
    $("#grandTotal").val((subtotal - discountTotal).toFixed(2));
  }

  function updatePurchaseRowNumbers() {
    $("#purchaseItemsTable tbody tr").each(function (index) {
      var rowNumberCell = $(this).find(".purchase-row-number");

      if (rowNumberCell.length) {
        rowNumberCell.text(index + 1);
      }
    });
  }

  function initPurchaseForm() {
    var form = byId("purchaseForm");
    var tableBody = document.querySelector("#purchaseItemsTable tbody");
    var template = byId("purchaseItemTemplate");

    if (!form || !tableBody || !template || !window.jQuery || form.dataset.purchaseReady === "true") {
      return;
    }

    updatePurchaseRow($("#purchaseItemsTable tbody tr").first());
    updatePurchaseTotal();

    $(document).off("click.purchase", "#addPurchaseRow");
    $(document).on("click.purchase", "#addPurchaseRow", function () {
      var nextIndex = parseInt(tableBody.dataset.nextIndex || "1", 10);
      var html = template.innerHTML
        .replace(/__INDEX__/g, nextIndex)
        .replace(/__ROW__/g, nextIndex + 1);

      $(tableBody).append(html);
      tableBody.dataset.nextIndex = String(nextIndex + 1);
      initEnhancedSelects($(tableBody).find("tr:last"));
      updatePurchaseRowNumbers();
      updatePurchaseTotal();
    });

    $(document).off("click.purchase", ".removePurchaseRow");
    $(document).on("click.purchase", ".removePurchaseRow", function () {
      var row = $(this).closest("tr");

      if ($("#purchaseItemsTable tbody tr").length === 1) {
        row.find("input").val("");
        row.find(".qty-input").val(1);
        row.find(".free-qty-input").val(0);
        row.find(".mrp-input").val(0);
        row.find(".price-input").val(0);
        row.find(".cc-rate-input").val(0);
        row.find(".discount-input").val(0);
        row.find(".free-goods-input").val("0.00");
        row.find(".subtotal-input").val("0.00");
        row.find("select").val("");
      } else {
        row.remove();
      }

      updatePurchaseRowNumbers();
      updatePurchaseTotal();
    });

    $(document).off("input.purchase", "#purchaseItemsTable .qty-input, #purchaseItemsTable .free-qty-input, #purchaseItemsTable .mrp-input, #purchaseItemsTable .price-input, #purchaseItemsTable .cc-rate-input, #purchaseItemsTable .discount-input");
    $(document).on("input.purchase", "#purchaseItemsTable .qty-input, #purchaseItemsTable .free-qty-input, #purchaseItemsTable .mrp-input, #purchaseItemsTable .price-input, #purchaseItemsTable .cc-rate-input, #purchaseItemsTable .discount-input", function () {
      updatePurchaseRow($(this).closest("tr"));
      updatePurchaseTotal();
    });

    $(document).off("change.purchase", ".purchase-product-select");
    $(document).on("change.purchase", ".purchase-product-select", function () {
      var $select = $(this);
      var productId = $select.val();
      var infoUrl = $select.data("productInfoUrl");
      var $row = $select.closest("tr");

      if (!productId || !infoUrl) {
        return;
      }

      $.get(infoUrl, { product_id: productId }, function (response) {
        if (!response) {
          return;
        }

        if (response.purchase_price !== undefined) {
          $row.find(".price-input").val(parseFloat(response.purchase_price || 0).toFixed(2));
        }
        if (response.mrp !== undefined) {
          $row.find(".mrp-input").val(parseFloat(response.mrp || 0).toFixed(2));
        }
        if (response.cc_rate !== undefined) {
          $row.find(".cc-rate-input").val(parseFloat(response.cc_rate || 0).toFixed(2));
        }

        var infoText = response.name
          ? response.name + " | MRP: " + parseFloat(response.mrp || 0).toFixed(2) + " | CC: " + parseFloat(response.cc_rate || 0).toFixed(2) + "%"
          : "Select product to auto fill latest purchase rate.";
        $row.find(".purchase-stock-note").text(infoText);

        updatePurchaseRow($row);
        updatePurchaseTotal();
      });
    });

    $(form).off("submit.purchase");
    $(form).on("submit.purchase", function (event) {
      event.preventDefault();
      showLoader();

      if (typeof $(form).ajaxSubmit !== "function") {
        form.submit();
        return;
      }

      $(form).ajaxSubmit({
        success: function (response) {
          showNotification(response.message, response.type);
          hideLoader();

          if (response.type === "success" && response.redirect) {
            window.setTimeout(function () {
              window.location.href = response.redirect;
            }, 700);
          }
        },
        error: function (xhr) {
          var response = xhr.responseJSON || {};
          showNotification(response.message || "Could not save purchase.", "error");
          hideLoader();
        },
      });
    });

    form.dataset.purchaseReady = "true";
    updatePurchaseRowNumbers();
  }

  function updateSalesRow(row) {
    var qty = parseFloat($(row).find(".sales-qty-input").val()) || 0;
    var freeQty = parseFloat($(row).find(".sales-free-qty-input").val()) || 0;
    var mrp = parseFloat($(row).find(".sales-mrp-input").val()) || 0;
    var price = parseFloat($(row).find(".sales-price-input").val()) || 0;
    var ccRate = parseFloat($(row).find(".sales-cc-rate-input").val()) || 0;
    var discountPercent = parseFloat($(row).find(".sales-discount-input").val()) || 0;
    var baseAmount = qty * price;
    var discountAmount = baseAmount * discountPercent / 100;
    var freeGoodsValue = freeQty * (mrp * ccRate / 100);

    $(row).data("discountAmount", discountAmount);
    $(row).find(".sales-free-value-input").val(freeGoodsValue.toFixed(2));
    $(row).find(".sales-subtotal-input").val(baseAmount.toFixed(2));
  }

  function updateSalesTotal() {
    var subtotal = 0;
    var discountTotal = 0;
    var freeGoodsTotal = 0;

    $("#salesItemsTable .sales-subtotal-input").each(function () {
      subtotal += parseFloat($(this).val()) || 0;
    });

    $("#salesItemsTable tbody tr").each(function () {
      discountTotal += parseFloat($(this).data("discountAmount")) || 0;
      freeGoodsTotal += parseFloat($(this).find(".sales-free-value-input").val()) || 0;
    });

    $("#salesSubtotal").val(subtotal.toFixed(2));
    $("#salesDiscountTotal").val(discountTotal.toFixed(2));
    $("#salesFreeGoodsTotal").val(freeGoodsTotal.toFixed(2));
    $("#salesGrandTotal").val((subtotal - discountTotal).toFixed(2));
  }

  function updateSalesRowNumbers() {
    $("#salesItemsTable tbody tr").each(function (index) {
      var rowNumberCell = $(this).find(".sales-row-number");

      if (rowNumberCell.length) {
        rowNumberCell.text(index + 1);
      }
    });
  }

  function refreshSalesProductInfo(selectElement) {
    var $select = $(selectElement);
    var productId = $select.val();
    var infoUrl = $select.data("productInfoUrl");
    var $row = $select.closest("tr");

    if (!productId || !infoUrl) {
      return;
    }

    $.get(infoUrl, { product_id: productId }, function (response) {
      if (!response) {
        return;
      }

      if (response.price !== undefined) {
        $row.find(".sales-price-input").val(parseFloat(response.price || 0).toFixed(2));
      }
      if (response.mrp !== undefined) {
        $row.find(".sales-mrp-input").val(parseFloat(response.mrp || 0).toFixed(2));
      }
      if (response.cc_rate !== undefined) {
        $row.find(".sales-cc-rate-input").val(parseFloat(response.cc_rate || 0).toFixed(2));
      }

      var $batchSelect = $row.find(".sales-batch-select");
      if ($batchSelect.length) {
        $batchSelect.empty().append('<option value="">Select batch</option>');

        if (Array.isArray(response.batches)) {
          response.batches.forEach(function (batch, index) {
            var option = new Option(batch.text + " | Qty: " + (batch.available || 0), batch.id, index === 0, index === 0);
            $batchSelect.append(option);
          });
        }
      }

      var stockText = response.name
        ? response.name + " | Stock: " + (response.stock || 0) + " | MRP: " + parseFloat(response.mrp || 0).toFixed(2) + " | CC: " + parseFloat(response.cc_rate || 0).toFixed(2) + "%"
        : "Select product to auto fill price and stock.";
      $row.find(".sales-stock-note").text(stockText);

      updateSalesRow($row);
      updateSalesTotal();
    });
  }

  function initSalesInvoiceForm() {
    var form = byId("salesForm");
    var tableBody = document.querySelector("#salesItemsTable tbody");
    var template = byId("salesItemTemplate");

    if (!form || !tableBody || !template || !window.jQuery || form.dataset.salesReady === "true") {
      return;
    }

    function syncSalesPaymentModeState() {
      var $paidAmountInput = $(form).find('input[name="paid_amount"]');
      var $paymentModeSelect = $("#salesPaymentMode");
      var $help = $("#salesPaymentModeHelp");
      var paidAmount = parseFloat($paidAmountInput.val()) || 0;
      var requiresPaymentMode = paidAmount > 0;

      $paymentModeSelect.prop("disabled", !requiresPaymentMode);

      if (!requiresPaymentMode) {
        $paymentModeSelect.val("").trigger("change");
        if ($help.length) {
          $help.text("Optional while the invoice is unpaid or fully on credit.");
        }
      } else if ($help.length) {
        $help.text("Required because some amount is being received now.");
      }
    }

    updateSalesRow($("#salesItemsTable tbody tr").first());
    updateSalesTotal();
    syncSalesPaymentModeState();

    $(document).off("click.sales", "#addSalesRow");
    $(document).on("click.sales", "#addSalesRow", function () {
      var nextIndex = parseInt(tableBody.dataset.nextIndex || "1", 10);
      var html = template.innerHTML
        .replace(/__INDEX__/g, nextIndex)
        .replace(/__ROW__/g, nextIndex + 1);

      $(tableBody).append(html);
      tableBody.dataset.nextIndex = String(nextIndex + 1);
      initEnhancedSelects($(tableBody).find("tr:last"));
      updateSalesRowNumbers();
      updateSalesTotal();
    });

    $(document).off("click.sales", ".removeSalesRow");
    $(document).on("click.sales", ".removeSalesRow", function () {
      var row = $(this).closest("tr");

      if ($("#salesItemsTable tbody tr").length === 1) {
        row.find("input").val("");
        row.find(".sales-qty-input").val(1);
        row.find(".sales-free-qty-input").val(0);
        row.find(".sales-mrp-input").val(0);
        row.find(".sales-price-input").val(0);
        row.find(".sales-cc-rate-input").val(0);
        row.find(".sales-discount-input").val(0);
        row.find(".sales-free-value-input").val("0.00");
        row.find(".sales-subtotal-input").val("0.00");
        row.find("select").val("");
        row.find(".sales-batch-select").empty().append('<option value="">Select batch</option>');
      } else {
        row.remove();
      }

      updateSalesRowNumbers();
      updateSalesTotal();
    });

    $(document).off("input.sales change.sales", "#salesItemsTable .sales-qty-input, #salesItemsTable .sales-free-qty-input, #salesItemsTable .sales-mrp-input, #salesItemsTable .sales-price-input, #salesItemsTable .sales-cc-rate-input, #salesItemsTable .sales-discount-input");
    $(document).on("input.sales change.sales", "#salesItemsTable .sales-qty-input, #salesItemsTable .sales-free-qty-input, #salesItemsTable .sales-mrp-input, #salesItemsTable .sales-price-input, #salesItemsTable .sales-cc-rate-input, #salesItemsTable .sales-discount-input", function () {
      updateSalesRow($(this).closest("tr"));
      updateSalesTotal();
    });

    $(document).off("change.sales", ".sales-product-select");
    $(document).on("change.sales", ".sales-product-select", function () {
      refreshSalesProductInfo(this);
    });

    $(document).off("input.salesPayment change.salesPayment", '#salesForm input[name="paid_amount"], #salesTypeSelect');
    $(document).on("input.salesPayment change.salesPayment", '#salesForm input[name="paid_amount"], #salesTypeSelect', function () {
      syncSalesPaymentModeState();
    });

    $(form).off("submit.sales");
    $(form).on("submit.sales", function (event) {
      event.preventDefault();
      showLoader();

      if (typeof $(form).ajaxSubmit !== "function") {
        form.submit();
        return;
      }

      $(form).ajaxSubmit({
        success: function (response) {
          showNotification(response.message, response.type);
          hideLoader();

          if (response.type === "success" && response.redirect) {
            window.setTimeout(function () {
              window.location.href = response.redirect;
            }, 700);
          }
        },
        error: function (xhr) {
          var response = xhr.responseJSON || {};
          showNotification(response.message || "Could not save sales invoice.", "error");
          hideLoader();
        },
      });
    });

    form.dataset.salesReady = "true";
    updateSalesRowNumbers();
  }

  function initAjaxForms() {
    if (!window.jQuery || !$.fn.ajaxSubmit) {
      return;
    }

    $(document).off("submit.ajaxForm", ".js-ajax-form");
    $(document).on("submit.ajaxForm", ".js-ajax-form", function (event) {
      event.preventDefault();

      var form = this;
      var $form = $(form);
      var reloadTableSelector = form.dataset.reloadTable || "";

      showLoader();
      $form.ajaxSubmit({
        success: function (response) {
          showNotification(response.message, response.type);
          hideLoader();

          if (response.type === "success") {
            var modal = form.closest(".modal");
            if (modal && window.bootstrap) {
              var modalInstance = bootstrap.Modal.getInstance(modal);
              if (modalInstance) {
                modalInstance.hide();
              }
            }

            if (reloadTableSelector && window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable(reloadTableSelector)) {
              $(reloadTableSelector).DataTable().draw(false);
            }

            if (response.redirect) {
              window.setTimeout(function () {
                window.location.href = response.redirect;
              }, 700);
            }
          }
        },
        error: function (xhr) {
          var response = xhr.responseJSON || {};
          showNotification(response.message || "Something went wrong.", "error");
          hideLoader();
        },
      });
    });
  }

  function initImportPreview() {
    $(document).off("change.importPreview", ".js-import-preview-input");
    $(document).on("change.importPreview", ".js-import-preview-input", function () {
      var input = this;
      var targetSelector = input.dataset.previewTarget || "";
      var previewBox = targetSelector ? document.querySelector(targetSelector) : null;
      var file = input.files && input.files[0] ? input.files[0] : null;

      if (!previewBox) {
        return;
      }

      previewBox.classList.add("d-none");
      previewBox.innerHTML = "";

      if (!file) {
        return;
      }

      if (!/\.csv$/i.test(file.name)) {
        previewBox.classList.remove("d-none");
        previewBox.innerHTML = '<div class="alert alert-light border mb-0">Preview is available for CSV files. XLSX import will still work when you submit.</div>';
        return;
      }

      var reader = new FileReader();
      reader.onload = function (event) {
        var text = String(event.target.result || "");
        var rows = text.split(/\r?\n/).filter(function (row) {
          return row.trim() !== "";
        }).slice(0, 6);

        if (!rows.length) {
          return;
        }

        var html = '<div class="table-responsive"><table class="table table-bordered mb-0">';
        rows.forEach(function (row, index) {
          var cells = row.split(",");
          html += '<tr>';
          cells.forEach(function (cell) {
            html += '<' + (index === 0 ? 'th' : 'td') + '>' + cell.trim() + '</' + (index === 0 ? 'th' : 'td') + '>';
          });
          html += '</tr>';
        });
        html += '</table></div>';

        previewBox.classList.remove("d-none");
        previewBox.innerHTML = html;
      };

      reader.readAsText(file);
    });
  }

  function ensureElementHasId($element) {
    if (!$element || !$element.length) {
      return "";
    }

    var existingId = $element.attr("id");
    if (existingId) {
      return "#" + existingId;
    }

    var generatedId = "quickTarget" + Date.now() + Math.floor(Math.random() * 1000);
    $element.attr("id", generatedId);
    return "#" + generatedId;
  }

  function findQuickTargetElement(triggerElement, selector) {
    if (!selector || !window.jQuery) {
      return $();
    }

    var $trigger = $(triggerElement);
    var $closestScope = $trigger.closest("tr, .row, .modal-content, .modal-body, .card, form, .admin-page-wrap");

    if ($closestScope.length) {
      var $scopedMatch = $closestScope.find(selector).first();
      if ($scopedMatch.length) {
        return $scopedMatch;
      }
    }

    return $(selector).first();
  }

  function appendQuickOptionToTarget(targetSelector, payload) {
    if (!targetSelector || !payload || payload.id === undefined || !window.jQuery) {
      return;
    }

    var $target = $(targetSelector).first();
    if (!$target.length) {
      return;
    }

    var optionText = payload.text || payload.name || payload.supplier_name || payload.unit_name || payload.product_name || ("Item #" + payload.id);
    var optionValue = String(payload.id);
    var existingOption = $target.find('option[value="' + optionValue + '"]');

    if (existingOption.length) {
      existingOption.text(optionText);
    } else {
      $target.append(new Option(optionText, optionValue, true, true));
    }

    $target.val(optionValue).trigger("change");
  }

  function resetQuickCreateForm(form) {
    if (!form || !window.jQuery) {
      return;
    }

    form.reset();

    $(form).find("select").each(function () {
      var $select = $(this);
      if ($select.hasClass("select2-hidden-accessible")) {
        $select.val("").trigger("change");
      }
    });
  }

  function initQuickCreateModals() {
    if (!window.jQuery || typeof bootstrap === "undefined") {
      return;
    }

    $(document).off("click.quickCreate", ".js-open-quick-create");
    $(document).on("click.quickCreate", ".js-open-quick-create", function (event) {
      event.preventDefault();

      var modalSelector = this.dataset.quickModal || "";
      var modalElement = modalSelector ? document.querySelector(modalSelector) : null;

      if (!modalElement) {
        return;
      }

      var targetSelector = this.dataset.quickTargetSelect || "";
      var $targetElement = findQuickTargetElement(this, targetSelector);
      var resolvedTarget = ensureElementHasId($targetElement);

      modalElement.dataset.quickTargetSelect = resolvedTarget || targetSelector;
      modalElement.dataset.quickDropdownAlias = this.dataset.dropdownAlias || "";
      modalElement.dataset.quickDropdownLabel = this.dataset.dropdownLabel || "";
      modalElement.dataset.quickDropdownSupportsData = this.dataset.dropdownSupportsData || "0";
      modalElement.dataset.quickUnitType = this.dataset.unitType || "";
      syncCsrfInputs(modalElement);

      var parentModal = $(this).closest(".modal.show");
      var parentModalId = parentModal.length ? (parentModal.attr("id") || "") : "";
      modalElement.dataset.parentModalId = parentModalId;

      if (document.activeElement && typeof document.activeElement.blur === "function") {
        document.activeElement.blur();
      }

      if (parentModalId && parentModalId !== modalElement.id) {
        var parentModalElement = byId(parentModalId);
        var childModalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
        var handleParentHidden = function () {
          parentModalElement.removeEventListener("hidden.bs.modal", handleParentHidden);
          childModalInstance.show();
        };

        parentModalElement.addEventListener("hidden.bs.modal", handleParentHidden);
        bootstrap.Modal.getOrCreateInstance(parentModalElement).hide();
        return;
      }

      bootstrap.Modal.getOrCreateInstance(modalElement).show();
    });

    $(document).off("submit.quickCreate", ".js-quick-create-form");
    $(document).on("submit.quickCreate", ".js-quick-create-form", function (event) {
      event.preventDefault();

      var form = this;
      var modalElement = form.closest(".modal");
      var targetSelector = modalElement ? (modalElement.dataset.quickTargetSelect || "") : "";
      syncCsrfInputs(modalElement || document);

      showLoader();

      $.ajax({
        url: form.action,
        type: form.method || "POST",
        data: new FormData(form),
        processData: false,
        contentType: false,
        headers: {
          "X-CSRF-TOKEN": getCsrfToken()
        },
        success: function (response) {
          hideLoader();
          showNotification(response.message || "Saved successfully.", response.type || "success");

          if (response && response.data) {
            appendQuickOptionToTarget(targetSelector, response.data);
          }

          if (modalElement) {
            bootstrap.Modal.getOrCreateInstance(modalElement).hide();
          }

          resetQuickCreateForm(form);
        },
        error: function (xhr) {
          hideLoader();
          showNotification(getAjaxErrorMessage(xhr, "Could not save right now."), "error");
        },
      });
    });

    document.querySelectorAll(".modal").forEach(function (modalElement) {
      if (modalElement.dataset.quickStackReady === "true") {
        return;
      }

      modalElement.addEventListener("shown.bs.modal", function () {
        var unitTypeSelect = modalElement.querySelector('select[name="type"]');
        if (unitTypeSelect && modalElement.dataset.quickUnitType) {
          unitTypeSelect.value = modalElement.dataset.quickUnitType;
        }

        var quickProductCompanySelect = modalElement.querySelector('select[name="company_id"]');
        var quickProductCcInput = modalElement.querySelector('input[name="cc_rate"]');

        if (quickProductCompanySelect && quickProductCcInput && modalElement.dataset.quickProductCcReady !== "true") {
          quickProductCcInput.addEventListener("input", function () {
            quickProductCcInput.dataset.userEdited = "true";
          });

          quickProductCompanySelect.addEventListener("change", function () {
            var selectedOption = quickProductCompanySelect.options[quickProductCompanySelect.selectedIndex];
            var defaultCcRate = selectedOption ? parseFloat(selectedOption.dataset.defaultCcRate || "0") : 0;
            var safeCcRate = Number.isFinite(defaultCcRate) ? defaultCcRate : 0;

            if (quickProductCcInput.dataset.userEdited !== "true" || !quickProductCcInput.value) {
              quickProductCcInput.value = safeCcRate.toFixed(2);
            }
          });

          modalElement.dataset.quickProductCcReady = "true";
        }

        if (quickProductCompanySelect && quickProductCcInput) {
          quickProductCcInput.dataset.userEdited = "";
          quickProductCompanySelect.dispatchEvent(new Event("change"));
        }
      });

      modalElement.addEventListener("hidden.bs.modal", function () {
        var parentModalId = modalElement.dataset.parentModalId || "";

        if (document.activeElement && typeof document.activeElement.blur === "function") {
          document.activeElement.blur();
        }

        modalElement.dataset.parentModalId = "";
        modalElement.dataset.quickUnitType = "";

        var quickProductCcInput = modalElement.querySelector('input[name="cc_rate"]');
        if (quickProductCcInput) {
          quickProductCcInput.dataset.userEdited = "";
        }

        if (parentModalId) {
          var parentModalElement = byId(parentModalId);

          if (parentModalElement) {
            window.setTimeout(function () {
              bootstrap.Modal.getOrCreateInstance(parentModalElement).show();
            }, 120);
          }
        }
      });

      modalElement.dataset.quickStackReady = "true";
    });
  }

  function dropdownOptionUrl(template, id) {
    return String(template || "").replace("__ID__", String(id || ""));
  }

  function renderSettingsDropdownOptionRow(option, index) {
    var statusClass = option.is_active ? "bg-success" : "bg-danger";
    var toggleClass = option.is_active ? "btn-outline-warning" : "btn-outline-success";
    var toggleIcon = option.is_active ? "fa-toggle-on" : "fa-toggle-off";

    return '<tr data-id="' + option.id + '">' +
      '<td>' + index + '</td>' +
      '<td class="dropdown-option-name">' + escapeHtml(option.name) + '</td>' +
      '<td><code>' + escapeHtml(option.alias) + '</code></td>' +
      '<td class="dropdown-option-data">' + escapeHtml(option.data || "-") + '</td>' +
      '<td class="dropdown-option-status"><span class="badge ' + statusClass + '">' + (option.is_active ? "Active" : "Inactive") + '</span></td>' +
      '<td><div class="table-action-group">' +
        '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn js-dropdown-option-edit" title="Edit" data-id="' + option.id + '" data-dropdown-alias="' + escapeHtml(option.alias) + '" data-dropdown-label="' + escapeHtml(option.alias_label || option.alias) + '" data-dropdown-supports-data="' + (option.alias === "payment_mode" || option.alias === "expense_category" ? 1 : 0) + '" data-name="' + escapeHtml(option.name) + '" data-data="' + escapeHtml(option.data || "") + '" data-status="' + (option.is_active ? 1 : 0) + '"><i class="fa-solid fa-pen-to-square"></i></button>' +
        '<button type="button" class="btn btn-sm ' + toggleClass + ' table-action-btn js-dropdown-option-toggle" title="Toggle" data-id="' + option.id + '" data-dropdown-alias="' + escapeHtml(option.alias) + '" data-name="' + escapeHtml(option.name) + '" data-status="' + (option.is_active ? 1 : 0) + '"><i class="fa-solid ' + toggleIcon + '"></i></button>' +
        '<button type="button" class="btn btn-sm btn-outline-danger table-action-btn js-dropdown-option-delete" title="Delete" data-id="' + option.id + '" data-dropdown-alias="' + escapeHtml(option.alias) + '" data-name="' + escapeHtml(option.name) + '"><i class="fa-solid fa-trash"></i></button>' +
      '</div></td>' +
    '</tr>';
  }

  function renderQuickDropdownOptionRow(option, index) {
    var statusClass = option.is_active ? "bg-success" : "bg-danger";
    var toggleClass = option.is_active ? "btn-outline-warning" : "btn-outline-success";
    var toggleIcon = option.is_active ? "fa-toggle-on" : "fa-toggle-off";

    return '<tr data-id="' + option.id + '">' +
      '<td>' + index + '</td>' +
      '<td>' + escapeHtml(option.name) + '</td>' +
      '<td>' + escapeHtml(option.data || "-") + '</td>' +
      '<td><span class="badge ' + statusClass + '">' + (option.is_active ? "Active" : "Inactive") + '</span></td>' +
      '<td><div class="table-action-group">' +
        '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn js-quick-dropdown-option-edit" title="Edit" data-id="' + option.id + '" data-dropdown-alias="' + escapeHtml(option.alias) + '" data-name="' + escapeHtml(option.name) + '" data-data="' + escapeHtml(option.data || "") + '" data-status="' + (option.is_active ? 1 : 0) + '"><i class="fa-solid fa-pen-to-square"></i></button>' +
        '<button type="button" class="btn btn-sm ' + toggleClass + ' table-action-btn js-quick-dropdown-option-toggle" title="Toggle" data-id="' + option.id + '" data-dropdown-alias="' + escapeHtml(option.alias) + '" data-name="' + escapeHtml(option.name) + '" data-status="' + (option.is_active ? 1 : 0) + '"><i class="fa-solid ' + toggleIcon + '"></i></button>' +
        '<button type="button" class="btn btn-sm btn-outline-danger table-action-btn js-quick-dropdown-option-delete" title="Delete" data-id="' + option.id + '" data-dropdown-alias="' + escapeHtml(option.alias) + '" data-name="' + escapeHtml(option.name) + '"><i class="fa-solid fa-trash"></i></button>' +
      '</div></td>' +
    '</tr>';
  }

  function syncDropdownOptionSelects(alias, options, preferredId, preferredTargetSelector) {
    if (!window.jQuery || !alias) {
      return;
    }

    var activeOptions = (options || []).filter(function (option) {
      return !!option.is_active;
    });
    var preferredValue = preferredId != null ? String(preferredId) : "";

    $("select[data-dropdown-alias='" + alias + "']").each(function () {
      var $select = $(this);
      var placeholderOption = $select.find("option:first");
      var placeholderText = placeholderOption.length ? placeholderOption.text() : "Select option";
      var currentValue = String($select.val() || "");
      var keepValue = currentValue;

      if (preferredValue && preferredTargetSelector && $select.is(preferredTargetSelector)) {
        keepValue = preferredValue;
      }

      $select.empty().append(new Option(placeholderText, "", false, false));

      activeOptions.forEach(function (option) {
        var optionValue = String(option.id);
        var isSelected = keepValue === optionValue;
        $select.append(new Option(option.name, optionValue, isSelected, isSelected));
      });

      if (keepValue && !activeOptions.some(function (option) { return String(option.id) === keepValue; })) {
        $select.val("");
      }

      $select.trigger("change");
    });
  }

  function refreshSettingsDropdownOptionTable(alias, rows) {
    var $tableBody = $("table.dropdown-option-table[data-dropdown-alias='" + alias + "'] tbody");

    if (!$tableBody.length) {
      return;
    }

    $tableBody.empty();

    if (!rows.length) {
      $tableBody.append('<tr><td colspan="6" class="text-center text-muted">No options added yet.</td></tr>');
      return;
    }

    rows.forEach(function (option, index) {
      $tableBody.append(renderSettingsDropdownOptionRow(option, index + 1));
    });
  }

  function refreshQuickDropdownOptionTable(rows) {
    var $tableBody = $("#quickDropdownOptionTable tbody");

    if (!$tableBody.length) {
      return;
    }

    $tableBody.empty();

    if (!rows.length) {
      $tableBody.append('<tr><td colspan="5" class="text-center text-muted">No options added yet.</td></tr>');
      return;
    }

    rows.forEach(function (option, index) {
      $tableBody.append(renderQuickDropdownOptionRow(option, index + 1));
    });
  }

  function refreshDropdownOptionAlias(alias, preferredId, preferredTargetSelector) {
    var listUrl = $("#quickDropdownOptionForm").data("listUrl") || $("#dropdownOptionForm").data("listUrl");

    if (!listUrl || !alias) {
      return $.Deferred().resolve().promise();
    }

    return $.get(listUrl, { alias: alias }, function (response) {
      var rows = response.data || [];
      var quickAlias = $("#quick_dropdown_option_alias").val() || "";

      refreshSettingsDropdownOptionTable(alias, rows);

      if (quickAlias === alias) {
        refreshQuickDropdownOptionTable(rows);
      }

      syncDropdownOptionSelects(alias, rows, preferredId, preferredTargetSelector);
    });
  }

  function setDropdownOptionDataVisibility($wrap, supportsData) {
    if (!$wrap || !$wrap.length) {
      return;
    }

    $wrap.toggleClass("d-none", !supportsData);
    $wrap.find("input").prop("disabled", !supportsData);
  }

  function initQuickDropdownOptionCrud() {
    if (!window.jQuery || typeof bootstrap === "undefined") {
      return;
    }

    var modalElement = byId("quickDropdownOptionModal");
    var form = byId("quickDropdownOptionForm");

    if (!modalElement || !form || modalElement.dataset.quickCrudReady === "true") {
      return;
    }

    var modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    var $form = $(form);
    var $title = $("#quickDropdownOptionModalTitle");
    var $helpText = $("#quickDropdownOptionHelpText");
    var $nameField = $("#quick_dropdown_option_name");
    var $dataField = $("#quick_dropdown_option_data");
    var $statusField = $("#quick_dropdown_option_status");
    var $aliasField = $("#quick_dropdown_option_alias");
    var $idField = $("#quick_dropdown_option_id");
    var $dataWrap = $("#quickDropdownOptionDataWrap");
    var $submitButton = $("#quickDropdownOptionSubmitBtn");

    function resetQuickDropdownOptionForm() {
      var alias = modalElement.dataset.quickDropdownAlias || "";
      var label = modalElement.dataset.quickDropdownLabel || "Option";
      var supportsData = modalElement.dataset.quickDropdownSupportsData === "1";

      $idField.val("");
      $aliasField.val(alias);
      $nameField.val("");
      $dataField.val("");
      $statusField.prop("checked", true);
      $title.text("Manage " + label);
      $helpText.text("Use this quick modal when a " + label.toLowerCase() + " is missing during entry.");
      $submitButton.html('<i class="fa-solid fa-save"></i> Save Option');
      setDropdownOptionDataVisibility($dataWrap, supportsData);
    }

    modalElement.addEventListener("show.bs.modal", function () {
      resetQuickDropdownOptionForm();
      refreshDropdownOptionAlias($aliasField.val(), "", modalElement.dataset.quickTargetSelect || "");
    });

    $(document).off("click.quickDropdownOptionReset", "#quickDropdownOptionResetBtn");
    $(document).on("click.quickDropdownOptionReset", "#quickDropdownOptionResetBtn", function () {
      resetQuickDropdownOptionForm();
    });

    $(document).off("click.quickDropdownOptionEdit", ".js-quick-dropdown-option-edit");
    $(document).on("click.quickDropdownOptionEdit", ".js-quick-dropdown-option-edit", function () {
      $idField.val($(this).data("id"));
      $aliasField.val($(this).data("dropdown-alias"));
      $nameField.val($(this).data("name"));
      $dataField.val($(this).data("data"));
      $statusField.prop("checked", $(this).data("status") == 1);
      $title.text("Edit " + (modalElement.dataset.quickDropdownLabel || "Option"));
      $submitButton.html('<i class="fa-solid fa-save"></i> Update Option');
    });

    $(document).off("click.quickDropdownOptionToggle", ".js-quick-dropdown-option-toggle");
    $(document).on("click.quickDropdownOptionToggle", ".js-quick-dropdown-option-toggle", function () {
      var optionId = $(this).data("id");
      var alias = $(this).data("dropdown-alias");
      var nextState = $(this).data("status") == 1 ? 0 : 1;

      showLoader();

      $.ajax({
        url: dropdownOptionUrl($form.data("updateUrlTemplate"), optionId),
        type: "POST",
        data: {
          _token: $form.find('input[name="_token"]').val(),
          _method: "PUT",
          alias: alias,
          status: nextState,
          name: $(this).data("name")
        },
        success: function (response) {
          hideLoader();
          showNotification(response.message || "Option updated.", response.type || "success");
          refreshDropdownOptionAlias(alias, "", modalElement.dataset.quickTargetSelect || "");
        },
        error: function (xhr) {
          hideLoader();
          showNotification(getAjaxErrorMessage(xhr, "Could not update option."), "error");
        }
      });
    });

    $(document).off("click.quickDropdownOptionDelete", ".js-quick-dropdown-option-delete");
    $(document).on("click.quickDropdownOptionDelete", ".js-quick-dropdown-option-delete", function () {
      var optionId = $(this).data("id");
      var alias = $(this).data("dropdown-alias");

      function runDelete() {
        showLoader();

        $.ajax({
          url: dropdownOptionUrl($form.data("deleteUrlTemplate"), optionId),
          type: "POST",
          data: {
            _token: $form.find('input[name="_token"]').val(),
            _method: "DELETE"
          },
          success: function (response) {
            hideLoader();
            showNotification(response.message || "Option deleted.", response.type || "success");
            resetQuickDropdownOptionForm();
            refreshDropdownOptionAlias(alias, "", modalElement.dataset.quickTargetSelect || "");
          },
          error: function (xhr) {
            hideLoader();
            showNotification(getAjaxErrorMessage(xhr, "Could not delete option."), "error");
          }
        });
      }

      if (typeof Swal !== "undefined") {
        Swal.fire({
          title: "Delete option?",
          text: "This will remove the option from the shared list.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#DB1F48",
          cancelButtonColor: "#6b7280",
          confirmButtonText: "Delete",
        }).then(function (result) {
          if (result.isConfirmed) {
            runDelete();
          }
        });
        return;
      }

      if (window.confirm("Delete this option?")) {
        runDelete();
      }
    });

    $form.off("submit.quickDropdownOptionCrud");
    $form.on("submit.quickDropdownOptionCrud", function (event) {
      event.preventDefault();

      var optionId = $idField.val();
      var alias = $aliasField.val();
      var submitUrl = optionId
        ? dropdownOptionUrl($form.data("updateUrlTemplate"), optionId)
        : $form.data("storeUrl");

      showLoader();

      $.ajax({
        url: submitUrl,
        type: "POST",
        data: {
          _token: $form.find('input[name="_token"]').val(),
          _method: optionId ? "PUT" : "POST",
          alias: alias,
          name: $nameField.val(),
          data: $dataWrap.hasClass("d-none") ? "" : $dataField.val(),
          status: $statusField.is(":checked") ? 1 : 0
        },
        success: function (response) {
          hideLoader();
          showNotification(response.message || "Option saved.", response.type || "success");
          resetQuickDropdownOptionForm();
          refreshDropdownOptionAlias(alias, response && response.data ? response.data.id : "", modalElement.dataset.quickTargetSelect || "");
          modalInstance.hide();
        },
        error: function (xhr) {
          hideLoader();
          showNotification(getAjaxErrorMessage(xhr, "Could not save option."), "error");
        }
      });
    });

    modalElement.dataset.quickCrudReady = "true";
  }

  function initDropdownOptionManager() {
    if (!window.jQuery || typeof bootstrap === "undefined") {
      return;
    }

    var modalElement = byId("dropdownOptionModal");
    var form = byId("dropdownOptionForm");

    if (!modalElement || !form || modalElement.dataset.managerReady === "true") {
      return;
    }

    var modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    var $form = $(form);
    var $idField = $("#dropdown_option_id");
    var $aliasField = $("#dropdown_option_alias");
    var $nameField = $("#dropdown_option_name");
    var $dataField = $("#dropdown_option_data");
    var $dataWrap = $("#dropdownOptionDataWrap");
    var $statusField = $("#dropdown_option_status");
    var $submitButton = $("#dropdownOptionSubmitBtn");
    var $title = $("#dropdownOptionModalLabel");

    function resetManagerForm(alias, label, supportsData) {
      $idField.val("");
      $aliasField.val(alias || "");
      $nameField.val("");
      $dataField.val("");
      $statusField.prop("checked", true);
      $title.text((label || "Dropdown Option") + " Master");
      $submitButton.html('<i class="fa-solid fa-save me-1"></i> Save');
      setDropdownOptionDataVisibility($dataWrap, !!supportsData);
    }

    $(document).off("click.dropdownOptionAdd", ".js-dropdown-option-add");
    $(document).on("click.dropdownOptionAdd", ".js-dropdown-option-add", function () {
      resetManagerForm(
        $(this).data("dropdown-alias"),
        $(this).data("dropdown-label"),
        $(this).data("dropdown-supports-data") == 1
      );
      modalInstance.show();
    });

    $(document).off("click.dropdownOptionEdit", ".js-dropdown-option-edit");
    $(document).on("click.dropdownOptionEdit", ".js-dropdown-option-edit", function () {
      resetManagerForm(
        $(this).data("dropdown-alias"),
        $(this).data("dropdown-label"),
        $(this).data("dropdown-supports-data") == 1
      );
      $idField.val($(this).data("id"));
      $nameField.val($(this).data("name"));
      $dataField.val($(this).data("data"));
      $statusField.prop("checked", $(this).data("status") == 1);
      $submitButton.html('<i class="fa-solid fa-save me-1"></i> Update');
      modalInstance.show();
    });

    $(document).off("click.dropdownOptionToggle", ".js-dropdown-option-toggle");
    $(document).on("click.dropdownOptionToggle", ".js-dropdown-option-toggle", function () {
      var optionId = $(this).data("id");
      var alias = $(this).data("dropdown-alias");
      var nextState = $(this).data("status") == 1 ? 0 : 1;

      showLoader();

      $.ajax({
        url: dropdownOptionUrl($form.data("updateUrlTemplate"), optionId),
        type: "POST",
        data: {
          _token: $form.find('input[name="_token"]').val(),
          _method: "PUT",
          alias: alias,
          status: nextState,
          name: $(this).data("name")
        },
        success: function (response) {
          hideLoader();
          showNotification(response.message || "Option updated.", response.type || "success");
          refreshDropdownOptionAlias(alias);
        },
        error: function (xhr) {
          hideLoader();
          showNotification(getAjaxErrorMessage(xhr, "Could not update option."), "error");
        }
      });
    });

    $(document).off("click.dropdownOptionDelete", ".js-dropdown-option-delete");
    $(document).on("click.dropdownOptionDelete", ".js-dropdown-option-delete", function () {
      var optionId = $(this).data("id");
      var alias = $(this).data("dropdown-alias");

      function runDelete() {
        showLoader();

        $.ajax({
          url: dropdownOptionUrl($form.data("deleteUrlTemplate"), optionId),
          type: "POST",
          data: {
            _token: $form.find('input[name="_token"]').val(),
            _method: "DELETE"
          },
          success: function (response) {
            hideLoader();
            showNotification(response.message || "Option deleted.", response.type || "success");
            refreshDropdownOptionAlias(alias);
          },
          error: function (xhr) {
            hideLoader();
            showNotification(getAjaxErrorMessage(xhr, "Could not delete option."), "error");
          }
        });
      }

      if (typeof Swal !== "undefined") {
        Swal.fire({
          title: "Delete option?",
          text: "This will remove the option from the shared list.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#DB1F48",
          cancelButtonColor: "#6b7280",
          confirmButtonText: "Delete",
        }).then(function (result) {
          if (result.isConfirmed) {
            runDelete();
          }
        });
        return;
      }

      if (window.confirm("Delete this option?")) {
        runDelete();
      }
    });

    $form.off("submit.dropdownOptionManager");
    $form.on("submit.dropdownOptionManager", function (event) {
      event.preventDefault();

      var optionId = $idField.val();
      var alias = $aliasField.val();
      var submitUrl = optionId
        ? dropdownOptionUrl($form.data("updateUrlTemplate"), optionId)
        : $form.data("storeUrl");

      showLoader();

      $.ajax({
        url: submitUrl,
        type: "POST",
        data: {
          _token: $form.find('input[name="_token"]').val(),
          _method: optionId ? "PUT" : "POST",
          alias: alias,
          name: $nameField.val(),
          data: $dataWrap.hasClass("d-none") ? "" : $dataField.val(),
          status: $statusField.is(":checked") ? 1 : 0
        },
        success: function (response) {
          hideLoader();
          showNotification(response.message || "Option saved.", response.type || "success");
          refreshDropdownOptionAlias(alias, response && response.data ? response.data.id : "");
          modalInstance.hide();
        },
        error: function (xhr) {
          hideLoader();
          showNotification(getAjaxErrorMessage(xhr, "Could not save option."), "error");
        }
      });
    });

    modalElement.dataset.managerReady = "true";
  }

  function renderQuickPaymentModeRow(mode, index) {
    var canDelete = ["cash", "bank"].indexOf(String(mode.name).toLowerCase()) === -1;
    var deleteButton = canDelete
      ? '<button type="button" class="btn btn-sm btn-outline-danger table-action-btn quickPaymentModeDeleteBtn" data-id="' + mode.id + '" title="Delete"><i class="fa-solid fa-trash"></i></button>'
      : "";
    var modeType = String(mode.mode_type || mode.type || "cash");

    return '<tr data-id="' + mode.id + '">' +
      '<td>' + index + '</td>' +
      '<td>' + mode.name + '</td>' +
      '<td>' + modeType.charAt(0).toUpperCase() + modeType.slice(1) + '</td>' +
      '<td><span class="report-badge ' + (mode.is_active ? "report-badge-success" : "report-badge-danger") + '">' + (mode.is_active ? "Active" : "Inactive") + '</span></td>' +
      '<td><div class="table-action-group">' +
        '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn quickPaymentModeEditBtn" data-id="' + mode.id + '" data-name="' + mode.name + '" data-mode-type="' + modeType + '" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>' +
        '<button type="button" class="btn btn-sm ' + (mode.is_active ? "btn-outline-warning" : "btn-outline-success") + ' table-action-btn quickPaymentModeToggleBtn" data-id="' + mode.id + '" data-active="' + (mode.is_active ? 1 : 0) + '" title="Toggle"><i class="fa-solid ' + (mode.is_active ? "fa-toggle-on" : "fa-toggle-off") + '"></i></button>' +
        deleteButton +
      '</div></td>' +
    '</tr>';
  }

  function syncPaymentModeSelects(paymentModes, preferredModeId, preferredTargetSelector) {
    if (!window.jQuery) {
      return;
    }

    var activeModes = (paymentModes || []).filter(function (mode) {
      return !!mode.is_active;
    });
    var distinctTypes = [];
    activeModes.forEach(function (mode) {
      var modeType = String(mode.mode_type || mode.type || "").toLowerCase();
      if (modeType && !distinctTypes.some(function (row) { return row.type === modeType; })) {
        distinctTypes.push({
          type: modeType,
          label: String(mode.name || modeType).trim(),
        });
      }
    });
    var preferredValue = preferredModeId != null ? String(preferredModeId) : "";

    $("select[name='payment_mode_id']").each(function () {
      var $select = $(this);
      var placeholderOption = $select.find("option:first");
      var placeholderText = placeholderOption.length ? placeholderOption.text() : "Select mode";
      var currentValue = String($select.val() || "");
      var keepValue = currentValue;

      if (preferredValue && preferredTargetSelector && $select.is(preferredTargetSelector)) {
        keepValue = preferredValue;
      }

      $select.empty().append(new Option(placeholderText, "", false, false));

      activeModes.forEach(function (mode) {
        var optionValue = String(mode.id);
        var isSelected = keepValue === optionValue;
        $select.append(new Option(mode.name, optionValue, isSelected, isSelected));
      });

      if (keepValue && !activeModes.some(function (mode) { return String(mode.id) === keepValue; })) {
        $select.val("");
      }

      $select.trigger("change");
    });

    $("select.js-payment-mode-select").each(function () {
      var $select = $(this);
      var placeholderOption = $select.find("option:first");
      var placeholderText = placeholderOption.length ? placeholderOption.text() : "Select mode";
      var currentValue = String($select.val() || "");
      var keepValue = currentValue;

      if (preferredValue && preferredTargetSelector && $select.is(preferredTargetSelector)) {
        keepValue = preferredValue;
      }

      $select.empty().append(new Option(placeholderText, "", false, false));

      distinctTypes.forEach(function (row) {
        var isSelected = keepValue === row.type;
        $select.append(new Option(row.label, row.type, isSelected, isSelected));
      });

      if (keepValue && !distinctTypes.some(function (row) { return row.type === keepValue; })) {
        $select.val("");
      }

      $select.trigger("change");
    });
  }

  function initQuickPaymentModeCrud() {
    if (!window.jQuery || typeof bootstrap === "undefined") {
      return;
    }

    var modalElement = byId("quickPaymentModeModal");
    var form = byId("quickPaymentModeForm");

    if (!modalElement || !form || modalElement.dataset.quickCrudReady === "true") {
      return;
    }

    var modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    var $form = $(form);
    var $tableBody = $("#quickPaymentModeTable tbody");
    var $title = $("#quickPaymentModeModalTitle");
    var $submitButton = $("#quickPaymentModeSubmitBtn");
    var $idField = $("#quick_payment_mode_id");
    var $nameField = $("#quick_payment_mode_name");
    var $typeField = $("#quick_payment_mode_mode_type");

    function quickPaymentModeUrl(template, id) {
      return String(template || "").replace("__ID__", String(id || ""));
    }

    // This small reset keeps the modal simple: empty form means create, filled form means edit.
    function resetQuickPaymentModeForm() {
      $idField.val("");
      $nameField.val("");
      $typeField.val("cash");
      $title.text("Manage Payment Modes");
      $submitButton.html('<i class="fa-solid fa-save"></i> Save Mode');
    }

    function refreshQuickPaymentModeTable(preferredModeId) {
      return $.get($form.data("listUrl"), function (response) {
        var rows = response.data || [];
        $tableBody.empty();

        if (!rows.length) {
          $tableBody.append('<tr><td colspan="5" class="text-center text-muted">No payment modes added yet.</td></tr>');
        } else {
          rows.forEach(function (mode, index) {
            $tableBody.append(renderQuickPaymentModeRow(mode, index + 1));
          });
        }

        syncPaymentModeSelects(rows, preferredModeId, modalElement.dataset.quickTargetSelect || "");
      });
    }

    modalElement.addEventListener("show.bs.modal", function () {
      resetQuickPaymentModeForm();
      refreshQuickPaymentModeTable();
    });

    $(document).off("click.quickPaymentModeReset", "#quickPaymentModeResetBtn");
    $(document).on("click.quickPaymentModeReset", "#quickPaymentModeResetBtn", function () {
      resetQuickPaymentModeForm();
    });

    $(document).off("click.quickPaymentModeEdit", ".quickPaymentModeEditBtn");
    $(document).on("click.quickPaymentModeEdit", ".quickPaymentModeEditBtn", function () {
      $idField.val($(this).data("id"));
      $nameField.val($(this).data("name"));
      $typeField.val($(this).data("mode-type"));
      $title.text("Edit Payment Mode");
      $submitButton.html('<i class="fa-solid fa-save"></i> Update Mode');
    });

    $(document).off("click.quickPaymentModeToggle", ".quickPaymentModeToggleBtn");
    $(document).on("click.quickPaymentModeToggle", ".quickPaymentModeToggleBtn", function () {
      var modeId = $(this).data("id");
      var nextState = $(this).data("active") == 1 ? 0 : 1;

      showLoader();

      $.post(quickPaymentModeUrl($form.data("updateUrlTemplate"), modeId), {
        _token: $form.find('input[name="_token"]').val(),
        is_active: nextState
      }, function (response) {
        hideLoader();
        showNotification(response.message || "Payment mode updated.", response.type || "success");
        refreshQuickPaymentModeTable();
      }).fail(function (xhr) {
        hideLoader();
        var response = xhr.responseJSON || {};
        showNotification(response.message || "Could not update payment mode.", "error");
      });
    });

    $(document).off("click.quickPaymentModeDelete", ".quickPaymentModeDeleteBtn");
    $(document).on("click.quickPaymentModeDelete", ".quickPaymentModeDeleteBtn", function () {
      var modeId = $(this).data("id");

      function runDelete() {
        showLoader();

        $.post(quickPaymentModeUrl($form.data("deleteUrlTemplate"), modeId), {
          _token: $form.find('input[name="_token"]').val()
        }, function (response) {
          hideLoader();
          showNotification(response.message || "Payment mode deleted.", response.type || "success");
          resetQuickPaymentModeForm();
          refreshQuickPaymentModeTable();
        }).fail(function (xhr) {
          hideLoader();
          var response = xhr.responseJSON || {};
          showNotification(response.message || "Could not delete payment mode.", "error");
        });
      }

      if (typeof Swal !== "undefined") {
        Swal.fire({
          title: "Delete payment mode?",
          text: "This will remove the custom mode from the list.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#DB1F48",
          cancelButtonColor: "#6b7280",
          confirmButtonText: "Delete",
        }).then(function (result) {
          if (result.isConfirmed) {
            runDelete();
          }
        });
        return;
      }

      if (window.confirm("Delete this payment mode?")) {
        runDelete();
      }
    });

    $form.off("submit.quickPaymentModeCrud");
    $form.on("submit.quickPaymentModeCrud", function (event) {
      event.preventDefault();

      var modeId = $idField.val();
      var submitUrl = modeId
        ? quickPaymentModeUrl($form.data("updateUrlTemplate"), modeId)
        : $form.data("storeUrl");

      showLoader();

      $.post(submitUrl, {
        _token: $form.find('input[name="_token"]').val(),
        name: $nameField.val(),
        mode_type: $typeField.val()
      }, function (response) {
        hideLoader();
        showNotification(response.message || "Payment mode saved.", response.type || "success");
        resetQuickPaymentModeForm();
        refreshQuickPaymentModeTable(response && response.data ? response.data.id : "");
        modalInstance.hide();
      }).fail(function (xhr) {
        hideLoader();
        var response = xhr.responseJSON || {};
        showNotification(response.message || "Could not save payment mode.", "error");
      });
    });

    modalElement.dataset.quickCrudReady = "true";
  }

  function renderQuickPartyTypeRow(partyType, index) {
    return '<tr data-id="' + partyType.id + '">' +
      '<td>' + index + '</td>' +
      '<td>' + partyType.name + '</td>' +
      '<td><code>' + partyType.code + '</code></td>' +
      '<td><span class="badge ' + (partyType.is_active ? "bg-success" : "bg-danger") + '">' + (partyType.is_active ? "Active" : "Inactive") + '</span></td>' +
      '<td><div class="table-action-group">' +
        '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn quickPartyTypeEditBtn" data-id="' + partyType.id + '" data-name="' + partyType.name + '" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>' +
        '<button type="button" class="btn btn-sm ' + (partyType.is_active ? "btn-outline-warning" : "btn-outline-success") + ' table-action-btn quickPartyTypeToggleBtn" data-id="' + partyType.id + '" data-active="' + (partyType.is_active ? 1 : 0) + '" title="Toggle"><i class="fa-solid ' + (partyType.is_active ? "fa-toggle-on" : "fa-toggle-off") + '"></i></button>' +
        '<button type="button" class="btn btn-sm btn-outline-danger table-action-btn quickPartyTypeDeleteBtn" data-id="' + partyType.id + '" title="Delete"><i class="fa-solid fa-trash"></i></button>' +
      '</div></td>' +
    '</tr>';
  }

  function syncPartyTypeSelects(partyTypes, preferredPartyTypeId, preferredTargetSelector) {
    if (!window.jQuery) {
      return;
    }

    var availableTypes = partyTypes || [];
    var preferredValue = preferredPartyTypeId != null ? String(preferredPartyTypeId) : "";

    $("select.js-party-type-select").each(function () {
      var $select = $(this);
      var placeholderOption = $select.find("option:first");
      var placeholderText = placeholderOption.length ? placeholderOption.text() : "Select party type";
      var currentValue = String($select.val() || "");
      var keepValue = currentValue;

      if (preferredValue && preferredTargetSelector && $select.is(preferredTargetSelector)) {
        keepValue = preferredValue;
      }

      $select.empty().append(new Option(placeholderText, "", false, false));

      availableTypes.forEach(function (partyType) {
        var optionValue = String(partyType.code || "");
        var optionLabel = String(partyType.name || "") + (partyType.is_active ? "" : " (Inactive)");
        var isSelected = keepValue === optionValue;
        $select.append(new Option(optionLabel, optionValue, isSelected, isSelected));
      });

      if (keepValue && !availableTypes.some(function (partyType) { return String(partyType.code) === keepValue; })) {
        $select.val("");
      }

      $select.trigger("change");
    });
  }

  function initQuickPartyTypeCrud() {
    if (!window.jQuery || typeof bootstrap === "undefined") {
      return;
    }

    var modalElement = byId("quickPartyTypeModal");
    var form = byId("quickPartyTypeForm");

    if (!modalElement || !form || modalElement.dataset.quickCrudReady === "true") {
      return;
    }

    var modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    var $form = $(form);
    var $tableBody = $("#quickPartyTypeTable tbody");
    var $title = $("#quickPartyTypeModalTitle");
    var $submitButton = $("#quickPartyTypeSubmitBtn");
    var $idField = $("#quick_party_type_id");
    var $nameField = $("#quick_party_type_name");

    function quickPartyTypeUrl(template, id) {
      return String(template || "").replace("__ID__", String(id || ""));
    }

    function resetQuickPartyTypeForm() {
      $idField.val("");
      $nameField.val("");
      $title.text("Manage Party Types");
      $submitButton.html('<i class="fa-solid fa-save"></i> Save Party Type');
    }

    function refreshQuickPartyTypeTable(preferredPartyTypeId) {
      return $.get($form.data("listUrl"), function (response) {
        var rows = response.data || [];
        $tableBody.empty();
        var $pageTableBody = $("#partyTypeTable tbody");
        if ($pageTableBody.length) {
          $pageTableBody.empty();
        }

        if (!rows.length) {
          $tableBody.append('<tr><td colspan="5" class="text-center text-muted">No party types added yet.</td></tr>');
          if ($pageTableBody.length) {
            $pageTableBody.append('<tr><td colspan="5" class="text-center text-muted">No party types added yet.</td></tr>');
          }
        } else {
          rows.forEach(function (partyType, index) {
            var rowHtml = renderQuickPartyTypeRow(partyType, index + 1);
            $tableBody.append(rowHtml);
            if ($pageTableBody.length) {
              $pageTableBody.append(rowHtml);
            }
          });
        }

        syncPartyTypeSelects(rows, preferredPartyTypeId, modalElement.dataset.quickTargetSelect || "");
      });
    }

    modalElement.addEventListener("show.bs.modal", function () {
      resetQuickPartyTypeForm();
      refreshQuickPartyTypeTable();
    });

    $(document).off("click.quickPartyTypeReset", "#quickPartyTypeResetBtn");
    $(document).on("click.quickPartyTypeReset", "#quickPartyTypeResetBtn", function () {
      resetQuickPartyTypeForm();
    });

    $(document).off("click.quickPartyTypeEdit", ".quickPartyTypeEditBtn");
    $(document).on("click.quickPartyTypeEdit", ".quickPartyTypeEditBtn", function () {
      $idField.val($(this).data("id"));
      $nameField.val($(this).data("name"));
      $title.text("Edit Party Type");
      $submitButton.html('<i class="fa-solid fa-save"></i> Update Party Type');
    });

    $(document).off("click.quickPartyTypeToggle", ".quickPartyTypeToggleBtn");
    $(document).on("click.quickPartyTypeToggle", ".quickPartyTypeToggleBtn", function () {
      var partyTypeId = $(this).data("id");
      var nextState = $(this).data("active") == 1 ? 0 : 1;

      showLoader();

      $.post(quickPartyTypeUrl($form.data("updateUrlTemplate"), partyTypeId), {
        _token: $form.find('input[name="_token"]').val(),
        is_active: nextState
      }, function (response) {
        hideLoader();
        showNotification(response.message || "Party type updated.", response.type || "success");
        refreshQuickPartyTypeTable();
      }).fail(function (xhr) {
        hideLoader();
        showNotification(getAjaxErrorMessage(xhr, "Could not update party type."), "error");
      });
    });

    $(document).off("click.quickPartyTypeDelete", ".quickPartyTypeDeleteBtn");
    $(document).on("click.quickPartyTypeDelete", ".quickPartyTypeDeleteBtn", function () {
      var partyTypeId = $(this).data("id");

      function runDelete() {
        showLoader();

        $.post(quickPartyTypeUrl($form.data("deleteUrlTemplate"), partyTypeId), {
          _token: $form.find('input[name="_token"]').val()
        }, function (response) {
          hideLoader();
          showNotification(response.message || "Party type deleted.", response.type || "success");
          resetQuickPartyTypeForm();
          refreshQuickPartyTypeTable();
        }).fail(function (xhr) {
          hideLoader();
          showNotification(getAjaxErrorMessage(xhr, "Could not delete party type."), "error");
        });
      }

      if (typeof Swal !== "undefined") {
        Swal.fire({
          title: "Delete party type?",
          text: "This will remove the label from the reusable party list.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#DB1F48",
          cancelButtonColor: "#6b7280",
          confirmButtonText: "Delete",
        }).then(function (result) {
          if (result.isConfirmed) {
            runDelete();
          }
        });
        return;
      }

      if (window.confirm("Delete this party type?")) {
        runDelete();
      }
    });

    $form.off("submit.quickPartyTypeCrud");
    $form.on("submit.quickPartyTypeCrud", function (event) {
      event.preventDefault();

      var partyTypeId = $idField.val();
      var submitUrl = partyTypeId
        ? quickPartyTypeUrl($form.data("updateUrlTemplate"), partyTypeId)
        : $form.data("storeUrl");

      showLoader();

      $.post(submitUrl, {
        _token: $form.find('input[name="_token"]').val(),
        name: $nameField.val()
      }, function (response) {
        hideLoader();
        showNotification(response.message || "Party type saved.", response.type || "success");
        resetQuickPartyTypeForm();
        refreshQuickPartyTypeTable(response && response.data ? response.data.id : "");
        modalInstance.hide();
      }).fail(function (xhr) {
        hideLoader();
        showNotification(getAjaxErrorMessage(xhr, "Could not save party type."), "error");
      });
    });

    modalElement.dataset.quickCrudReady = "true";
  }

  function renderQuickSupplierTypeRow(supplierType, index) {
    return '<tr data-id="' + supplierType.id + '">' +
      '<td>' + index + '</td>' +
      '<td>' + supplierType.name + '</td>' +
      '<td><code>' + supplierType.code + '</code></td>' +
      '<td><span class="badge ' + (supplierType.is_active ? "bg-success" : "bg-danger") + '">' + (supplierType.is_active ? "Active" : "Inactive") + '</span></td>' +
      '<td><div class="table-action-group">' +
        '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn quickSupplierTypeEditBtn" data-id="' + supplierType.id + '" data-name="' + supplierType.name + '" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>' +
        '<button type="button" class="btn btn-sm ' + (supplierType.is_active ? "btn-outline-warning" : "btn-outline-success") + ' table-action-btn quickSupplierTypeToggleBtn" data-id="' + supplierType.id + '" data-active="' + (supplierType.is_active ? 1 : 0) + '" title="Toggle"><i class="fa-solid ' + (supplierType.is_active ? "fa-toggle-on" : "fa-toggle-off") + '"></i></button>' +
        '<button type="button" class="btn btn-sm btn-outline-danger table-action-btn quickSupplierTypeDeleteBtn" data-id="' + supplierType.id + '" title="Delete"><i class="fa-solid fa-trash"></i></button>' +
      '</div></td>' +
    '</tr>';
  }

  function syncSupplierTypeSelects(supplierTypes, preferredSupplierTypeId, preferredTargetSelector) {
    if (!window.jQuery) {
      return;
    }

    var availableTypes = supplierTypes || [];
    var preferredValue = preferredSupplierTypeId != null ? String(preferredSupplierTypeId) : "";

    $("select.js-supplier-type-select").each(function () {
      var $select = $(this);
      var placeholderOption = $select.find("option:first");
      var placeholderText = placeholderOption.length ? placeholderOption.text() : "Select supplier type";
      var currentValue = String($select.val() || "");
      var keepValue = currentValue;

      if (preferredValue && preferredTargetSelector && $select.is(preferredTargetSelector)) {
        keepValue = preferredValue;
      }

      $select.empty().append(new Option(placeholderText, "", false, false));

      availableTypes.forEach(function (supplierType) {
        var optionValue = String(supplierType.code || "");
        var optionLabel = String(supplierType.name || "") + (supplierType.is_active ? "" : " (Inactive)");
        var isSelected = keepValue === optionValue;
        $select.append(new Option(optionLabel, optionValue, isSelected, isSelected));
      });

      if (keepValue && !availableTypes.some(function (supplierType) { return String(supplierType.code) === keepValue; })) {
        $select.val("");
      }

      $select.trigger("change");
    });
  }

  function initQuickSupplierTypeCrud() {
    if (!window.jQuery || typeof bootstrap === "undefined") {
      return;
    }

    var modalElement = byId("quickSupplierTypeModal");
    var form = byId("quickSupplierTypeForm");

    if (!modalElement || !form || modalElement.dataset.quickCrudReady === "true") {
      return;
    }

    var modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    var $form = $(form);
    var $tableBody = $("#quickSupplierTypeTable tbody");
    var $title = $("#quickSupplierTypeModalTitle");
    var $submitButton = $("#quickSupplierTypeSubmitBtn");
    var $idField = $("#quick_supplier_type_id");
    var $nameField = $("#quick_supplier_type_name");

    function quickSupplierTypeUrl(template, id) {
      return String(template || "").replace("__ID__", String(id || ""));
    }

    function resetQuickSupplierTypeForm() {
      $idField.val("");
      $nameField.val("");
      $title.text("Manage Supplier Types");
      $submitButton.html('<i class="fa-solid fa-save"></i> Save Supplier Type');
    }

    function refreshQuickSupplierTypeTable(preferredSupplierTypeId) {
      return $.get($form.data("listUrl"), function (response) {
        var rows = response.data || [];
        $tableBody.empty();
        var $pageTableBody = $("#supplierTypeTable tbody");
        if ($pageTableBody.length) {
          $pageTableBody.empty();
        }

        if (!rows.length) {
          $tableBody.append('<tr><td colspan="5" class="text-center text-muted">No supplier types added yet.</td></tr>');
          if ($pageTableBody.length) {
            $pageTableBody.append('<tr><td colspan="5" class="text-center text-muted">No supplier types added yet.</td></tr>');
          }
        } else {
          rows.forEach(function (supplierType, index) {
            var rowHtml = renderQuickSupplierTypeRow(supplierType, index + 1);
            $tableBody.append(rowHtml);
            if ($pageTableBody.length) {
              $pageTableBody.append(rowHtml);
            }
          });
        }

        syncSupplierTypeSelects(rows, preferredSupplierTypeId, modalElement.dataset.quickTargetSelect || "");
      });
    }

    modalElement.addEventListener("show.bs.modal", function () {
      resetQuickSupplierTypeForm();
      refreshQuickSupplierTypeTable();
    });

    $(document).off("click.quickSupplierTypeReset", "#quickSupplierTypeResetBtn");
    $(document).on("click.quickSupplierTypeReset", "#quickSupplierTypeResetBtn", function () {
      resetQuickSupplierTypeForm();
    });

    $(document).off("click.quickSupplierTypeEdit", ".quickSupplierTypeEditBtn");
    $(document).on("click.quickSupplierTypeEdit", ".quickSupplierTypeEditBtn", function () {
      $idField.val($(this).data("id"));
      $nameField.val($(this).data("name"));
      $title.text("Edit Supplier Type");
      $submitButton.html('<i class="fa-solid fa-save"></i> Update Supplier Type');
    });

    $(document).off("click.quickSupplierTypeToggle", ".quickSupplierTypeToggleBtn");
    $(document).on("click.quickSupplierTypeToggle", ".quickSupplierTypeToggleBtn", function () {
      var supplierTypeId = $(this).data("id");
      var nextState = $(this).data("active") == 1 ? 0 : 1;

      showLoader();

      $.post(quickSupplierTypeUrl($form.data("updateUrlTemplate"), supplierTypeId), {
        _token: $form.find('input[name="_token"]').val(),
        is_active: nextState
      }, function (response) {
        hideLoader();
        showNotification(response.message || "Supplier type updated.", response.type || "success");
        refreshQuickSupplierTypeTable();
      }).fail(function (xhr) {
        hideLoader();
        showNotification(getAjaxErrorMessage(xhr, "Could not update supplier type."), "error");
      });
    });

    $(document).off("click.quickSupplierTypeDelete", ".quickSupplierTypeDeleteBtn");
    $(document).on("click.quickSupplierTypeDelete", ".quickSupplierTypeDeleteBtn", function () {
      var supplierTypeId = $(this).data("id");

      function runDelete() {
        showLoader();

        $.post(quickSupplierTypeUrl($form.data("deleteUrlTemplate"), supplierTypeId), {
          _token: $form.find('input[name="_token"]').val()
        }, function (response) {
          hideLoader();
          showNotification(response.message || "Supplier type deleted.", response.type || "success");
          resetQuickSupplierTypeForm();
          refreshQuickSupplierTypeTable();
        }).fail(function (xhr) {
          hideLoader();
          showNotification(getAjaxErrorMessage(xhr, "Could not delete supplier type."), "error");
        });
      }

      if (typeof Swal !== "undefined") {
        Swal.fire({
          title: "Delete supplier type?",
          text: "This will remove the label from the reusable supplier list.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#DB1F48",
          cancelButtonColor: "#6b7280",
          confirmButtonText: "Delete",
        }).then(function (result) {
          if (result.isConfirmed) {
            runDelete();
          }
        });
        return;
      }

      if (window.confirm("Delete this supplier type?")) {
        runDelete();
      }
    });

    $form.off("submit.quickSupplierTypeCrud");
    $form.on("submit.quickSupplierTypeCrud", function (event) {
      event.preventDefault();

      var supplierTypeId = $idField.val();
      var submitUrl = supplierTypeId
        ? quickSupplierTypeUrl($form.data("updateUrlTemplate"), supplierTypeId)
        : $form.data("storeUrl");

      showLoader();

      $.post(submitUrl, {
        _token: $form.find('input[name="_token"]').val(),
        name: $nameField.val()
      }, function (response) {
        hideLoader();
        showNotification(response.message || "Supplier type saved.", response.type || "success");
        resetQuickSupplierTypeForm();
        refreshQuickSupplierTypeTable(response && response.data ? response.data.id : "");
        modalInstance.hide();
      }).fail(function (xhr) {
        hideLoader();
        showNotification(getAjaxErrorMessage(xhr, "Could not save supplier type."), "error");
      });
    });

    modalElement.dataset.quickCrudReady = "true";
  }

  function renderQuickExpenseCategoryRow(category, index) {
    return '<tr data-id="' + category.id + '">' +
      '<td>' + index + '</td>' +
      '<td>' + category.name + '</td>' +
      '<td><span class="report-badge ' + (category.is_active ? "report-badge-success" : "report-badge-danger") + '">' + (category.is_active ? "Active" : "Inactive") + '</span></td>' +
      '<td><div class="table-action-group">' +
        '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn quickExpenseCategoryEditBtn" data-id="' + category.id + '" data-name="' + category.name + '" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>' +
        '<button type="button" class="btn btn-sm ' + (category.is_active ? "btn-outline-warning" : "btn-outline-success") + ' table-action-btn quickExpenseCategoryToggleBtn" data-id="' + category.id + '" data-active="' + (category.is_active ? 1 : 0) + '" title="Toggle"><i class="fa-solid ' + (category.is_active ? "fa-toggle-on" : "fa-toggle-off") + '"></i></button>' +
        '<button type="button" class="btn btn-sm btn-outline-danger table-action-btn quickExpenseCategoryDeleteBtn" data-id="' + category.id + '" title="Delete"><i class="fa-solid fa-trash"></i></button>' +
      '</div></td>' +
    '</tr>';
  }

  function syncExpenseCategorySelects(categories, preferredCategoryId, preferredTargetSelector) {
    if (!window.jQuery) {
      return;
    }

    var preferredValue = preferredCategoryId != null ? String(preferredCategoryId) : "";

    $("select[name='expense_category_id']").each(function () {
      var $select = $(this);
      var placeholderOption = $select.find("option:first");
      var placeholderText = placeholderOption.length ? placeholderOption.text() : "Select category";
      var currentValue = String($select.val() || "");
      var keepValue = currentValue;

      if (preferredValue && preferredTargetSelector && $select.is(preferredTargetSelector)) {
        keepValue = preferredValue;
      }

      $select.empty().append(new Option(placeholderText, "", false, false));

      (categories || []).forEach(function (category) {
        var optionValue = String(category.id);
        var optionLabel = String(category.name || "") + (category.is_active ? "" : " (Inactive)");
        var isSelected = keepValue === optionValue;
        $select.append(new Option(optionLabel, optionValue, isSelected, isSelected));
      });

      if (keepValue && !categories.some(function (category) { return String(category.id) === keepValue; })) {
        $select.val("");
      }

      $select.trigger("change");
    });
  }

  function initQuickExpenseCategoryCrud() {
    if (!window.jQuery || typeof bootstrap === "undefined") {
      return;
    }

    var modalElement = byId("quickExpenseCategoryModal");
    var form = byId("quickExpenseCategoryForm");

    if (!modalElement || !form || modalElement.dataset.quickCrudReady === "true") {
      return;
    }

    var modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
    var $form = $(form);
    var $tableBody = $("#quickExpenseCategoryTable tbody");
    var $title = $("#quickExpenseCategoryModalTitle");
    var $submitButton = $("#quickExpenseCategorySubmitBtn");
    var $idField = $("#quick_expense_category_id");
    var $nameField = $("#quick_expense_category_name");

    function quickExpenseCategoryUrl(template, id) {
      return String(template || "").replace("__ID__", String(id || ""));
    }

    function resetQuickExpenseCategoryForm() {
      $idField.val("");
      $nameField.val("");
      $title.text("Manage Expense Categories");
      $submitButton.html('<i class="fa-solid fa-save"></i> Save Category');
    }

    function refreshQuickExpenseCategoryTable(preferredCategoryId) {
      return $.get($form.data("listUrl"), function (response) {
        var rows = response.data || [];
        $tableBody.empty();

        if (!rows.length) {
          $tableBody.append('<tr><td colspan="4" class="text-center text-muted">No expense categories added yet.</td></tr>');
        } else {
          rows.forEach(function (category, index) {
            $tableBody.append(renderQuickExpenseCategoryRow(category, index + 1));
          });
        }

        syncExpenseCategorySelects(rows, preferredCategoryId, modalElement.dataset.quickTargetSelect || "");
      });
    }

    modalElement.addEventListener("show.bs.modal", function () {
      resetQuickExpenseCategoryForm();
      refreshQuickExpenseCategoryTable();
    });

    $(document).off("click.quickExpenseCategoryReset", "#quickExpenseCategoryResetBtn");
    $(document).on("click.quickExpenseCategoryReset", "#quickExpenseCategoryResetBtn", function () {
      resetQuickExpenseCategoryForm();
    });

    $(document).off("click.quickExpenseCategoryEdit", ".quickExpenseCategoryEditBtn");
    $(document).on("click.quickExpenseCategoryEdit", ".quickExpenseCategoryEditBtn", function () {
      $idField.val($(this).data("id"));
      $nameField.val($(this).data("name"));
      $title.text("Edit Expense Category");
      $submitButton.html('<i class="fa-solid fa-save"></i> Update Category');
    });

    $(document).off("click.quickExpenseCategoryToggle", ".quickExpenseCategoryToggleBtn");
    $(document).on("click.quickExpenseCategoryToggle", ".quickExpenseCategoryToggleBtn", function () {
      var categoryId = $(this).data("id");
      var nextState = $(this).data("active") == 1 ? 0 : 1;

      showLoader();

      $.post(quickExpenseCategoryUrl($form.data("updateUrlTemplate"), categoryId), {
        _token: $form.find('input[name="_token"]').val(),
        is_active: nextState
      }, function (response) {
        hideLoader();
        showNotification(response.message || "Expense category updated.", response.type || "success");
        refreshQuickExpenseCategoryTable();
      }).fail(function (xhr) {
        hideLoader();
        var response = xhr.responseJSON || {};
        showNotification(response.message || "Could not update expense category.", "error");
      });
    });

    $(document).off("click.quickExpenseCategoryDelete", ".quickExpenseCategoryDeleteBtn");
    $(document).on("click.quickExpenseCategoryDelete", ".quickExpenseCategoryDeleteBtn", function () {
      var categoryId = $(this).data("id");

      function runDelete() {
        showLoader();

        $.post(quickExpenseCategoryUrl($form.data("deleteUrlTemplate"), categoryId), {
          _token: $form.find('input[name="_token"]').val()
        }, function (response) {
          hideLoader();
          showNotification(response.message || "Expense category deleted.", response.type || "success");
          resetQuickExpenseCategoryForm();
          refreshQuickExpenseCategoryTable();
        }).fail(function (xhr) {
          hideLoader();
          var response = xhr.responseJSON || {};
          showNotification(response.message || "Could not delete expense category.", "error");
        });
      }

      if (typeof Swal !== "undefined") {
        Swal.fire({
          title: "Delete expense category?",
          text: "This will remove the category from the master list.",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#DB1F48",
          cancelButtonColor: "#6b7280",
          confirmButtonText: "Delete",
        }).then(function (result) {
          if (result.isConfirmed) {
            runDelete();
          }
        });
        return;
      }

      if (window.confirm("Delete this expense category?")) {
        runDelete();
      }
    });

    $form.off("submit.quickExpenseCategoryCrud");
    $form.on("submit.quickExpenseCategoryCrud", function (event) {
      event.preventDefault();

      var categoryId = $idField.val();
      var submitUrl = categoryId
        ? quickExpenseCategoryUrl($form.data("updateUrlTemplate"), categoryId)
        : $form.data("storeUrl");

      showLoader();

      $.post(submitUrl, {
        _token: $form.find('input[name="_token"]').val(),
        name: $nameField.val()
      }, function (response) {
        hideLoader();
        showNotification(response.message || "Expense category saved.", response.type || "success");
        resetQuickExpenseCategoryForm();
        refreshQuickExpenseCategoryTable(response && response.data ? response.data.id : "");
        modalInstance.hide();
      }).fail(function (xhr) {
        hideLoader();
        var response = xhr.responseJSON || {};
        showNotification(response.message || "Could not save expense category.", "error");
      });
    });

    modalElement.dataset.quickCrudReady = "true";
  }

  function getSidebarPreferenceStorageKey() {
    return "pharmacy:sidebar:state";
  }

  function applySidebarPreference(state) {
    var html = document.documentElement;

    if (!html) {
      return;
    }

    // The theme uses this attribute to show the sidebar preview on hover when it is collapsed.
    html.removeAttribute("data-icon-overlay");

    if (window.innerWidth < 992) {
      html.setAttribute("data-toggled", "close");
      return;
    }

    if (state === "collapsed") {
      html.setAttribute("data-toggled", "icon-overlay-close");
      return;
    }

    html.setAttribute("data-toggled", "close");
  }

  function initSidebarPreference() {
    var html = document.documentElement;
    var toggleButton = document.querySelector(".sidemenu-toggle");

    if (!html || !toggleButton) {
      return;
    }

    var storageKey = getSidebarPreferenceStorageKey();
    var savedState = "open";

    try {
      savedState = window.localStorage.getItem(storageKey) || "open";
    } catch (error) {
      savedState = "open";
    }

    if (window.innerWidth >= 992) {
      applySidebarPreference(savedState === "collapsed" ? "collapsed" : "open");
    } else if (!html.getAttribute("data-toggled")) {
      html.setAttribute("data-toggled", "close");
    }

    if (toggleButton.dataset.sidebarPreferenceReady !== "true") {
      // We listen in capture phase so the theme's default click handler does not fight our saved state.
      toggleButton.addEventListener("click", function (event) {
        if (window.innerWidth < 992) {
          return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        var isCollapsed = html.getAttribute("data-toggled") === "icon-overlay-close";
        var nextState = isCollapsed ? "open" : "collapsed";

        applySidebarPreference(nextState);

        try {
          window.localStorage.setItem(storageKey, nextState);
        } catch (error) {
          // localStorage can be blocked in private browsing modes.
        }
      }, true);

      if (!window.__sidebarPreferenceResizeBound) {
        window.addEventListener("resize", function () {
          if (window.innerWidth >= 992) {
            var currentState = "open";

            try {
              currentState = window.localStorage.getItem(storageKey) || "open";
            } catch (error) {
              currentState = "open";
            }

            applySidebarPreference(currentState === "collapsed" ? "collapsed" : "open");
          } else {
            html.setAttribute("data-toggled", "close");
            html.removeAttribute("data-icon-overlay");
          }
        });

        window.__sidebarPreferenceResizeBound = true;
      }

      toggleButton.dataset.sidebarPreferenceReady = "true";
    }
  }

  function submitFormSafely(form) {
    if (!form) {
      return;
    }

    if (typeof form.requestSubmit === "function") {
      form.requestSubmit();
      return;
    }

    form.submit();
  }

  function initEntryFormShortcuts() {
    if (window.__entryShortcutBound) {
      return;
    }

    var pageTitle = String(document.body.dataset.page || "").trim().toLowerCase();
    var shortcutMap = {
      "create purchase order": {
        addRowButtonId: "addPurchaseRow",
        formSelector: "#purchaseForm"
      },
      "sales invoice create": {
        addRowButtonId: "addSalesRow",
        formSelector: "#salesForm"
      },
      "receive purchase order": {
        formSelector: "form[action*='/receive']"
      }
    };
    var shortcutConfig = shortcutMap[pageTitle];

    if (!shortcutConfig) {
      return;
    }

    document.addEventListener("keydown", function (event) {
      var targetTag = event.target && event.target.tagName ? event.target.tagName.toLowerCase() : "";

      if (shortcutConfig.addRowButtonId && event.ctrlKey && event.shiftKey && event.key.toLowerCase() === "a") {
        event.preventDefault();
        var addRowButton = byId(shortcutConfig.addRowButtonId);
        if (addRowButton) {
          addRowButton.click();
        }
        return;
      }

      if ((event.ctrlKey || event.metaKey) && event.key === "Enter") {
        if (targetTag === "textarea") {
          return;
        }

        event.preventDefault();

        if (shortcutConfig.formSelector) {
          submitFormSafely(document.querySelector(shortcutConfig.formSelector));
        }
      }
    });

    window.__entryShortcutBound = true;
  }

  function initBatchHistoryTable() {
    var tableElement = byId("batchHistoryTable");

    if (!tableElement || !window.jQuery || !$.fn.DataTable || $.fn.DataTable.isDataTable(tableElement)) {
      return;
    }

    window.initServerSideDataTable({
      selector: tableElement,
      pageLength: 15,
      sort: false,
      searchColumns: [1],
      columns: [
        { data: "sno" },
        { data: "batch_no" },
        { data: "reference_no" },
        { data: "supplier" },
        { data: "purchase_date" },
        { data: "expiry_date" },
        { data: "quantity" },
        { data: "purchase_price" },
        { data: "subtotal" },
      ],
      ajaxUrl: tableElement.dataset.listUrl,
      ajaxData: function (request) {
        request.product_id = byId("batch_product_id") ? byId("batch_product_id").value : "";
      },
    });
  }

  function createChart(canvas, config) {
    if (!canvas || typeof Chart === "undefined") {
      return;
    }

    if (canvas._chartInstance) {
      canvas._chartInstance.destroy();
    }

    canvas._chartInstance = new Chart(canvas, config);
  }

  function initDashboardCharts() {
    var overviewBarChart = byId("overviewBarChart");
    if (overviewBarChart) {
      createChart(overviewBarChart, {
        type: "bar",
        data: {
          labels: JSON.parse(overviewBarChart.dataset.labels || "[]"),
          datasets: [
            {
              label: "Overview Count",
              data: JSON.parse(overviewBarChart.dataset.values || "[]"),
              backgroundColor: ["#2563eb", "#16a34a", "#f97316", "#7c3aed", "#ef4444", "#0ea5e9"],
              borderRadius: 8,
              maxBarThickness: 36,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: "rgba(148, 163, 184, 0.18)",
              },
              ticks: {
                precision: 0,
              },
            },
            x: {
              grid: {
                display: false,
              },
            },
          },
        },
      });
    }

    var purchaseTrendChart = byId("purchaseTrendChart");
    if (purchaseTrendChart) {
      createChart(purchaseTrendChart, {
        type: "line",
        data: {
          labels: JSON.parse(purchaseTrendChart.dataset.labels || "[]"),
          datasets: [
            {
              label: "Purchase Amount",
              data: JSON.parse(purchaseTrendChart.dataset.values || "[]"),
              borderColor: "#0f62fe",
              backgroundColor: "rgba(15, 98, 254, 0.12)",
              pointBackgroundColor: "#0f62fe",
              pointRadius: 4,
              fill: true,
              tension: 0.35,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: "rgba(148, 163, 184, 0.15)",
              },
            },
            x: {
              grid: {
                display: false,
              },
            },
          },
        },
      });
    }

    var stockCategoryChart = byId("stockCategoryChart");
    if (stockCategoryChart) {
      createChart(stockCategoryChart, {
        type: "doughnut",
        data: {
          labels: JSON.parse(stockCategoryChart.dataset.labels || "[]"),
          datasets: [
            {
              data: JSON.parse(stockCategoryChart.dataset.values || "[]"),
              backgroundColor: ["#0f62fe", "#22c55e", "#f97316", "#8b5cf6", "#ef4444", "#14b8a6"],
              borderWidth: 0,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: "62%",
          plugins: {
            legend: {
              position: "bottom",
              labels: {
                boxWidth: 10,
                usePointStyle: true,
              },
            },
          },
        },
      });
    }

    var salesPurchaseChart = byId("salesPurchaseChart");
    if (salesPurchaseChart) {
      createChart(salesPurchaseChart, {
        type: "bar",
        data: {
          labels: JSON.parse(salesPurchaseChart.dataset.labels || "[]"),
          datasets: [
            {
              label: "Sales",
              data: JSON.parse(salesPurchaseChart.dataset.sales || "[]"),
              backgroundColor: "rgba(34, 197, 94, 0.72)",
              borderRadius: 8,
              maxBarThickness: 28,
            },
            {
              label: "Purchase",
              data: JSON.parse(salesPurchaseChart.dataset.purchase || "[]"),
              backgroundColor: "rgba(37, 99, 235, 0.72)",
              borderRadius: 8,
              maxBarThickness: 28,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: "top",
            },
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: "rgba(148, 163, 184, 0.15)",
              },
            },
            x: {
              grid: {
                display: false,
              },
            },
          },
        },
      });
    }

    var topSellingProductsChart = byId("topSellingProductsChart");
    if (topSellingProductsChart) {
      createChart(topSellingProductsChart, {
        type: "bar",
        data: {
          labels: JSON.parse(topSellingProductsChart.dataset.labels || "[]"),
          datasets: [
            {
              label: "Qty Sold",
              data: JSON.parse(topSellingProductsChart.dataset.values || "[]"),
              backgroundColor: "rgba(249, 115, 22, 0.78)",
              borderRadius: 8,
              maxBarThickness: 22,
            },
          ],
        },
        options: {
          indexAxis: "y",
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
          },
          scales: {
            x: {
              beginAtZero: true,
              grid: {
                color: "rgba(148, 163, 184, 0.15)",
              },
            },
            y: {
              grid: {
                display: false,
              },
            },
          },
        },
      });
    }
  }

  window.showLoader = showLoader;
  window.hideLoader = hideLoader;
  window.showNotification = showNotification;
  window.initEnhancedSelects = initEnhancedSelects;
  window.addTableColumnSearch = addColumnSearch;
  window.initServerSideDataTable = initServerSideDataTable;
  window.syncCsrfInputs = syncCsrfInputs;
  window.showDatePicker = function () {
    if (window.jQuery && $("#nepali-datepicker").length && $("#nepali-datepicker").nepaliDatePicker) {
      $("#nepali-datepicker").nepaliDatePicker({
        container: ".datepick",
      });
    }
  };

  document.addEventListener("DOMContentLoaded", function () {
    syncCsrfInputs(document);
    initBootstrapUi();
    initChoices();
    initFooterYear();
    initScrollToTop();
    initHeaderScroll();
    initImagePreviewInput();
    initNotificationReadState();
    initNotificationLoadMore();
    initRememberedTabs();
    initEnhancedSelects(document);
    initConfirmForms();
    initAjaxForms();
    initImportPreview();
    initQuickCreateModals();
    initQuickDropdownOptionCrud();
    initDropdownOptionManager();
    initQuickPartyTypeCrud();
    initQuickSupplierTypeCrud();
    initSidebarPreference();
    initDataTableTabAdjust();
    initStandardDataTables();
    initEntryFormShortcuts();
  });

  window.addEventListener("load", function () {
    hideLoader();
    initWaves();
    initPurchaseTable();
    initPurchaseForm();
    initSalesInvoiceForm();
    initBatchHistoryTable();
    initDashboardCharts();
    updateNotificationTrayState();
  });
})();
