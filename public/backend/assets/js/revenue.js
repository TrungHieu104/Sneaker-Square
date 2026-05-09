// mychart.js
$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    $('#btn-dashboard-filter').click(function () { 
        var from_date = $('#start_coupon').val();
        var to_date = $('#end_coupon').val();
        var selectedFilter = $('#color-product').val(); // Lấy giá trị của lựa chọn lọc

        // Kiểm tra từ ngày và đến ngày
        var isFromDateValid = (from_date);
        var isToDateValid = (to_date);
    
        if (!isFromDateValid) {
            $('#start_coupon').next('.error-text').text('Chọn ngày bắt đầu!').show();
        } else {
            $('#start_coupon').next('.error-text').hide();
        }
    
        if (!isToDateValid) {
            $('#end_coupon').next('.error-text').text('Chọn ngày kết thúc!').show();
        } else {
            $('#end_coupon').next('.error-text').hide();
        }
    
        if (isFromDateValid && isToDateValid) {
            if (from_date >= to_date) {
                $('#end_coupon').next('.error-text').text('Ngày kết thúc phải lớn hơn ngày bắt đầu!').show();
                return;
            } else {
                $('#start_coupon').next('.error-text').hide();
                $('#end_coupon').next('.error-text').hide();
            }
        }
        
        if (isFromDateValid && isToDateValid && (from_date < to_date)) {
            $.ajax({
                url: "/admin/filter-by-date",
                method: "POST",
                dataType: "JSON",
                data: { from_date: from_date, to_date: to_date },
                success: function (response) {
                    if (response.error) {
                        alert(response.error); 
                    } else {
                        initializeChart(response);
                        console.log(response);
                    }
                }
            });
        }
   
    });
    function initializeChart(response) {
        var categories = response.map(item => item.period);
        var salesData = response.map(item => item.sales);
        var profitData = response.map(item => item.profit);

        var options = {
            series: [
            {
                name: 'Doanh thu',
                data: salesData
            }, 
            {
                name: 'Lợi nhuận',
                data: profitData
            }
            ],
            chart: {
                type: 'bar',
                height: 350
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: categories,
            },
            yaxis: {
                title: {
                    text: 'VNĐ (việt nam đồng)'
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + " đ";
                    }
                }
            }
        };
        chart = new ApexCharts(document.querySelector("#totalRevenueChart"), options);
        chart.render();
        chart.updateSeries([
            {
                name: 'Doanh thu',
                data: salesData
            }, 
            {
                name: 'Lợi nhuận',
                data: profitData
            }
        ]);
    }

    $('.dashboard-filter').change(function() {
        var dashboard_value = $(this).val();
        $.ajax({
            url: "/admin/filter-dashboard",
            method: "POST",
            dataType: "JSON",
            data: {dashboard_value: dashboard_value},
            success: function(response) {
                initializeChart(response);
                // console.log(response);
            }
        })
    })
});