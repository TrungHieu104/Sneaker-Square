$(document).ready(function () {
    var queryStringObject = {};
    if ($('.filtertrue').length > 0) {
        RefreshFilters("no");
        popTriggerList();
    }

    $(document).on('change', '.getsort', function () {

        var value = $(this).val();
        var name = $(this).attr('name');
        queryStringObject[name] = [value];
        if (value == "") {
            delete queryStringObject[name];
        }
        RefreshFilters("yes");
    });

});

function RefreshFilters(type) {
    var queryStringObject = {};
    if (type != "clear-all") {
        var value = $('.getsort option:selected').val();
        var name = $('.getsort').attr('name');
        queryStringObject[name] = [value];
        if (value == "") {
            delete queryStringObject[name];
        }
        if (type === "yes") {
            filterproducts(queryStringObject);
        }
    } else {
        filterproducts(queryStringObject);
    }
}

function filterproducts(queryStringObject) {
    let searchParams = new URLSearchParams(window.location.search);
    if (searchParams.has('q')) {
        let parameterQuery = searchParams.get('q');
        var queryString = "?q=" + parameterQuery;
    } else {
        var queryString = "";
    }
    for (var key in queryStringObject) {
        if (queryString == '') {
            queryString += "?" + key + "=";
        } else {
            queryString += "&" + key + "=";
        }
        var queryValue = "";
        for (var i in queryStringObject[key]) {
            if (queryValue == '') {
                queryValue += queryStringObject[key][i];
            } else {
                queryValue += "~" + queryStringObject[key][i];
            }
        }
        queryString += queryValue;
    }
    if (history.pushState) {
        var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + queryString;
        newurl = newurl.replace("&undefined=undefined", "");
        window.history.pushState({ path: newurl }, '', newurl);
    }
    if (newurl.indexOf("?") >= 0) {
        newurl = newurl + "&json=";
    } else {
        newurl = newurl + "?json=";
    }
    $.ajax({
        url: newurl,
        type: 'get',
        dataType: 'json',
        success: function (response) {
            $("#appendProducts").html(response.view);
        },
        error: function () { }
    });
}