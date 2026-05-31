const form = document.querySelector(".form-add-delivery");
const lname = document.querySelector(".lname");
const phone = document.querySelector(".phone");
const email = document.querySelector(".email");
const address = document.querySelector(".address");
const province = document.querySelector(".province-select");
const district = document.querySelector(".district-select");
const ward = document.querySelector(".ward-select");
const service = document.querySelector(".service-select");
let lnameValid = false;
let phoneValid = false;
let emailValid = false;
let addressValid = false;
let provinceValid = false;
let districtValid = false;
let wardValid = false;
let serviceValid = false;

if(form) {
    form.addEventListener("submit", (e) => {
        e.preventDefault();
    
        let lnameValid = checkName();
        let phoneValid = checkPhone();
        let emailValid = checkEmail();
        let addressValid = checkAddress();
        let provinceValid = checkProvince();
        let districtValid = checkDistrict();
        let wardValid = checkWard();
        let serviceValid = checkService();
    
        lname.addEventListener("keyup", () => {
            lnameValid = checkName();
        });
    
        phone.addEventListener("keyup", () => {
            phoneValid = checkPhone();
        });
    
        email.addEventListener("keyup", () => {
            emailValid = checkEmail();
        });
    
        address.addEventListener("keyup", () => {
            addressValid = checkAddress();
        });
    
        province.addEventListener("change", () => {
            provinceValid = checkProvince(); 
        });
    
        district.addEventListener("change", () => {
            districtValid = checkDistrict(); 
        });
    
        ward.addEventListener("change", () => {
            wardValid = checkWard(); 
        });
    
        service.addEventListener("change", () => {
            serviceValid = checkService(); 
        });
    
        if(lnameValid && phoneValid && emailValid && addressValid && provinceValid && districtValid && wardValid && serviceValid) {
            form.submit();
        }
    });
}

function checkName() {
    const lnameValue = lname.value.trim();
    if(lnameValue === '') {
        lname.classList.add("input-error");
        setErrorFor(lname, 'Vui lòng nhập trường này');
        lname.focus();
        return false;
    } else if(isValidName(lnameValue)) {
        lname.classList.add("input-error");
        setErrorFor(lname, 'Trường này phải là chữ');
        lname.focus();
        return false;
    } else if(lnameValue.length < 3) {
        lname.classList.add("input-error");
        setErrorFor(lname, 'Họ tên quá ngắn');
        lname.focus();
        return false;
    }else{
        setSuccessFor(lname);
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

function checkPhone() {
    const phoneValue = phone.value.trim();

    if(phoneValue === '') {
        phone.classList.add("input-error");
        setErrorFor(phone, 'Vui lòng nhập trường này');
        phone.focus();
        return false;
    }else if(!Number(phoneValue)) {
        phone.classList.add("input-error");
        setErrorFor(phone, 'Trường này phải là số');
        phone.focus();
        return false;
    }else if(phoneValue.length < 10 || phoneValue.length > 10) {
        phone.classList.add("input-error");
        setErrorFor(phone, 'Phải đủ 10 chữ số');
        phone.focus();
        return false;
    }else if(!phoneRegex(phoneValue)) {
        phone.classList.add("input-error");
        setErrorFor(phone, 'Số điện thoại không hợp lệ');
        phone.focus();
        return false;
    }else {
        setSuccessFor(phone);
        return true;
    }
}

function phoneRegex(phone) {
    return /(84|0[3|5|7|8|9])+([0-9]{8})/.test(phone)
}

function checkEmail() {
    const emailValue = email.value.trim();
    if(emailValue === '') {
        email.classList.add("input-error");
        setErrorFor(email, 'Vui lòng nhập trường này');
        email.focus();
        return false;
    } else if(emailValue.length < 5) {
        email.classList.add("input-error");
        setErrorFor(email, 'Email quá ngắn');
        email.focus();
        return false;
    }else if(!(checkEmailRegex(emailValue))) {
        email.classList.add("input-error");
        setErrorFor(email, 'Email sai định dạng');
        email.focus();
        return false;
    }else{
        setSuccessFor(email);
        return true;
    }
}

function checkEmailRegex(email) {
    return /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        .test(email);
}

function checkAddress () {
    const addressValue = address.value.trim();
    if(addressValue === '') {
        address.classList.add("input-error");
        setErrorFor(address, 'Vui lòng nhập trường này');
        address.focus();
        return false;
    } else if(addressValue.length < 5) {
        address.classList.add("input-error");
        setErrorFor(address, 'Địa chỉ quá ngắn');
        address.focus();
        return false;
    }else{
        setSuccessFor(address);
        return true;
    }
}

function checkProvince() {
    if(province.selectedIndex == 0) {
        province.classList.add("input-error");
        setErrorFor(province, 'Vui lòng chọn trường này');
        province.focus();
        return false;
    }else {
        setSuccessFor(province);
        return true;
    }
}

function checkDistrict() {
    if(district.selectedIndex == 0) {
        district.classList.add("input-error");
        setErrorFor(district, 'Vui lòng chọn trường này');
        district.focus();
        return false;
    }else {
        setSuccessFor(district);
        return true;
    }
}

function checkWard() {
    if(ward.selectedIndex == 0) {
        ward.classList.add("input-error");
        setErrorFor(ward, 'Vui lòng chọn trường này');
        ward.focus();
        return false;
    }else {
        setSuccessFor(ward);
        return true;
    }
}

function checkService() {
    if(service.selectedIndex == 0) {
        service.classList.add("input-error");
        setErrorFor(service, 'Vui lòng chọn trường này');
        service.focus();
        return false;
    }else {
        setSuccessFor(service);
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
    const formChildSelect = formControl.querySelector(".form-select");
    if (formChild) {
        formChild.className = "form-control input-success";
    }
    if (formChildSelect) {
        formChildSelect.className = "form-select input-success";
    }
}