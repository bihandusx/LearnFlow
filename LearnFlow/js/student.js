document.addEventListener("DOMContentLoaded", function () {
    const dashboardShell = document.querySelector(".dashboard-shell");
    const mobileMenuBtn = document.querySelector(".mobile-menu-btn");

    if (mobileMenuBtn && dashboardShell) {
        mobileMenuBtn.addEventListener("click", function () {
            dashboardShell.classList.toggle("sidebar-open");
        });
    }

    const menuLinks = document.querySelectorAll(".menu-link");

    menuLinks.forEach(function (link) {
        link.addEventListener("click", function () {
            if (window.innerWidth <= 1200 && dashboardShell) {
                dashboardShell.classList.remove("sidebar-open");
            }
        });
    });

    const iconButtons = document.querySelectorAll(".icon-btn");

    iconButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            this.classList.add("clicked");

            setTimeout(() => {
                this.classList.remove("clicked");
            }, 200);
        });
    });

    const notificationItems = document.querySelectorAll(".notification-item");

    notificationItems.forEach(function (item) {
        item.addEventListener("click", function () {
            this.classList.remove("unread");
        });
    });

    const confirmButtons = document.querySelectorAll("[data-confirm]");

    confirmButtons.forEach(function (button) {
        button.addEventListener("click", function (event) {
            const message =
                this.getAttribute("data-confirm") ||
                "Are you sure you want to continue?";

            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });

    const fileInputs = document.querySelectorAll('input[type="file"]');

    fileInputs.forEach(function (input) {
        input.addEventListener("change", function () {
            const targetId = this.getAttribute("data-file-name");

            if (!targetId) {
                return;
            }

            const fileNameDisplay = document.getElementById(targetId);

            if (!fileNameDisplay) {
                return;
            }

            if (this.files.length > 0) {
                fileNameDisplay.textContent = this.files[0].name;
            } else {
                fileNameDisplay.textContent = "No file selected";
            }
        });
    });

    const forms = document.querySelectorAll("[data-validate]");

    forms.forEach(function (form) {
        form.addEventListener("submit", function (event) {
            const requiredFields = form.querySelectorAll("[required]");
            let valid = true;

            requiredFields.forEach(function (field) {
                if (!field.value.trim()) {
                    field.style.borderColor = "#dc2626";
                    valid = false;
                } else {
                    field.style.borderColor = "";
                }
            });

            if (!valid) {
                event.preventDefault();
                alert("Please complete all required fields.");
            }
        });
    });

    window.addEventListener("resize", function () {
        if (window.innerWidth > 1200 && dashboardShell) {
            dashboardShell.classList.remove("sidebar-open");
        }
    });
});