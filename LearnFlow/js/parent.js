/* =========================================================
   LEARNFLOW - PARENT DASHBOARD JAVASCRIPT
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    /* -------------------------
       MOBILE SIDEBAR TOGGLE
    ------------------------- */

    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.getElementById("sidebar");

    if (menuToggle && sidebar) {

        menuToggle.addEventListener("click", function () {

            sidebar.classList.toggle("show");

        });

    }


    /* -------------------------
       CLOSE SIDEBAR WHEN
       CLICKING OUTSIDE
    ------------------------- */

    document.addEventListener("click", function (event) {

        if (!sidebar || !menuToggle) {
            return;
        }

        const clickedInsideSidebar = sidebar.contains(event.target);
        const clickedMenuButton = menuToggle.contains(event.target);

        if (!clickedInsideSidebar && !clickedMenuButton) {

            sidebar.classList.remove("show");

        }

    });


    /* -------------------------
       NOTIFICATION BUTTON
    ------------------------- */

    const notificationButton =
        document.getElementById("notificationButton");

    if (notificationButton) {

        notificationButton.addEventListener("click", function () {

            alert("You have 3 new notifications.");

        });

    }

});
