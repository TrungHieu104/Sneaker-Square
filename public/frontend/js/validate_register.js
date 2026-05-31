const form = document.getElementById("formAuthentication");
const email = document.getElementById("email");
const password = document.getElementById("password");
const samePassword = document.getElementById("same_password");
const username = document.getElementById("username");
const remember = document.getElementById("terms-conditions");

let isEmailValid = false;
let isPasswordValid = false;
let isUsernameValid = false;
let isSamePasswordValid = false;
let isRememberValid = false;

form.addEventListener("submit", (e) => {
    e.preventDefault();
    let isEmailValid = checkEmail();
    let isPasswordValid = checkPassword();
    let isUsernameValid = checkUname();
    let isSamePasswordValid = checkSamePassword();
    let isRememberValid = rememberCheck();
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
    if(username) {
        username.addEventListener("keyup", () => {
            isUsernameValid = checkUname();
        });
    }
    if(samePassword) {
        samePassword.addEventListener("keyup", () => {
            isSamePasswordValid = checkSamePassword();
        });
    }
    if(remember) {
        remember.addEventListener("change", () => {
            isRememberValid = rememberCheck();
        });
    }
    if (isEmailValid && isPasswordValid && isUsernameValid && isSamePasswordValid && isRememberValid) {
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
    } else if(!checkEmailRegex(emailValue)) {
        email.classList.add("input-error");
        setErrorFor(email, 'Email sai định dạng');
        email.focus();
        return false;
    }
    else {
        setSuccessFor(email);
        return true;
    }
}

function checkEmailRegex(email) {
    return /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        .test(email);
}

function checkUname() {
    if(username) {
        const usernameValue = username.value.trim();
    
        if(usernameValue === '') {
            username.classList.add("input-error");
            setErrorFor(username, 'Họ tên không được để trống');
            username.focus();
            return false;
        }else if(isValidName(usernameValue)) {
            username.classList.add("input-error");
            setErrorFor(username, 'Họ tên phải là chữ');
            username.focus();
            return false;
        }else if(usernameValue.length < 3) {
            username.classList.add("input-error");
            setErrorFor(username, 'Họ tên phải hơn 3 ký tự');
            username.focus();
            return false;
        }else {
            setSuccessFor(username);
            return true;
        }
    }
}

function removeAscent(str) {
    if (str === null || str === undefined) return str;
    str = str.toLowerCase();
    str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
    str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
    str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
    str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
    str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
    str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
    str = str.replace(/đ/g, "d");
    return str;
}

function isValidName(string) {
    var re = /^[a-zA-Z!@#\$%\^\&*\)\(+=._-]{3,}$/g // regex here
    return re.test(removeAscent(string))
}

function rememberCheck() {
    if(remember) {
        if (remember.checked === false) {
            remember.classList.add("input-error");
            setErrorFor(remember, 'Phải chọn trường này');
            document.getElementById("error-remember").style.display = "block";
            remember.focus();
            return false;
        } else {
            document.getElementById("error-remember").style.display = "none";
            return true;
        }
    }
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
    if(samePassword) {
        const samePasswordValue = samePassword.value.trim();
        if(samePasswordValue === '') {
            samePassword.classList.add("input-error");
            setErrorFor(samePassword, 'Mật khẩu không được để trống');
            samePassword.focus();
            return false;
        } else if(password.value.trim() !== samePasswordValue) {
            samePassword.classList.add("input-error");
            setErrorFor(samePassword, 'Mật khẩu không khớp');
            samePassword.focus();
            return false;
        }else {
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