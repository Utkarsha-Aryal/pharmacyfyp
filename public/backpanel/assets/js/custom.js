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

  function addColumnSearch(api, columns) {
    columns.forEach(function (index) {
      api.columns(index).every(function () {
        var column = this;
        var columnHeader = column.header();
        var columnName = columnHeader.innerText.trim();
        var input = document.createElement("input");

        $(input)
          .appendTo($(columnHeader).empty())
          .attr("placeholder", columnName)
          .css("width", "100%")
          .addClass("search-input-highlight")
          .on("keyup change", function () {
            column.search(this.value).draw();
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
      searchDelay: 300,
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
        decorateDataTableUi($(this.api().table().container()));

        if (searchColumns.length > 0) {
          addColumnSearch(this.api(), searchColumns);
        }

        if (typeof options.afterInit === "function") {
          options.afterInit.call(this, this.api());
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

    updateSalesRow($("#salesItemsTable tbody tr").first());
    updateSalesTotal();

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
      bootstrap.Modal.getOrCreateInstance(modalElement).show();
    });

    $(document).off("submit.quickCreate", ".js-quick-create-form");
    $(document).on("submit.quickCreate", ".js-quick-create-form", function (event) {
      event.preventDefault();

      var form = this;
      var modalElement = form.closest(".modal");
      var targetSelector = modalElement ? (modalElement.dataset.quickTargetSelect || "") : "";

      showLoader();

      $.ajax({
        url: form.action,
        type: form.method || "POST",
        data: new FormData(form),
        processData: false,
        contentType: false,
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
          var response = xhr.responseJSON || {};
          showNotification(response.message || "Could not save right now.", "error");
        },
      });
    });
  }

  function renderQuickPaymentModeRow(mode, index) {
    var canDelete = ["cash", "bank"].indexOf(String(mode.name).toLowerCase()) === -1;
    var deleteButton = canDelete
      ? '<button type="button" class="btn btn-sm btn-outline-danger table-action-btn quickPaymentModeDeleteBtn" data-id="' + mode.id + '" title="Delete"><i class="fa-solid fa-trash"></i></button>'
      : "";

    return '<tr data-id="' + mode.id + '">' +
      '<td>' + index + '</td>' +
      '<td>' + mode.name + '</td>' +
      '<td>' + String(mode.type || "").charAt(0).toUpperCase() + String(mode.type || "").slice(1) + '</td>' +
      '<td><span class="report-badge ' + (mode.is_active ? "report-badge-success" : "report-badge-danger") + '">' + (mode.is_active ? "Active" : "Inactive") + '</span></td>' +
      '<td><div class="table-action-group">' +
        '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn quickPaymentModeEditBtn" data-id="' + mode.id + '" data-name="' + mode.name + '" data-type="' + mode.type + '" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>' +
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
    var $typeField = $("#quick_payment_mode_type");

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
      $typeField.val($(this).data("type"));
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
        type: $typeField.val()
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
  window.showDatePicker = function () {
    if (window.jQuery && $("#nepali-datepicker").length && $("#nepali-datepicker").nepaliDatePicker) {
      $("#nepali-datepicker").nepaliDatePicker({
        container: ".datepick",
      });
    }
  };

  document.addEventListener("DOMContentLoaded", function () {
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
      initQuickPaymentModeCrud();
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
