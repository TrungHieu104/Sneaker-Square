$( function() {
    $( "#start_coupon" ).datepicker({
        prevText:" < ",
        nextText:" > ",
        dateFormat:"dd-mm-yy",
        dayNamesMin: ["Chủ nhật","Thứ 2","Thứ 3","Thứ 4","Thứ 5","Thứ 6","Thứ 7"],
        monthNames: ["Tháng 1 -", "Tháng 2 -", "Tháng 3 -", "Tháng 4 -", "Tháng 5 -", "Tháng 6 -", "Tháng 7 -", "Tháng 8 -", "Tháng 9 -", "Tháng 10 -", "Tháng 11 -", "Tháng 12 -"],
        duration: "slow"
    });
    $( "#end_coupon" ).datepicker({
        prevText:" < ",
        nextText:" > ",
        dateFormat:"dd-mm-yy",
        dayNamesMin: ["Chủ nhật","Thứ 2","Thứ 3","Thứ 4","Thứ 5","Thứ 6","Thứ 7"],
        monthNames: ["Tháng 1 -", "Tháng 2 -", "Tháng 3 -", "Tháng 4 -", "Tháng 5 -", "Tháng 6 -", "Tháng 7 -", "Tháng 8 -", "Tháng 9 -", "Tháng 10 -", "Tháng 11 -", "Tháng 12 -"],
        duration: "slow"
    });
    $( ".update-pro-date" ).datepicker({
        prevText:" < ",
        nextText:" > ",
        dateFormat:"dd-mm-yy",
        dayNamesMin: ["Chủ nhật","Thứ 2","Thứ 3","Thứ 4","Thứ 5","Thứ 6","Thứ 7"],
        monthNames: ["Tháng 1 -", "Tháng 2 -", "Tháng 3 -", "Tháng 4 -", "Tháng 5 -", "Tháng 6 -", "Tháng 7 -", "Tháng 8 -", "Tháng 9 -", "Tháng 10 -", "Tháng 11 -", "Tháng 12 -"],
        duration: "slow"
    });
} );

document.addEventListener('DOMContentLoaded', function () {
    var dropdownButton = document.getElementById('cardOpt1');
    var dropdownMenu = document.getElementById('myDropdown');

    // Ngăn chặn sự kiện đóng khi nhấp vào nút dropdown
    // dropdownButton.addEventListener('click', function (event) {
    //     event.stopPropagation();
    // });

    // Ngăn chặn sự kiện đóng khi nhấp vào phần lọc theo
    var filterButton = document.querySelector('#accordionExample2 .accordion-button');
    if (filterButton) {
        filterButton.addEventListener('click', function (event) {
            event.stopPropagation();
            // console.log('Button clicked!');
        });
    } else {
        // console.log('Không tồn tại!');
    }

    // Đóng dropdown khi nhấp ra ngoài
    // document.addEventListener('click', function () {
    //     dropdownMenu.classList.remove('show');
    // });
});