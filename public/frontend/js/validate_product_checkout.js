const form = document.querySelector(".form-add-delivery");
const lname = document.querySelector(".lname");
const phone = document.querySelector(".phone");
const email = document.querySelector(".email");
const address = document.querySelector(".address");
const province = document.querySelector(".province-select");
const district = document.querySelector(".district-select");
const ward = document.querySelector(".ward-select");
let lnameValid = false;
let phoneValid = false;
let emailValid = false;
let addressValid = false;
let provinceValid = false;
let districtValid = false;
let wardValid = false;

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
    
        if(lnameValid && phoneValid && emailValid && addressValid && provinceValid && districtValid && wardValid) {
            form.submit();
        }
    });
}

/**
 * The address pickers hide their <select> behind a search box. Validation reads
 * the select, but the class that reddens a field and the element worth focusing
 * are the box the customer can actually see.
 */
function controlFor(input) {
    const wrap = input.closest(".addr-pick");

    return wrap ? wrap.querySelector(".addr-pick__input") : input;
}

function fieldOf(input) {
    const wrap = input.closest(".addr-pick");

    return wrap ? wrap.parentElement : input.parentElement;
}

function checkName() {
    const lnameValue = lname.value.trim();
    if(lnameValue === '') {
        setErrorFor(lname, 'Vui lòng nhập trường này');
        controlFor(lname).focus();
        return false;
    } else if(!isValidName(lnameValue)) {
        setErrorFor(lname, 'Trường này phải là chữ');
        controlFor(lname).focus();
        return false;
    } else if(lnameValue.length < 3) {
        setErrorFor(lname, 'Họ tên quá ngắn');
        controlFor(lname).focus();
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

/**
 * A name is letters and spaces. removeAscent() has already folded the
 * Vietnamese letters down to a-z, so the class stays ascii.
 *
 * No /g flag here: test() on a global regex carries lastIndex between calls,
 * so the second look at the same name answers differently from the first.
 */
function isValidName(string) {
    return /^[a-z ]+$/.test(removeAscent(string).trim());
}

function checkPhone() {
    const phoneValue = phone.value.trim();

    if(phoneValue === '') {
        setErrorFor(phone, 'Vui lòng nhập trường này');
        controlFor(phone).focus();
        return false;
    }else if(!Number(phoneValue)) {
        setErrorFor(phone, 'Trường này phải là số');
        controlFor(phone).focus();
        return false;
    }else if(phoneValue.length < 10 || phoneValue.length > 10) {
        setErrorFor(phone, 'Phải đủ 10 chữ số');
        controlFor(phone).focus();
        return false;
    }else if(!phoneRegex(phoneValue)) {
        setErrorFor(phone, 'Số điện thoại không hợp lệ');
        controlFor(phone).focus();
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
        setErrorFor(email, 'Vui lòng nhập trường này');
        controlFor(email).focus();
        return false;
    } else if(emailValue.length < 5) {
        setErrorFor(email, 'Email quá ngắn');
        controlFor(email).focus();
        return false;
    }else if(!(checkEmailRegex(emailValue))) {
        setErrorFor(email, 'Email sai định dạng');
        controlFor(email).focus();
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
        setErrorFor(address, 'Vui lòng nhập trường này');
        controlFor(address).focus();
        return false;
    } else if(addressValue.length < 5) {
        setErrorFor(address, 'Địa chỉ quá ngắn');
        controlFor(address).focus();
        return false;
    }else{
        setSuccessFor(address);
        return true;
    }
}

function checkProvince() {
    if(province.selectedIndex == 0) {
        setErrorFor(province, 'Vui lòng chọn trường này');
        controlFor(province).focus();
        return false;
    }else {
        setSuccessFor(province);
        return true;
    }
}

function checkDistrict() {
    if(district.selectedIndex == 0) {
        setErrorFor(district, 'Vui lòng chọn trường này');
        controlFor(district).focus();
        return false;
    }else {
        setSuccessFor(district);
        return true;
    }
}

function checkWard() {
    if(ward.selectedIndex == 0) {
        setErrorFor(ward, 'Vui lòng chọn trường này');
        controlFor(ward).focus();
        return false;
    }else {
        setSuccessFor(ward);
        return true;
    }
}

function setErrorFor(input, message) {
    const control = controlFor(input);
    control.classList.remove("input-success");
    control.classList.add("input-error");
    fieldOf(input).querySelectorAll('small').forEach(element => {
        element.innerText = message;
    });
}

function setSuccessFor(input) {
    // Toggle, never reassign className: the address pickers keep the class that
    // hides their native select on the very element being marked.
    const control = controlFor(input);
    control.classList.remove("input-error");
    control.classList.add("input-success");
    fieldOf(input).querySelectorAll('small').forEach(element => {
        element.innerText = '';
    });
}