document.addEventListener("DOMContentLoaded", function () {

    //click button quality product detail
    let btnQuality = document.querySelectorAll('.btn_quality');

    btnQuality.forEach((amountInput) => {
        let amount = parseInt(amountInput.value);
        let plusButton = amountInput.nextElementSibling;
        let minusButton = amountInput.previousElementSibling;

        plusButton.addEventListener('click', () => {
            if (amount >= 1) {

                amountInput.value = ++amount;

                if (amount >= 20) {
                    plusButton.disabled = true;
                    plusButton.style.opacity = 0.6;
                }
            }
        })
        minusButton.addEventListener('click', () => {
            if (amount > 1) {

                amountInput.value = --amount;

                if (amount < 20) {
                    plusButton.disabled = false;
                    plusButton.style.opacity = 1;
                }
            }
        });
        amountInput.addEventListener('input', () => {
            amount = amountInput.value;
            amount = parseInt(amount);
            amount = (isNaN(amount) || amount == 0) ? 1 : (amount > 20) ? 20 : amount;
            if (isNaN(amount) || amount == 0) {
                amount = 1;
            } else if (amount >= 20) {
                alert("Tối đa 20 sản phẩm");
                plusButton.disabled = true;
                plusButton.style.opacity = 0.6;
                amount = 20;
            } else if(amount < 1) {
                alert("Phải là số dương");
                amount = 1;
            } else {
                plusButton.disabled = false;
            }
            amountInput.value = amount;
        });
    })
});