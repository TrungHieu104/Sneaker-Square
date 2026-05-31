const form = document.getElementById("formAuthentication");
const password = document.getElementById("password");
const samePassword = document.getElementById("same_password");
let isPasswordValid = false;
let isSamePasswordValid = false;

if(form) {
    form.addEventListener("submit", (e) => {
        e.preventDefault();
        let isPasswordValid = checkPassword();
        let isSamePasswordValid = checkSamePassword();
        if(password) {
            password.addEventListener("keyup", () => {
                isPasswordValid = checkPassword();
            });
        }
        if (samePassword) {
            samePassword.addEventListener("keyup", () => {
                isSamePasswordValid = checkSamePassword();
            });
        }
        if (isPasswordValid && isSamePasswordValid) {
            form.submit();
        }
    });
}

function checkPassword() {
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

function checkSamePassword() {
    if (samePassword) {
        const samePasswordValue = samePassword.value.trim();
        if (samePasswordValue === '') {
            samePassword.classList.add("input-error");
            setErrorFor(samePassword, 'Mật khẩu không được để trống');
            samePassword.focus();
            return false;
        } else if (password.value.trim() !== samePasswordValue) {
            samePassword.classList.add("input-error");
            setErrorFor(samePassword, 'Mật khẩu không khớp');
            samePassword.focus();
            return false;
        } else {
            setSuccessFor(samePassword);
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

