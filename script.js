
// KCRPMS - JAVASCRIPT
// Login Page Functionality

// 1. SHOW / HIDE PASSWORD

const passwordInput = document.getElementById("password");
const showPassword = document.getElementById("showPassword");

showPassword.addEventListener("change", function () {

    if (this.checked) {
        passwordInput.type = "text";
    } else {
        passwordInput.type = "password";
    }

});


// 2. LOGIN FORM VALIDATION

const loginForm = document.getElementById("loginForm");
const message = document.getElementById("message");

loginForm.addEventListener("submit", function (event) {

    // Get user input
    const email = document.getElementById("email").value.trim();
    const password = passwordInput.value;

    // Check empty fields
    if (email === "" || password === "") {

        event.preventDefault();
        message.textContent = "Please fill in all fields.";
        message.style.color = "red";

        return;
    }

    // Check email format
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailPattern.test(email)) {

        event.preventDefault();
        message.textContent = "Please enter a valid email address.";
        message.style.color = "red";

        return;
    }

});

const loginErrors = {
    invalid_input: "Enter a valid email address and password.",
    login_failed: "The email or password is incorrect, or the account is inactive.",
    server_error: "Login is temporarily unavailable. Please try again later."
};

const errorCode = new URLSearchParams(window.location.search).get("error");

if (errorCode && loginErrors[errorCode]) {
    message.textContent = loginErrors[errorCode];
    message.style.color = "red";
}
