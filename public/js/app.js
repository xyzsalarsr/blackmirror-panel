// public/js/app.js

class AdminPanel {
  constructor() {
    this.currentSearch = "";
    this.init();
  }

  init() {
    this.initSidebar();
    this.initModals();
    this.initForms();
    this.initChart();
    this.initSearch();}

  initSidebar() {
    const hamburger = document.getElementById("hamburger");
    const sidebar = document.getElementById("sidebar");
    const sidebarClose = document.getElementById("sidebarClose");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    if (hamburger) {
      hamburger.addEventListener("click", () => {
        sidebar.classList.add("active");
        if (sidebarOverlay) sidebarOverlay.classList.add("active");
      });
    }

    if (sidebarClose) {
      sidebarClose.addEventListener("click", () => {
        sidebar.classList.remove("active");
        if (sidebarOverlay) sidebarOverlay.classList.remove("active");
      });
    }

    if (sidebarOverlay) {
      sidebarOverlay.addEventListener("click", () => {
        sidebar.classList.remove("active");
        sidebarOverlay.classList.remove("active");
      });
    }
  }

  initModals() {
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("modal-overlay")) {
        e.target.classList.remove("active");
      }
    });
  }

  openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add("active");
  }

  closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove("active");
  }

  initForms() {
    const addUserForm = document.getElementById("addUserForm");
    const editUserForm = document.getElementById("editUserForm");
    const addTransForm = document.getElementById("addTransactionForm");
    const editTransForm = document.getElementById("editTransactionForm");
    const settingsForm = document.getElementById("settingsForm");

    if (addUserForm)
      addUserForm.addEventListener("submit", this.handleAddUser.bind(this));
    if (editUserForm)
      editUserForm.addEventListener("submit", this.handleEditUser.bind(this));
    if (addTransForm)
      addTransForm.addEventListener("submit", this.handleAddTransaction.bind(this));
    if (editTransForm)
      editTransForm.addEventListener("submit", this.handleEditTransaction.bind(this));
    if (settingsForm)
      settingsForm.addEventListener("submit", this.handleSettings.bind(this));
  }

  initSearch() {
    const input = document.getElementById("searchInput");
    if (!input) return;

    let debounceTimer;
    input.addEventListener("input", () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        this.currentSearch = input.value.trim();
        this.loadUsers(1, this.currentSearch);
      }, 400);
    });
  }

  async loadUsers(page = 1, search = "") {
    this.currentSearch = search;

    const tbody = document.getElementById("usersTableBody");
    const pagination = document.getElementById("paginationContainer");
    const counter = document.getElementById("usersCount");

    if (!tbody) return;

    try {
      const params = new URLSearchParams({ page: "users", p: page, search });
      const response = await fetch(`?${params}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      const result = await response.json();

      if (result.success) {
        tbody.innerHTML = result.html;
        if (pagination) pagination.innerHTML = result.pagination;
        if (counter) counter.textContent = result.total;
      }
    } catch (err) {
      this.showToast("خطا در بارگذاری کاربران", "error");
    }
  }

  async handleAddUser(e) {
    e.preventDefault();
    const form = e.target;
    try {
      const response = await fetch("?page=users&action=store", {
        method: "POST",
        body: new FormData(form),
      });
      const result = await response.json();
      if (result.success) {
        this.showToast("کاربر با موفقیت افزوده شد", "success");
        this.closeModal("addUserModal");
        form.reset();
        this.loadUsers(1, this.currentSearch);
      } else {
        this.showToast("خطا در افزودن کاربر", "error");
      }
    } catch {
      this.showToast("خطا در ارتباط با سرور", "error");
    }
  }

  async handleEditUser(e) {
    e.preventDefault();
    const form = e.target;
    try {
      const response = await fetch("?page=users&action=update", {
        method: "POST",
        body: new FormData(form),
      });
      const result = await response.json();
      if (result.success) {
        this.showToast("کاربر با موفقیت ویرایش شد", "success");
        this.closeModal("editUserModal");
        this.loadUsers(1, this.currentSearch);
      } else {
        this.showToast("خطا در ویرایش کاربر", "error");
      }
    } catch {
      this.showToast("خطا در ارتباط با سرور", "error");
    }
  }

  async deleteUser(id) {
    if (!confirm("آیا از حذف این کاربر اطمینان دارید؟")) return;
    try {
      const formData = new FormData();
      formData.append("id", id);
      const response = await fetch("?page=users&action=delete", {
        method: "POST",
        body: formData,
      });
      const result = await response.json();
      if (result.success) {
        this.showToast("کاربر با موفقیت حذف شد", "success");
        this.loadUsers(1, this.currentSearch);
      } else {
        this.showToast("خطا در حذف کاربر", "error");
      }
    } catch {
      this.showToast("خطا در ارتباط با سرور", "error");
    }
  }

  editUser(user) {
    if (!document.getElementById("editUserModal")) return;
    document.getElementById("editUserId").value = user.id ?? "";
    document.getElementById("editUserName").value = user.name ?? "";
    document.getElementById("editUserOrder").value = user.order_number ?? "";
    document.getElementById("editUserAmount").value = user.amount ?? "";
    document.getElementById("editUserFetchUrl").value = user.fetch_url_sub ?? "";
    document.getElementById("editUserUseFetch").checked = user.use_fetch == 1;
    this.openModal("editUserModal");
  }

  async handleAddTransaction(e) {
    e.preventDefault();
    const form = e.target;
    try {
      const response = await fetch("?page=accounting&action=store", {
        method: "POST",
        body: new FormData(form),
      });
      const result = await response.json();
      if (result.success) {
        this.showToast("تراکنش با موفقیت افزوده شد", "success");
        this.closeModal("addTransactionModal");
        form.reset();
        this.loadTransactions(1);
      } else {
        this.showToast("خطا در افزودن تراکنش", "error");
      }
    } catch {
      this.showToast("خطا در ارتباط با سرور", "error");
    }
  }

  async handleEditTransaction(e) {
    e.preventDefault();
    const form = e.target;
    try {
      const response = await fetch("?page=accounting&action=update", {
        method: "POST",
        body: new FormData(form),
      });
      const result = await response.json();
      if (result.success) {
        this.showToast("تراکنش با موفقیت ویرایش شد", "success");
        this.closeModal("editTransactionModal");
        this.loadTransactions(this.currentTransactionPage ?? 1);
      } else {
        this.showToast("خطا در ویرایش تراکنش", "error");
      }
    } catch {
      this.showToast("خطا در ارتباط با سرور", "error");
    }
  }

  async deleteTransaction(id) {
    if (!confirm("آیا از حذف این تراکنش اطمینان دارید؟")) return;
    try {
      const formData = new FormData();
      formData.append("id", id);
      const response = await fetch("?page=accounting&action=delete", {
        method: "POST",
        body: formData,
      });
      const result = await response.json();
      if (result.success) {
        this.showToast("تراکنش با موفقیت حذف شد", "success");
        this.loadTransactions(this.currentTransactionPage ?? 1);
      } else {
        this.showToast("خطا در حذف تراکنش", "error");
      }
    } catch {
      this.showToast("خطا در ارتباط با سرور", "error");
    }
  }

  async loadTransactions(page = 1) {
    this.currentTransactionPage = page;

    const tbody = document.getElementById("transactionsTableBody");
    const pagination = document.getElementById("transactionsPagination");
    const counter = document.getElementById("transactionsCount");

    if (!tbody) return;

    try {
      const params = new URLSearchParams({ page: "accounting", p: page });
      const response = await fetch(`?${params}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });
      const result = await response.json();
      if (result.success) {
        tbody.innerHTML = result.html;
        if (pagination) pagination.innerHTML = result.pagination;
        if (counter) counter.textContent = result.total;
      }
    } catch {
      this.showToast("خطا در بارگذاری تراکنش‌ها", "error");
    }
  }

  editTransaction(trans) {
    if (!document.getElementById("editTransactionModal")) return;
    document.getElementById("editTransactionId").value = trans.id ?? "";
    document.getElementById("editTransactionAmount").value = trans.amount ?? "";
    this.openModal("editTransactionModal");
  }

  async handleSettings(e) {
    e.preventDefault();
    const form = e.target;
    try {
      const response = await fetch("?page=settings&action=update", {
        method: "POST",
        body: new FormData(form),
      });
      const result = await response.json();
      if (result.success) {
        this.showToast("تنظیمات با موفقیت ذخیره شد", "success");
        setTimeout(() => location.reload(), 1000);
      } else {
        this.showToast("خطا در ذخیره تنظیمات", "error");
      }
    } catch {
      this.showToast("خطا در ارتباط با سرور", "error");
    }
  }

  initChart() {
    const chartCanvas = document.getElementById("mainChart");
    if (!chartCanvas) return;
    if (!window.chartLabels || !window.chartRevenues || !window.chartSignups) return;

    new Chart(chartCanvas.getContext("2d"), {
      type: "line",
      data: {
        labels: window.chartLabels,
        datasets: [
          {
            label: "درآمد",
            data: window.chartRevenues,
            borderColor: "rgb(99, 102, 241)",
            backgroundColor: "rgba(99, 102, 241, 0.1)",
            tension: 0.4,
            fill: true,
            yAxisID: "y",
          },
          {
            label: "ثبت‌نام",
            data: window.chartSignups,
            borderColor: "rgb(34, 197, 94)",
            backgroundColor: "rgba(34, 197, 94, 0.1)",
            tension: 0.4,
            fill: true,
            yAxisID: "y1",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: "index", intersect: false },
        plugins: {
          legend: {
            display: true,
            labels: {
              color: "rgba(255,255,255,0.8)",
              font: { family: "YekanBakh" },
            },
          },
        },
        scales: {
          y: {
            type: "linear",
            display: true,
            position: "right",
            beginAtZero: true,
            grid: { color: "rgba(255,255,255,0.05)" },
            ticks: {
              color: "rgba(255,255,255,0.6)",
              font: { family: "YekanBakh" },
            },
          },
          y1: {
            type: "linear",
            display: true,
            position: "left",
            beginAtZero: true,
            grid: { drawOnChartArea: false },
            ticks: {
              color: "rgba(255,255,255,0.6)",
              font: { family: "YekanBakh" },
            },
          },x: {
            grid: { color: "rgba(255,255,255,0.05)" },
            ticks: {
              color: "rgba(255,255,255,0.6)",
              font: { family: "YekanBakh" },
            },
          },
        },
      },
    });
  }

  showToast(msg, type = "info") {
    const t = document.createElement("div");
    t.className = `toast toast-${type}`;
    t.textContent = msg;
    t.style.cssText = `
      position:fixed;top:20px;left:50%;transform:translateX(-50%);
      padding:1rem 1.5rem;background:${type==="success"?"#10b981":"#ef4444"};
      color:white;border-radius:10px;z-index:9999;`;
    document.body.appendChild(t);
    setTimeout(()=>t.remove(),2500);
  }

  copySubLink(link) {
    navigator.clipboard.writeText(link)
      .then(()=>this.showToast("کپی شد","success"))
      .catch(()=>this.showToast("خطا در کپی","error"));
  }

  togglePassword(id) {
    const input = document.getElementById(id);
    const icon = event.target;
    input.type = input.type === "password" ? "text" : "password";
    icon.textContent = input.type === "password" ? "👁️" : "🙈";
  }

  sleep(ms){return new Promise(r=>setTimeout(r,ms));}
}

const app = new AdminPanel();

function openModal(id){app.openModal(id);}
function closeModal(id){app.closeModal(id);}
function deleteUser(id){app.deleteUser(id);}
function deleteTransaction(id){app.deleteTransaction(id);}
function editUser(u){app.editUser(u);}
function editTransaction(t){app.editTransaction(t);}
function togglePassword(id){app.togglePassword(id);}
function copySubLink(l){app.copySubLink(l);}
function loadUsers(p){app.loadUsers(p, app.currentSearch);}
function loadTransactions(p){app.loadTransactions(p);}
