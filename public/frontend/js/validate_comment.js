const commentForm = document.getElementById("form-comment");
const commentName = document.getElementById("comment_name");
const commentEmail = document.getElementById("comment_email");
const commentContent = document.getElementById("comment_content");

let isEmailCmtValid = false;
let isNameCmtValid = false;
let isContentCmtValid = false;

commentForm.addEventListener("submit", (e) => {
    e.preventDefault();
    let isEmailCmtValid = checkEmail();
    let isNameCmtValid = checkName();
    let isContentCmtValid = checkContent();
    
    if(commentName) {
        commentName.addEventListener("keyup", () => {
            isNameCmtValid = checkName();
        });
    }

    if(commentEmail) {
        commentEmail.addEventListener("keyup", () => {
            isEmailCmtValid = checkEmail();
        });
    }

    if(commentContent) {
        commentContent.addEventListener("keyup", () => {
            isContentCmtValid = checkContent();
        });
    }

    if (isNameCmtValid && isEmailCmtValid && isContentCmtValid) {
        commentForm.submit();
    }
});

function checkEmail() {
    const emailCmtValue = commentEmail.value.trim();
    if (emailCmtValue === '') {
        commentEmail.classList.add("input-error");
        setErrorFor(commentEmail, 'Email không được để trống');
        commentEmail.focus();
        return false;
    } else if (emailCmtValue.length < 5) {
        commentEmail.classList.add("input-error");
        setErrorFor(commentEmail, 'Email quá ngắn');
        commentEmail.focus();
        return false;
    } else if (!checkEmailRegex(emailCmtValue)) {
        commentEmail.classList.add("input-error");
        setErrorFor(commentEmail, 'Email sai định dạng');
        commentEmail.focus();
        return false;
    } else {
        setSuccessFor(commentEmail);
        return true;
    }

}

function checkEmailRegex(commentEmail) {
    return /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        .test(commentEmail);
}

function checkName() {
    const nameCmtValue = commentName.value.trim();
    if (nameCmtValue === '') {
        commentName.classList.add("input-error");
        setErrorFor(commentName, 'Họ tên không được để trống');
        commentName.focus();
        return false;
    }else if(isValidName(nameCmtValue)) {
        commentName.classList.add("input-error");
        setErrorFor(commentName, 'Họ tên phải là chữ');
        commentName.focus();
        return false;
    } else if (nameCmtValue.length < 3) {
        commentName.classList.add("input-error");
        setErrorFor(commentName, 'Họ tên quá ngắn');
        commentName.focus();
        return false;
    } else {
        setSuccessFor(commentName);
        return true;
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

function checkContent() {
    const contentCmtValue = commentContent.value.trim();
    if (contentCmtValue === '') {
        commentContent.classList.add("input-error");
        setErrorFor(commentContent, 'Nội dung không được để trống');
        commentContent.focus();
        return false;
    } else if (contentCmtValue.length < 5) {
        commentContent.classList.add("input-error");
        setErrorFor(commentContent, 'Nội dung quá ngắn');
        commentContent.focus();
        return false;
    } else {
        setSuccessFor(commentContent);
        return true;
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