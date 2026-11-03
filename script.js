
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

    // Prevent the browser from submitting the form
    event.preventDefault();

    // Get user input
    const email = document.getElementById("email").value.trim();
    const password = passwordInput.value.trim();

    // Check empty fields
    if (email === "" || password === "") {

        message.textContent = "Please fill in all fields.";
        message.style.color = "red";

        return;
    }

    // Check email format
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailPattern.test(email)) {

        message.textContent = "Please enter a valid email address.";
        message.style.color = "red";

        return;
    }

    // Display feedback
    message.textContent = "Form validated successfully! Authentication will be connected later.";
    message.style.color = "green";

});
