document.addEventListener("DOMContentLoaded", function () {

    const iconSearch = document.getElementById("icon__search");
    const formSearch = document.getElementById("form_search");

    iconSearch.addEventListener("click", (event) => {
        event.stopPropagation();
        if (formSearch.classList.contains('activeSearch')) {
            formSearch.classList.remove('activeSearch');
        } else {
            formSearch.classList.add('activeSearch');
        }
    })

    //click out form search 
    document.addEventListener("click", function (event) {
        if (event.target !== iconSearch && !formSearch.contains(event.target)) {
            formSearch.classList.remove('activeSearch');
        }
    })
    document.querySelector('.input_search').addEventListener("click", function (event) {
        event.stopPropagation();
    });
});