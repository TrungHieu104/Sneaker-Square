const form = document.getElementById("formAuthentication");
const email = document.getElementById("email");
const password = document.getElementById("password");

let isEmailValid = false;
let isPasswordValid = false;

form.addEventListener("submit", (e) => {
    e.preventDefault();
    let isEmailValid = checkEmail();
    let isPasswordValid = checkPassword();
    
    if(email) {
        email.addEventListener("keyup", () => {
            isEmailValid = checkEmail();
        });
    }

    if(password) {
        password.addEventListener("keyup", () => {
            isPasswordValid = checkPassword();
        });
    }

    if (isEmailValid && isPasswordValid) {
        form.submit();
    }
});

function checkEmail() {
    const emailValue = email.value.trim();
    if (emailValue === '') {
        email.classList.add("input-error");
        setErrorFor(email, 'Email không được để trống');
        email.focus();
        return false;
    } else if (emailValue.length < 5) {
        email.classList.add("input-error");
        setErrorFor(email, 'Email quá ngắn');
        email.focus();
        return false;
    } else if (!checkEmailRegex(emailValue)) {
        email.classList.add("input-error");
        setErrorFor(email, 'Email sai định dạng');
        email.focus();
        return false;
    } else {
        setSuccessFor(email);
        return true;
    }

}

function checkEmailRegex(email) {
    return /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        .test(email);
}

function checkPassword() {
    if (password) {
        const passwordValue = password.value.trim();
        if (passwordValue === '') {
            password.classList.add("input-error");
            setErrorFor(password, 'Mật khẩu không được để trống');
            password.focus();
            return false;
        } else if (passwordValue.length < 8) {
            password.classList.add("input-error");
            setErrorFor(password, 'Mật khẩu phải đủ 8 ký tự');
            password.focus();
            return false;
        } else if (!(/[0-9]/.test(passwordValue))) {
            password.classList.add("input-error");
            setErrorFor(password, 'Mật khẩu phải có số');
            password.focus();
            return false;
        } else if (!(/[A-Z]/.test(passwordValue))) {
            password.classList.add("input-error");
            setErrorFor(password, 'Mật khẩu phải có chữ hoa');
            password.focus();
            return false;
        } else if (!(/[a-z]/.test(passwordValue))) {
            password.classList.add("input-error");
            setErrorFor(password, 'Mật khẩu phải có chữ thường');
            password.focus();
            return false;
        } else if (!(/[^0-9a-zA-Z]/.test(passwordValue))) {
            password.classList.add("input-error");
            setErrorFor(password, 'Mật khẩu phải có ký tự đặc biệt');
            password.focus();
            return false;
        } else {
            setSuccessFor(password);
            return true;
        }
    }
}

function setErrorFor(input, message) {
    const formControl = input.parentElement;
    const small = formControl.querySelectorAll('small');
    small.forEach(element => {
        element.innerText = message;
    });
}

function setSuccessFor(input) {
    const formControl = input.parentElement;
    const formChild = formControl.querySelector(".form-control");
    formChild.className = "form-control input-success";
}