document.addEventListener("DOMContentLoaded", () => {

    const toggle = document.getElementById("togglePassword");
    const password = document.getElementById("password");

    if (toggle && password) {

        toggle.addEventListener("click", () => {

            if (password.type === "password") {

                password.type = "text";
                toggle.textContent = "🙈";
                toggle.setAttribute("aria-label", "Skrýt heslo");

            } else {

                password.type = "password";
                toggle.textContent = "👁";
                toggle.setAttribute("aria-label", "Zobrazit heslo");

            }

        });

    }

});

