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
    if (window.toastr) {
      var toastType = type === "success" ? "success" : (type === "warning" ? "warning" : "error");
      window.toastr.options = {
        closeButton: true,
        progressBar: false,
        newestOnTop: true,
        positionClass: "toast-top-right",
        timeOut: 2800,
        extendedTimeOut: 600,
        preventDuplicates: true,
        closeDuration: 180,
        showDuration: 180,
        hideDuration: 180,
      };
      window.toastr[toastType](message || "Something happened.");
      return;
    }

    var notification = byId("customNotification");
    if (!notification) {
      return;
    }

    notification.textContent = message || "Something happened.";
    notification.classList.remove("notification-success", "notification-error");
    notification.classList.add(type === "success" ? "notification-success" : "notification-error");
    notification.style.display = "block";

    window.setTimeout(function () {
      notification.style.display = "none";
    }, 3200);
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
    var price = parseFloat($(row).find(".price-input").val()) || 0;
    $(row).find(".subtotal-input").val((qty * price).toFixed(2));
  }

  function updatePurchaseTotal() {
    var total = 0;

    $("#purchaseItemsTable .subtotal-input").each(function () {
      total += parseFloat($(this).val()) || 0;
    });

    $("#grandTotal").val(total.toFixed(2));
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
        row.find(".price-input").val(0);
        row.find(".subtotal-input").val("0.00");
        row.find("select").val("");
      } else {
        row.remove();
      }

      updatePurchaseRowNumbers();
      updatePurchaseTotal();
    });

    $(document).off("input.purchase", "#purchaseItemsTable .qty-input, #purchaseItemsTable .price-input");
    $(document).on("input.purchase", "#purchaseItemsTable .qty-input, #purchaseItemsTable .price-input", function () {
      updatePurchaseRow($(this).closest("tr"));
      updatePurchaseTotal();
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
    var price = parseFloat($(row).find(".sales-price-input").val()) || 0;
    var discountPercent = parseFloat($(row).find(".sales-discount-input").val()) || 0;
    var taxPercent = parseFloat($(row).find(".sales-tax-input").val()) || 0;
    var baseAmount = qty * price;
    var discountAmount = baseAmount * discountPercent / 100;
    var taxableAmount = baseAmount - discountAmount;
    var taxAmount = taxableAmount * taxPercent / 100;
    var subtotal = taxableAmount + taxAmount;

    $(row).find(".sales-subtotal-input").val(subtotal.toFixed(2));
  }

  function updateSalesTotal() {
    var total = 0;

    $("#salesItemsTable .sales-subtotal-input").each(function () {
      total += parseFloat($(this).val()) || 0;
    });

    $("#salesGrandTotal").val(total.toFixed(2));
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

      var stockText = response.name ? response.name + " stock: " + (response.stock || 0) : "Select product to auto fill price and stock.";
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
        row.find(".sales-price-input").val(0);
        row.find(".sales-discount-input").val(0);
        row.find(".sales-tax-input").val(0);
        row.find(".sales-subtotal-input").val("0.00");
        row.find("select").val("");
      } else {
        row.remove();
      }

      updateSalesRowNumbers();
      updateSalesTotal();
    });

    $(document).off("input.sales change.sales", "#salesItemsTable .sales-qty-input, #salesItemsTable .sales-price-input, #salesItemsTable .sales-discount-input, #salesItemsTable .sales-tax-input");
    $(document).on("input.sales change.sales", "#salesItemsTable .sales-qty-input, #salesItemsTable .sales-price-input, #salesItemsTable .sales-discount-input, #salesItemsTable .sales-tax-input", function () {
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

  function initPrintActions() {
    if (window.__printActionBound) {
      return;
    }

    function expandDataTablesForPrint(targetElement) {
      var states = [];

      if (!window.jQuery || !$.fn.DataTable) {
        return states;
      }

      $(targetElement).find("table").each(function () {
        if (!$.fn.DataTable.isDataTable(this)) {
          return;
        }

        var api = $(this).DataTable();
        var pageInfo = api.page.info();

        states.push({
          api: api,
          length: api.page.len(),
          page: pageInfo ? pageInfo.page : 0,
        });

        api.page.len(-1).draw(false);
      });

      return states;
    }

    function restoreDataTablesAfterPrint(states) {
      (states || []).forEach(function (state) {
        state.api.page.len(state.length).draw(false);

        if (state.length !== -1) {
          state.api.page(state.page).draw("page");
        }
      });
    }

    $(document).on("click.printArea", ".js-print-trigger", function (event) {
      event.preventDefault();

      var targetSelector = this.dataset.printTarget || "";
      var printTarget = targetSelector ? document.querySelector(targetSelector) : null;

      if (!printTarget) {
        window.print();
        return;
      }

      document.body.classList.add("print-target-active");
      printTarget.classList.add("print-active-target");
      var tableStates = expandDataTablesForPrint(printTarget);

      var cleanup = function () {
        document.body.classList.remove("print-target-active");
        printTarget.classList.remove("print-active-target");
        restoreDataTablesAfterPrint(tableStates);
      };

      window.addEventListener("afterprint", cleanup, { once: true });

      window.setTimeout(function () {
        window.print();
        window.setTimeout(cleanup, 400);
      }, 80);
    });

    window.__printActionBound = true;
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
              backgroundColor: ["#2563eb", "#16a34a", "#f97316", "#7c3aed", "#ef4444"],
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
  }

  // Add one reusable print button on list pages so we do not repeat the same Blade code everywhere.
  function initAutoTablePrintButtons() {
    if (!document.querySelector(".page-header-breadcrumb")) {
      return;
    }

    // dashboard has charts and tabs, so the auto list print button is more confusing than helpful there
    if (document.querySelector(".dashboard-wrap")) {
      return;
    }

    if (document.querySelector(".page-header-breadcrumb .js-print-trigger")) {
      return;
    }

    var actionGroup = document.querySelector(".page-header-breadcrumb .d-flex.gap-2, .page-header-breadcrumb .right-content.gap-2, .page-header-breadcrumb .d-flex");
    var tableCard = document.querySelector(".card.custom-card table") ? document.querySelector(".card.custom-card table").closest(".card.custom-card") : null;

    if (!actionGroup || !tableCard) {
      return;
    }

    if (!tableCard.id) {
      tableCard.id = "autoPrintCard" + String(Date.now());
    }

    var printButton = document.createElement("button");
    printButton.type = "button";
    printButton.className = "btn btn-print js-print-trigger js-auto-print-list";
    printButton.setAttribute("data-print-target", "#" + tableCard.id);
    printButton.innerHTML = '<i class="fa-solid fa-print"></i> Print';
    actionGroup.insertBefore(printButton, actionGroup.firstChild);
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
    initDataTableTabAdjust();
    initStandardDataTables();
    initAutoTablePrintButtons();
    initPrintActions();
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
