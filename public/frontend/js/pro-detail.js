// Noty is loaded by the layout on both the product page and the cart. The alert()
// it replaces froze the page until the customer dismissed it.
function notifyQuantity(text) {
    if (window.Noty) {
        new Noty({
            type: 'warning',
            layout: 'topRight',
            theme: 'mint',
            text: text,
            timeout: 2500,
        }).show();

        return;
    }

    alert(text);
}

document.addEventListener("DOMContentLoaded", function () {

    //click button quality product detail
    let btnQuality = document.querySelectorAll('.btn_quality');

    btnQuality.forEach((amountInput) => {
        let amount = parseInt(amountInput.value);
        let plusButton = amountInput.nextElementSibling;
        let minusButton = amountInput.previousElementSibling;

        // Read off the input rather than hard-coded, so the product page can lower
        // it to what the chosen variant actually has in stock.
        const ceiling = () => parseInt(amountInput.getAttribute('max')) || 20;

        plusButton.addEventListener('click', () => {
            if (amount >= 1) {

                amountInput.value = ++amount;

                if (amount >= ceiling()) {
                    plusButton.disabled = true;
                    plusButton.style.opacity = 0.6;
                }
            }
        })
        minusButton.addEventListener('click', () => {
            if (amount > 1) {

                amountInput.value = --amount;

                if (amount < ceiling()) {
                    plusButton.disabled = false;
                    plusButton.style.opacity = 1;
                }
            }
        });
        amountInput.addEventListener('input', () => {
            amount = amountInput.value;
            amount = parseInt(amount);
            const max = ceiling();
            amount = (isNaN(amount) || amount == 0) ? 1 : (amount > max) ? max : amount;
            if (isNaN(amount) || amount == 0) {
                amount = 1;
            } else if (amount >= max) {
                notifyQuantity("Chỉ có thể mua tối đa " + max + " sản phẩm");
                plusButton.disabled = true;
                plusButton.style.opacity = 0.6;
                amount = max;
            } else if(amount < 1) {
                notifyQuantity("Số lượng phải lớn hơn 0");
                amount = 1;
            } else {
                plusButton.disabled = false;
            }
            amountInput.value = amount;
        });
    })
});